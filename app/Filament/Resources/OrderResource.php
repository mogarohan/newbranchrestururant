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
use Filament\Support\Colors\Color; 
use Illuminate\Database\Eloquent\Collection; // 🌟 IMPORT FOR BULK ACTION 🌟

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Order History';
    protected static ?string $navigationGroup = 'Operations';

    /* --- DISABLE EDIT, CREATE, DELETE --- */
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
        return parent::getEloquentQuery()
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->where('is_hidden', false) // 🌟 NAYA: Khali e j orders dekhadse je hidden nathi 🌟
            ->with([
                'items.menuItem', 
                'table', 
                'roomSession.room', 
                'parcelQrSession.parcelQrCode'
            ])
            ->orderBy('created_at', 'desc');
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
                    ->color(fn (Order $record): array => match ($record->service_type) {
                        'parcel' => Color::Amber,        
                        'room_service' => Color::Blue,   
                        default => Color::Emerald,       
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('table', fn($q) => $q->where('table_number', 'like', "%{$search}%"))
                            ->orWhereHas('parcelQrSession.parcelQrCode', fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('roomSession.room', fn($q) => $q->where('room_number', 'like', "%{$search}%"))
                            ->orWhere('service_type', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->color(fn(string $state): array => match (strtolower($state)) {
                        'placed' => Color::Red,
                        'accepted', 'partial_accepted' => Color::Orange,
                        'preparing' => Color::Yellow,
                        'ready' => Color::Cyan,
                        'served', 'completed' => Color::Emerald,
                        'cancelled', 'rejected' => Color::Rose,
                        default => Color::Gray,
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
                // 🌟 NAYA: Single Order Clear Action 🌟
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
                // 🌟 NAYA: Multiple Orders Bulk Clear Action 🌟
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