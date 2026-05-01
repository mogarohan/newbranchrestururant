<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('session.' . $this->order->qr_session_id),
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.kitchen')
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => 'cancelled',
            'message' => 'Order was cancelled.'
        ];
    }
}