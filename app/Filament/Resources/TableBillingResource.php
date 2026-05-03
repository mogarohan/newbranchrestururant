<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableBillingResource\Pages;
use App\Models\RestaurantTable;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TableBillingResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Billing Checkout';
    protected static ?string $navigationGroup = 'Finance';

    public static function canAccess(): bool
    {
        // return auth()->check()
        //     && auth()->user()->restaurant_id
        //     && in_array(auth()->user()->role->name ?? '', ['restaurant_admin', 'manager', 'branch_admin']);
        return false; // Temporarily disable access to billing resource for all users
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->where('restaurant_id', $user->restaurant_id);

        if ($user->isRestaurantAdmin()) {
            $query->whereNull('branch_id');
        } elseif ($user->isBranchAdmin() || $user->isManager()) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query->with([
            'sessions' => fn($q) => $q->where('is_active', true),
            // 👇 FIX: Ignore completed orders in the query
            'sessions.orders' => fn($q) => $q->whereNotIn('status', ['cancelled', 'completed']),
            'sessions.orders.items.menuItem.category',
            'sessions.guests' => fn($q) => $q->where('is_active', true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('
                <style>
                    /* Hides outer table borders and makes it transparent */
                    .fi-ta-ctn {
                        background-color: transparent !important;
                        box-shadow: none !important;
                        border: none !important;
                    }
                    .fi-ta-header-toolbar, .fi-ta-footer {
                        background-color: transparent !important;
                        border-color: rgba(156, 163, 175, 0.2) !important;
                    }
                    .fi-ta-content {
                        background-color: transparent !important;
                    }
                    
                    /* Clean Box Layout for Cards */
                    .fi-ta-record {
                        background-color: #ffffff !important;
                        border: 1px solid rgba(156, 163, 175, 0.3) !important;
                        border-radius: 12px !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
                        transition: all 0.2s ease;
                        cursor: pointer;
                        overflow: hidden;
                    }
                    .dark .fi-ta-record {
                        background-color: #1e293b !important;
                        border: 1px solid rgba(255, 255, 255, 0.1) !important;
                    }
                    .fi-ta-record:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 6px 15px rgba(244, 125, 32, 0.15) !important;
                        border-color: rgba(244, 125, 32, 0.5) !important;
                    }
                </style>
                <span style="font-size: 1.25rem; font-weight: 800;">Active Tables Checkout</span>
            '))
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
                '2xl' => 4,
            ])
            ->recordClasses(fn(RestaurantTable $record) => 'flex flex-col h-full justify-between')
            ->columns([
                Tables\Columns\Layout\Stack::make([

                    // --- 1. PREFIXED TITLE (TABLE NO + HOST NAME) ---
                    Tables\Columns\TextColumn::make('table_number')
                        ->formatStateUsing(function ($state, RestaurantTable $record) {
                            $primarySession = $record->sessions->where('is_primary', true)->first();

                            $hostName = ($primarySession && !empty($primarySession->customer_name))
                                ? $primarySession->customer_name
                                : '-';

                            return new HtmlString("
                                <div style='display: flex; align-items: center; justify-content: center; gap: 1.5rem; width: 100%; line-height: 1.2;'>
                                    <div style='display: flex; flex-direction: column; align-items: center;'>
                                        <span style='font-size: 0.65rem; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;'>Table</span>
                                        <span style='color: #F47D20; font-size: 1.1rem; font-weight: 900;'>{$state}</span>
                                    </div>
                                    
                                    <div style='width: 1px; height: 28px; background-color: rgba(156, 163, 175, 0.3);'></div>
                                    
                                    <div style='display: flex; flex-direction: column; align-items: center;'>
                                        <span style='font-size: 0.65rem; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;'>Customer</span>
                                        <span style='color: #3B82F6; font-size: 1.1rem; font-weight: 900;'>{$hostName}</span>
                                    </div>
                                </div>
                            ");
                        })
                        ->alignCenter()
                        ->extraAttributes(['style' => 'padding: 0.75rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2); background: rgba(244, 125, 32, 0.03);']),

                    // --- 2. SUMMARY (ACTIVE DINERS & BILL) ---
                    Tables\Columns\Layout\Grid::make(2)->schema([
                        Tables\Columns\TextColumn::make('active_customers')
                            ->label('Active Diners')
                            ->state(function (RestaurantTable $record) {
                                $count = $record->sessions->count();
                                return $count > 0 ? "{$count} People" : "Empty";
                            })
                            ->color('info')
                            ->weight(FontWeight::Bold)
                            ->extraAttributes(['style' => 'text-align: center; padding: 1rem;']),

                        Tables\Columns\TextColumn::make('due_amount')
                            ->label('Balance Due')
                            ->state(function (RestaurantTable $record) {
                                return $record->sessions->flatMap->orders
                                    ->whereNotIn('status', ['completed', 'cancelled'])
                                    ->sum('total_amount');
                            })
                            ->money('INR')
                            ->weight(FontWeight::Black)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->color(fn($state) => $state > 0 ? 'danger' : 'gray')
                            ->extraAttributes(['style' => 'text-align: center; padding: 1rem; border-left: 1px solid rgba(156, 163, 175, 0.2);']),
                    ]),

                    // --- 3. CLICK TO SETTLE INDICATOR ---
                    Tables\Columns\TextColumn::make('checkout_hint')
                        ->state(function () {
                            return new HtmlString("
                                <div style='background-color: rgba(244, 125, 32, 0.1); color: #F47D20; border: 1px solid rgba(244, 125, 32, 0.2); border-radius: 8px; padding: 0.5rem; text-align: center; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 1rem 1rem 1rem;'>
                                    Click to Settle &rarr;
                                </div>
                            ");
                        })
                        ->extraAttributes(['style' => 'padding: 0;'])
                        ->alignCenter(),
                ])->space(0),
            ])
            ->recordAction('checkout')
            ->actions([
                Tables\Actions\Action::make('checkout')
                    ->label('Checkout')
                    ->hiddenLabel()
                    ->modalHeading(fn(RestaurantTable $record) => "Checkout - Table {$record->table_number}")
                    ->modalWidth('6xl')
                    ->modalSubmitActionLabel('Confirm Payment')
                    ->fillForm(function (RestaurantTable $record): array {
                        $total = $record->sessions->flatMap->orders
                            ->whereNotIn('status', ['completed', 'cancelled'])
                            ->sum('total_amount');

                        return [
                            'subtotal' => $total,
                            'discount_amount' => 0,
                            'tax_percentage' => 5, // Default 5% Tax
                            'extra_charges' => 0, // Default Extra Charges
                            'payment_method' => 'cash',
                        ];
                    })
                    ->form([
                        Forms\Components\Grid::make(12)->schema([

                            // 📜 LEFT COLUMN: DETAILED MASTER RECEIPT
                            Forms\Components\Section::make('Master Order History')
                                ->columnSpan(7)
                                ->schema([
                                    Forms\Components\Placeholder::make('receipt')
                                        ->hiddenLabel()
                                        ->content(function (RestaurantTable $record, Forms\Get $get) {

                                            // 1. GRAB LIVE VALUES
                                            $sub = (float) $get('subtotal');
                                            $disc = (float) $get('discount_amount');
                                            $taxP = (float) $get('tax_percentage');
                                            $extra = (float) $get('extra_charges');

                                            $taxable = max(0, $sub - $disc);
                                            $taxAmt = $taxable * ($taxP / 100);
                                            $grandTotal = $taxable + $taxAmt + $extra;

                                            // 2. BUILD THE RECEIPT HTML
                                            $html = '<div class="max-h-[550px] overflow-y-auto p-6 bg-white dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl font-mono shadow-sm">';
                                            $html .= '<h2 class="text-center text-2xl font-black text-gray-900 dark:text-white mb-1">TABLE ' . $record->table_number . '</h2>';
                                            $html .= '<div class="text-center text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 border-b-2 border-dashed border-gray-300 dark:border-gray-600 pb-4 mb-5">FINAL BILLING SUMMARY</div>';

                                            $hasOrders = false;
                                            $totalOrdersCount = 0;
                                            $primarySession = $record->sessions->where('is_primary', true)->first();

                                            if ($primarySession) {
                                                // --- HOST ORDERS ---
                                                $hostOrdersCount = $primarySession->orders->whereNotIn('status', ['completed', 'cancelled'])->count();
                                                $hasOrders = $hasOrders || $hostOrdersCount > 0;

                                                $html .= "<div class='mb-6'>";
                                                $html .= "<div class='text-base font-bold bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 rounded-lg flex justify-between items-center'><span>👑 HOST: {$primarySession->customer_name}</span> <span class='font-normal text-xs text-gray-500 dark:text-gray-400'>({$hostOrdersCount} Orders)</span></div>";

                                                foreach ($primarySession->orders->whereNotIn('status', ['completed', 'cancelled']) as $order) {
                                                    $totalOrdersCount++;
                                                    $html .= "<div class='mt-3 pl-3 border-l-2 border-gray-200 dark:border-gray-700'>";
                                                    $html .= "<div class='text-xs font-semibold text-gray-400 dark:text-gray-500 mb-2'>Order #{$order->id}</div>";

                                                    foreach ($order->items as $item) {
                                                        $name = $item->menuItem ? $item->menuItem->name : $item->item_name;
                                                        $category = $item->menuItem?->category ? strtoupper($item->menuItem->category->name) : 'GENERAL';

                                                        $html .= "
                                                        <div class='flex justify-between items-start text-sm mb-2 text-gray-800 dark:text-gray-200'>
                                                            <span><strong class='text-orange-500 dark:text-orange-400'>{$item->quantity}x</strong> {$name} <br><span class='text-[10px] text-gray-500 dark:text-gray-400'>[{$category}]</span></span>
                                                            <span class='font-bold'>₹{$item->total_price}</span>
                                                        </div>";
                                                    }
                                                    $html .= "</div>";
                                                }
                                                $html .= "</div>";

                                                // --- GUEST ORDERS ---
                                                $guests = $record->sessions->where('host_session_id', $primarySession->id);

                                                if ($guests->isNotEmpty()) {
                                                    $html .= "<div class='text-sm font-bold text-center border-y border-dashed border-gray-300 dark:border-gray-600 py-2 my-6 text-gray-500 dark:text-gray-400 tracking-widest'>--- JOINED GUESTS ---</div>";

                                                    foreach ($guests as $guest) {
                                                        $guestOrdersCount = $guest->orders->whereNotIn('status', ['completed', 'cancelled'])->count();
                                                        if ($guestOrdersCount > 0) {
                                                            $hasOrders = true;

                                                            $html .= "<div class='mb-5'>";
                                                            $html .= "<div class='text-sm font-bold bg-orange-50 dark:bg-orange-500/10 border border-orange-100 dark:border-orange-500/20 text-gray-900 dark:text-white px-3 py-2 rounded-lg flex justify-between items-center'><span>👤 GUEST: {$guest->customer_name}</span> <span class='font-normal text-xs text-gray-500 dark:text-gray-400'>({$guestOrdersCount} Orders)</span></div>";

                                                            foreach ($guest->orders->whereNotIn('status', ['completed', 'cancelled']) as $order) {
                                                                $totalOrdersCount++;
                                                                $html .= "<div class='mt-3 pl-3 border-l-2 border-orange-200 dark:border-orange-500/30'>";
                                                                $html .= "<div class='text-xs font-semibold text-gray-400 dark:text-gray-500 mb-2'>Order #{$order->id}</div>";

                                                                foreach ($order->items as $item) {
                                                                    $name = $item->menuItem ? $item->menuItem->name : $item->item_name;
                                                                    $category = $item->menuItem?->category ? strtoupper($item->menuItem->category->name) : 'GENERAL';

                                                                    $html .= "
                                                                    <div class='flex justify-between items-start text-sm mb-2 text-gray-800 dark:text-gray-200'>
                                                                        <span><strong class='text-orange-500 dark:text-orange-400'>{$item->quantity}x</strong> {$name} <br><span class='text-[10px] text-gray-500 dark:text-gray-400'>[{$category}]</span></span>
                                                                        <span class='font-bold'>₹{$item->total_price}</span>
                                                                    </div>";
                                                                }
                                                                $html .= "</div>";
                                                            }
                                                            $html .= "</div>";
                                                        }
                                                    }
                                                }
                                            }

                                            if (!$hasOrders) {
                                                return new HtmlString("<div class='text-center p-6 text-gray-500 dark:text-gray-400 font-bold'>No unpaid orders found to bill.</div>");
                                            }

                                            // 3. LIVE SUBTOTAL / TAX / EXTRA / DISCOUNT SECTION
                                            $html .= "
                                                <div class='border-t-2 border-gray-900 dark:border-gray-100 mt-6 pt-4'>
                                                    <div class='flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-4'>
                                                        <span>Total Orders Delivered:</span>
                                                        <span>{$totalOrdersCount}</span>
                                                    </div>
                                                    
                                                    <div class='flex justify-between text-sm text-gray-800 dark:text-gray-200 mb-1'>
                                                        <span>Subtotal:</span>
                                                        <span class='font-bold'>₹" . number_format($sub, 2) . "</span>
                                                    </div>";

                                            if ($disc > 0) {
                                                $html .= "
                                                    <div class='flex justify-between text-sm text-green-600 dark:text-green-400 mb-1'>
                                                        <span>Discount:</span>
                                                        <span class='font-bold'>- ₹" . number_format($disc, 2) . "</span>
                                                    </div>";
                                            }

                                            if ($taxAmt > 0) {
                                                $html .= "
                                                    <div class='flex justify-between text-sm text-red-500 dark:text-red-400 mb-3'>
                                                        <span>Tax ({$taxP}%):</span>
                                                        <span class='font-bold'>+ ₹" . number_format($taxAmt, 2) . "</span>
                                                    </div>";
                                            }

                                            if ($extra > 0) {
                                                $html .= "
                                                    <div class='flex justify-between text-sm text-gray-800 dark:text-gray-200 mb-3'>
                                                        <span>Extra Charges:</span>
                                                        <span class='font-bold'>+ ₹" . number_format($extra, 2) . "</span>
                                                    </div>";
                                            }

                                            $html .= "
                                                    <div class='flex justify-between text-xl font-black text-gray-900 dark:text-white mt-4 border-t border-dashed border-gray-300 dark:border-gray-700 pt-3'>
                                                        <span>GRAND TOTAL</span>
                                                        <span class='text-emerald-600 dark:text-emerald-400'>₹" . number_format($grandTotal, 2) . "</span>
                                                    </div>
                                                </div>
                                            </div>";

                                            return new HtmlString($html);
                                        })
                                ]),

                            // 💰 RIGHT COLUMN: PAYMENT GATEWAY
                            Forms\Components\Section::make('Payment Collection')
                                ->columnSpan(5)
                                ->schema([
                                    Forms\Components\TextInput::make('subtotal')
                                        ->label('Subtotal (₹)')
                                        ->numeric()
                                        ->readOnly()
                                        ->extraInputAttributes(['class' => 'font-bold text-gray-900 dark:text-white']),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('discount_amount')
                                            ->label('Discount (₹)')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true),

                                        Forms\Components\TextInput::make('tax_percentage')
                                            ->label('Tax (%)')
                                            ->numeric()
                                            ->default(5)
                                            ->live(onBlur: true),

                                        Forms\Components\TextInput::make('extra_charges')
                                            ->label('Extra Chg (₹)')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true),
                                    ]),

                                    // 👇 REAL-TIME CALCULATED GRAND TOTAL
                                    Forms\Components\Placeholder::make('grand_total')
                                        ->label('Total to Collect')
                                        ->content(function (Forms\Get $get) {
                                            $sub = (float) $get('subtotal');
                                            $disc = (float) $get('discount_amount');
                                            $taxP = (float) $get('tax_percentage');
                                            $extra = (float) $get('extra_charges');

                                            $taxable = max(0, $sub - $disc);
                                            $taxAmt = $taxable * ($taxP / 100);
                                            $total = $taxable + $taxAmt + $extra;

                                            return new HtmlString("
                                                <div class='text-3xl font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 p-4 rounded-xl border border-emerald-500/30 text-center shadow-sm'>
                                                    ₹" . number_format($total, 2) . "
                                                    <div class='text-xs font-normal text-gray-500 mt-1'>Includes ₹" . number_format($taxAmt, 2) . " Tax & ₹" . number_format($extra, 2) . " Extra</div>
                                                </div>
                                            ");
                                        }),

                                    Forms\Components\ToggleButtons::make('payment_method')
                                        ->label('Select Payment Method')
                                        ->options([
                                            'cash' => 'Cash',
                                            'upi' => 'UPI QR',
                                            'card' => 'Card'
                                        ])
                                        ->inline()
                                        ->required()
                                        ->live()
                                        ->default('cash'),

                                    Forms\Components\Placeholder::make('upi_qr')
                                        ->hiddenLabel()
                                        ->visible(fn(Forms\Get $get) => $get('payment_method') === 'upi')
                                        ->content(function (RestaurantTable $record, Forms\Get $get) {
                                            $sub = (float) $get('subtotal');
                                            $disc = (float) $get('discount_amount');
                                            $taxP = (float) $get('tax_percentage');
                                            $extra = (float) $get('extra_charges');

                                            $taxable = max(0, $sub - $disc);
                                            $taxAmt = $taxable * ($taxP / 100);
                                            $grandTotal = number_format($taxable + $taxAmt + $extra, 2, '.', '');

                                            // 1. Fetch correct UPI ID
                                            $upiId = null;
                                            if ($record->branch_id) {
                                                $branch = Branch::find($record->branch_id);
                                                $upiId = $branch ? $branch->upi_id : null;
                                            }
                                            if (!$upiId) {
                                                $restaurant = Restaurant::find($record->restaurant_id);
                                                $upiId = $restaurant ? $restaurant->upi_id : null;
                                            }

                                            // 2. Handle missing UPI ID
                                            if (empty($upiId)) {
                                                return new HtmlString("
                                                    <div class='p-4 bg-red-50 text-red-600 rounded-lg text-center border border-red-200 mt-4'>
                                                        <strong class='block mb-1'>Missing UPI ID</strong>
                                                        Please add a UPI ID to the Restaurant or Branch settings.
                                                    </div>
                                                ");
                                            }

                                            // 3. Generate internal SVG QR Code
                                            $merchantName = urlencode($record->restaurant->name ?? 'Restaurant');
                                            $upiString = "upi://pay?pa={$upiId}&pn={$merchantName}&am={$grandTotal}&cu=INR";

                                            $qrSvg = QrCode::format('svg')->size(180)->margin(1)->generate($upiString);
                                            $qrSvg = preg_replace('/<\?xml.*?\?>/', '', $qrSvg);

                                            return new HtmlString("
                                                <div class='flex flex-col items-center justify-center p-6 bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl mt-4'>
                                                    <span class='text-sm font-bold text-gray-500 dark:text-gray-400 mb-4 uppercase tracking-widest'>Scan to Pay ₹{$grandTotal}</span>
                                                    <div class='bg-white p-2 rounded-lg shadow-sm border border-gray-100 flex justify-center items-center'>
                                                        {$qrSvg}
                                                    </div>
                                                    <span class='text-xs text-gray-400 mt-4 text-center'>
                                                        ID: {$upiId} <br>Amount is fixed.
                                                    </span>
                                                </div>
                                            ");
                                        }),

                                    Forms\Components\TextInput::make('transaction_reference')
                                        ->label('Transaction ID / UTR')
                                        ->visible(fn(Forms\Get $get) => $get('payment_method') !== 'cash'),
                                ]),
                        ]), // 👇 FIX: Changed from `]);` to `]),` to correctly close the schema array element.
                    ])
                    ->action(function (RestaurantTable $record, array $data) {
                        $activeSessions = $record->sessions()->where('is_active', true)->get();
                        $sessionIds = $activeSessions->pluck('id')->toArray();

                        $validOrders = Order::whereIn('qr_session_id', $sessionIds)
                            ->whereNotIn('status', ['completed', 'cancelled'])
                            ->get();

                        $orderIds = $validOrders->pluck('id')->toArray();
                        $latestOrderId = collect($orderIds)->last();

                        // 🧮 Math
                        $sub = (float) $data['subtotal'];
                        $disc = (float) $data['discount_amount'];
                        $taxP = (float) $data['tax_percentage'];
                        $extra = (float) ($data['extra_charges'] ?? 0);

                        $taxable = max(0, $sub - $disc);
                        $taxAmt = $taxable * ($taxP / 100);
                        $grandTotal = $taxable + $taxAmt + $extra;

                        if ($latestOrderId && $grandTotal > 0) {
                            Payment::create([
                                'restaurant_id' => $record->restaurant_id,
                                'branch_id' => $record->branch_id,
                                'order_id' => $latestOrderId,
                                'subtotal' => $sub,
                                'discount_amount' => $disc,
                                'tax_amount' => $taxAmt,
                                'extra_charges' => $extra,
                                'amount' => $grandTotal,
                                'payment_method' => $data['payment_method'],
                                'status' => 'paid',
                                'transaction_reference' => $data['transaction_reference'] ?? null,
                                'paid_at' => now(),
                            ]);
                        }

                        if (!empty($orderIds)) {
                            Order::whereIn('id', $orderIds)->update(['status' => 'completed']);
                            $updatedOrders = Order::whereIn('id', $orderIds)->get();
                            foreach ($updatedOrders as $ord) {
                                \App\Events\OrderStatusUpdated::dispatch($ord);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Payment Confirmed Successfully!')
                            ->body('The customer can now download their bill.')
                            ->success()
                            ->send();
                    })
                    ->after(fn(\Livewire\Component $livewire) => $livewire->dispatch('$refresh')),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTableBillings::route('/')
        ];
    }
}