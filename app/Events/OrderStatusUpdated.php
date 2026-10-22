<?php

namespace App\Events;

use App\Models\Order;
use App\Models\QrSession;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Str;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable; 

    public array $orderPayload;
    protected int $restaurantId;
    protected ?int $qrSessionId;
    protected ?int $roomSessionId;
    protected ?int $parcelQrSessionId;
    protected ?int $branchId;

    public function __construct(Order $order)
    {
        $this->restaurantId = $order->restaurant_id;
        $this->qrSessionId = $order->qr_session_id;
        $this->roomSessionId = $order->room_session_id;
        $this->parcelQrSessionId = $order->parcel_qr_session_id;
        $this->branchId = $order->branch_id;

        $order->loadMissing(['items.menuItem']);

        $this->orderPayload = [
            'id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'branch_id' => $order->branch_id,
            'restaurant_table_id' => $order->restaurant_table_id,
            'qr_session_id' => $order->qr_session_id,
            'room_session_id' => $order->room_session_id,
            'parcel_qr_session_id' => $order->parcel_qr_session_id,
            'customer_name' => $order->customer_name,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'notes' => $order->notes,
            'total_amount' => (float) ($order->confirmed_total ?? $order->total_amount),
            'items' => $order->items ? $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'menu_item_id' => $item->menu_item_id,
                    'item_name' => $item->item_name,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => (int) ($item->confirmed_qty ?? $item->quantity),
                    'total_price' => (float) $item->total_price,
                    'notes' => $item->notes,
                    'menu_item' => ['name' => $item->item_name],
                ];
            })->values()->toArray() : [],
            'created_at' => $order->created_at ? $order->created_at->toIso8601String() : null,
            'updated_at' => $order->updated_at ? $order->updated_at->toIso8601String() : null,
        ];
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('restaurant.' . $this->restaurantId)
        ];

        if ($this->roomSessionId) {
            $channels[] = new PrivateChannel('session.' . $this->roomSessionId);
        } elseif ($this->parcelQrSessionId) {
            $channels[] = new PrivateChannel('session.' . $this->parcelQrSessionId);
        } elseif ($this->qrSessionId) {
            $channels[] = new PrivateChannel('session.' . $this->qrSessionId);

            $session = QrSession::find($this->qrSessionId);

            if ($session) {
                if ($session->is_primary) {
                    $guestIds = QrSession::where('host_session_id', $session->id)->pluck('id');
                    foreach ($guestIds as $guestId) {
                        $channels[] = new PrivateChannel('session.' . $guestId);
                    }
                } else if ($session->host_session_id) {
                    $channels[] = new PrivateChannel('session.' . $session->host_session_id);

                    $otherGuestIds = QrSession::where('host_session_id', $session->host_session_id)
                        ->where('id', '!=', $session->id)
                        ->pluck('id');

                    foreach ($otherGuestIds as $guestId) {
                        $channels[] = new PrivateChannel('session.' . $guestId);
                    }
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
            'branch_id' => $this->branchId,
            'order' => $this->orderPayload
        ];
    }
}