<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Order History';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'dine_in' => '🍽️ Dine-in',
                        'parcel' => '🛍️ Parcel',
                        'room_service' => '🚪 Room',
                        default => ucfirst($state ?? 'Dine-in'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'dine_in' => 'primary',
                        'parcel' => 'warning',
                        'room_service' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('table.table_number')
                    ->label('Location')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->service_type === 'parcel') {
                            return $record->parcelQrSession?->parcelQrCode?->name ?? 'Parcel Counter';
                        }
                        if ($record->service_type === 'room_service') {
                            return 'Room ' . ($record->roomSession?->room?->room_number ?? '-');
                        }
                        return $state ? 'Table ' . $state : '-';
                    }),

                Tables\Columns\TextColumn::make('items_summary')
                    ->label('Ordered Items')
                    ->state(function ($record) {
                        return $record->items->map(function ($item) {
                            $qty = $item->confirmed_qty ?? $item->quantity;
                            return "{$qty}x {$item->item_name}";
                        })->implode(', ');
                    })
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->items->map(fn ($i) => ($i->confirmed_qty ?? $i->quantity) . 'x ' . $i->item_name . ' (₹' . number_format($i->unit_price, 2) . ')')->implode(PHP_EOL)),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('INR')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'refunded' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'Pending')),

                Tables\Columns\TextColumn::make('status')
                    ->label('Kitchen Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'placed' => 'gray',
                        'accepted' => 'info',
                        'preparing' => 'warning',
                        'ready' => 'success',
                        'served' => 'primary',
                        'rejected', 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'Placed'))),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No Orders Found')
            ->emptyStateDescription('This customer has not placed any orders yet.');
    }
}
