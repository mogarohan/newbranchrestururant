<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\QrSession;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
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
                'table_id' => 'required|exists:restaurant_tables,id',
                'session_token' => 'required|string',
                'notes' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.notes' => 'nullable|string|max:500',
            ]);

            $restaurant = Restaurant::findOrFail($validated['restaurant_id']);
            $table = RestaurantTable::findOrFail($validated['table_id']);

            if ($table->restaurant_id !== $restaurant->id) {
                throw ValidationException::withMessages(['table_id' => ['Table does not belong to this restaurant.']]);
            }

            $session = QrSession::where('session_token', $validated['session_token'])
                ->where('restaurant_table_id', $table->id)
                ->where('is_active', true)
                ->first();

            if (!$session || $session->expires_at < now()) {
                throw ValidationException::withMessages(['session_token' => ['Session expired or invalid.']]);
            }

            if (!$session->is_primary && $session->join_status !== 'approved') {
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
                    ->where('branch_id', $table->branch_id)
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

            DB::transaction(function () use ($restaurant, $table, $session, $validated, $preparedItems, $totalAmount, &$order) {

                $order = Order::create([
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $table->branch_id,
                    'restaurant_table_id' => $table->id,
                    'qr_session_id' => $session->id,
                    'customer_name' => $session->customer_name,
                    'status' => 'placed',
                    'payment_status' => 'pending', // 👈 Initialize as pending for both models
                    'tax_amount' => 0,
                    'total_amount' => $totalAmount,
                    'notes' => $validated['notes'] ?? null,
                ]);

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
                        'table_number' => $table->table_number ?? $table->number,
                        'is_pay_first' => $restaurant->is_pay_first // Track workflow type
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
                'is_pay_first' => $restaurant->is_pay_first // 👈 Tell frontend if they need to pay immediately
            ], 201);

        } catch (\Exception $e) {
            if ($idempotencyKeyStr) {
                IdempotencyKey::where('key', $idempotencyKeyStr)->update(['status' => 'failed']);
            }
            throw $e;
        }
    }

    public function getSessionOrders($token)
    {
        $session = QrSession::where('session_token', $token)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

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
            
        $upiId = $session->restaurantTable->branch->upi_id ?? $session->restaurant->upi_id ?? null;

        // 👇 Dynamic Billing Summary Calculation 👇
        $activeOrders = $orders->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served']);
        $orderSubtotal = $activeOrders->sum('total_amount');
        $amountPaid = $activeOrders->where('payment_status', 'paid')->sum('total_amount');

        if ($payment) {
            // Manager generated a bill, trust the Payment Model!
            $invoiceGrandTotal = $payment->subtotal + $payment->tax_amount + $payment->extra_charges - $payment->discount_amount;
            $amountDue = $payment->amount; // This was accurately stored by ManagerDashboard
        } else {
            // No official bill generated yet
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

    public function requestBill(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $session = QrSession::where('session_token', $token)->first();

        if (!$session) {
            return response()->json(['message' => 'Invalid session.'], 404);
        }

        $table = RestaurantTable::find($session->restaurant_table_id);
        $tableNumber = $table ? ($table->number ?? $table->table_number) : '?';

        try {
            event(new \App\Events\BillRequested(
                $session->restaurant_id,
                $session->restaurant_table_id,
                $tableNumber,
                $session->customer_name
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bill Request Broadcast Failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Bill requested successfully.']);
    }

    public function selectPaymentMethod(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $session = QrSession::where('session_token', $token)->first();

        if (!$session) return response()->json(['message' => 'Invalid session.'], 404);

        $method = $request->input('method');

        $groupIds = QrSession::where('host_session_id', $session->is_primary ? $session->id : $session->host_session_id)
            ->orWhere('id', $session->is_primary ? $session->id : $session->host_session_id)
            ->pluck('id');
            
        $orderIds = Order::whereIn('qr_session_id', $groupIds)->pluck('id');
        
        $payment = Payment::whereIn('order_id', $orderIds)->where('status', 'pending')->first();

        if ($payment) {
            $payment->update(['payment_method' => $method]);
            $tableNum = $session->restaurantTable->table_number ?? $session->restaurantTable->number ?? '?';
            event(new \App\Events\PaymentMethodSelected($session->restaurant_id, $tableNum, $method));
        }

        return response()->json(['message' => 'Method selected.']);
    }

    public function cancel(Request $request, $orderId)
    {
        $session = \App\Models\QrSession::where('session_token', $request->bearerToken())
            ->where('is_active', true)
            ->firstOrFail();

        $order = \App\Models\Order::where('id', $orderId)
            ->where('qr_session_id', $session->id)
            ->firstOrFail();

        if (!in_array($order->status, ['pending', 'placed', 'accepted'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only cancel an order before the kitchen starts preparing it.'
            ], 400);
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'cancelled']);

        \App\Models\OrderStatusLog::create([
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