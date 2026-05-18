<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\QrSession;
use Filament\Pages\Page;
use App\Models\ActivityLog;
use Filament\Support\Enums\MaxWidth;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB; 

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
    public $extraCharges = 0;

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
            
            // Broadcast null to tell the app the bill was cancelled
            event(new \App\Events\BillGenerated($viewData['hostSessionId'], null));

            $this->discountAmount = 0;
            $this->taxPercentage = 0;
            $this->extraCharges = 0;

            Notification::make()
                ->title('Bill Cancelled')
                ->body('The generated bill has been cancelled. You can add extra charges and regenerate it.')
                ->warning()
                ->send();
        }
    }

    public function printPendingBill()
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];
        $hostSessionId = $viewData['hostSessionId'];

        if (!$pendingPayment || !$hostSessionId) {
            Notification::make()->title('No active bill found.')->danger()->send();
            return;
        }

        $session = QrSession::find($hostSessionId);
        $restaurant = auth()->user()->restaurant;
        $table = RestaurantTable::find($session->restaurant_table_id);

        $orders = Order::with('items')->where('qr_session_id', $hostSessionId)
            ->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])
            ->get();

        $itemsHtml = '';
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $itemsHtml .= "<tr>
                    <td style='padding: 4px 0; border-bottom: 1px dashed #ccc;'>{$item->quantity}x {$item->item_name}</td>
                    <td style='padding: 4px 0; border-bottom: 1px dashed #ccc; text-align: right;'>{$item->total_price}</td>
                </tr>";
            }
        }

        // We use native HTML/CSS designed specifically for 80mm POS Thermal Printers
        $html = "
        <html>
        <head>
            <style>
                @page { margin: 0; size: 80mm auto; }
                body { margin: 10px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #000; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
            </style>
        </head>
        <body>
            <div style='text-align: center;'>
                <h2 style='margin: 0; font-size: 18px; font-weight: bold;'>{$restaurant->name}</h2>
                <p style='margin: 4px 0;'>Table: {$table->table_number} | {$session->customer_name}</p>
                <p style='margin: 2px 0; font-size: 10px; color: #555;'>" . now()->format('d M Y h:i A') . "</p>
                <p style='margin: 8px 0; font-weight: bold; font-size: 14px;'>*** ESTIMATE BILL ***</p>
            </div>
            
            <hr style='border-top: 1px dashed #000; border-bottom: none; margin: 10px 0;' />
            
            <table>
                {$itemsHtml}
            </table>
            
            <hr style='border-top: 1px dashed #000; border-bottom: none; margin: 10px 0;' />
            
            <table>
                <tr><td style='text-align: left; padding: 2px 0;'>Subtotal</td><td style='text-align: right;'>{$pendingPayment->subtotal}</td></tr>
                <tr><td style='text-align: left; padding: 2px 0;'>Tax</td><td style='text-align: right;'>{$pendingPayment->tax_amount}</td></tr>
                <tr><td style='text-align: left; padding: 2px 0;'>Extra Charges</td><td style='text-align: right;'>{$pendingPayment->extra_charges}</td></tr>
                <tr><td style='text-align: left; padding: 2px 0;'>Discount</td><td style='text-align: right;'>-{$pendingPayment->discount_amount}</td></tr>
                <tr>
                    <td style='text-align: left; font-weight: bold; font-size: 16px; padding-top: 10px;'>TOTAL DUE</td>
                    <td style='text-align: right; font-weight: bold; font-size: 16px; padding-top: 10px;'>{$pendingPayment->amount}</td>
                </tr>
            </table>
            
            <hr style='border-top: 1px dashed #000; border-bottom: none; margin: 15px 0 10px 0;' />
            
            <div style='text-align: center; margin-top: 10px; font-size: 11px;'>
                <p style='margin: 2px 0;'>Please proceed to payment.</p>
                <p style='margin: 2px 0; font-weight: bold;'>Thank you for dining with us!</p>
            </div>
        </body>
        </html>
        ";

        // 👇 Inject Native Javascript to open the browser's Print Menu instantly 👇
        $escapedHtml = json_encode($html);
        $this->js("
            const printWindow = window.open('', '_blank', 'width=400,height=600');
            printWindow.document.write({$escapedHtml});
            printWindow.document.close();
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                printWindow.onafterprint = () => printWindow.close();
            }, 250);
        ");
    }

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
                            ->live()
                            ->afterStateUpdated(fn($state, callable $set) => $set('unit_price', \App\Models\MenuItem::find($state)?->price ?? 0)),
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
                foreach ($data['items'] as $item) {
                    $totalAmount += ($item['unit_price'] * $item['quantity']);
                }

                $order = Order::create([
                    'restaurant_id' => auth()->user()->restaurant_id,
                    'branch_id' => auth()->user()->branch_id,
                    'restaurant_table_id' => $this->selectedTableId,
                    'qr_session_id' => $hostSessionId,
                    'customer_name' => 'Manager (Dashboard)',
                    'total_amount' => $totalAmount,
                    'status' => 'accepted',
                    'payment_status' => 'paid',
                ]);

                foreach ($data['items'] as $item) {
                    $menuItem = \App\Models\MenuItem::find($item['menu_item_id']);

                    $order->items()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
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

    public function editOrderAction(): Action
    {
        return Action::make('editOrderAction')
            ->label('Edit Order')
            ->modalHeading(fn(array $arguments) => 'Edit Order #' . ($arguments['orderId'] ?? ''))
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
                            ->afterStateUpdated(fn($state, callable $set) => $set('unit_price', \App\Models\MenuItem::find($state)?->price ?? 0)),
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
                $order = Order::with('items')->find($arguments['orderId']);
                if (!$order) return [];

                return [
                    'items' => $order->items->map(function ($item) {
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
                if (!$order) return;

                $totalAmount = 0;
                $existingItemIds = [];

                foreach ($data['items'] as $itemData) {
                    $totalPrice = $itemData['unit_price'] * $itemData['quantity'];
                    $totalAmount += $totalPrice;

                    $menuItem = \App\Models\MenuItem::find($itemData['menu_item_id']);

                    if (!empty($itemData['id'])) {
                        $orderItem = $order->items()->find($itemData['id']);
                        if ($orderItem) {
                            $orderItem->update([
                                'menu_item_id' => $itemData['menu_item_id'],
                                'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                                'quantity' => $itemData['quantity'],
                                'unit_price' => $itemData['unit_price'],
                                'total_price' => $totalPrice,
                                'notes' => $itemData['notes'] ?? null,
                            ]);
                            $existingItemIds[] = $orderItem->id;
                        }
                    } else {
                        $newItem = $order->items()->create([
                            'menu_item_id' => $itemData['menu_item_id'],
                            'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $totalPrice,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                        $existingItemIds[] = $newItem->id;
                    }
                }

                $order->items()->whereNotIn('id', $existingItemIds)->delete();
                $order->update(['total_amount' => $totalAmount]);

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
            && in_array(auth()->user()->role->name ?? null, ['manager', 'branch_admin','restauranrt_admin']);
    }

    public function openTable($tableId)
    {
        if ($this->selectedTableId === $tableId) {
            $this->selectedTableId = null;
        } else {
            $this->selectedTableId = $tableId;
            $this->discountAmount = 0;
            $this->taxPercentage = 0;
            $this->extraCharges = 0; 
        }
    }

    public function toggleReservation($tableId)
    {
        $user = auth()->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);

        $activeCount = QrSession::where('restaurant_table_id', $table->id)
            ->where('is_active', true)
            ->count();

        if ($activeCount > 0) {
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

        $activeSessions = QrSession::where('restaurant_table_id', $table->id)
            ->where('is_active', true)
            ->get();

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

        OrderStatusUpdated::dispatch($order);
    }

    public function acceptPayFirstOrder($orderId)
    {
        $user = auth()->user();
        $order = Order::where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        
        $order->update([
            'status' => 'accepted',
            'payment_status' => 'paid'
        ]);

        KitchenQueue::firstOrCreate(
            ['order_id' => $order->id],
            ['current_status' => 'placed', 'priority' => 0]
        );

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => 'accepted',
            'changed_by' => $user->id,
        ]);

        OrderStatusUpdated::dispatch($order);
        Notification::make()->title('Payment Confirmed & Sent to Kitchen')->success()->send();
    }

    public function rejectPayFirstOrder($orderId)
    {
        $user = auth()->user();
        $order = Order::where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        
        $order->update([
            'status' => 'rejected',
            'payment_status' => 'failed'
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => 'rejected',
            'changed_by' => $user->id,
        ]);

        OrderStatusUpdated::dispatch($order);
        Notification::make()->title('Order Rejected (No Payment)')->danger()->send();
    }

    public function sendBillToCustomer()
    {
        $viewData = $this->getViewData();
        $hostSessionId = $viewData['hostSessionId'];
        
        if (!$hostSessionId) return;

        if (\App\Models\Invoice::where('qr_session_id', $hostSessionId)->exists()) {
            Notification::make()->title('Invoice already generated for this session.')->warning()->send();
            return;
        }
        
        $session = QrSession::find($hostSessionId);
        if (!$session) return;

        $orders = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served']);

        if ($orders->isEmpty()) return;

        $subtotal = $orders->sum('total_amount');
        $amountAlreadyPaid = $orders->where('payment_status', 'paid')->sum('total_amount');
        
        $taxable = max(0, $subtotal - (float) $this->discountAmount);
        $taxAmt = $taxable * ((float) $this->taxPercentage / 100);
        $extra = (float) $this->extraCharges;

        $invoiceGrandTotal = $taxable + $taxAmt + $extra;
        
        $amountDue = round($invoiceGrandTotal - $amountAlreadyPaid, 2);
        $amountDue = max(0, $amountDue); 

        $billStatus = $amountDue > 0 ? 'pending' : 'paid';

        $latestOrderId = $orders->pluck('id')->last();
        $transactionRef = 'ORD' . $latestOrderId . '_' . Str::random(10);

        try {
            DB::transaction(function () use ($latestOrderId, $subtotal, $taxAmt, $extra, $amountDue, $billStatus, $transactionRef, $session) {
                
                $payment = Payment::updateOrCreate(
                    ['order_id' => $latestOrderId],
                    [
                        'restaurant_id' => auth()->user()->restaurant_id,
                        'branch_id' => auth()->user()->branch_id,
                        'subtotal' => $subtotal,
                        'discount_amount' => $this->discountAmount,
                        'tax_amount' => $taxAmt,
                        'extra_charges' => $extra,
                        'amount' => $amountDue, 
                        'status' => $billStatus,
                        'payment_method' => $billStatus === 'paid' ? 'online' : 'pending',
                        'transaction_reference' => $transactionRef,
                        'paid_at' => $billStatus === 'paid' ? now() : null,
                    ]
                );

                $upiId = auth()->user()->branch_id
                    ? \App\Models\Branch::find(auth()->user()->branch_id)->upi_id
                    : \App\Models\Restaurant::find(auth()->user()->restaurant_id)->upi_id;

                $paymentPayload = array_merge($payment->toArray(), [
                    'upi_id' => $upiId,
                    'merchant_category_code' => '5812',
                ]);

                if ($billStatus === 'paid') {
                    $invoice = \App\Services\Orders\InvoiceService::generateInvoice($session, $payment);
                    $session->update(['status' => 'completed']);
                    
                    $paymentPayload['invoice_number'] = $invoice->invoice_number;
                }

                event(new \App\Events\BillGenerated($session->id, $paymentPayload));
            });

            if ($billStatus === 'paid') {
                Notification::make()
                    ->title('Bill Auto-Settled & Invoice Generated')
                    ->body('Because the amount due was ₹0, the official invoice was automatically generated.')
                    ->success()
                    ->send();
                
                $this->selectedTableId = null;
                $this->discountAmount = 0;
                $this->taxPercentage = 0;
                $this->extraCharges = 0;
            } else {
                Notification::make()
                    ->title('Final Bill Sent!')
                    ->body('The bill is now displaying on the customer\'s screen for payment. You can also print the physical bill now.')
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error generating bill')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function confirmPayment()
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];
        $hostSessionId = $viewData['hostSessionId'];

        if (!$pendingPayment || !$hostSessionId) return;

        if (\App\Models\Invoice::where('qr_session_id', $hostSessionId)->exists()) {
            Notification::make()->title('Invoice already generated for this session.')->warning()->send();
            
            $this->selectedTableId = null;
            return;
        }

        $session = QrSession::find($hostSessionId);
        
        if (!$session) {
            Notification::make()->title('Session not found.')->danger()->send();
            return;
        }

        try {
            DB::transaction(function () use ($pendingPayment, $session, &$paymentPayload) {
                
                $pendingPayment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => $pendingPayment->payment_method === 'pending' ? 'cash' : $pendingPayment->payment_method
                ]);

                $invoice = \App\Services\Orders\InvoiceService::generateInvoice($session, $pendingPayment);
                
                $session->update(['status' => 'completed']);

                $upiId = auth()->user()->branch_id
                    ? \App\Models\Branch::find(auth()->user()->branch_id)->upi_id
                    : \App\Models\Restaurant::find(auth()->user()->restaurant_id)->upi_id;

                $paymentPayload = array_merge($pendingPayment->toArray(), [
                    'upi_id' => $upiId,
                    'merchant_category_code' => '5812',
                    'invoice_number' => $invoice->invoice_number,
                ]);
            });

            event(new \App\Events\BillGenerated($hostSessionId, $paymentPayload));

            Notification::make()
                ->title('Payment Confirmed & Invoice Generated')
                ->body('The official tax invoice has been generated successfully.')
                ->success()
                ->send();
                
            $this->selectedTableId = null;
            $this->discountAmount = 0;
            $this->taxPercentage = 0;
            $this->extraCharges = 0;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Invoice Generation Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;
        $branchId = $user->branch_id;

        $tablesQuery = RestaurantTable::where('restaurant_id', $restaurantId);
        if ($branchId)
            $tablesQuery->where('branch_id', $branchId);
        else
            $tablesQuery->whereNull('branch_id');

        $tables = $tablesQuery
            ->with(['qrSessions' => fn($q) => $q->where('is_active', true)]) // Fetch active sessions to calculate totals
            ->withCount([
                'qrSessions as active_sessions_count' => fn($q) => $q->where('is_active', true),
            ])
            ->get()
            ->map(function ($table) {
                // Calculate live totals only for ACTIVE sessions on this table
                $activeSessionIds = $table->qrSessions->pluck('id')->toArray();
                
                $orders = Order::whereIn('qr_session_id', $activeSessionIds)
                    ->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])
                    ->get();
                
                $subtotal = $orders->sum('total_amount');
                $amountPaid = $orders->where('payment_status', 'paid')->sum('total_amount');
                
                // Attach the calculated metrics back to the table object
                $table->live_subtotal = $subtotal;
                $table->live_due = max(0, $subtotal - $amountPaid);
                $table->live_orders_count = $orders->count();
                
                return $table;
            })
            ->sortByDesc(function ($t) {
                if ($t->active_sessions_count > 0) return 2;
                if (($t->status ?? '') === 'reserved' || ($t->is_reserved ?? false)) return 1;
                return 0;
            })
            ->values();

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
            ->whereNull('room_session_id')
            ->with(['items.menuItem.category', 'restaurantTable', 'restaurant']) 
            ->orderBy('created_at', 'asc')->get();

        return compact(
            'tables',
            'totalTables',
            'activeTables',
            'occupancyRate',
            'activeSessions',
            'incomingOrders',
            'selectedTableData',
            'tableOrders',
            'activeDinersList',
            'hostSessionId',
            'pendingPayment'
        );
    }
}