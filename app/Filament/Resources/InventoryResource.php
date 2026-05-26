<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\MenuItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InventoryResource extends Resource
{
    protected static ?string $model = MenuItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Inventory Control';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?string $title = 'Live Stock & Inventory';
    protected static ?int $navigationSort = 2;

    // ── Access Control (SaaS Feature Flag Protection Linked) ──────────────────
    public static function canAccess(): bool
    {
        $user = auth()->user();

        // 👇 SYSTEM SECURITY CHECK: Checking if the restaurant actually purchased/owns inventory tier features 👇
        return auth()->check()
            && $user->restaurant_id
            && (bool) $user->restaurant?->has_inventory // Dynamic authorization link via your new migration column
            && in_array($user->role->name ?? null, ['manager', 'branch_admin', 'restaurant_admin']);
    }

    // ── Isolated Data Query ─────────────────────────────────────
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $q = parent::getEloquentQuery()->where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $q->where(fn($q2) => $q2->where('branch_id', $user->branch_id)->orWhereNull('branch_id'));
        } else {
            $q->whereNull('branch_id');
        }

        return $q->with('category');
    }

    // ── Pro Form Setup (Form Layout unchanged for fallback edits) ──
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('📦 Core Inventory Engine')
                ->description('Choose whether to enforce automated stock tracking bounds on this menu option.')
                ->icon('heroicon-o-cpu-chip')
                ->schema([
                    Forms\Components\Toggle::make('track_stock')
                        ->label('Activate Real-Time Stock Tracking')
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Available Units in Kitchen')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('portions')
                        ->visible(fn(Forms\Get $get) => $get('track_stock'))
                        ->required(fn(Forms\Get $get) => $get('track_stock')),

                    Forms\Components\TextInput::make('low_stock_threshold')
                        ->label('Low Stock Alert Warning Point')
                        ->numeric()
                        ->minValue(1)
                        ->default(5)
                        ->suffix('portions')
                        ->visible(fn(Forms\Get $get) => $get('track_stock')),

                    Forms\Components\Toggle::make('is_available')
                        ->label('Publish Visibility Status')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    // ── Pro Table Component Layout ──────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('
                <style>
                    .fi-ta-header-ctn { background: rgba(255,255,255,0.02) !important; padding: 16px !important; }
                    .fi-ta-record { transition: all 0.2s ease-in-out !important; }
                    .fi-ta-record:hover { background: rgba(42, 71, 149, 0.02) !important; }
                    .fi-ta-text-input input { font-weight: bold !important; text-align: center !important; }
                </style>
            '))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('DISH PROFILE')
                    ->searchable()
                    ->weight('black')
                    ->color('primary')
                    ->description(fn(MenuItem $r) => 'Category: ' . ($r->category?->name ?? 'Unassigned')),

                Tables\Columns\TextColumn::make('price')
                    ->label('PRICE RATE')
                    ->money('INR')
                    ->weight('bold'),

                Tables\Columns\ToggleColumn::make('track_stock')
                    ->label('TRACK STOCK')
                    ->onColor('info'),

                // 👇 AUTOMATIC MENU ENABLE LOGIC ON STOCK UPDATE 👇
                Tables\Columns\TextInputColumn::make('stock_quantity')
                    ->label('STOCK QUANTITY')
                    ->placeholder('Unlimited')
                    ->type('number')
                    ->disabled(fn(MenuItem $record) => !$record->track_stock)
                    ->updateStateUsing(function (MenuItem $record, $state) {
                        $qty = blank($state) ? null : (int) $state;

                        $updateData = ['stock_quantity' => $qty];

                        // Swiggy/Zomato Logic: Agar stock 0 se zyada add kiya toh menu status auto-enable ho jaye
                        if ($qty !== null && $qty > 0) {
                            $updateData['is_available'] = true;
                        }

                        $record->update($updateData);
                        return $qty;
                    }),

                Tables\Columns\TextInputColumn::make('low_stock_threshold')
                    ->label('ALERT THRESHOLD')
                    ->type('number')
                    ->disabled(fn(MenuItem $record) => !$record->track_stock),

                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('MENU STATUS')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->filters([
                // ── FILTERS COMPLETELY REMOVED AS REQUESTED ──
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Refill')
                    ->icon('heroicon-m-bolt')
                    ->color('success')
                    ->button()
                    ->size('xs')
                    ->visible(fn(MenuItem $r) => $r->track_stock)
                    ->form([
                        Forms\Components\TextInput::make('add_qty')
                            ->label('Portions to Inject to Line')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (MenuItem $record, array $data) {
                        $newQty = ($record->stock_quantity ?? 0) + (int) $data['add_qty'];
                        $record->update([
                            'stock_quantity' => $newQty,
                            'is_available' => $newQty > 0 ? true : $record->is_available,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title("Refilled! {$record->name} pushed to {$newQty}")
                            ->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enable_tracking')
                    ->label('Batch Track Selection')
                    ->icon('heroicon-o-archive-box')
                    ->form([
                        Forms\Components\TextInput::make('stock_quantity')->label('Starting Stock')->numeric()->minValue(0)->required(),
                        Forms\Components\TextInput::make('low_stock_threshold')->label('Warning Cap Limit')->numeric()->minValue(1)->default(5)->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $records->each(fn($r) => $r->update([
                            'track_stock' => true,
                            'stock_quantity' => $data['stock_quantity'],
                            'low_stock_threshold' => $data['low_stock_threshold'],
                            'is_available' => ((int) $data['stock_quantity'] > 0) ? true : $r->is_available,
                        ]));
                        \Filament\Notifications\Notification::make()->title('Stock tracking enabled for batch items')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            // ── CHRONOLOGICAL FIFO SORT: Strict sequencing order preserved
            ->defaultSort('id', 'asc')
            ->poll('15s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInventory::route('/'),
        ];
    }
}