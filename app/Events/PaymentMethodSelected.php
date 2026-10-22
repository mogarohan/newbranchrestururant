<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentMethodSelected implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $restaurantId;
    public $tableNumber;
    public $method;
    public $branchId;

    public function __construct($restaurantId, $tableNumber, $method, $branchId = null)
    {
        $this->restaurantId = $restaurantId;
        $this->tableNumber = $tableNumber;
        $this->method = $method;
        $this->branchId = $branchId;
    }

    public function broadcastOn(): array
    {
        // Pushes directly to the Manager's Dashboard
        return [new PrivateChannel('restaurant.' . $this->restaurantId . '.alerts')];
    }

    public function broadcastAs(): string
    {
        return 'PaymentMethodSelected';
    }

    public function broadcastWith(): array
    {
        return [
            'restaurant_id' => $this->restaurantId,
            'table_number' => $this->tableNumber,
            'method' => $this->method,
            'branch_id' => $this->branchId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}