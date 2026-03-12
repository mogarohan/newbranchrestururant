<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\HtmlString;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Incoming Orders';
    protected static ?string $navigationGroup = 'Operations';

    /* ---------------- FIXED STATUS HEADER (DISPLAY ONLY) ---------------- */
    public static function init(): void
    {
        FilamentView::registerRenderHook(
            'panels::resource.pages.list-records.table.before',
            fn(): string => Blade::render('
            @php
                $resId = auth()->user()->restaurant_id;
                $counts = \App\Models\Order::where("restaurant_id", $resId)
                    ->selectRaw("status, count(*) as total")
                    ->groupBy("status")
                    ->pluck("total", "status");
                
                // HIGHER CONTRAST COLOR COMBINATIONS
                $statuses = [
                    "placed"    => ["label" => "Placed",    "border" => "#ef4444", "text" => "#b91c1c", "bg" => "#fef2f2"], 
                    "preparing" => ["label" => "Preparing", "border" => "#f97316", "text" => "#c2410c", "bg" => "#fff7ed"], 
                    "ready"     => ["label" => "Ready",     "border" => "#10b981", "text" => "#047857", "bg" => "#ecfdf5"], 
                    "served"    => ["label" => "Served",    "border" => "#3b82f6", "text" => "#1d4ed8", "bg" => "#eff6ff"], 
                    "completed" => ["label" => "Completed", "border" => "#8b5cf6", "text" => "#7c3aed", "bg" => "#f5f3ff"], 
                    "cancelled" => ["label" => "Cancelled", "border" => "#9ca3af", "text" => "#4b5563", "bg" => "#f3f4f6"], 
                ];
            @endphp
            <div style="position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 1.25rem 0; margin-top: -1rem; margin-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; padding: 0 1rem; justify-content: center;">
                    @foreach($statuses as $key => $data)
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 1.25rem; background: {{ $data[\'bg\'] }}; border-radius: 0.75rem; border: 1.5px solid {{ $data[\'border\'] }}; min-width: 140px; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <span style="font-size: 0.75rem; font-weight: 800; color: {{ $data[\'text\'] }}; text-transform: uppercase; letter-spacing: 0.05em;">{{ $data[\'label\'] }}</span>
                            <span style="font-size: 1.5rem; font-weight: 900; color: {{ $data[\'text\'] }}; line-height: 1;">{{ $counts[$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        '),
        );
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name, ['']);
    }

    public static function getEloquentQuery(): Builder
    {
        static::init();

        return parent::getEloquentQuery()
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->with(['items.menuItem', 'table'])
            // Keeps placed orders at the top, then preparing, etc.
            ->orderByRaw("FIELD(status, 'placed', 'preparing', 'ready', 'served','completed', 'cancelled')")
            ->orderBy('created_at', 'asc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
                '2xl' => 4,
            ])
            ->columns([
                Panel::make([
                    Stack::make([
                        Split::make([
                            Stack::make([
                                Tables\Columns\TextColumn::make('id')
                                    ->formatStateUsing(fn($state) => "#{$state}")
                                    ->weight(FontWeight::Black)
                                    ->color('gray')
                                    ->size('lg'),

                                Tables\Columns\TextColumn::make('created_at')
                                    ->since()
                                    ->color(fn(Order $record) => $record->created_at->diffInSeconds(now()) >= 30 && $record->status === 'placed' ? 'danger' : 'gray')
                                    ->weight(fn(Order $record) => $record->created_at->diffInSeconds(now()) >= 30 && $record->status === 'placed' ? FontWeight::Bold : FontWeight::Medium)
                                    ->size('xs')
                                    ->icon('heroicon-m-clock'),
                            ]),

                            Stack::make([
                                Tables\Columns\TextColumn::make('status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'placed' => 'danger',
                                        'preparing' => 'warning',
                                        'ready' => 'success',
                                        'served' => 'info',
                                        'cancelled' => 'gray',
                                        default => 'info',
                                    })
                                    ->formatStateUsing(fn($state) => strtoupper($state))
                                    ->alignEnd(),

                                Tables\Columns\TextColumn::make('customer_name')
                                    ->label('Customer')
                                    ->size('sm')
                                    ->weight(FontWeight::Bold)
                                    ->color('gray')
                                    ->alignEnd()
                                    ->limit(15),
                            ])->alignment('end'),
                        ]),

                        /* --- TABLE NO. --- */
                        Tables\Columns\TextColumn::make('table.table_number')
                            ->formatStateUsing(fn($state) => $state ? "TABLE: {$state}" : "TAKEAWAY")
                            ->weight(FontWeight::ExtraBold)
                            ->size('xl')
                            ->icon('heroicon-m-map-pin')
                            ->color('primary')
                            ->extraAttributes(['class' => 'mt-1']),

                        /* --- ITEMS LIST HTML --- */
                        Tables\Columns\TextColumn::make('items_list')
                            ->label('Items')
                            ->html()
                            ->getStateUsing(function (Order $record) {
                                return $record->items->map(function ($item) {
                                    $category = strtoupper($item->menuItem?->category?->name ?? 'GENERAL');
                                    $name = $item->menuItem ? $item->menuItem->name : $item->item_name;
                                    $qty = $item->quantity;
                                    $price = number_format($item->unit_price * $qty, 2);

                                    $html = "
                                        <div style='margin-bottom: 8px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 6px;'>
                                            <div style='font-weight: 800; font-size: 0.65rem; color: #9ca3af; letter-spacing: 0.05em;'>{$category}</div>
                                            <div style='display: flex; justify-content: space-between; align-items: baseline; width: 100%;'>
                                                <span style='flex-grow: 1; font-size: 0.9rem; font-weight: 600; color: #374151;'><span style='color: #111827;'>{$qty}x</span> {$name}</span>
                                                <span style='font-weight: 700; font-size: 0.85rem; margin-left: 10px; text-align: right; color: #4b5563;'>₹{$price}</span>
                                            </div>";

                                    if ($item->notes) {
                                        $html .= "<div style='font-size: 0.75rem; color: #ef4444; margin-top: 4px; font-style: italic;'>📝 {$item->notes}</div>";
                                    }

                                    $html .= "</div>";

                                    return $html;
                                })->implode('');
                            })
                            ->extraAttributes(['class' => 'mt-3 pt-2 border-t border-gray-200']),

                        /* --- TOTAL --- */
                        Split::make([
                            Tables\Columns\TextColumn::make('total_label')
                                ->default('ORDER TOTAL')
                                ->size('sm')
                                ->weight(FontWeight::ExtraBold)
                                ->color('gray'),
                            Tables\Columns\TextColumn::make('total_amount')
                                ->money('INR')
                                ->weight(FontWeight::Black)
                                ->size('lg')
                                ->color('primary')
                                ->alignEnd(),
                        ])->extraAttributes(['class' => 'pt-3 mt-1']),

                    ])->space(3),
                ])
                    ->extraAttributes(fn(Order $record): array => [
                        'style' => match ($record->status) {
                            'placed' => 'border: 2px solid #ef4444 !important; border-radius: 1rem; background-color: #fef2f2;',
                            'preparing' => 'border: 2px solid #f97316 !important; border-radius: 1rem; background-color: #fff7ed;',
                            'ready' => 'border: 2px solid #10b981 !important; border-radius: 1rem; background-color: #ecfdf5;',
                            'served' => 'border: 2px solid #3b82f6 !important; border-radius: 1rem; background-color: #eff6ff;',
                            'cancelled' => 'border: 2px solid #9ca3af !important; background-color: #f3f4f6 !important; border-radius: 1rem; opacity: 0.8;',
                            default => 'border: 2px solid #9ca3af !important; border-radius: 1rem;',
                        },
                        'class' => 'shadow-md hover:shadow-lg transition-all duration-300',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Reject')
                    ->button()
                    ->color('danger')
                    ->icon('heroicon-m-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn(Order $record) => $record->status === 'placed')
                    ->action(fn(Order $record) => static::processOrder($record, 'cancelled')),

                Tables\Actions\Action::make('confirm')
                    ->label('Accept')
                    ->button()
                    ->color('warning')
                    ->icon('heroicon-o-fire')
                    ->visible(fn(Order $record) => $record->status === 'placed')
                    ->action(fn(Order $record) => static::processOrder($record, 'preparing')),

                Tables\Actions\Action::make('served')
                    ->label('Mark Served')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->visible(fn(Order $record) => in_array($record->status, ['preparing', 'ready']))
                    ->action(fn(Order $record) => static::processOrder($record, 'served')),
            ]);
    }

    public static function processOrder(Order $record, $status)
    {
        $record->update(['status' => $status]);

        if ($status === 'preparing') {
            KitchenQueue::create([
                'order_id' => $record->id,
                'current_status' => 'placed',
                'priority' => 0,
            ]);
        }

        OrderStatusLog::create([
            'order_id' => $record->id,
            'from_status' => 'placed',
            'to_status' => $status,
            'changed_by' => auth()->id(),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }
}