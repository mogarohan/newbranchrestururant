<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        // Ensure fresh DB state & load relations
        $this->order = $order->fresh(['items.menuItem.category', 'restaurantTable']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('session.' . $this->order->qr_session_id),
            new PrivateChannel('restaurant.' . $this->order->restaurant_id) 
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    // 🔥 UPGRADE: Versioning & Idempotency
    public function broadcastWith(): array
    {
        return [
            'version' => 1,
            'event_id' => Str::uuid()->toString(),
            'order' => $this->order
        ];
    }
}