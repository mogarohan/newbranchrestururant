<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $tableId;

    public function __construct($sessionId, $tableId)
    {
        $this->sessionId = $sessionId;
        $this->tableId = $tableId;
    }

    public function broadcastOn(): array
    {
        // 👇 FIX: Broadcast to BOTH the session ID and the Table ID to guarantee delivery
        return [
            new PrivateChannel('session.' . $this->sessionId),
            new PrivateChannel('session.' . $this->tableId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SessionEnded';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'Table cleaned by manager',
        ];
    }
}