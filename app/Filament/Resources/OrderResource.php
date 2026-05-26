<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Order History';
    protected static ?string $navigationGroup = 'Operations';

    /* --- DISABLE EDIT, CREATE, DELETE --- */
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit($record): bool
    {
        return false;
    }
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? '', ['manager', 'restaurant_admin', 'branch_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->with(['items.menuItem', 'table'])
            ->orderBy('created_at', 'desc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('table.table_number')
                    ->label('Table')
                    ->formatStateUsing(fn($state) => $state ? "T-{$state}" : "Takeaway")
                    ->searchable()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->color(fn(string $state): string => match ($state) {
                        'placed' => 'danger',
                        'preparing' => 'warning',
                        'ready' => 'success',
                        'served', 'completed' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('items_summary')
                    ->label('Items')
                    ->getStateUsing(function (Order $record) {
                        return $record->items->map(fn($i) => "{$i->quantity}x {$i->item_name}")->implode(', ');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('INR')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
            ])
            ->filters([
                // 1. Status Filter
                SelectFilter::make('status')
                    ->options([
                        'accepted' => 'Accepted',
                        'placed' => 'Placed',
                        'preparing' => 'Preparing',
                        'ready' => 'Ready',
                        'served' => 'Served',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                // 2. Date Range Filter
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('From Date'),
                        DatePicker::make('created_until')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    })
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }
}