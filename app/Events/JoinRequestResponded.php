<?php

namespace App\Events;

use App\Models\QrSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JoinRequestResponded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $guestSession;
    public $status;

    public function __construct(QrSession $guestSession, $status)
    {
        $this->guestSession = $guestSession;
        $this->status = $status; // 'approved' or 'rejected'
    }

    public function broadcastOn(): array
    {
        // 🔥 CRITICAL: This broadcasts back to the GUEST's session ID!
        return [
            new PrivateChannel('session.' . $this->guestSession->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'JoinRequestResponded';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => $this->status, // Maps directly to event.status in React Native
        ];
    }
}