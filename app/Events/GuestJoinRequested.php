<?php

namespace App\Events;

use App\Models\QrSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GuestJoinRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $guest;
    public $eventId;

    public function __construct(QrSession $guestSession)
    {
        $this->guest = $guestSession;
        $this->eventId = Str::uuid()->toString();
    }

    public function broadcastOn(): array
    {
        // 🔥 CRITICAL: This MUST broadcast to the HOST's session ID, not the guest's!
        return [
            new PrivateChannel('session.' . $this->guest->host_session_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'GuestJoinRequested';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'guest' => $this->guest // Maps directly to event.guest in React Native
        ];
    }
}