<?php

namespace App\Events;

use App\Models\QrSession;
use Illuminate\Support\Str;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestJoinRequested implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $guestSession;

    public function __construct(QrSession $guestSession)
    {
        $this->guestSession = $guestSession;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('session.' . $this->guestSession->host_session_id)
        ];
    }

    public function broadcastAs(): string
    {
        return 'GuestJoinRequested';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => Str::uuid()->toString(),
            'guest' => [
                'id' => $this->guestSession->id,
                'customer_name' => $this->guestSession->customer_name
            ]
        ];
    }
}