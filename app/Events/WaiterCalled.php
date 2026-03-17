<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use App\Models\RestaurantTable; // 🔥 Import Model

class WaiterCalled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $restaurantId;
    public $tableId;
    public $tableNumber;
    public $customerName;
    public $eventId;
    public $branchId; // 🔥 NEW

    public function __construct($restaurantId, $tableId, $tableNumber, $customerName = 'Guest')
    {
        $this->restaurantId = $restaurantId;
        $this->tableId = $tableId;
        $this->tableNumber = $tableNumber;
        $this->customerName = $customerName;
        $this->eventId = Str::uuid()->toString();

        // 🔥 Auto-fetch branch_id
        $table = RestaurantTable::find($tableId);
        $this->branchId = $table ? $table->branch_id : null;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.' . $this->restaurantId . '.alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'WaiterCalled';
    }

    public function broadcastWith(): array
    {
        return [
            'restaurant_id' => $this->restaurantId,
            'table_id' => $this->tableId,
            'table_number' => $this->tableNumber,
            'customer_name' => $this->customerName,
            'branch_id' => $this->branchId, // 🔥 Send branch_id to frontend
            'event_id' => $this->eventId,
            'timestamp' => now()->timestamp,
        ];
    }
}