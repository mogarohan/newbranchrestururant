<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog; // 👈 NEW
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\OrderStatusUpdated;

class WaiterAppController extends Controller
{
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

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if (!in_array($user->role->name, ['waiter', 'manager', 'restaurant_admin'])) {
            throw ValidationException::withMessages(['email' => ['Unauthorized access.']]);
        }

        $token = $user->createToken('waiter-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name,
                'restaurant_id' => $user->restaurant_id,
                'branch_id' => $user->branch_id,
                'total_served' => $user->total_served ?? 0,
            ]
        ]);
    }

    // 🔥 NEW: Profile fetch method for real-time updates
    public function getProfile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->name,
            'restaurant_id' => $user->restaurant_id,
            'total_served' => $user->total_served ?? 0,
        ]);
    }

    public function markAsServed(Request $request, $id)
    {
        $user = $request->user();

        $query = Order::where('restaurant_id', $user->restaurant_id)->where('id', $id);
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $order = $query->firstOrFail();

        if ($order->status !== 'ready') {
            return response()->json(['message' => 'Order is not ready.'], 400);
        }

        DB::transaction(function () use ($order, $user) {
            $order->update(['status' => 'served']);
            \App\Models\KitchenQueue::where('order_id', $order->id)->delete();

            // Waiter ka count badhao
            $user->increment('total_served');

            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status' => 'ready',
                'to_status' => 'served',
                'changed_by' => $user->id,
            ]);
            // 👇 NEW: Record Activity Log for Waiter
            ActivityLog::create([
                'actor_type' => 'staff',
                'actor_id' => $user->id,
                'action' => 'marked_served',
                'entity_type' => Order::class,
                'entity_id' => $order->id,
                'metadata' => [
                    'table_id' => $order->restaurant_table_id,
                ]
            ]);
        });

        event(new OrderStatusUpdated($order));

        return response()->json([
            'message' => 'Order marked as served successfully.',
            'total_served' => $user->total_served
        ]);
    }

    public function getReadyOrders(Request $request)
    {
        $user = $request->user();
        $query = Order::with(['items.menuItem', 'table', 'session'])
            ->where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        $orders = $query->whereIn('status', ['pending', 'placed', 'preparing', 'ready'])
            ->orderBy('updated_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'updated_at' => $order->updated_at,
                    'items' => $order->items,
                    'table_number' => $order->table ? ($order->table->number ?? $order->table->table_number) : 'Takeaway',
                    'customer_name' => $order->customer_name ?? 'Guest',
                    'total_items' => $order->items->sum('quantity'),
                    'notes' => $order->notes,
                ];
            });

        return response()->json($orders);
    }

    public function getTables(Request $request)
    {
        $user = $request->user();
        $query = RestaurantTable::where('restaurant_id', $user->restaurant_id);
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $tables = $query->get()->map(function ($table) {
            return [
                'id' => $table->id,
                'number' => $table->number ?? $table->table_number,
                'status' => $table->status ?? 'available',
                'capacity' => $table->seating_capacity ?? 4,
            ];
        });
        return response()->json($tables);
    }

    public function updateTableStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string|in:available,occupied,cleaning']);
        $user = $request->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($id);
        $table->update(['status' => $request->status]);
        ActivityLog::create([
            'actor_type' => 'staff',
            'actor_id' => $user->id,
            'action' => 'updated_table_status',
            'entity_type' => RestaurantTable::class,
            'entity_id' => $table->id,
            'metadata' => [
                'from_status' => $oldStatus,
                'to_status' => $request->status,
            ]
        ]);
        event(new \App\Events\TableStatusUpdated($table->id, $table->status, $table->restaurant_id));
        return response()->json(['message' => 'Table status updated', 'table' => $table]);
    }
}