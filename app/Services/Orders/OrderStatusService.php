<?php
namespace App\Services\Restaurant;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\KitchenQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class OrderStatusService
{
    public const FLOW = [
        'placed' => ['preparing', 'cancelled'],
        'preparing' => ['ready'],
        'ready' => ['served'],
        'served' => ['completed'],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::FLOW[$from] ?? []);
    }

    public static function transition(Order $order, string $to, string $actor): void
    {
        if (!self::canTransition($order->status, $to)) {
            throw new \RuntimeException('Invalid order status transition');
        }

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $order->status,
            'to_status' => $to,
            'changed_by' => $actor,
        ]);

        $order->update(['status' => $to]);

        KitchenQueue::updateOrCreate(
            ['order_id' => $order->id],
            ['current_status' => $to]
        );
    }
}
