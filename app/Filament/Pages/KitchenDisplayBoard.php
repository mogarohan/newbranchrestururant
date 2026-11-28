<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\KitchenQueue;
use App\Models\OrderItem;
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

    protected ?string $maxWidth = 'full';

    public static function canAccess(): bool
    {
        return auth()->check() && in_array(auth()->user()->role->name ?? null, ['chef', 'restaurant_admin', 'branch_admin', 'manager']);
    }

    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;
        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
        ];
    }

    protected function getBaseQueueQuery()
    {
        $user = auth()->user();

        $query = KitchenQueue::with([
            'order.items', 
            'order.table', 
            'order.roomSession.room', 
            'order.parcelQrSession.parcelQrCode'
        ])
            ->whereHas('order', function ($q) use ($user) {
                $q->where('restaurant_id', $user->restaurant_id);

                if ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id);
                } else {
                    $q->whereNull('branch_id'); 
                }
            });

        return $query;
    }

    public function getPlacedOrdersProperty()
    {
        return $this->getBaseQueueQuery()
            ->where('current_status', 'placed')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getPreparingOrdersProperty()
    {
        return $this->getBaseQueueQuery()
            ->where('current_status', 'preparing')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getReadyOrdersProperty()
    {
        return $this->getBaseQueueQuery()
            ->where('current_status', 'ready')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function updateItemStatus($itemId, $newStatus)
    {
        $user = auth()->user();
        $item = OrderItem::whereHas('order', function ($q) use ($user) {
            $q->where('restaurant_id', $user->restaurant_id);
            if ($user->branch_id) {
                $q->where('branch_id', $user->branch_id);
            }
        })->findOrFail($itemId);

        if ($item->status === $newStatus) {
            return;
        }

        $order = $item->order;
        $oldOrderStatus = $order->status;

        DB::transaction(function () use ($item, $newStatus, $order) {
            $item->update(['status' => $newStatus]);
            $order->syncStatusFromItems(true);
        });

        // If syncStatusFromItems did not change overall order status (and therefore did not broadcast),
        // we must broadcast OrderStatusUpdated now so Waiters and Managers immediately get the item ready alert!
        if ($oldOrderStatus === $order->fresh()->status) {
            OrderStatusUpdated::dispatch($order->fresh(['items.menuItem']));
        }

        Notification::make()
            ->title("{$item->item_name} marked as " . strtoupper(str_replace('_', ' ', $newStatus)))
            ->success()
            ->send();
    }

    public function updateStatus($queueId, $newStatus)
    {
        $record = KitchenQueue::with('order.items')->findOrFail($queueId);
        $oldStatus = $record->current_status;

        DB::transaction(function () use ($record, $newStatus, $oldStatus) {
            $record->update(['current_status' => $newStatus]);
            $record->order->update(['status' => $newStatus]);

            // Synchronize items
            if ($newStatus === 'preparing') {
                $record->order->items()
                    ->where('quantity', '>', 0)
                    ->where('status', 'placed')
                    ->update(['status' => 'preparing']);
            } elseif ($newStatus === 'ready') {
                $record->order->items()
                    ->where('quantity', '>', 0)
                    ->whereNotIn('status', ['cancelled', 'served'])
                    ->update(['status' => 'ready']);
            }

            OrderStatusLog::create([
                'order_id' => $record->order_id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => auth()->id(),
            ]);

            OrderStatusUpdated::dispatch($record->order->fresh(['items.menuItem']));
        });

        Notification::make()
            ->title("Order moved to " . strtoupper($newStatus))
            ->success()
            ->send();
    }
}
