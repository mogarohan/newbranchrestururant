<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use App\Events\OrderStatusUpdated;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;

class ManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.manager-dashboard';
    protected static ?string $navigationLabel = 'Restaurant Manager Dashboard';
    protected static ?string $title = 'Restaurant Dashboard Control';

    protected static ?int $navigationSort = 1;

    public $selectedTableId = null;

    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
        ];
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? null, ['manager', 'branch_admin']);
    }

    public function openTable($tableId)
    {
        if ($this->selectedTableId === $tableId) {
            $this->selectedTableId = null;
        } else {
            $this->selectedTableId = $tableId;
        }
    }

    // 👇 NEW: Toggle Reservation Method
    public function toggleReservation($tableId)
    {
        $table = RestaurantTable::where('restaurant_id', auth()->user()->restaurant_id)->findOrFail($tableId);
        
        // Don't allow reserving an occupied table
        if ($table->qrSessions()->where('is_active', true)->count() > 0) {
            Notification::make()
                ->title('Table is occupied')
                ->body('Cannot reserve a table that is currently in use.')
                ->danger()
                ->send();
            return;
        }

        // Toggle logic based on string status
        if ($table->status === 'reserved') {
            $table->update(['status' => 'available']);
            Notification::make()->title("Table {$table->table_number} is now Available")->success()->send();
        } else {
            $table->update(['status' => 'reserved']);
            Notification::make()->title("Table {$table->table_number} is Reserved")->success()->send();
        }
        
        $this->selectedTableId = null; // Close the right panel to refresh view
    }

    public function updateStatus($orderId, $status)
    {
        $order = Order::where('restaurant_id', auth()->user()->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => $status]);

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

        OrderStatusUpdated::dispatch($order);
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;
        $branchId = $user->branch_id;

        $tablesQuery = RestaurantTable::where('restaurant_id', $restaurantId);

        if ($branchId) {
            $tablesQuery->where('branch_id', $branchId);
        } else {
            $tablesQuery->whereNull('branch_id');
        }

        $tables = $tablesQuery
            ->withCount([
                'qrSessions as active_sessions_count' => fn($q) => $q->where('is_active', true),
                'orders as preparing_count' => fn($q) => $q->where('status', 'preparing'),
                'orders as ready_count' => fn($q) => $q->where('status', 'ready'),
            ])
            ->withSum([
                'orders as total_bill' => fn($q) => $q->whereIn('status', ['preparing', 'ready', 'served'])
            ], 'total_amount')
            ->orderBy('table_number', 'asc')
            ->get();

        $totalTables = $tables->count();
        $activeTables = $tables->where('active_sessions_count', '>', 0)->count();
        $occupancyRate = $totalTables > 0 ? round(($activeTables / $totalTables) * 100) : 0;
        $freeTables = $totalTables - $activeTables;
        $activeSessions = $tables->sum('active_sessions_count');

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

        $ordersQuery = Order::where('restaurant_id', $restaurantId)
            ->where('status', 'placed');

        if ($branchId) {
            $ordersQuery->where('branch_id', $branchId);
        } else {
            $ordersQuery->whereNull('branch_id');
        }

        $incomingOrders = $ordersQuery
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