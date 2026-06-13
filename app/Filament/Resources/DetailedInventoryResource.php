<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DetailedInventoryResource\Pages;
use App\Models\InventoryTransaction;
use App\Models\GroceryItem;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DetailedInventoryResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Inventory Ledger';
    protected static ?string $navigationGroup = 'Detailed Inventory';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return auth()->check()
            && $user->restaurant_id
            && (bool) $user->restaurant?->has_detailed_inventory
            && in_array($user->role->name ?? null, ['manager', 'branch_admin', 'restaurant_admin']);
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $q = parent::getEloquentQuery()->where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $q->where(fn($q2) => $q2->where('branch_id', $user->branch_id)->orWhereNull('branch_id'));
        } else {
            $q->whereNull('branch_id');
        }

        return $q->with(['groceryItem', 'performer']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('DATE')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('groceryItem.name')
                    ->label('INGREDIENT')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('TYPE')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match($state) {
                        'addition' => '➕ Addition',
                        'deduction' => '➖ Deduction',
                        'spoilage' => '🗑️ Spoilage',
                        'order_fulfillment' => '📦 Order',
                        'order_cancellation' => '↩️ Cancelled',
                        default => $state,
                    })
                    ->color(fn(string $state) => match($state) {
                        'addition', 'order_cancellation' => 'success',
                        'deduction', 'order_fulfillment' => 'warning',
                        'spoilage' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('QUANTITY')
                    ->numeric(4)
                    ->weight('bold')
                    ->prefix(fn(InventoryTransaction $r) => in_array($r->type, ['addition', 'order_cancellation']) ? '+' : '-')
                    ->color(fn(InventoryTransaction $r) => in_array($r->type, ['addition', 'order_cancellation']) ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('reference_id')
                    ->label('ORDER #')
                    ->placeholder('—')
                    ->prefix('ORD-')
                    ->visible(fn() => true),

                Tables\Columns\TextColumn::make('performer.name')
                    ->label('BY')
                    ->placeholder('System')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('NOTES')
                    ->limit(40)
                    ->tooltip(fn(InventoryTransaction $r) => $r->notes)
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Transaction Type')
                    ->options([
                        'addition' => 'Addition',
                        'deduction' => 'Deduction',
                        'spoilage' => 'Spoilage',
                        'order_fulfillment' => 'Order Fulfillment',
                        'order_cancellation' => 'Order Cancellation',
                    ]),

                Tables\Filters\SelectFilter::make('grocery_item_id')
                    ->label('Ingredient')
                    ->options(fn() => GroceryItem::where('restaurant_id', auth()->user()->restaurant_id)->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDetailedInventory::route('/'),
        ];
    }
}
