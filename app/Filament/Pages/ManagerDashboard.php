<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use Filament\Pages\Page;
use App\Models\ActivityLog; 
use Filament\Support\Enums\MaxWidth;
use App\Events\OrderStatusUpdated;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache; 
use Illuminate\Support\Str; 
use Filament\Actions\Action; // 👈 NEW: For custom Page Actions
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;

class ManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.manager-dashboard';
    protected static ?string $navigationLabel = 'Manager Dashboard';
    protected static ?string $title = 'Manager Dashboard Control';

    protected static ?int $navigationSort = 1;

    public $selectedTableId = null;
    
    // Billing Properties
    public $discountAmount = 0;
    public $taxPercentage = 0;

    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.TableStatusUpdated" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.WaiterCalled" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.BillRequested" => 'notifyBillRequested',
            "echo-private:restaurant.{$restaurantId}.alerts,.PaymentMethodSelected" => 'notifyPaymentMethod',
        ];
    }

    public function notifyBillRequested($event)
    {
        $tableNum = $event['table_number'] ?? '?';
        $customer = $event['customer_name'] ?? 'A customer';

        $cacheKey = "bill_requested_alert_{$tableNum}";
        
        if (!Cache::has($cacheKey)) {
            Notification::make()
                ->title("Bill Requested: Table {$tableNum}")
                ->body("{$customer} has requested their final bill.")
                ->warning() 
                ->persistent() 
                ->send();
                
            Cache::put($cacheKey, true, now()->addSeconds(30));
        }
    }

    public function notifyPaymentMethod($event)
    {
        $tableNum = $event['table_number'] ?? '?';
        $method = strtoupper($event['method'] ?? 'CASH');

        Notification::make()
            ->title("Payment Update: Table {$tableNum}")
            ->body("Customer selected {$method} for payment.")
            ->info()
            ->send();
            
        $this->dispatch('$refresh');
    }

    public function cancelPendingBill()
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];
        
        if ($pendingPayment && $pendingPayment->status === 'pending') {
            $pendingPayment->delete();
            event(new \App\Events\BillGenerated($viewData['hostSessionId'], null));
            $this->discountAmount = 0;
            $this->taxPercentage = 0;

            Notification::make()
                ->title('Bill Cancelled')
                ->body('The bill has been voided. The customer can now place new orders.')
                ->warning()
                ->send();
        }
    }

    // 👇 UPDATED: Place Order from Dashboard
    public function placeOrderAction(): Action
    {
        return Action::make('placeOrderAction')
            ->label('Place Order')
            ->modalHeading('Place Order on Behalf of Customer')
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->form([
                Repeater::make('items')
                    ->schema([
                        Select::make('menu_item_id')
                            ->label('Menu Item')
                            ->options(\App\Models\MenuItem::where('restaurant_id', auth()->user()->restaurant_id)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live() // Required to trigger afterStateUpdated
                            ->afterStateUpdated(fn ($state, callable $set) => $set('unit_price', \App\Models\MenuItem::find($state)?->price ?? 0)),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        Hidden::make('unit_price'),
                        TextInput::make('notes')->nullable(),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->addActionLabel('Add Another Item')
            ])
            ->action(function (array $data) {
                $viewData = $this->getViewData();
                $hostSessionId = $viewData['hostSessionId'];
                
                if (!$hostSessionId) {
                    Notification::make()->title('No active session on this table.')->danger()->send();
                    return;
                }
                
                $totalAmount = 0;
                foreach($data['items'] as $item) {
                    $totalAmount += ($item['unit_price'] * $item['quantity']);
                }

                $order = Order::create([
                    'restaurant_id' => auth()->user()->restaurant_id,
                    'branch_id' => auth()->user()->branch_id,
                    'restaurant_table_id' => $this->selectedTableId,
                    'qr_session_id' => $hostSessionId,
                    'customer_name' => 'Manager (Dashboard)',
                    'total_amount' => $totalAmount,
                    'status' => 'accepted', // Auto-accept since manager placed it
                ]);

                foreach($data['items'] as $item) {
                    // 👇 FIX: Fetch the menu item to get the name
                    $menuItem = \App\Models\MenuItem::find($item['menu_item_id']);
                    
                    $order->items()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'item_name' => $menuItem ? $menuItem->name : 'Custom Item', // 👈 Added item_name
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['unit_price'] * $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                KitchenQueue::firstOrCreate(
                    ['order_id' => $order->id],
                    ['current_status' => 'placed', 'priority' => 0]
                );

                OrderStatusUpdated::dispatch($order);
                Notification::make()->title('Order placed successfully.')->success()->send();
            });
    }

    // 👇 UPDATED: Edit Existing Order from Dashboard
    public function editOrderAction(): Action
    {
        return Action::make('editOrderAction')
            ->label('Edit Order')
            ->modalHeading(fn (array $arguments) => 'Edit Order #' . ($arguments['orderId'] ?? ''))
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->form([
                Repeater::make('items')
                    ->schema([
                        Hidden::make('id'),
                        Select::make('menu_item_id')
                            ->label('Menu Item')
                            ->options(\App\Models\MenuItem::where('restaurant_id', auth()->user()->restaurant_id)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('unit_price', \App\Models\MenuItem::find($state)?->price ?? 0)),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Hidden::make('unit_price'),
                        TextInput::make('notes')->nullable(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add Item')
            ])
            ->fillForm(function (array $arguments) {
                // Fetch the existing order items to populate the form
                $order = Order::with('items')->find($arguments['orderId']);
                if(!$order) return [];
                
                return [
                    'items' => $order->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'menu_item_id' => $item->menu_item_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'notes' => $item->notes,
                        ];
                    })->toArray()
                ];
            })
            ->action(function (array $data, array $arguments) {
                $order = Order::find($arguments['orderId']);
                if(!$order) return;

                $totalAmount = 0;
                $existingItemIds = [];

                foreach($data['items'] as $itemData) {
                    $totalPrice = $itemData['unit_price'] * $itemData['quantity'];
                    $totalAmount += $totalPrice;
                    
                    // 👇 FIX: Fetch the menu item to get the name
                    $menuItem = \App\Models\MenuItem::find($itemData['menu_item_id']);

                    if (!empty($itemData['id'])) {
                        // Update existing item
                        $orderItem = $order->items()->find($itemData['id']);
                        if ($orderItem) {
                            $orderItem->update([
                                'menu_item_id' => $itemData['menu_item_id'],
                                'item_name' => $menuItem ? $menuItem->name : 'Custom Item', // 👈 Added item_name
                                'quantity' => $itemData['quantity'],
                                'unit_price' => $itemData['unit_price'],
                                'total_price' => $totalPrice,
                                'notes' => $itemData['notes'] ?? null,
                            ]);
                            $existingItemIds[] = $orderItem->id;
                        }
                    } else {
                        // Create a newly added item
                        $newItem = $order->items()->create([
                            'menu_item_id' => $itemData['menu_item_id'],
                            'item_name' => $menuItem ? $menuItem->name : 'Custom Item', // 👈 Added item_name
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $totalPrice,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                        $existingItemIds[] = $newItem->id;
                    }
                }

                // Delete any items that were removed in the Repeater
                $order->items()->whereNotIn('id', $existingItemIds)->delete();
                $order->update(['total_amount' => $totalAmount]);

                // Sync the update in real-time
                OrderStatusUpdated::dispatch($order);
                Notification::make()->title('Order updated successfully.')->success()->send();
            });
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
            $this->discountAmount = 0;
            $this->taxPercentage = 0;
        }
    }

    public function toggleReservation($tableId)
    {
        $user = auth()->user(); 
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);
        
        if ($table->qrSessions()->where('is_active', true)->count() > 0) {
            Notification::make()->title('Table is occupied')->danger()->send();
            return;
        }

        $oldStatus = $table->status; 

        if ($table->status === 'reserved') {
            $table->update(['status' => 'available']);
            Notification::make()->title("Table {$table->table_number} is now Available")->success()->send();
        } else {
            $table->update(['status' => 'reserved']);
            Notification::make()->title("Table {$table->table_number} is Reserved")->success()->send();
        }
        
        ActivityLog::create([
            'actor_type' => 'manager',
            'actor_id' => $user->id,
            'action' => 'toggled_reservation',
            'entity_type' => RestaurantTable::class,
            'entity_id' => $table->id,
            'metadata' => ['from_status' => $oldStatus, 'to_status' => $table->status]
        ]);
        
        $this->selectedTableId = null; 
    }

    public function cleanTable($tableId)
    {
        $user = auth()->user(); 
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);
        
        $activeSessions = $table->qrSessions()->where('is_active', true)->get();
        $closedSessionsCount = $activeSessions->count(); 

        foreach ($activeSessions as $session) {
            $session->update(['is_active' => false]);
            event(new \App\Events\SessionEnded($session->id, $table->id));
        }

        $table->update(['status' => 'available']);
        
        ActivityLog::create([
            'actor_type' => 'manager',
            'actor_id' => $user->id,
            'action' => 'cleaned_table',
            'entity_type' => RestaurantTable::class,
            'entity_id' => $table->id,
            'metadata' => ['sessions_closed' => $closedSessionsCount]
        ]);
        
        event(new \App\Events\TableStatusUpdated($table->id, 'available', $table->restaurant_id));
        
        Notification::make()->title("Table {$table->table_number} Cleaned")->success()->send();
            
        if ($this->selectedTableId === $tableId) {
            $this->selectedTableId = null;
        }
    }

    public function updateStatus($orderId, $status)
    {
        $user = auth()->user();
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
        
        ActivityLog::create([
            'actor_type' => 'manager',
            'actor_id' => $user->id,
            'action' => 'updated_order_status',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'metadata' => ['from_status' => $oldStatus, 'to_status' => $status]
        ]);

        OrderStatusUpdated::dispatch($order);
    }

    public function sendBillToCustomer()
    {
        $viewData = $this->getViewData();
        $orders = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served']);
        
        if ($orders->isEmpty()) return;

        $subtotal = $orders->sum('total_amount');
        $taxable = max(0, $subtotal - (float) $this->discountAmount);
        $taxAmt = $taxable * ((float) $this->taxPercentage / 100);
        $grandTotal = $taxable + $taxAmt;

        $latestOrderId = $orders->pluck('id')->last();
        $transactionRef = 'ORD' . $latestOrderId . '_' . Str::random(10);

        $payment = Payment::updateOrCreate(
            ['order_id' => $latestOrderId],
            [
                'restaurant_id' => auth()->user()->restaurant_id,
                'branch_id' => auth()->user()->branch_id,
                'subtotal' => $subtotal,
                'discount_amount' => $this->discountAmount,
                'tax_amount' => $taxAmt,
                'amount' => $grandTotal,
                'status' => 'pending', 
                'payment_method' => 'pending', 
                'transaction_reference' => $transactionRef,
            ]
        );

        $upiId = auth()->user()->branch_id 
            ? \App\Models\Branch::find(auth()->user()->branch_id)->upi_id 
            : \App\Models\Restaurant::find(auth()->user()->restaurant_id)->upi_id;

        $merchantCode = '5812';

        $paymentPayload = array_merge($payment->toArray(), [
            'upi_id' => $upiId,
            'merchant_category_code' => $merchantCode,
        ]);

        event(new \App\Events\BillGenerated($viewData['hostSessionId'], $paymentPayload));

        Notification::make()
            ->title('Bill Sent!')
            ->body('The generated bill is now displaying on the customer\'s screen.')
            ->success()
            ->send();
    }

    public function confirmPayment()
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];
        
        if (!$pendingPayment) return;

        $pendingPayment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $pendingPayment->payment_method === 'pending' ? 'cash' : $pendingPayment->payment_method 
        ]);

        $orderIds = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])->pluck('id');
        
        Order::whereIn('id', $orderIds)->update(['status' => 'completed']);
        
        foreach (Order::whereIn('id', $orderIds)->get() as $ord) {
            OrderStatusUpdated::dispatch($ord);
        }

        Notification::make()
            ->title('Payment Confirmed')
            ->body('Customer can now download their PDF receipt.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;
        $branchId = $user->branch_id;

        $tablesQuery = RestaurantTable::where('restaurant_id', $restaurantId);
        if ($branchId) $tablesQuery->where('branch_id', $branchId);
        else $tablesQuery->whereNull('branch_id');

        $tables = $tablesQuery
            ->withCount([
                'qrSessions as active_sessions_count' => fn($q) => $q->where('is_active', true),
            ])
            ->withSum([
                'orders as total_bill' => fn($q) => $q->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])
            ], 'total_amount')
            ->get();

        $totalTables = $tables->count();
        $activeTables = $tables->where('active_sessions_count', '>', 0)->count();
        $occupancyRate = $totalTables > 0 ? round(($activeTables / $totalTables) * 100) : 0;
        $activeSessions = $tables->sum('active_sessions_count');

        $selectedTableData = null;
        $tableOrders = collect(); 
        $activeDinersList = collect(); 
        $hostSessionId = null;
        $pendingPayment = null;

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
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
                $pendingPayment = Payment::whereIn('order_id', $tableOrders->pluck('id'))
                    ->whereIn('status', ['pending', 'paid'])
                    ->latest()
                    ->first();
            }
        }
        
        $incomingOrders = Order::where('restaurant_id', $restaurantId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereNull('branch_id')
            ->where('status', 'placed')
            ->with(['items.menuItem.category', 'restaurantTable'])
            ->orderBy('created_at', 'asc')->get();

        return compact(
            'tables', 'totalTables', 'activeTables', 'occupancyRate', 
            'activeSessions', 'incomingOrders', 'selectedTableData',
            'tableOrders', 'activeDinersList', 'hostSessionId', 'pendingPayment'
        );
    }
}