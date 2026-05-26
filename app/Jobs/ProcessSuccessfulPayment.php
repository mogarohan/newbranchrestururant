<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessSuccessfulPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $paymentId;
    public $tries = 3;

    public function __construct($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    public function middleware()
    {
        // Prevents two workers from processing the exact same payment simultaneously
        return [new WithoutOverlapping($this->paymentId)];
    }

    public function handle()
    {
        DB::transaction(function () {
            $payment = Payment::lockForUpdate()->find($this->paymentId);

            if (!$payment || $payment->status === Payment::STATUS_PAID) {
                return;
            }

            // Mark Payment as Paid
            $payment->update([
                'status' => Payment::STATUS_PAID,
                'gateway_status' => Payment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            // Mark linked Order as Paid
            $order = Order::find($payment->order_id);
            if ($order) {
                $order->update(['payment_status' => 'paid']);

                // Trigger WebSockets to update Manager/Waiter screens instantly!
                event(new \App\Events\PaymentMethodSelected($payment->restaurant_id, '?', 'PAID ONLINE'));
                event(new \App\Events\OrderStatusUpdated($order));
            }
        });
    }
}