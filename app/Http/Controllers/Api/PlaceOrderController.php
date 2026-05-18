<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\QrSession;
use App\Models\RoomSession;
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
        $isRoom = $request->input('type') === 'room';
        
        if ($idempotencyKeyStr) {
            $existingKey = IdempotencyKey::where('key', $idempotencyKeyStr)
                ->where('scope', 'place_order')
                ->first();

            if ($existingKey) {
                if ($existingKey->status === 'completed') {
                    return response()->json([
                        'message' => 'Order already placed successfully (Idempotent replay).',
                        'order_id' => $existingKey->reference_id,
                        'is_replay' => true
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
                        'status' => 'processing'
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
            $validated = $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'table_id' => 'required', // Acts as room_id for rooms
                'session_token' => 'required|string',
                'notes' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.notes' => 'nullable|string|max:500',
            ]);

            $restaurant = Restaurant::findOrFail($validated['restaurant_id']);

            if ($isRoom) {
                $entity = Room::findOrFail($validated['table_id']);
                $session = RoomSession::where('session_token', $validated['session_token'])
                    ->where('room_id', $entity->id)
                    ->where('status', 'active')
                    ->first();
            } else {
                $entity = RestaurantTable::findOrFail($validated['table_id']);
                $session = QrSession::where('session_token', $validated['session_token'])
                    ->where('restaurant_table_id', $entity->id)
                    ->where('is_active', true)
                    ->first();
            }

            if ($entity->restaurant_id !== $restaurant->id) {
                throw ValidationException::withMessages(['table_id' => ['Entity does not belong to this restaurant.']]);
            }

            if (!$session || ($isRoom ? $session->check_out_at < now() : $session->expires_at < now())) {
                throw ValidationException::withMessages(['session_token' => ['Session expired or invalid.']]);
            }

            if (!$isRoom && !$session->is_primary && $session->join_status !== 'approved') {
                throw ValidationException::withMessages(['session_token' => ['Waiting for primary approval.']]);
            }

            $subtotal = 0;
            $preparedItems = [];

            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::where('id', $item['menu_item_id'])->where('restaurant_id', $restaurant->id)->first();

                if (!$menuItem) {
                    throw ValidationException::withMessages(['items' => ['One or more items are invalid.']]);
                }

                $branchStatus = DB::table('branch_menu_item_status')
                    ->where('menu_item_id', $menuItem->id)
                    ->where('branch_id', $entity->branch_id)
                    ->first();

                $isAvailable = $branchStatus ? (bool) $branchStatus->is_available : (bool) $menuItem->is_available;

                if (!$isAvailable) {
                    throw ValidationException::withMessages(['items' => ["{$menuItem->name} is currently unavailable at this branch."]]);
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
                ];
            }

            $totalAmount = $subtotal;
            $order = null;

            DB::transaction(function () use ($restaurant, $entity, $session, $validated, $preparedItems, $totalAmount, $isRoom, &$order) {

                $orderData = [
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $entity->branch_id,
                    'customer_name' => $isRoom ? $session->guest_name : $session->customer_name,
                    'status' => 'placed',
                    'payment_status' => 'pending', 
                    'tax_amount' => 0,
                    'total_amount' => $totalAmount,
                    'notes' => $validated['notes'] ?? null,
                ];

                if ($isRoom) {
                    $orderData['room_session_id'] = $session->id;
                } else {
                    $orderData['restaurant_table_id'] = $entity->id;
                    $orderData['qr_session_id'] = $session->id;
                }

                $order = Order::create($orderData);

                foreach ($preparedItems as $itemData) {
                    $order->items()->create($itemData);
                }

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => 'placed',
                    'changed_by_type' => 'customer',
                    'changed_by_id' => null,
                ]);

                ActivityLog::create([
                    'actor_type' => 'customer',
                    'actor_id' => $session->id, 
                    'action' => 'placed_order',
                    'entity_type' => Order::class,
                    'entity_id' => $order->id,
                    'metadata' => [
                        'total_amount' => $totalAmount,
                        'item_count' => count($preparedItems),
                        'table_number' => $isRoom ? $entity->room_number : ($entity->table_number ?? $entity->number),
                        'is_pay_first' => $restaurant->is_pay_first
                    ]
                ]);
            });

            if ($idempotencyKeyStr) {
                IdempotencyKey::where('key', $idempotencyKeyStr)->update([
                    'status' => 'completed',
                    'reference_id' => $order->id
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
                'is_pay_first' => $restaurant->is_pay_first 
            ], 201);

        } catch (\Exception $e) {
            if ($idempotencyKeyStr) {
                IdempotencyKey::where('key', $idempotencyKeyStr)->update(['status' => 'failed']);
            }
            throw $e;
        }
    }

    public function getSessionOrders(Request $request, $token)
    {
        $isRoom = $request->query('type') === 'room';

        if ($isRoom) {
            $session = RoomSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Session not found'], 404);

            $orders = Order::with(['items'])
                ->where('room_session_id', $session->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $session = QrSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Session not found'], 404);

            $groupIds = QrSession::where(function($q) use ($session) {
                    if ($session->is_primary) {
                        $q->where('host_session_id', $session->id)->orWhere('id', $session->id);
                    } else {
                        $q->where('host_session_id', $session->host_session_id)->orWhere('id', $session->host_session_id);
                    }
                })
                ->where('created_at', '>=', now()->subHours(12)) 
                ->pluck('id');

            $orders = Order::with(['items'])
                ->whereIn('qr_session_id', $groupIds)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'customer_name' => $order->customer_name,
                'created_at' => $order->created_at,
                'items' => $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'item_name' => $item->item_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => $item->quantity,
                        'total_price' => $item->total_price,
                        'notes' => $item->notes,
                        'menu_item' => ['name' => $item->item_name]
                    ];
                })
            ];
        });

        $orderIds = $orders->pluck('id');
        $payment = Payment::whereIn('order_id', $orderIds)
            ->whereIn('status', ['pending', 'paid'])
            ->latest()
            ->first();
            
        $upiId = $isRoom 
            ? ($session->room->branch->upi_id ?? $session->room->restaurant->upi_id ?? null)
            : ($session->restaurantTable->branch->upi_id ?? $session->restaurant->upi_id ?? null);

        $activeOrders = $orders->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served']);
        $orderSubtotal = $activeOrders->sum('total_amount');
        $amountPaid = $activeOrders->where('payment_status', 'paid')->sum('total_amount');

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
                'amount_due' => $amountDue
            ],
            'payment' => $payment ? array_merge($payment->toArray(), ['upi_id' => $upiId]) : null
        ]);
    }

    // 👇 FULLY RESTORED AND UPDATED METHODS BELOW 👇

    public function requestBill(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $isRoom = $request->input('type') === 'room';

        if ($isRoom) {
            $session = RoomSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Invalid session.'], 404);
            $table = Room::find($session->room_id);
            $tableNumber = $table ? $table->room_number : '?';
        } else {
            $session = QrSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Invalid session.'], 404);
            $table = RestaurantTable::find($session->restaurant_table_id);
            $tableNumber = $table ? ($table->number ?? $table->table_number) : '?';
        }

        try {
            event(new \App\Events\BillRequested(
                $session->restaurant_id,
                $isRoom ? $session->room_id : $session->restaurant_table_id,
                $tableNumber,
                $isRoom ? $session->guest_name : $session->customer_name
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bill Request Broadcast Failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Bill requested successfully.']);
    }

    public function selectPaymentMethod(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $isRoom = $request->input('type') === 'room';
        $method = $request->input('method');

        if ($isRoom) {
            $session = RoomSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Invalid session.'], 404);
            
            $orderIds = Order::where('room_session_id', $session->id)->pluck('id');
            $tableNum = $session->room->room_number ?? '?';
        } else {
            $session = QrSession::where('session_token', $token)->first();
            if (!$session) return response()->json(['message' => 'Invalid session.'], 404);

            $groupIds = QrSession::where('host_session_id', $session->is_primary ? $session->id : $session->host_session_id)
                ->orWhere('id', $session->is_primary ? $session->id : $session->host_session_id)
                ->pluck('id');
                
            $orderIds = Order::whereIn('qr_session_id', $groupIds)->pluck('id');
            $tableNum = $session->restaurantTable->table_number ?? $session->restaurantTable->number ?? '?';
        }
        
        $payment = Payment::whereIn('order_id', $orderIds)->where('status', 'pending')->first();

        if ($payment) {
            $payment->update(['payment_method' => $method]);
            event(new \App\Events\PaymentMethodSelected($session->restaurant_id, $tableNum, $method));
        }

        return response()->json(['message' => 'Method selected.']);
    }

    public function cancel(Request $request, $orderId)
    {
        $isRoom = $request->input('type') === 'room';
        $token = $request->bearerToken();

        if ($isRoom) {
            $session = RoomSession::where('session_token', $token)
                ->where('status', 'active')
                ->firstOrFail();

            $order = Order::where('id', $orderId)
                ->where('room_session_id', $session->id)
                ->firstOrFail();
        } else {
            $session = QrSession::where('session_token', $token)
                ->where('is_active', true)
                ->firstOrFail();

            $order = Order::where('id', $orderId)
                ->where('qr_session_id', $session->id)
                ->firstOrFail();
        }

        if (!in_array($order->status, ['pending', 'placed', 'accepted'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only cancel an order before the kitchen starts preparing it.'
            ], 400);
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'cancelled']);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => 'cancelled',
            'changed_by_type' => 'customer',
        ]);

        event(new OrderCancelled($order));
        \App\Events\OrderStatusUpdated::dispatch($order);

        return response()->json(['status' => 'success', 'message' => 'Order cancelled successfully.']);
    }
}