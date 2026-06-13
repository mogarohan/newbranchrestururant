<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroceryItemResource\Pages;
use App\Filament\Resources\GroceryItemResource\Widgets\GroceryStatsOverview;
use App\Models\GroceryItem;
use App\Models\MeasurementUnit;
use App\Services\InventoryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GroceryItemResource extends Resource
{
    protected static ?string $model = GroceryItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Raw Materials';
    protected static ?string $navigationGroup = 'Detailed Inventory';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return auth()->check()
            && $user->restaurant_id
            && (bool) $user->restaurant?->has_detailed_inventory
            && in_array($user->role->name ?? null, ['manager', 'branch_admin', 'restaurant_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $q = parent::getEloquentQuery()->where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $q->where(fn($q2) => $q2->where('branch_id', $user->branch_id)->orWhereNull('branch_id'));
        } else {
            $q->whereNull('branch_id');
        }

        return $q->with('measurementUnit');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('restaurant_id')
                ->default(fn() => auth()->user()->restaurant_id),

            Forms\Components\Hidden::make('branch_id')
                ->default(fn() => auth()->user()->branch_id),

            Forms\Components\Section::make('Raw Material Details')
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Ingredient Name')
                        ->placeholder('e.g., Tomato, Cheese, Flour')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU / Code')
                        ->placeholder('Optional barcode or internal code')
                        ->maxLength(50),

                    Forms\Components\Select::make('measurement_unit_id')
                        ->label('Default Unit')
                        ->options(fn() => MeasurementUnit::where('restaurant_id', auth()->user()->restaurant_id)->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('cost_per_unit')
                        ->label('Cost per Unit')
                        ->numeric()
                        ->prefix('₹')
                        ->placeholder('Purchase price per unit'),
                ])->columns(2),

            Forms\Components\Section::make('Stock Levels')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\TextInput::make('current_stock')
                        ->label('Current Stock')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->required(),

                    Forms\Components\TextInput::make('low_stock_threshold')
                        ->label('Low Stock Alert Threshold')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('INGREDIENT')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(GroceryItem $r) => $r->sku ? "SKU: {$r->sku}" : null),

                Tables\Columns\TextColumn::make('measurementUnit.short_name')
                    ->label('UNIT')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('CURRENT STOCK')
                    ->numeric(2)
                    ->weight('bold')
                    ->color(fn(GroceryItem $r) => match($r->stock_status) {
                        'out_of_stock' => 'danger',
                        'low' => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('low_stock_threshold')
                    ->label('ALERT AT')
                    ->numeric(2)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('cost_per_unit')
                    ->label('COST/UNIT')
                    ->money('INR')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('STATUS')
                    ->badge()
                    ->getStateUsing(fn(GroceryItem $r) => match($r->stock_status) {
                        'out_of_stock' => 'Out of Stock',
                        'low' => 'Low Stock',
                        default => 'In Stock',
                    })
                    ->color(fn(GroceryItem $r) => match($r->stock_status) {
                        'out_of_stock' => 'danger',
                        'low' => 'warning',
                        default => 'success',
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->actions([
                Tables\Actions\Action::make('add_stock')
                    ->label('Add Stock')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->button()
                    ->size('xs')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity to Add')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->maxLength(500),
                    ])
                    ->action(function (GroceryItem $record, array $data) {
                        InventoryService::addStock($record, (float) $data['quantity'], auth()->id(), $data['notes'] ?? null);
                        \Filament\Notifications\Notification::make()
                            ->title("Added {$data['quantity']} to {$record->name}")
                            ->success()->send();
                    }),

                Tables\Actions\Action::make('record_spoilage')
                    ->label('Spoilage')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->button()
                    ->size('xs')
                    ->outlined()
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Spoiled Quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Reason')
                            ->placeholder('e.g., Expired, damaged')
                            ->maxLength(500),
                    ])
                    ->action(function (GroceryItem $record, array $data) {
                        InventoryService::recordSpoilage($record, (float) $data['quantity'], auth()->id(), $data['notes'] ?? null);
                        \Filament\Notifications\Notification::make()
                            ->title("Recorded spoilage of {$data['quantity']} for {$record->name}")
                            ->warning()->send();
                    }),

                Tables\Actions\EditAction::make()->button()->size('xs')->outlined(),
                Tables\Actions\DeleteAction::make()->button()->size('xs')->outlined(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->poll('30s');
    }

    public static function getWidgets(): array
    {
        return [
            GroceryStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroceryItems::route('/'),
            'create' => Pages\CreateGroceryItem::route('/create'),
            'edit' => Pages\EditGroceryItem::route('/{record}/edit'),
        ];
    }
}
