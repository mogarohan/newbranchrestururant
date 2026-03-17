<?php

namespace App\Events;

use App\Models\Order;
use App\Models\QrSession;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order->fresh(['items.menuItem.category', 'restaurantTable']);
    }

    public function broadcastOn(): array
    {
        // 1. Always notify the Restaurant/Waiter
        $channels = [
            new PrivateChannel('restaurant.' . $this->order->restaurant_id)
        ];

        // 2. Notify the exact person who placed the order
        $channels[] = new PrivateChannel('session.' . $this->order->qr_session_id);

        // 3. GROUP BILLING NOTIFICATIONS: Ping the rest of the table!
        $session = QrSession::find($this->order->qr_session_id);

        if ($session) {
            if ($session->is_primary) {
                // If Host ordered, notify all their guests
                $guestIds = QrSession::where('host_session_id', $session->id)->pluck('id');
                foreach ($guestIds as $guestId) {
                    $channels[] = new PrivateChannel('session.' . $guestId);
                }
            } else if ($session->host_session_id) {
                // If a Guest ordered, notify the Host
                $channels[] = new PrivateChannel('session.' . $session->host_session_id);

                // And notify any other guests at the same table
                $otherGuestIds = QrSession::where('host_session_id', $session->host_session_id)
                    ->where('id', '!=', $session->id)
                    ->pluck('id');

                foreach ($otherGuestIds as $guestId) {
                    $channels[] = new PrivateChannel('session.' . $guestId);
                }
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'version' => 1,
            'event_id' => Str::uuid()->toString(),
            'branch_id' => $this->order->branch_id, // 🔥 FE can easily check this now
            'order' => $this->order
        ];
    }
}