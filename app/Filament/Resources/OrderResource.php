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
use Illuminate\Database\Eloquent\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Order History';
    protected static ?string $navigationGroup = 'Operations';

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? '', ['manager', 'restaurant_admin', 'branch_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
<<<<<<< HEAD
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->where('restaurant_id', $user->restaurant_id)
            ->with(['items.menuItem', 'table', 'branch'])
=======
        return parent::getEloquentQuery()
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->where('is_hidden', false)
            ->with([
                'items.menuItem', 
                'table', 
                'roomSession.room', 
                'parcelQrSession.parcelQrCode'
            ])
>>>>>>> 7882cc5702ee743df08d3676f8f1c82ea3149f15
            ->orderBy('created_at', 'desc');

        if ($user->isBranchAdmin() || $user->isManager()) {
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('daily_order_number')
                    ->label('Order #')
                    ->formatStateUsing(fn($state, $record) => $state ? "#{$state}" : "#{$record->id}")
                    ->weight('bold')
                    ->searchable(['daily_order_number', 'id']) 
                    ->sortable(),

<<<<<<< HEAD
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Outlet')
                    ->badge()
                    ->color('warning')
                    ->placeholder('Main Restaurant')
                    ->visible(fn() => auth()->user()?->isRestaurantAdmin() && (bool) auth()->user()?->restaurant?->has_branches),

                Tables\Columns\TextColumn::make('table.table_number')
                    ->label('Table')
                    ->formatStateUsing(fn($state) => $state ? "T-{$state}" : "Takeaway")
                    ->searchable()
                    ->color('primary')
                    ->weight('bold'),
=======
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->getStateUsing(function (Order $record) {
                        if ($record->service_type === 'parcel') {
                            $name = $record->parcelQrSession->parcelQrCode->name ?? 'Parcel Queue';
                            return "🛍️ " . strtoupper($name);
                        } elseif ($record->service_type === 'room_service') {
                            $room = $record->roomSession->room->room_number ?? '?';
                            return "🚪 ROOM " . $room;
                        } else {
                            $tableNum = $record->table->table_number ?? 'Takeaway';
                            if (str_contains(strtolower($tableNum), 'takeaway')) {
                                return "🥡 TAKEAWAY";
                            }
                            $cleanNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $tableNum);
                            return "🍽️ TABLE-" . trim($cleanNum);
                        }
                    })
                    ->badge()
                    ->color(fn (Order $record): string => match ($record->service_type) {
                        'parcel' => 'warning',       
                        'room_service' => 'info',    
                        default => 'success',        
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('table', fn($q) => $q->where('table_number', 'like', "%{$search}%"))
                            ->orWhereHas('parcelQrSession.parcelQrCode', fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('roomSession.room', fn($q) => $q->where('room_number', 'like', "%{$search}%"))
                            ->orWhere('service_type', 'like', "%{$search}%");
                    }),
>>>>>>> 7882cc5702ee743df08d3676f8f1c82ea3149f15

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'placed', 'cancelled', 'rejected' => 'danger', 
                        'accepted', 'partial_accepted', 'preparing' => 'warning', 
                        'ready' => 'info', 
                        'served', 'completed' => 'success', 
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
                    ->timezone('Asia/Kolkata') 
                    ->sortable(),
            ])
            ->filters([
<<<<<<< HEAD
                // 0. Branch / Outlet Filter (Restaurant Admin only)
                SelectFilter::make('branch_id')
                    ->label('Filter by Outlet')
                    ->options(function () {
                        $user = auth()->user();
                        if (!$user || !$user->isRestaurantAdmin()) return [];
                        $branches = \App\Models\Branch::where('restaurant_id', $user->restaurant_id)->pluck('name', 'id')->toArray();
                        return ['main' => '🏢 Main Restaurant'] + $branches;
                    })
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (!$value) return;
                        if ($value === 'main') {
                            $query->whereNull('branch_id');
                        } else {
                            $query->where('branch_id', $value);
                        }
                    })
                    ->visible(fn() => auth()->user()?->isRestaurantAdmin() && (bool) auth()->user()?->restaurant?->has_branches),

                // 1. Status Filter
=======
>>>>>>> 7882cc5702ee743df08d3676f8f1c82ea3149f15
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
            ->actions([
                Tables\Actions\Action::make('clear_history')
                    ->label('Clear')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear from History')
                    ->modalDescription('Are you sure you want to remove this order from the UI? It will remain safe in the database.')
                    ->action(fn (Order $record) => $record->update(['is_hidden' => true]))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('clear_selected')
                        ->label('Clear Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Clear Selected Orders')
                        ->modalDescription('Are you sure you want to remove these orders from the UI? They will remain safe in the database.')
                        ->action(fn (Collection $records) => $records->each->update(['is_hidden' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }
}