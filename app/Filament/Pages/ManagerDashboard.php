<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use Filament\Pages\Page;
use App\Models\ActivityLog; // 👈 NEW
use Filament\Support\Enums\MaxWidth;
use App\Events\OrderStatusUpdated;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;

class ManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.manager-dashboard';
    protected static ?string $navigationLabel = 'Manager Dashboard';
    protected static ?string $title = 'Manager Dashboard Control';

    protected static ?int $navigationSort = 1;

    public $selectedTableId = null;

    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.TableStatusUpdated" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.WaiterCalled" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.BillRequested" => 'notifyBillRequested',
        ];
    }
// 👇 ADD THIS FUNCTION
    public function notifyBillRequested($event)
    {
        $tableNum = $event['table_number'] ?? '?';
        $customer = $event['customer_name'] ?? 'A customer';

        Notification::make()
            ->title("Bill Requested: Table {$tableNum}")
            ->body("{$customer} has requested their final bill.")
            ->warning() // Orange warning color grabs attention
            ->persistent() // Stays on screen until dismissed
            ->send();
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

    public function toggleReservation($tableId)
    {
        $user = auth()->user(); // 👇 FIX: Defined $user
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);
        
        if ($table->qrSessions()->where('is_active', true)->count() > 0) {
            Notification::make()
                ->title('Table is occupied')
                ->body('Cannot reserve a table that is currently in use.')
                ->danger()
                ->send();
            return;
        }

        $oldStatus = $table->status; // 👇 FIX: Defined $oldStatus before changing it

        if ($table->status === 'reserved') {
            $table->update(['status' => 'available']);
            Notification::make()->title("Table {$table->table_number} is now Available")->success()->send();
        } else {
            $table->update(['status' => 'reserved']);
            Notification::make()->title("Table {$table->table_number} is Reserved")->success()->send();
        }
        
        // 👇 FIXED: Activity Log uses the defined variables
        ActivityLog::create([
            'actor_type' => 'manager',
            'actor_id' => $user->id,
            'action' => 'toggled_reservation',
            'entity_type' => RestaurantTable::class,
            'entity_id' => $table->id,
            'metadata' => [
                'from_status' => $oldStatus,
                'to_status' => $table->status,
            ]
        ]);
        
        $this->selectedTableId = null; 
    }

    public function cleanTable($tableId)
    {
        $user = auth()->user(); // 👇 FIX: Defined $user
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);
        
        $activeSessions = $table->qrSessions()->where('is_active', true)->get();
        $closedSessionsCount = $activeSessions->count(); // 👇 FIX: Defined count for log

        foreach ($activeSessions as $session) {
            $session->update(['is_active' => false]);
            event(new \App\Events\SessionEnded($session->id, $table->id));
        }

        $table->update(['status' => 'available']);
        
        // 👇 FIXED: Activity Log uses the defined variables
        ActivityLog::create([
            'actor_type' => 'manager',
            'actor_id' => $user->id,
            'action' => 'cleaned_table',
            'entity_type' => RestaurantTable::class,
            'entity_id' => $table->id,
            'metadata' => [
                'sessions_closed' => $closedSessionsCount,
            ]
        ]);
        
        // Let waiters know the table is free again
        event(new \App\Events\TableStatusUpdated($table->id, 'available', $table->restaurant_id));
        
        Notification::make()
            ->title("Table {$table->table_number} Cleaned")
            ->body('All sessions closed and customer apps reset.')
            ->success()
            ->send();
            
        if ($this->selectedTableId === $tableId) {
            $this->selectedTableId = null;
        }
    }

    public function updateStatus($orderId, $status)
    {
        $user = auth()->user(); // 👇 FIX: Defined $user
        $order = Order::where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        
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
            'changed_by' => $user->id,
        ]);
        
        // 👇 FIXED: Activity Log uses the defined variables
        ActivityLog::create([
            'actor_type' => 'manager',
            'actor_id' => $user->id,
            'action' => 'updated_order_status',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'metadata' => [
                'from_status' => $oldStatus,
                'to_status' => $status,
            ]
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
                'orders as total_orders_count' => fn($q) => $q->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served']),
            ])
            ->withSum([
                'orders as total_bill' => fn($q) => $q->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])
            ], 'total_amount')
            ->get()
            ->sortBy(function ($table) {
                $isOccupied = $table->active_sessions_count > 0;
                $isReserved = !$isOccupied && (($table->status ?? '') === 'reserved' || ($table->is_reserved ?? false));
                
                $priority = 3;
                if ($isOccupied) $priority = 1;
                elseif ($isReserved) $priority = 2;

                $numericPart = preg_replace('/[^0-9]/', '', $table->table_number);
                $paddedNumber = str_pad($numericPart ?: '0', 5, '0', STR_PAD_LEFT);
                $alphaPart = preg_replace('/[^a-zA-Z]/', '', $table->table_number);

                return $priority . '-' . $alphaPart . '-' . $paddedNumber;
            })->values();

        $totalTables = $tables->count();
        $activeTables = $tables->where('active_sessions_count', '>', 0)->count();
        $occupancyRate = $totalTables > 0 ? round(($activeTables / $totalTables) * 100) : 0;
        $freeTables = $totalTables - $activeTables;
        $activeSessions = $tables->sum('active_sessions_count');

        $selectedTableData = null;
        $tableOrders = collect(); 
        $activeDinersList = collect(); 
        $hostSessionId = null;

        if ($this->selectedTableId) {
            $selectedTableData = RestaurantTable::with([
                'qrSessions' => fn($q) => $q->where('is_active', true)
            ])->find($this->selectedTableId);

            if ($selectedTableData && $selectedTableData->qrSessions->isNotEmpty()) {
                $sessionIds = $selectedTableData->qrSessions->pluck('id')->toArray();
                $activeDinersList = $selectedTableData->qrSessions;
                
                $hostSession = $activeDinersList->where('is_primary', true)->first();
                $hostSessionId = $hostSession ? $hostSession->id : null;

                $tableOrders = Order::with('items.menuItem.category')
                    ->whereIn('qr_session_id', $sessionIds)
                    ->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served', 'cancelled', 'rejected'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }
        
        $ordersQuery = Order::where('restaurant_id', $restaurantId)->where('status', 'placed');
        if ($branchId) {
            $ordersQuery->where('branch_id', $branchId);
        } else {
            $ordersQuery->whereNull('branch_id');
        }

        $incomingOrders = $ordersQuery->with(['items.menuItem.category', 'restaurantTable'])->orderBy('created_at', 'asc')->get();

        return compact(
            'tables',
            'totalTables',
            'activeTables',
            'occupancyRate',
            'freeTables',
            'activeSessions',
            'incomingOrders',
            'selectedTableData',
            'tableOrders',      
            'activeDinersList', 
            'hostSessionId'     
        );
    }
}