<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillGenerated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $sessionId;
    public $paymentData;

    public function __construct($sessionId, $paymentData)
    {
        $this->sessionId = $sessionId;
        $this->paymentData = $paymentData;
    }

    public function broadcastOn(): array
    {
        // Pushes directly to the customer's phone
        return [new PrivateChannel('session.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'BillGenerated';
    }
}