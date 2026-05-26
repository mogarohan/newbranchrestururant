<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\QrSession;
use App\Models\MenuItem;
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

    // ── Boot: show low-stock alerts on page load ─────────────────────────────
    public function mount(): void
    {
        $this->checkLowStockAlerts();
    }

    public function checkLowStockAlerts(): void
    {
        $user = auth()->user();

        $base = MenuItem::where('restaurant_id', $user->restaurant_id)
            ->where('track_stock', true)
            ->when(
                $user->branch_id,
                fn($q) => $q->where(fn($q2) => $q2->where('branch_id', $user->branch_id)->orWhereNull('branch_id')),
                fn($q) => $q->whereNull('branch_id')
            );

        $outItems = (clone $base)->where('stock_quantity', '<=', 0)->pluck('name');
        $lowItems = (clone $base)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->pluck('name');

        if ($outItems->isNotEmpty()) {
            Notification::make()
                ->title('❌ Out of Stock: ' . $outItems->take(3)->implode(', ') . ($outItems->count() > 3 ? '...' : ''))
                ->body('These items are hidden from the customer menu automatically.')
                ->danger()
                ->persistent()
                ->send();
        }

        if ($lowItems->isNotEmpty()) {
            Notification::make()
                ->title('⚠️ Low Stock: ' . $lowItems->take(3)->implode(', ') . ($lowItems->count() > 3 ? '...' : ''))
                ->body('Restock soon from Inventory → Stock Management.')
                ->warning()
                ->send();
        }
    }

    // ── Realtime listeners ────────────────────────────────────────────────────
    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            // 👇 Changed to trigger Browser Notification handler
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => 'handleOrderStatusUpdated',
            "echo-private:restaurant.{$restaurantId}.alerts,.TableStatusUpdated" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.WaiterCalled" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.BillRequested" => 'notifyBillRequested',
            "echo-private:restaurant.{$restaurantId}.alerts,.PaymentMethodSelected" => 'notifyPaymentMethod',
            "echo-private:restaurant.{$restaurantId},.NewParcelOrder" => '$refresh',
        ];
    }

    // 👇 ADDED: Handle the order status update and trigger browser notification
    public function handleOrderStatusUpdated($event)
    {
        $this->dispatch('$refresh');

        $order = $event['order'] ?? null;
        $status = $order['status'] ?? null;

        $tableNum = $order['table_number'] ?? $order['restaurant_table_id'] ?? 'Unknown';

        // Trigger browser notification ONLY if it is a brand new 'placed' order
        if ($status === 'placed') {
            $this->dispatch(
                'trigger-browser-notification',
                title: "🛎️ Action Required: New Order",
                body: "Table {$tableNum} just placed a new order. Please confirm it."
            );
        }
    }

    public function notifyBillRequested($event): void
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

            // 👇 Optional: Add a browser notification for Bills too!
            $this->dispatch(
                'trigger-browser-notification',
                title: "💰 Bill Requested",
                body: "Table {$tableNum} ({$customer}) requested their bill."
            );

            Cache::put($cacheKey, true, now()->addSeconds(30));
        }
    }

    public function notifyPaymentMethod($event): void
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

    // ── Billing actions (FULLY RESTORED & OPTIMIZED) ───────────────────────────
    public function cancelPendingBill(): void
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];

        if ($pendingPayment && $pendingPayment->status === 'pending') {
            $pendingPayment->delete();
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

    public function printPendingBill(): void
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

        $gstIn = $restaurant->gst_no ?? '-';
        $phone = $restaurant->phone ?? '012345678910';
        $address = $restaurant->address ?? '-';

        $orders = Order::with('items')
            ->where('qr_session_id', $hostSessionId)
            ->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served'])
            ->get();

        $itemsHtml = '';
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $displayQty = $item->confirmed_qty ?? $item->quantity;
                if ($displayQty <= 0)
                    continue;

                $rate = $item->unit_price;
                $amount = $rate * $displayQty;

                $itemsHtml .= "
                <tr>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000;'>{$item->item_name}</td>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000; text-align: center;'>{$displayQty}</td>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000; text-align: right;'>" . number_format($rate, 2) . "</td>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000; text-align: right;'>" . number_format($amount, 2) . "</td>
                </tr>";
            }
        }

        $html = "
        <html>
        <head>
            <style>
                @page { margin: 0; size: 80mm auto; }
                body { margin: 5px; font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #000; }
                table { width: 100%; border-collapse: collapse; }
            </style>
        </head>
        <body>
            <div style='text-align:center;'>
                <h2 style='margin:0; font-size:20px; font-weight:bold; text-transform: uppercase;'>{$restaurant->name}</h2>
                <div style='font-size: 12px; margin-top: 5px;'>{$address}</div>
                <div style='font-size: 12px; margin-top: 2px;'>MOB: {$phone}</div>
                <div style='font-size: 12px; margin-top: 2px;'>GSTIN: {$gstIn}</div>
            </div>
            <hr style='border-top:1px dashed #000; margin:10px 0;'/>
            <div style='display: flex; justify-content: space-between; font-size: 12px; font-weight: bold;'>
                <span>Bill No: #{$pendingPayment->id}</span>
                <span>Date: " . now()->format('d/m/Y') . "</span>
            </div>
            <div style='display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; margin-top: 5px;'>
                <span>Name: {$session->customer_name}</span>
                <span>Mode: " . strtoupper($pendingPayment->payment_method) . "</span>
            </div>
            <div style='font-size: 13px; font-weight: bold; margin-top: 5px;'>TABLE : T-{$table->table_number}</div>
            <hr style='border-top:1px dashed #000; margin:10px 0;'/>
            <table>
                <thead>
                    <tr style='text-transform: uppercase; font-size: 11px; border-bottom: 1px dashed #000;'>
                        <th style='text-align:left; padding-bottom:5px;'>Description</th>
                        <th style='text-align:center; padding-bottom:5px;'>Qty</th>
                        <th style='text-align:right; padding-bottom:5px;'>Rate</th>
                        <th style='text-align:right; padding-bottom:5px;'>Amount</th>
                    </tr>
                </thead>
                <tbody>{$itemsHtml}</tbody>
            </table>
            <div style='text-align:right; margin-top: 10px; font-size: 14px; font-weight: bold;'>
                <div>Sub Total: ₹" . number_format($pendingPayment->subtotal, 2) . "</div>
                <div style='font-size: 18px; margin-top: 5px;'>Grand Total: ₹" . number_format($pendingPayment->amount, 2) . "</div>
            </div>
            <div style='text-align:center; margin-top:20px; font-size:12px; font-weight:bold;'>
                *** THANK YOU FOR DINING WITH US! ***
            </div>
        </body>
        </html>";

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

    public function sendBillToCustomer(): void
    {
        $viewData = $this->getViewData();
        $hostSessionId = $viewData['hostSessionId'];

        if (!$hostSessionId)
            return;

        if (\App\Models\Invoice::where('qr_session_id', $hostSessionId)->exists()) {
            Notification::make()->title('Invoice already generated for this session.')->warning()->send();
            return;
        }

        $session = QrSession::find($hostSessionId);
        if (!$session)
            return;

        $orders = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);
        if ($orders->isEmpty())
            return;

        $subtotal = $orders->sum(function ($order) {
            return $order->confirmed_total ?? $order->total_amount;
        });
        $amountAlreadyPaid = $orders->where('payment_status', 'paid')->sum(function ($order) {
            return $order->confirmed_total ?? $order->total_amount;
        });

        $taxable = max(0, $subtotal - (float) $this->discountAmount);
        $taxAmt = $taxable * ((float) $this->taxPercentage / 100);
        $extra = (float) $this->extraCharges;
        $invoiceGrandTotal = $taxable + $taxAmt + $extra;
        $amountDue = max(0, round($invoiceGrandTotal - $amountAlreadyPaid, 2));
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
                    ->success()->send();

                $this->selectedTableId = null;
                $this->discountAmount = 0;
                $this->taxPercentage = 0;
                $this->extraCharges = 0;
            } else {
                Notification::make()
                    ->title('Final Bill Sent!')
                    ->body('The bill is now displaying on the customer\'s screen for payment.')
                    ->success()->send();
            }
        } catch (\Exception $e) {
            Notification::make()->title('Error generating bill')->body($e->getMessage())->danger()->send();
        }
    }

    public function confirmPayment(): void
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];
        $hostSessionId = $viewData['hostSessionId'];

        if (!$pendingPayment || !$hostSessionId)
            return;

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
                    'payment_method' => $pendingPayment->payment_method === 'pending' ? 'cash' : $pendingPayment->payment_method,
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
                ->success()->send();

            $this->selectedTableId = null;
            $this->discountAmount = 0;
            $this->taxPercentage = 0;
            $this->extraCharges = 0;
        } catch (\Exception $e) {
            Notification::make()->title('Invoice Generation Failed')->body($e->getMessage())->danger()->send();
        }
    }

    // ── Order actions ─────────────────────────────────────────────────────────
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
                            ->options(
                                MenuItem::where('restaurant_id', auth()->user()->restaurant_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('unit_price', MenuItem::find($state)?->price ?? 0)
                            ),
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
                    ->addActionLabel('Add Another Item'),
            ])
            ->action(function (array $data) {
                $viewData = $this->getViewData();
                $hostSessionId = $viewData['hostSessionId'];

                if (!$hostSessionId) {
                    Notification::make()->title('No active session on this table.')->danger()->send();
                    return;
                }

                $validatedItems = [];
                $outOfStockItemNames = [];

                DB::transaction(function () use ($data, &$validatedItems, &$outOfStockItemNames) {
                    foreach ($data['items'] as $item) {
                        $menuItem = MenuItem::where('id', $item['menu_item_id'])->lockForUpdate()->first();

                        if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                            if ($menuItem->stock_quantity <= 0) {
                                $outOfStockItemNames[] = $menuItem->name;
                                continue;
                            } elseif ($menuItem->stock_quantity < $item['quantity']) {
                                $item['quantity'] = $menuItem->stock_quantity;
                            }
                        }
                        $validatedItems[] = $item;
                    }
                });

                if (!empty($outOfStockItemNames)) {
                    Notification::make()
                        ->title('Some Items Sold Out! 🚨')
                        ->body('The following items just sold out and were removed from this ticket: ' . implode(', ', $outOfStockItemNames))
                        ->warning()
                        ->persistent()
                        ->send();
                }

                if (empty($validatedItems)) {
                    Notification::make()->title('Order Cancelled')->body('All items requested are currently out of stock.')->danger()->send();
                    return;
                }

                $totalAmount = 0;
                foreach ($validatedItems as $item) {
                    $totalAmount += ($item['unit_price'] * $item['quantity']);
                }

                $order = Order::create([
                    'restaurant_id' => auth()->user()->restaurant_id,
                    'branch_id' => auth()->user()->branch_id,
                    'restaurant_table_id' => $this->selectedTableId,
                    'qr_session_id' => $hostSessionId,
                    'customer_name' => 'Manager (Dashboard)',
                    'total_amount' => $totalAmount,
                    'confirmed_total' => $totalAmount,
                    'status' => 'accepted',
                    'payment_status' => 'paid',
                ]);

                foreach ($validatedItems as $item) {
                    $menuItem = MenuItem::find($item['menu_item_id']);

                    if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                        $menuItem->decrement('stock_quantity', $item['quantity']);
                        if ($menuItem->fresh()->stock_quantity <= 0) {
                            $menuItem->update(['is_available' => false]);
                        }
                    }

                    $order->items()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                        'quantity' => $item['quantity'],
                        'confirmed_qty' => $item['quantity'],
                        'requested_qty' => $item['quantity'],
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
                            ->options(
                                MenuItem::where('restaurant_id', auth()->user()->restaurant_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('unit_price', MenuItem::find($state)?->price ?? 0)
                            ),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Hidden::make('unit_price'),
                        TextInput::make('notes')->nullable(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add Item'),
            ])
            ->fillForm(function (array $arguments) {
                $order = Order::with('items')->find($arguments['orderId']);
                if (!$order)
                    return [];

                return [
                    'items' => $order->items->map(fn($item) => [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'notes' => $item->notes,
                    ])->toArray(),
                ];
            })
            ->action(function (array $data, array $arguments) {
                $order = Order::find($arguments['orderId']);
                if (!$order)
                    return;

                $totalAmount = 0;
                $existingItemIds = [];

                foreach ($data['items'] as $itemData) {
                    $totalPrice = $itemData['unit_price'] * $itemData['quantity'];
                    $totalAmount += $totalPrice;
                    $menuItem = MenuItem::find($itemData['menu_item_id']);

                    if (!empty($itemData['id'])) {
                        $orderItem = $order->items()->find($itemData['id']);
                        if ($orderItem) {
                            $orderItem->update([
                                'menu_item_id' => $itemData['menu_item_id'],
                                'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                                'quantity' => $itemData['quantity'],
                                'confirmed_qty' => $itemData['quantity'],
                                'requested_qty' => $itemData['quantity'],
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
                            'confirmed_qty' => $itemData['quantity'],
                            'requested_qty' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $totalPrice,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                        $existingItemIds[] = $newItem->id;
                    }
                }

                $order->items()->whereNotIn('id', $existingItemIds)->delete();
                $order->update([
                    'total_amount' => $totalAmount,
                    'confirmed_total' => $totalAmount
                ]);

                OrderStatusUpdated::dispatch($order);
                Notification::make()->title('Order updated successfully.')->success()->send();
            });
    }

    // ── Access & layout ───────────────────────────────────────────────────────
    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? null, ['manager', 'branch_admin', 'restaurant_admin']);
    }

    // ── Table management ──────────────────────────────────────────────────────
    public function openTable($tableId): void
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

    public function toggleReservation($tableId): void
    {
        $user = auth()->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);

        $activeCount = QrSession::where('restaurant_table_id', $table->id)
            ->where('is_active', true)->count();

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
            'metadata' => ['from_status' => $oldStatus, 'to_status' => $table->status],
        ]);

        $this->selectedTableId = null;
    }

    public function cleanTable($tableId): void
    {
        $user = auth()->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);

        $activeSessions = QrSession::where('restaurant_table_id', $table->id)->where('is_active', true)->get();
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
            'metadata' => ['sessions_closed' => $closedSessionsCount],
        ]);

        event(new \App\Events\TableStatusUpdated($table->id, 'available', $table->restaurant_id));
        Notification::make()->title("Table {$table->table_number} Cleaned")->success()->send();

        if ($this->selectedTableId === $tableId) {
            $this->selectedTableId = null;
        }
    }

    // ── Order status updates ──────────────────────────────────────────────────
    public function updateStatus($orderId, $status)
    {
        $user = auth()->user();
        $order = Order::with('items')->where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        $stockNotes = [];
        $wasPartial = false;

        if ($status === 'accepted') {
            DB::transaction(function () use ($order, &$wasPartial, &$stockNotes) {
                $newTotal = 0;

                foreach ($order->items as $orderItem) {
                    if (!$orderItem->menu_item_id)
                        continue;

                    $menuItem = MenuItem::where('id', $orderItem->menu_item_id)->lockForUpdate()->first();

                    if (!$menuItem || !$menuItem->track_stock || $menuItem->stock_quantity === null) {
                        $newTotal += $orderItem->total_price;
                        $orderItem->update(['confirmed_qty' => $orderItem->quantity]);
                        continue;
                    }

                    $available = (int) max(0, $menuItem->stock_quantity);
                    $requested = (int) $orderItem->quantity;

                    if ($available <= 0) {
                        $stockNotes[] = '"' . $menuItem->name . '" was out of stock.';
                        $orderItem->update([
                            'quantity' => 0,
                            'confirmed_qty' => 0,
                            'total_price' => 0,
                        ]);
                        $wasPartial = true;
                    } elseif ($available < $requested) {
                        $newQty = $available;
                        $newTotalPrice = $orderItem->unit_price * $newQty;
                        $stockNotes[] = '"' . $menuItem->name . '": ordered ' . $requested . ', only ' . $available . ' available.';

                        $menuItem->decrement('stock_quantity', $newQty);
                        if ($menuItem->fresh()->stock_quantity <= 0) {
                            $menuItem->update(['is_available' => false]);
                        }

                        $orderItem->update([
                            'quantity' => $newQty,
                            'confirmed_qty' => $newQty,
                            'total_price' => $newTotalPrice,
                        ]);
                        $newTotal += $newTotalPrice;
                        $wasPartial = true;
                    } else {
                        $menuItem->decrement('stock_quantity', $requested);
                        if ($menuItem->fresh()->stock_quantity <= 0) {
                            $menuItem->update(['is_available' => false]);
                        }

                        $orderItem->update(['confirmed_qty' => $requested]);
                        $newTotal += $orderItem->total_price;
                    }
                }

                $order->update([
                    'total_amount' => $newTotal,
                    'confirmed_total' => $newTotal,
                    'stock_note' => $wasPartial ? 'Adjusted: ' . implode(' | ', $stockNotes) : null,
                ]);
            });

            $finalStatus = $wasPartial ? 'partial_accepted' : 'accepted';
            $order->update(['status' => $finalStatus]);

            KitchenQueue::firstOrCreate(
                ['order_id' => $order->id],
                ['current_status' => 'placed', 'priority' => 0]
            );
        } else {
            $order->update(['status' => $status]);
        }

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => $order->fresh()->status,
            'changed_by' => $user->id,
        ]);

        OrderStatusUpdated::dispatch($order->fresh());
        event(new \App\Events\StockUpdated($order->restaurant_id));
    }

    public function acceptOrderWithStock(int $orderId, array $itemConfirmations): void
    {
        $user = auth()->user();
        $order = Order::with('items.menuItem')->where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $this->_runPartialAccept($order, $itemConfirmations);
    }

    protected function _runPartialAccept(Order $order, array $itemConfirmations): void
    {
        $user = auth()->user();
        $oldStatus = $order->status;
        $newTotal = 0;
        $isPartialOrder = false;

        DB::transaction(function () use ($order, $itemConfirmations, &$newTotal, &$isPartialOrder, $user) {
            foreach ($order->items as $orderItem) {
                $menuItem = $orderItem->menuItem;
                $confirmation = collect($itemConfirmations)->firstWhere('order_item_id', $orderItem->id);
                $managerConfirmedQty = $confirmation['confirmed_qty'] ?? null;
                $requestedQty = (int) $orderItem->quantity;

                if ($menuItem && $menuItem->track_stock) {
                    $availableStock = (int) max(0, $menuItem->stock_quantity ?? 0);
                    $confirmedQty = $managerConfirmedQty !== null ? min((int) $managerConfirmedQty, $availableStock, $requestedQty) : min($requestedQty, $availableStock);

                    if ($confirmedQty > 0) {
                        $menuItem->decrement('stock_quantity', $confirmedQty);
                        if ($menuItem->fresh()->stock_quantity <= 0) {
                            $menuItem->update(['is_available' => false]);
                        }
                    }
                } else {
                    $confirmedQty = $managerConfirmedQty ?? $requestedQty;
                }

                if ($confirmedQty < $requestedQty) {
                    $isPartialOrder = true;
                }

                $confirmedTotal = $orderItem->unit_price * $confirmedQty;
                $orderItem->update([
                    'requested_qty' => $requestedQty,
                    'confirmed_qty' => $confirmedQty,
                    'total_price' => $confirmedTotal,
                ]);

                $newTotal += $confirmedTotal;
            }

            $order->update([
                'status' => 'accepted',
                'is_partial' => $isPartialOrder,
                'confirmed_total' => $newTotal,
                'total_amount' => $newTotal,
            ]);

            KitchenQueue::firstOrCreate(
                ['order_id' => $order->id],
                ['current_status' => 'placed', 'priority' => 0]
            );
        });

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => 'accepted',
            'changed_by' => $user->id,
            'metadata' => ['is_partial' => $isPartialOrder, 'confirmed_total' => $newTotal],
        ]);

        OrderStatusUpdated::dispatch($order->fresh('items.menuItem'));
    }

    // ── View data ─────────────────────────────────────────────────────────────
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
            ->with(['qrSessions' => fn($q) => $q->where('is_active', true)])
            ->withCount(['qrSessions as active_sessions_count' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->map(function ($table) {
                $activeSessionIds = $table->qrSessions->pluck('id')->toArray();
                $orders = Order::whereIn('qr_session_id', $activeSessionIds)
                    ->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served'])
                    ->get();

                $table->live_subtotal = $orders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                $table->live_due = max(0, $table->live_subtotal - $orders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount));
                $table->live_orders_count = $orders->count();
                return $table;
            })
            ->sortByDesc(fn($t) => $t->active_sessions_count > 0 ? 2 : ((($t->status ?? '') === 'reserved') ? 1 : 0))
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
            $selectedTableData = RestaurantTable::with(['qrSessions' => fn($q) => $q->where('is_active', true)])->find($this->selectedTableId);

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
            ->orderBy('created_at', 'asc')
            ->get();

        $parcelOrders = Order::where('restaurant_id', $restaurantId)
            ->whereNull('restaurant_table_id')
            ->whereIn('status', ['placed', 'accepted', 'preparing', 'ready'])
            ->with(['items.menuItem'])
            ->orderBy('created_at', 'asc')
            ->get();

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
            'pendingPayment',
            'parcelOrders'
        );
    }
}