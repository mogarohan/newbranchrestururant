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

    protected ?string $maxWidth = 'full';

    public static function canAccess(): bool
    {
        // 🔥 Updated: Chef ke saath Manager aur Admin bhi dekh sakte hain
        return auth()->check() && in_array(auth()->user()->role->name ?? null, ['chef']);
    }

    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;
        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
        ];
    }

    // --- HELPER FOR ISOLATION ---

    protected function getBaseQueueQuery()
    {
        $user = auth()->user();

        $query = KitchenQueue::with(['order.items', 'order.table'])
            ->whereHas('order', function ($q) use ($user) {
                $q->where('restaurant_id', $user->restaurant_id);

                // 👇 FIX: Branch Isolation Logic
                if ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id);
                } else {
                    $q->whereNull('branch_id'); // Main Restaurant area
                }
            });

        return $query;
    }

    // --- COLUMN DATA FETCHERS ---

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