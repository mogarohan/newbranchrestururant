<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use App\Models\QrSession;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\OrderStatusUpdated;

class PlaceOrderController extends Controller
{
    public function store(Request $request)
    {
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
            throw ValidationException::withMessages([
                'table_id' => ['Table does not belong to this restaurant.']
            ]);
        }

        $session = QrSession::where('session_token', $validated['session_token'])
            ->where('restaurant_table_id', $table->id)
            ->where('is_active', true)
            ->first();

        if (!$session || $session->expires_at < now()) {
            throw ValidationException::withMessages([
                'session_token' => ['Session expired or invalid.']
            ]);
        }

        if (!$session->is_primary && $session->join_status !== 'approved') {
            throw ValidationException::withMessages([
                'session_token' => ['Waiting for primary approval.']
            ]);
        }

        $subtotal = 0;
        $preparedItems = [];

        foreach ($validated['items'] as $item) {
            $menuItem = MenuItem::where('id', $item['menu_item_id'])
                ->where('restaurant_id', $restaurant->id)
                ->first();

            if (!$menuItem) {
                throw ValidationException::withMessages([
                    'items' => ['One or more items are invalid.']
                ]);
            }

            // 👇 SAFETY FIX: Check branch availability override
            $branchStatus = DB::table('branch_menu_item_status')
                ->where('menu_item_id', $menuItem->id)
                ->where('branch_id', $table->branch_id)
                ->first();

            $isAvailable = $branchStatus ? (bool) $branchStatus->is_available : (bool) $menuItem->is_available;

            if (!$isAvailable) {
                throw ValidationException::withMessages([
                    'items' => ["{$menuItem->name} is currently unavailable at this branch."]
                ]);
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
        });

        if ($order) {
            OrderStatusUpdated::dispatch($order);
        }

        return response()->json([
            'message' => 'Order placed successfully.',
            'total_amount' => $totalAmount
        ], 201);
    }

    public function getSessionOrders($token)
    {
        $session = QrSession::where('session_token', $token)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        if ($session->is_primary) {
            $groupIds = QrSession::where('host_session_id', $session->id)
                ->orWhere('id', $session->id)
                ->pluck('id');
        } else {
            $groupIds = QrSession::where('host_session_id', $session->host_session_id)
                ->orWhere('id', $session->host_session_id)
                ->pluck('id');
        }

        $orders = Order::with(['items.menuItem'])
            ->whereIn('qr_session_id', $groupIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'customer_name' => $order->customer_name,
                    'created_at' => $order->created_at,
                    'items' => $order->items
                ];
            });

        return response()->json($orders);
    }
}