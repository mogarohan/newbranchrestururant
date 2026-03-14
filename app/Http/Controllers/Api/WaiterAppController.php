<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RestaurantTable; // 🔥 Added Table Model
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Events\OrderStatusUpdated;

class WaiterAppController extends Controller
{
    // 1. Waiter Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
                    ->where('is_active', true)
                    ->with('role')
                    ->first();

        // Ensure user exists, password is correct, and role is waiter (or manager)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if (! in_array($user->role->name, ['waiter', 'manager', 'restaurant_admin'])) {
            throw ValidationException::withMessages(['email' => ['Unauthorized access.']]);
        }

        // Generate Sanctum Token
        $token = $user->createToken('waiter-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name,
                'restaurant_id' => $user->restaurant_id,
            ]
        ]);
    }

  // 2. Get Ready Orders (Updated to include all active orders and missing fields)
    public function getReadyOrders(Request $request)
    {
        $user = $request->user();

        // 🔥 FIX: Include 'items.menuItem' so the frontend can display the food names
        $orders = Order::with(['items.menuItem', 'table', 'session'])
            ->where('restaurant_id', $user->restaurant_id)
            // Fetch all active order states so Waiter sees them immediately
            ->whereIn('status', ['pending', 'placed', 'preparing', 'ready']) 
            ->orderBy('updated_at', 'asc')
            ->get()
            ->map(function ($order) {
                // Extract unique notes
                $notes = $order->items->whereNotNull('notes')->pluck('notes')->filter()->implode(', ');

                return [
                    'id' => $order->id,
                    'status' => $order->status,             // 🔥 CRITICAL FIX: Frontend needs this to filter
                    'updated_at' => $order->updated_at,     // 🔥 CRITICAL FIX: Frontend needs this to sort/show time
                    'items' => $order->items,               // 🔥 CRITICAL FIX: Frontend needs this to list the food
                    'table' => $order->table,               // 🔥 CRITICAL FIX: Frontend needs this for Table Number
                    'table_number' => $order->table ? ($order->table->number ?? $order->table->table_number) : 'Takeaway',
                    'customer_name' => $order->customer_name ?? 'Guest',
                    'total_items' => $order->items->sum('quantity'),
                    'notes' => $notes ?: $order->notes,
                ];
            });

        return response()->json($orders);
    }

    // 3. Mark Order as Served
    public function markAsServed(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('restaurant_id', $user->restaurant_id)
            ->where('id', $id)
            ->firstOrFail();

        if ($order->status !== 'ready') {
            return response()->json(['message' => 'Order is not ready.'], 400);
        }

        // 1. Update the Main Order Status
        $order->update(['status' => 'served']);

        // 2. 🔥 NEW: Remove the order from the Kitchen Queue!
        \App\Models\KitchenQueue::where('order_id', $order->id)->delete();

        // 3. Log the status change
        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => 'ready',
            'to_status' => 'served',
            'changed_by' => $user->id,
        ]);

        // 4. 🔥 Notify the Customer AND the Kitchen that it was served!
        event(new \App\Events\OrderStatusUpdated($order));

        return response()->json(['message' => 'Order marked as served successfully.']);
    }

    // 🔥 4. GET ALL TABLES (Added missing method)
    public function getTables(Request $request)
    {
        $user = $request->user();

        $tables = RestaurantTable::where('restaurant_id', $user->restaurant_id)
            ->get()
            ->map(function ($table) {
                return [
                    'id' => $table->id,
                    'number' => $table->number ?? $table->table_number, // Map table number safely
                    'status' => $table->status ?? 'available',
                    'capacity' => $table->seating_capacity ?? 4,
                ];
            });

        return response()->json($tables);
    }

    // 🔥 5. UPDATE TABLE STATUS (Added missing method)
    public function updateTableStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:available,occupied,cleaning'
        ]);

        $user = $request->user();

        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $table->update(['status' => $request->status]);

        // Broadcast the change instantly to all other waiter tablets
        event(new \App\Events\TableStatusUpdated($table->id, $table->status, $table->restaurant_id));

        return response()->json([
            'message' => 'Table status updated', 
            'table' => $table
        ]);
    }
}