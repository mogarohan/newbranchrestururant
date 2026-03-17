<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Hash;
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
            ]
        ]);
    }

    public function getReadyOrders(Request $request)
    {
        $user = $request->user();

        // 👇 WAITER ISOLATION: Waiter ko sirf uski branch ke orders dikhenge
        $query = Order::with(['items.menuItem', 'table', 'session'])
            ->where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id'); // Agar waiter main restaurant ka hai toh
        }

        $orders = $query->whereIn('status', ['pending', 'placed', 'preparing', 'ready'])
            ->orderBy('updated_at', 'asc')
            ->get()
            ->map(function ($order) {
                $notes = $order->items->whereNotNull('notes')->pluck('notes')->filter()->implode(', ');

                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'updated_at' => $order->updated_at,
                    'items' => $order->items,
                    'table' => $order->table,
                    'table_number' => $order->table ? ($order->table->number ?? $order->table->table_number) : 'Takeaway',
                    'customer_name' => $order->customer_name ?? 'Guest',
                    'total_items' => $order->items->sum('quantity'),
                    'notes' => $notes ?: $order->notes,
                ];
            });

        return response()->json($orders);
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

        $order->update(['status' => 'served']);
        \App\Models\KitchenQueue::where('order_id', $order->id)->delete();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => 'ready',
            'to_status' => 'served',
            'changed_by' => $user->id,
        ]);

        event(new \App\Events\OrderStatusUpdated($order));

        return response()->json(['message' => 'Order marked as served successfully.']);
    }

    public function getTables(Request $request)
    {
        $user = $request->user();

        // 👇 WAITER ISOLATION: Waiter ko sirf uski branch ki tables dikhengi
        $query = RestaurantTable::where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        $tables = $query->get()
            ->map(function ($table) {
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
        $request->validate([
            'status' => 'required|string|in:available,occupied,cleaning'
        ]);

        $user = $request->user();

        $query = RestaurantTable::where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $table = $query->findOrFail($id);
        $table->update(['status' => $request->status]);

        event(new \App\Events\TableStatusUpdated($table->id, $table->status, $table->restaurant_id));

        return response()->json([
            'message' => 'Table status updated',
            'table' => $table
        ]);
    }
}