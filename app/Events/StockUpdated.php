<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $restaurantId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $restaurantId)
    {
        $this->restaurantId = $restaurantId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.' . $this->restaurantId),
        ];
    }

    /**
     * Broadcast event name alias matching Echo listeners.
     */
    public function broadcastAs(): string
    {
        return 'StockUpdated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'version' => 1,
            'restaurant_id' => $this->restaurantId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}