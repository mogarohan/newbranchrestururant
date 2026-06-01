<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\QrSession;
use App\Models\RoomSession;
use App\Models\ParcelQrSession; // 👈 NEW
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\IdempotencyKey;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\OrderStatusUpdated;
use App\Events\OrderCancelled;

class PlaceOrderController extends Controller
{
    public function store(Request $request)
    {
        $idempotencyKeyStr = $request->header('X-Idempotency-Key');
        $serviceType = $request->input('type'); // 'table', 'room', or 'parcel'
        $isRoom = $serviceType === 'room';
        $isParcel = $serviceType === 'parcel';

        // ── Idempotency check ────────────────────────────────────────────────
        if ($idempotencyKeyStr) {
            $existingKey = IdempotencyKey::where('key', $idempotencyKeyStr)
                ->where('scope', 'place_order')
                ->first();

            if ($existingKey) {
                if ($existingKey->status === 'completed') {
                    return response()->json([
                        'message' => 'Order already placed successfully (Idempotent replay).',
                        'order_id' => $existingKey->reference_id,
                        'is_replay' => true,
                    ], 200);
                }
                if ($existingKey->status === 'processing') {
                    return response()->json(['message' => 'Order is currently processing. Please wait.'], 409);
                }
                $existingKey->update(['status' => 'processing']);
            } else {
                try {
                    IdempotencyKey::create([
                        'key' => $idempotencyKeyStr,
                        'scope' => 'place_order',
                        'status' => 'processing',
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062) {
                        return response()->json(['message' => 'Order is currently processing. Please wait.'], 409);
                    }
                    throw $e;
                }
            }
        }

        try {
            // table_id is optional for parcels
            $validated = $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'table_id' => $isParcel ? 'nullable' : 'required',
                'session_token' => 'required|string',
                'notes' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.notes' => 'nullable|string|max:500',
            ]);

            $restaurant = Restaurant::findOrFail($validated['restaurant_id']);
            $entity = null;
            $session = null;

            // ── Resolve entity + session ─────────────────────────────────────
            if ($isRoom) {
                $entity = Room::findOrFail($validated['table_id']);
                $session = RoomSession::where('session_token', $validated['session_token'])
                    ->where('room_id', $entity->id)
                    ->where('status', 'active')
                    ->first();
            } elseif ($isParcel) {
                $session = ParcelQrSession::where('session_token', $validated['session_token'])
                    ->where('status', 'active')
                    ->first();
                $entity = $session ? $session->parcelQrCode : null;
            } else { // Table
                $entity = RestaurantTable::findOrFail($validated['table_id']);
                $session = QrSession::where('session_token', $validated['session_token'])
                    ->where('restaurant_table_id', $entity->id)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$entity || $entity->restaurant_id !== $restaurant->id) {
                throw ValidationException::withMessages([
                    'table_id' => ['Entity does not exist or does not belong to this restaurant.'],
                ]);
            }

            // Expiry/Validity Checks
            if (!$session) {
                throw ValidationException::withMessages(['session_token' => ['Session not found or inactive.']]);
            }
            if ($isRoom && $session->check_out_at < now()) {
                throw ValidationException::withMessages(['session_token' => ['Room session expired.']]);
            }
            if ($serviceType === 'table' && $session->expires_at < now()) {
                throw ValidationException::withMessages(['session_token' => ['Table session expired.']]);
            }
            // Parcels: Auto expire if inactive for 3 hours
            if ($isParcel && $session->last_activity_at && $session->last_activity_at->diffInHours(now()) >= 3) {
                $session->update(['status' => 'expired']);
                throw ValidationException::withMessages(['session_token' => ['Parcel session expired due to inactivity.']]);
            }
            if ($serviceType === 'table' && !$session->is_primary && $session->join_status !== 'approved') {
                throw ValidationException::withMessages(['session_token' => ['Waiting for primary approval.']]);
            }

            // Update parcel activity
            if ($isParcel) {
                $session->update(['last_activity_at' => now()]);
            }

            // ── Validate items + stock (BEFORE transaction) ──────────────────
            $subtotal = 0;
            $preparedItems = [];
            $stockErrors = [];

            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::where('id', $item['menu_item_id'])
                    ->where('restaurant_id', $restaurant->id)
                    ->lockForUpdate()  // prevent race conditions during pre-check
                    ->first();

                if (!$menuItem) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more items are invalid.'],
                    ]);
                }

                // ── Branch availability override ─────────────────────────────
                $branchStatus = DB::table('branch_menu_item_status')
                    ->where('menu_item_id', $menuItem->id)
                    ->where('branch_id', $entity->branch_id)
                    ->first();

                $isAvailable = $branchStatus
                    ? (bool) $branchStatus->is_available
                    : (bool) $menuItem->is_available;

                if (!$isAvailable) {
                    throw ValidationException::withMessages([
                        'items' => ["{$menuItem->name} is currently unavailable."],
                    ]);
                }

                // ── Stock validation ─────────────────────────────────────────
                if ($menuItem->track_stock && $menuItem->stock_quantity !== null) {
                    $requestedQty = (int) $item['quantity'];
                    $availableQty = (int) $menuItem->stock_quantity;

                    if ($availableQty <= 0) {
                        $stockErrors[] = "{$menuItem->name} is out of stock.";
                    } elseif ($requestedQty > $availableQty) {
                        $stockErrors[] = "Only {$availableQty} unit(s) of \"{$menuItem->name}\" available, but you requested {$requestedQty}.";
                    }
                }

                $totalPrice = $menuItem->price * $item['quantity'];
                $subtotal += $totalPrice;
                $preparedItems[] = [
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'unit_price' => $menuItem->price,
                    'quantity' => $item['quantity'],
                    'total_price' => $totalPrice,
                    'notes' => $item['notes'] ?? null,
                    '_menu_item' => $menuItem,
                ];
            }

            if (!empty($stockErrors)) {
                return response()->json([
                    'message' => 'Some items could not be ordered due to stock limits.',
                    'stock_errors' => $stockErrors,
                ], 422);
            }

            $totalAmount = $subtotal;
            $order = null;

            // ── Atomic transaction: create order + initial tracking ───────────────
            DB::transaction(function () use ($restaurant, $entity, $session, $validated, $preparedItems, $totalAmount, $isRoom, $isParcel, $serviceType, &$order) {

                // Build order data
                $orderData = [
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $entity->branch_id,
                    'service_type' => $serviceType === 'table' ? 'dine_in' : ($isRoom ? 'room_service' : 'parcel'), // 👈 MAP ENUM
                    'customer_name' => $isRoom ? $session->guest_name : $session->customer_name,
                    'status' => 'placed',
                    'payment_status' => 'pending',
                    'tax_amount' => 0,
                    'total_amount' => $totalAmount,
                    'confirmed_total' => $totalAmount,
                    'notes' => $validated['notes'] ?? null,
                ];

                if ($isRoom) {
                    $orderData['room_session_id'] = $session->id;
                } elseif ($isParcel) {
                    $orderData['parcel_qr_session_id'] = $session->id; // 👈 NEW
                } else {
                    $orderData['restaurant_table_id'] = $entity->id;
                    $orderData['qr_session_id'] = $session->id;
                }

                $order = Order::create($orderData);

                foreach ($preparedItems as $itemData) {
                    $order->items()->create([
                        'menu_item_id' => $itemData['menu_item_id'],
                        'item_name' => $itemData['item_name'],
                        'unit_price' => $itemData['unit_price'],
                        'quantity' => $itemData['quantity'],
                        'requested_qty' => $itemData['quantity'],
                        'confirmed_qty' => $itemData['quantity'],
                        'total_price' => $itemData['total_price'],
                        'notes' => $itemData['notes'],
                    ]);
                }

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => 'placed',
                    'changed_by_type' => 'customer',
                    'changed_by_id' => null,
                ]);

                // Determine entity identifier for activity log
                $entityIdentifier = '?';
                if ($isRoom) $entityIdentifier = $entity->room_number;
                elseif ($isParcel) $entityIdentifier = $entity->name;
                else $entityIdentifier = $entity->table_number ?? $entity->number;

                ActivityLog::create([
                    'actor_type' => 'customer',
                    'actor_id' => $session->id,
                    'action' => 'placed_order',
                    'entity_type' => Order::class,
                    'entity_id' => $order->id,
                    'metadata' => [
                        'service_type' => $orderData['service_type'],
                        'total_amount' => $totalAmount,
                        'item_count' => count($preparedItems),
                        'table_or_room' => $entityIdentifier,
                        'is_pay_first' => $restaurant->is_pay_first,
                    ],
                ]);
            });

            if ($idempotencyKeyStr) {
                IdempotencyKey::where('key', $idempotencyKeyStr)->update([
                    'status' => 'completed',
                    'reference_id' => $order->id,
                ]);
            }

            if ($order) {
                $order->unsetRelations();
                \App\Events\OrderStatusUpdated::dispatch($order);
            }

            return response()->json([
                'message' => 'Order placed successfully.',
                'total_amount' => $totalAmount,
                'order_id' => $order->id,
                'is_pay_first' => $restaurant->is_pay_first,
            ], 201);

        } catch (\Exception $e) {
            if ($idempotencyKeyStr) {
                IdempotencyKey::where('key', $idempotencyKeyStr)->update(['status' => 'failed']);
            }
            throw $e;
        }
    }

    // ── Get session orders ────────────────────────────────────────────────────
    public function getSessionOrders(Request $request, $token)
    {
        $serviceType = $request->query('type'); // 'table', 'room', or 'parcel'
        $orders = collect();
        $upiId = null;

        if ($serviceType === 'room') {
            $session = RoomSession::with('room.branch', 'room.restaurant')->where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Session not found'], 404);

            $orders = Order::with(['items'])->where('room_session_id', $session->id)->orderBy('created_at', 'desc')->get();
            $upiId = $session->room->branch->upi_id ?? $session->room->restaurant->upi_id ?? null;

        } elseif ($serviceType === 'parcel') {
            $session = ParcelQrSession::with('parcelQrCode.branch', 'parcelQrCode.restaurant')->where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Session not found'], 404);

            $orders = Order::with(['items'])->where('parcel_qr_session_id', $session->id)->orderBy('created_at', 'desc')->get();
            $upiId = $session->parcelQrCode->branch->upi_id ?? $session->parcelQrCode->restaurant->upi_id ?? null;

        } else { // Table
            $session = QrSession::with('restaurantTable.branch', 'restaurant')->where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Session not found'], 404);

            $groupIds = QrSession::where(function ($q) use ($session) {
                if ($session->is_primary) {
                    $q->where('host_session_id', $session->id)->orWhere('id', $session->id);
                } else {
                    $q->where('host_session_id', $session->host_session_id)->orWhere('id', $session->host_session_id);
                }
            })->where('created_at', '>=', now()->subHours(12))->pluck('id');

            $orders = Order::with(['items'])->whereIn('qr_session_id', $groupIds)->orderBy('created_at', 'desc')->get();
            $upiId = $session->restaurantTable->branch->upi_id ?? $session->restaurant->upi_id ?? null;
        }

        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->confirmed_total ?? $order->total_amount,
                'customer_name' => $order->customer_name,
                'created_at' => $order->created_at,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'item_name' => $item->item_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => $item->confirmed_qty ?? $item->quantity,
                        'requested_qty' => (int) $item->requested_qty,
                        'confirmed_qty' => $item->confirmed_qty !== null ? (int) $item->confirmed_qty : null,
                        'total_price' => $item->total_price,
                        'notes' => $item->notes,
                        'menu_item' => ['name' => $item->item_name],
                    ];
                }),
            ];
        });

        $orderIds = $orders->pluck('id');
        $payment = Payment::whereIn('order_id', $orderIds)->whereIn('status', ['pending', 'paid'])->latest()->first();

        $activeOrders = $orders->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);
        $orderSubtotal = $activeOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
        $amountPaid = $activeOrders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);

        if ($payment) {
            $invoiceGrandTotal = $payment->subtotal + $payment->tax_amount + $payment->extra_charges - $payment->discount_amount;
            $amountDue = $payment->amount;
        } else {
            $invoiceGrandTotal = $orderSubtotal;
            $amountDue = max(0, $invoiceGrandTotal - $amountPaid);
        }

        return response()->json([
            'orders' => $formattedOrders,
            'billing_summary' => [
                'grand_total' => $invoiceGrandTotal,
                'amount_paid' => $amountPaid,
                'amount_due' => $amountDue,
            ],
            'payment' => $payment ? array_merge($payment->toArray(), ['upi_id' => $upiId]) : null,
        ]);
    }

    // ── Request bill ─────────────────────────────────────────────────────────
    public function requestBill(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $serviceType = $request->input('type');

        try {
            if ($serviceType === 'room') {
                $session = RoomSession::where('session_token', $token)->firstOrFail();
                $tableNumber = $session->room->room_number ?? '?';
                event(new \App\Events\BillRequested($session->restaurant_id, $session->room_id, $tableNumber, $session->guest_name));
            } elseif ($serviceType === 'parcel') {
                $session = ParcelQrSession::where('session_token', $token)->firstOrFail();
                $counterName = $session->parcelQrCode->name ?? 'Parcel';
                event(new \App\Events\BillRequested($session->restaurant_id, $session->parcel_qr_code_id, $counterName, $session->customer_name));
            } else {
                $session = QrSession::where('session_token', $token)->firstOrFail();
                $tableNumber = $session->restaurantTable->number ?? $session->restaurantTable->table_number ?? '?';
                event(new \App\Events\BillRequested($session->restaurant_id, $session->restaurant_table_id, $tableNumber, $session->customer_name));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bill Request Broadcast Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid session.'], 404);
        }

        return response()->json(['message' => 'Bill requested successfully.']);
    }

    // ── Select payment method ────────────────────────────────────────────────
    public function selectPaymentMethod(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $serviceType = $request->input('type');
        $method = $request->input('method');

        $orderIds = [];
        $tableNum = '?';
        $restaurantId = null;

        if ($serviceType === 'room') {
            $session = RoomSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Invalid session.'], 404);
            $orderIds = Order::where('room_session_id', $session->id)->pluck('id');
            $tableNum = $session->room->room_number ?? '?';
            $restaurantId = $session->restaurant_id;
        } elseif ($serviceType === 'parcel') {
            $session = ParcelQrSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Invalid session.'], 404);
            $orderIds = Order::where('parcel_qr_session_id', $session->id)->pluck('id');
            $tableNum = $session->parcelQrCode->name ?? 'Parcel';
            $restaurantId = $session->restaurant_id;
        } else {
            $session = QrSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Invalid session.'], 404);
            $groupIds = QrSession::where('host_session_id', $session->is_primary ? $session->id : $session->host_session_id)
                ->orWhere('id', $session->is_primary ? $session->id : $session->host_session_id)
                ->pluck('id');
            $orderIds = Order::whereIn('qr_session_id', $groupIds)->pluck('id');
            $tableNum = $session->restaurantTable->table_number ?? $session->restaurantTable->number ?? '?';
            $restaurantId = $session->restaurant_id;
        }

        $payment = Payment::whereIn('order_id', $orderIds)->where('status', 'pending')->first();

        if ($payment) {
            $payment->update(['payment_method' => $method]);
            event(new \App\Events\PaymentMethodSelected($restaurantId, $tableNum, $method));
        }

        return response()->json(['message' => 'Method selected.']);
    }

    // ── Cancel order ─────────────────────────────────────────────────────────
    public function cancel(Request $request, $orderId)
    {
        $serviceType = $request->input('type');
        $token = $request->bearerToken();
        $order = null;

        if ($serviceType === 'room') {
            $session = RoomSession::where('session_token', $token)->where('status', 'active')->firstOrFail();
            $order = Order::where('id', $orderId)->where('room_session_id', $session->id)->firstOrFail();
        } elseif ($serviceType === 'parcel') {
            $session = ParcelQrSession::where('session_token', $token)->where('status', 'active')->firstOrFail();
            $order = Order::where('id', $orderId)->where('parcel_qr_session_id', $session->id)->firstOrFail();
        } else {
            $session = QrSession::where('session_token', $token)->where('is_active', true)->firstOrFail();
            $order = Order::where('id', $orderId)->where('qr_session_id', $session->id)->firstOrFail();
        }

        if (!in_array($order->status, ['pending', 'placed', 'accepted'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only cancel an order before the kitchen starts preparing it.',
            ], 400);
        }

        $oldStatus = $order->status;

        DB::transaction(function () use ($order, $oldStatus) {
            $order->update(['status' => 'cancelled']);

            foreach ($order->items as $orderItem) {
                if (!$orderItem->menu_item_id) continue;
                $menuItem = MenuItem::find($orderItem->menu_item_id);
                if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                    $qtyToRestore = $orderItem->confirmed_qty ?? $orderItem->quantity;
                    if ($qtyToRestore > 0) {
                        $menuItem->increment('stock_quantity', $qtyToRestore);
                        if ($menuItem->fresh()->stock_quantity > 0 && !$menuItem->is_available) {
                            $menuItem->update(['is_available' => true]);
                        }
                    }
                }
            }

            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status' => $oldStatus,
                'to_status' => 'cancelled',
                'changed_by_type' => 'customer',
            ]);
        });

        event(new OrderCancelled($order));
        \App\Events\OrderStatusUpdated::dispatch($order->fresh());

        return response()->json(['status' => 'success', 'message' => 'Order cancelled successfully.']);
    }
}