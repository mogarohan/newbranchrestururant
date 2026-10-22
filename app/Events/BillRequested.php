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
    public $branchId;
    public $serviceType;

    public function __construct($restaurantId, $tableId, $tableNumber, $customerName, $branchId = null, $serviceType = 'dine_in')
    {
        $this->restaurantId = $restaurantId;
        $this->tableId = $tableId;
        $this->tableNumber = $tableNumber;
        $this->customerName = $customerName;
        $this->serviceType = $serviceType;
        $this->eventId = (string) Str::uuid();

        if ($branchId !== null) {
            $this->branchId = $branchId;
        } else {
            $table = \App\Models\RestaurantTable::find($tableId);
            $this->branchId = $table ? $table->branch_id : null;
        }
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
            'branch_id' => $this->branchId,
            'service_type' => $this->serviceType,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}