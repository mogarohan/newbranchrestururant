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
        $channels = [
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.kitchen')
        ];

        // 👇 FIX: Route to correct channel based on order type
        if ($this->order->room_session_id) {
            $channels[] = new PrivateChannel('session.' . $this->order->room_session_id);
        } elseif ($this->order->qr_session_id) {
            $channels[] = new PrivateChannel('session.' . $this->order->qr_session_id);
        }

        return $channels;
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