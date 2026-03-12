<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use App\Events\OrderStatusUpdated; // 🔥 NEW: Import the Event

class ManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.manager-dashboard';
    protected static ?string $navigationLabel = 'Restaurant Dashboard';
    protected static ?string $title = 'Restaurant Dashboard Control';
        protected static ?int $navigationSort = 1;

    public $selectedTableId = null;
    // 🔥 Listen to Pusher and instantly refresh the page when an order drops!
    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            // Notice the leading dot (.) before OrderStatusUpdated! 
            // It tells Livewire this is a custom broadcast name, not a namespace.
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
        ];
    }

    private function getRestaurantId()
    {
        return auth()->user()->restaurant_id;
    }
    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full; // Force full width for 200 tables
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? null, [ 'manager']);
    }

    public function openTable($tableId)
    {
        if ($this->selectedTableId === $tableId) {
            $this->selectedTableId = null;
        } else {
            $this->selectedTableId = $tableId;
        }
    }

    public function updateStatus($orderId, $status)
    {
        $order = Order::where('restaurant_id', auth()->user()->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        // Send to kitchen when 'accepted', not 'preparing'
        if ($status === 'accepted') {
            KitchenQueue::firstOrCreate(
                ['order_id' => $order->id],
                ['current_status' => 'placed', 'priority' => 0]
            );
        }

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => auth()->id(),
        ]);

        // 🔥 NEW: Dispatch Event to update Customer's Phone in real-time
        OrderStatusUpdated::dispatch($order);
    }

    protected function getViewData(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        // 1. MASSIVE SCALE OPTIMIZATION: Dense Floor Plan counts and sums.
        $tables = RestaurantTable::where('restaurant_id', $restaurantId)
            ->withCount([
                'qrSessions as active_sessions_count' => fn($q) => $q->where('is_active', true),
                'orders as preparing_count' => fn($q) => $q->where('status', 'preparing'),
                'orders as ready_count' => fn($q) => $q->where('status', 'ready'),
            ])
            ->withSum([
                'orders as total_bill' => fn($q) => $q->whereIn('status', ['preparing', 'ready', 'served'])
            ], 'total_amount')
            ->orderBy('table_number', 'asc') // Fixed order for map mapping
            ->get();

        $totalTables = $tables->count();
        $activeTables = $tables->where('active_sessions_count', '>', 0)->count();
        $occupancyRate = $totalTables > 0 ? round(($activeTables / $totalTables) * 100) : 0;
        $freeTables = $totalTables - $activeTables;
        $activeSessions = $tables->sum('active_sessions_count');

        // 2. ONLY fetch heavy item data for the 1 table currently clicked
        $selectedTableData = null;
        if ($this->selectedTableId) {
            $selectedTableData = RestaurantTable::with([
                'qrSessions' => fn($q) => $q->where('is_active', true),
                'orders' => function ($q) {
                    $q->whereIn('status', ['preparing', 'ready', 'served'])
                        ->with('items.menuItem.category');
                }
            ])->find($this->selectedTableId);
        }

        // 3. Fetch Incoming Orders
        $incomingOrders = Order::where('restaurant_id', $restaurantId)
            ->where('status', 'placed')
            ->with(['items.menuItem.category', 'restaurantTable'])
            ->orderBy('created_at', 'asc')
            ->get();

        return compact(
            'tables',
            'totalTables',
            'activeTables',
            'occupancyRate',
            'freeTables',
            'activeSessions',
            'incomingOrders',
            'selectedTableData'
        );
    }
}