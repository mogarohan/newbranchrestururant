<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use App\Events\OrderStatusUpdated;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class KitchenDisplayBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static string $view = 'filament.pages.kitchen-display-board';
    protected static ?string $navigationLabel = 'Kitchen Command';
    protected static ?string $title = 'Kitchen Command';
    protected static ?string $navigationGroup = 'Kitchen';
    
    // 🔥 Make the page take up the full width of the monitor
    protected ?string $maxWidth = 'full';

    public static function canAccess(): bool
    {
        return auth()->user()?->role?->name === 'chef';
    }

    // 🔥 Listen for WebSockets to instantly refresh the board
    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;
        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
        ];
    }

    // --- COLUMN DATA FETCHERS ---

    public function getPlacedOrdersProperty()
    {
        return KitchenQueue::with(['order.items', 'order.table'])
            ->whereHas('order', fn($q) => $q->where('restaurant_id', auth()->user()->restaurant_id))
            ->where('current_status', 'placed')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getPreparingOrdersProperty()
    {
        return KitchenQueue::with(['order.items', 'order.table'])
            ->whereHas('order', fn($q) => $q->where('restaurant_id', auth()->user()->restaurant_id))
            ->where('current_status', 'preparing')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getReadyOrdersProperty()
    {
        return KitchenQueue::with(['order.items', 'order.table'])
            ->whereHas('order', fn($q) => $q->where('restaurant_id', auth()->user()->restaurant_id))
            ->where('current_status', 'ready')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // --- STATUS ACTION ---

    public function updateStatus($queueId, $newStatus)
    {
        $record = KitchenQueue::findOrFail($queueId);
        $oldStatus = $record->current_status;

        DB::transaction(function () use ($record, $newStatus, $oldStatus) {
            $record->update(['current_status' => $newStatus]);
            $record->order->update(['status' => $newStatus]);

            OrderStatusLog::create([
                'order_id' => $record->order_id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => auth()->id(),
            ]);

            OrderStatusUpdated::dispatch($record->order);
        });

        Notification::make()
            ->title("Order moved to " . strtoupper($newStatus))
            ->success()
            ->send();
    }
}