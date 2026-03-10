<?php

namespace App\Events;

use App\Models\QrSession;
use Illuminate\Support\Str;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JoinRequestResponded implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $guestSession;
    public $status;

    public function __construct(QrSession $guestSession, $status)
    {
        $this->guestSession = $guestSession;
        $this->status = $status;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('session.' . $this->guestSession->id)
        ];
    }

    public function broadcastAs(): string
    {
        return 'JoinRequestResponded';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => Str::uuid()->toString(),
            'status' => $this->status
        ];
    }
}