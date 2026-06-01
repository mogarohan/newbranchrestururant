<?php
// app/Events/PaymentStatusUpdated.php  ← NEW EVENT FILE

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $sessionId,
        public readonly int    $restaurantId,
        public readonly string $status,        // "paid"
    ) {}

    public function broadcastOn(): array
    {
        // Same private channel the frontend bills.tsx is already listening on:
        // echo.private(`session.${sessionId}`).listen('.PaymentStatusUpdated', ...)
        return [
            new PrivateChannel("session.{$this->sessionId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'status'        => $this->status,
            'restaurant_id' => $this->restaurantId,
            'session_id'    => $this->sessionId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'PaymentStatusUpdated';
    }
}