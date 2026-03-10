<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KitchenQueueResource\Pages;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use App\Events\OrderStatusUpdated;
class KitchenQueueResource extends Resource
{
    protected static ?string $model = KitchenQueue::class;
protected static bool $shouldRegisterNavigation = false; // 🔥 Hides the old table
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationLabel = 'Kitchen Display';
    protected static ?string $navigationGroup = 'Kitchen';

    /*
    |--------------------------------------------------------------------------
    | ACCESS CONTROL (Chef Only)
    |--------------------------------------------------------------------------
    */

    public static function canViewAny(): bool
    {
        return false;
        //auth()->user()?->role?->name === 'chef';
    }

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

    /*
    |--------------------------------------------------------------------------
    | QUERY (Only Current Restaurant Orders)
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'order.table',
            'order.items'
        ])
        ->whereHas('order', function ($query) {
            $query->where('restaurant_id', auth()->user()->restaurant_id);
        })
        ->orderByRaw("FIELD(current_status, 'placed', 'preparing', 'ready')")
        ->orderBy('created_at', 'asc');
}


    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE (Kitchen Display Board)
    |--------------------------------------------------------------------------
    */

   public static function table(Table $table): Table
    {
    return $table
        // ->poll('5s')
        ->contentGrid([
            'md' => 2,
            'xl' => 3,
        ])
        ->columns([
            Tables\Columns\Layout\Stack::make([

                /*
                |--------------------------------------------------------------------------
                | HEADER (Table No + Timer)
                |--------------------------------------------------------------------------
                */
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('order.table.table_number')
                        ->prefix('Table: ')
                        ->weight(FontWeight::Black)
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                        ->color('primary'),

                    Tables\Columns\TextColumn::make('created_at')
                        ->since()
                        ->icon('heroicon-m-clock')
                        ->color('gray')
                        ->alignEnd(),
                ]),

                /*
                |--------------------------------------------------------------------------
                | CUSTOMER
                |--------------------------------------------------------------------------
                */
                Tables\Columns\TextColumn::make('order.customer_name')
                    ->icon('heroicon-m-user')
                    ->color('gray'),

                /*
                |--------------------------------------------------------------------------
                | ITEMS (FIXED RELATION NAME)
                |--------------------------------------------------------------------------
                */
                Tables\Columns\TextColumn::make('order.items')
                    ->formatStateUsing(function ($record) {

                        if (!$record->order || !$record->order->items) {
                            return ' - ';
                        }

                        return new HtmlString(
                            $record->order->items->map(function ($item) {

                                $notes = $item->notes
                                    ? "<div class='text-xs text-red-600 font-bold italic ml-6 mt-1'>
                                            ⚠️ {$item->notes}
                                       </div>"
                                    : "";

                                return "
                                    <div class='mb-3 border-b border-gray-200 pb-2'>
                                        <div class='flex justify-between'>
                                            <span class='font-black text-lg mr-2'>
                                                {$item->quantity} x 
                                            </span>
                                            <span class='flex-1 font-medium text-gray-800'>
                                                {$item->item_name}
                                            </span>
                                        </div>
                                        {$notes}
                                    </div>
                                ";
                            })->implode('')
                        );
                    })
                    ->html(),

                /*
                |--------------------------------------------------------------------------
                | STATUS (BIGGER + UPPERCASE)
                |--------------------------------------------------------------------------
                */
                Tables\Columns\TextColumn::make('current_status')
                    ->badge()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                    ->color(fn (string $state): string => match ($state) {
                        'placed' => 'danger',
                        'preparing' => 'warning',
                        'ready' => 'success',
                        default => 'gray',
                    })
                    ->extraAttributes(['class' => 'text-lg font-bold mt-4'])
                    ->alignCenter(),

            ])->space(3),
        ])
        
/*
|--------------------------------------------------------------------------
| ACTION BUTTONS
|--------------------------------------------------------------------------
*/
->actions([

    // START COOKING
    Tables\Actions\Action::make('start_cooking')
        ->label('Start Cooking')
        ->icon('heroicon-m-play')
        ->button()
        ->color('warning')
        ->visible(fn ($record) =>
            $record->current_status === 'placed'
        )
        ->action(fn ($record) =>
            static::updateStatus($record, 'preparing')
        ),

    // MARK READY
    Tables\Actions\Action::make('mark_ready')
        ->label('Mark Ready')
        ->icon('heroicon-m-check-badge')
        ->button()
        ->color('success')
        ->visible(fn ($record) =>
            $record->current_status === 'preparing'
        )
        ->action(fn ($record) =>
            static::updateStatus($record, 'ready')
        ),

])

->bulkActions([]);
}

    /*
    |--------------------------------------------------------------------------
    | STATUS UPDATE LOGIC
    |--------------------------------------------------------------------------
    */

    public static function updateStatus($record, $newStatus): void
    {
        $oldStatus = $record->current_status;

        // Update Kitchen Queue
        $record->update([
            'current_status' => $newStatus,
        ]);

        // Update Main Order
        $record->order->update([
            'status' => $newStatus,
        ]);

        // Create Status Log
        OrderStatusLog::create([
            'order_id' => $record->order_id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => auth()->id(),
        ]);
        OrderStatusUpdated::dispatch($record->order);
    }

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKitchenQueues::route('/'),
        ];
    }
}
