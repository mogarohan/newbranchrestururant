<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableBillingResource\Pages;
use App\Models\RestaurantTable;
use App\Models\Payment;
use App\Models\Order;
use App\Models\QrSession;
use App\Models\OrderStatusLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString; // 👈 CSS Injection ke liye zaroori hai

class TableBillingResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Billing Checkout';
    protected static ?string $navigationGroup = 'Finance';

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager', 'branch_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->where('restaurant_id', $user->restaurant_id);

        // 👇 BRANCH ISOLATION: Main Restaurant admin sees only branch_id NULL, Branch admin sees only their ID
        if ($user->isRestaurantAdmin()) {
            $query->whereNull('branch_id');
        } elseif ($user->isBranchAdmin() || $user->isManager()) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query->with([
            'sessions' => fn($q) => $q->where('is_active', true),
            'sessions.orders' => fn($q) => $q->where('status', '!=', 'cancelled'),
            'sessions.orders.items.menuItem.category',
            'sessions.guests' => fn($q) => $q->where('is_active', true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // 🎨 CSS INJECTION FOR TRANSPARENT GRID CARDS
            ->heading(new HtmlString('
                <style>
                    /* Main Table Container */
                    .fi-ta-ctn {
                        background-color: transparent !important;
                        box-shadow: none !important;
                        border: none !important; /* Remove outer border for grid layout */
                    }
                    /* Toolbars (Header Search & Footer Pagination) */
                    .fi-ta-header-toolbar, .fi-ta-footer {
                        background-color: transparent !important;
                        border-color: rgba(156, 163, 175, 0.2) !important;
                    }
                    /* Inner Content wrapper */
                    .fi-ta-content {
                        background-color: transparent !important;
                    }
                    /* Individual Grid Cards */
                    .fi-ta-record {
                        background-color: transparent !important;
                        border: 1px solid rgba(156, 163, 175, 0.2) !important;
                        border-radius: 16px !important;
                        box-shadow: none !important;
                        transition: all 0.2s ease;
                    }
                    /* Card Hover Effect */
                    .fi-ta-record:hover {
                        background-color: rgba(234, 88, 12, 0.05) !important; /* Orange tint */
                        border-color: rgba(234, 88, 12, 0.4) !important;
                        transform: translateY(-3px);
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important;
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
            // Changed from solid backgrounds to clean flex layout
            ->recordClasses(fn(RestaurantTable $record) => 'flex flex-col')
            ->columns([
                Tables\Columns\Layout\Stack::make([

                    // --- 1. TABLE HEADER (DYNAMIC NAME LAA RAHA HAI YAHAN) ---
                    Tables\Columns\TextColumn::make('table_number')
                        ->formatStateUsing(function ($state, RestaurantTable $record) {
                            // Find active primary session (host)
                            $hostSession = $record->sessions->where('is_primary', true)->first();

                            // Agar host ka naam maujood hai, toh usko return karo, warna "Table {X}"
                            if ($hostSession && !empty($hostSession->customer_name)) {
                                return $hostSession->customer_name;
                            }

                            return "Table {$state}";
                        })
                        ->weight(FontWeight::Black)
                        ->color('primary')
                        ->alignCenter()
                        // Replaced solid background with transparent dashed border
                        ->extraAttributes(['style' => 'font-size: 1.5rem; padding: 1rem; border-bottom: 1px dashed rgba(156, 163, 175, 0.3); background: transparent; text-transform: uppercase;']),

                    // --- 2. SUMMARY NUMBERS ---
                    Tables\Columns\Layout\Grid::make(2)->schema([
                        Tables\Columns\TextColumn::make('total_bill')
                            ->label('Total Bill')
                            ->state(function (RestaurantTable $record) {
                                return $record->sessions->flatMap->orders->sum('total_amount');
                            })
                            ->money('INR')
                            ->weight(FontWeight::Bold)
                            ->extraAttributes(['style' => 'text-align: center; padding: 1rem;']),

                        Tables\Columns\TextColumn::make('active_customers')
                            ->label('Active Diners')
                            ->state(function (RestaurantTable $record) {
                                return $record->sessions->count() . ' People';
                            })
                            ->color('info')
                            ->weight(FontWeight::Bold)
                            ->extraAttributes(['style' => 'text-align: center; padding: 1rem;']),
                    ]),

                    // --- 3. BALANCE DUE ---
                    Tables\Columns\TextColumn::make('due_amount')
                        ->label('Balance Due')
                        ->state(function (RestaurantTable $record) {
                            $total = $record->sessions->flatMap->orders->sum('total_amount');
                            $orderIds = $record->sessions->flatMap->orders->pluck('id');
                            $paid = Payment::whereIn('order_id', $orderIds)->where('status', 'paid')->sum('amount');

                            return max(0, $total - $paid);
                        })
                        ->money('INR')
                        ->weight(FontWeight::Black)
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                        ->color(fn($state) => $state > 0 ? 'danger' : 'gray')
                        ->alignCenter()
                        // Removed solid background
                        ->extraAttributes(['style' => 'padding: 1rem; margin-top: 0.5rem; border-top: 1px dashed rgba(156, 163, 175, 0.3); background: transparent;']),

                ])->space(0),
            ])
            ->actions([

                /* ================= CHECKOUT ACTION ================= */
                Tables\Actions\Action::make('checkout')
                    ->label('Checkout & Settle')
                    ->icon('heroicon-o-credit-card')
                    ->button()
                    ->outlined() // 👈 Premium Outline Look added here
                    ->color('success')
                    ->modalHeading(fn(RestaurantTable $record) => "Checkout - Table {$record->table_number}")
                    ->modalWidth('6xl')
                    ->modalSubmitActionLabel('Confirm Payment & Clear Table')
                    ->fillForm(function (RestaurantTable $record): array {
                        $total = $record->sessions->flatMap->orders->sum('total_amount');
                        $orderIds = $record->sessions->flatMap->orders->pluck('id');
                        $paid = Payment::whereIn('order_id', $orderIds)->where('status', 'paid')->sum('amount');

                        return [
                            'subtotal' => max(0, $total - $paid),
                            'tip' => 0,
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
                                        ->content(function (RestaurantTable $record) {

                                            // Using Tailwind Classes for Dark/Light mode support instead of inline styles
                                            $html = '<div class="max-h-[500px] overflow-y-auto p-6 bg-white dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl font-mono shadow-sm">';
                                            $html .= '<h2 class="text-center text-2xl font-black text-gray-900 dark:text-white mb-1">TABLE ' . $record->table_number . '</h2>';
                                            $html .= '<div class="text-center text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 border-b-2 border-dashed border-gray-300 dark:border-gray-600 pb-4 mb-5">FINAL BILLING SUMMARY</div>';

                                            $hasOrders = false;
                                            $grandTotal = 0;
                                            $totalOrdersCount = 0;

                                            // 1. Identify the Primary Host Session
                                            $primarySession = $record->sessions->where('is_primary', true)->first();

                                            if ($primarySession) {
                                                // --- HOST ORDERS ---
                                                $hostOrdersCount = $primarySession->orders->count();
                                                $hasOrders = $hasOrders || $hostOrdersCount > 0;

                                                $html .= "<div class='mb-6'>";
                                                $html .= "<div class='text-base font-bold bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 rounded-lg flex justify-between items-center'><span>👑 HOST: {$primarySession->customer_name}</span> <span class='font-normal text-xs text-gray-500 dark:text-gray-400'>({$hostOrdersCount} Orders)</span></div>";

                                                foreach ($primarySession->orders as $order) {
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
                                                    $grandTotal += $order->total_amount;
                                                }
                                                $html .= "</div>";

                                                // --- GUEST ORDERS ---
                                                $guests = $record->sessions->where('host_session_id', $primarySession->id);

                                                if ($guests->isNotEmpty()) {
                                                    $html .= "<div class='text-sm font-bold text-center border-y border-dashed border-gray-300 dark:border-gray-600 py-2 my-6 text-gray-500 dark:text-gray-400 tracking-widest'>--- JOINED GUESTS ---</div>";

                                                    foreach ($guests as $guest) {
                                                        $guestOrdersCount = $guest->orders->count();
                                                        $hasOrders = $hasOrders || $guestOrdersCount > 0;

                                                        $html .= "<div class='mb-5'>";
                                                        $html .= "<div class='text-sm font-bold bg-orange-50 dark:bg-orange-500/10 border border-orange-100 dark:border-orange-500/20 text-gray-900 dark:text-white px-3 py-2 rounded-lg flex justify-between items-center'><span>👤 GUEST: {$guest->customer_name}</span> <span class='font-normal text-xs text-gray-500 dark:text-gray-400'>({$guestOrdersCount} Orders)</span></div>";

                                                        foreach ($guest->orders as $order) {
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
                                                            $grandTotal += $order->total_amount;
                                                        }
                                                        $html .= "</div>";
                                                    }
                                                }
                                            }

                                            if (!$hasOrders) {
                                                return new HtmlString("<div class='text-center p-6 text-gray-500 dark:text-gray-400'>No valid orders found to bill.</div>");
                                            }

                                            // Final Summary Footer
                                            $html .= "
                                                <div class='border-t-2 border-gray-900 dark:border-gray-100 mt-6 pt-4'>
                                                    <div class='flex justify-between text-sm text-gray-600 dark:text-gray-400'>
                                                        <span>Total Orders Delivered:</span>
                                                        <span>{$totalOrdersCount}</span>
                                                    </div>
                                                    <div class='flex justify-between text-xl font-black text-gray-900 dark:text-white mt-2'>
                                                        <span>GRAND TOTAL</span>
                                                        <span class='text-green-600 dark:text-green-400'>₹" . number_format($grandTotal, 2) . "</span>
                                                    </div>
                                                </div>
                                            </div>";

                                            return new HtmlString($html);
                                        })
                                ]),

                            // 💰 RIGHT COLUMN: PAYMENT GATEWAY & CLOSURE
                            Forms\Components\Section::make('Payment Collection')
                                ->columnSpan(5)
                                ->schema([
                                    Forms\Components\TextInput::make('subtotal')
                                        ->label('Remaining Bill Balance')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->readOnly()
                                        ->extraInputAttributes(['class' => 'font-bold text-lg text-gray-900 dark:text-white']),

                                    Forms\Components\TextInput::make('tip')
                                        ->label('Add Tip Amount (Optional)')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->default(0)
                                        ->live(onBlur: true),

                                    Forms\Components\Placeholder::make('grand_total')
                                        ->label('Total to Collect')
                                        ->content(function (Forms\Get $get) {
                                            $total = (float) $get('subtotal') + (float) $get('tip');
                                            // Updated to Tailwind Theme Aware UI
                                            return new HtmlString("
                                                <div class='text-3xl font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 p-4 rounded-xl border border-emerald-500/30 text-center shadow-sm'>
                                                    ₹" . number_format($total, 2) . "
                                                </div>
                                            ");
                                        }),

                                    Forms\Components\ToggleButtons::make('payment_method')
                                        ->label('Select Payment Method')
                                        ->options([
                                            'cash' => 'Cash',
                                            'upi' => 'UPI (QR)',
                                            'card' => 'Credit/Debit Card',
                                        ])
                                        ->icons([
                                            'cash' => 'heroicon-m-banknotes',
                                            'upi' => 'heroicon-m-qr-code',
                                            'card' => 'heroicon-m-credit-card',
                                        ])
                                        ->colors([
                                            'cash' => 'success',
                                            'upi' => 'info',
                                            'card' => 'warning',
                                        ])
                                        ->inline()
                                        ->required()
                                        ->default('cash'),

                                    Forms\Components\TextInput::make('transaction_reference')
                                        ->label('Transaction ID / UTR')
                                        ->placeholder('Required for Online Payments')
                                        ->required(fn(Forms\Get $get) => in_array($get('payment_method'), ['upi', 'card']))
                                        ->visible(fn(Forms\Get $get) => $get('payment_method') !== 'cash'),

                                ]),
                        ]),
                    ])
                    ->action(function (RestaurantTable $record, array $data) {

                        $activeSessions = $record->sessions()->where('is_active', true)->get();
                        $sessionIds = $activeSessions->pluck('id')->toArray();

                        $validOrders = Order::whereIn('qr_session_id', $sessionIds)
                            ->where('status', '!=', 'cancelled')
                            ->get();

                        $orderIds = $validOrders->pluck('id')->toArray();
                        $latestOrderId = collect($orderIds)->last();
                        $totalAmountToRecord = (float) $data['subtotal'] + (float) $data['tip'];

                        if ($latestOrderId && $totalAmountToRecord > 0) {
                            Payment::create([
                                'restaurant_id' => $record->restaurant_id,
                                'branch_id' => $record->branch_id,
                                'order_id' => $latestOrderId,
                                'amount' => $totalAmountToRecord,
                                'payment_method' => $data['payment_method'],
                                'status' => 'paid',
                                'transaction_reference' => $data['transaction_reference'] ?? null,
                                'paid_at' => now(),
                            ]);
                        }

                        if (!empty($orderIds)) {
                            Order::whereIn('id', $orderIds)->update(['status' => 'completed']);

                            foreach ($orderIds as $oId) {
                                OrderStatusLog::create([
                                    'order_id' => $oId,
                                    'from_status' => 'served',
                                    'to_status' => 'completed',
                                    'changed_by' => auth()->id(),
                                ]);
                            }
                        }

                        if (!empty($sessionIds)) {
                            QrSession::whereIn('id', $sessionIds)->update([
                                'is_active' => false,
                            ]);
                        }
                    }),
            ])
            ->recordAction(null)
            ->recordUrl(null);
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