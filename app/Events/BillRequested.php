<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class BillRequested implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $restaurantId;
    public $tableId;
    public $tableNumber;
    public $customerName;
    public $eventId;

    public function __construct($restaurantId, $tableId, $tableNumber, $customerName)
    {
        $this->restaurantId = $restaurantId;
        $this->tableId = $tableId;
        $this->tableNumber = $tableNumber;
        $this->customerName = $customerName;
        $this->eventId = (string) Str::uuid();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.' . $this->restaurantId . '.alerts')
        ];
    }

    public function broadcastAs(): string
    {
        return 'BillRequested';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'restaurant_id' => $this->restaurantId,
            'table_id' => $this->tableId,
            'table_number' => $this->tableNumber,
            'customer_name' => $this->customerName,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}