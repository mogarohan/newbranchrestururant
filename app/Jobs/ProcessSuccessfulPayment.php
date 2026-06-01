<?php
// app/Jobs/ProcessSuccessfulPayment.php
// Added: broadcasts PaymentStatusUpdated event so the frontend overlay auto-dismisses

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Order;
use App\Models\QrSession;
use App\Models\RoomSession;
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
        return [new WithoutOverlapping($this->paymentId)];
    }

    public function handle()
    {
        DB::transaction(function () {
            $payment = Payment::lockForUpdate()->find($this->paymentId);

            if (!$payment || $payment->status === Payment::STATUS_PAID) {
                return;
            }

            $payment->update([
                'status'         => Payment::STATUS_PAID,
                'gateway_status' => Payment::STATUS_PAID,
                'paid_at'        => now(),
            ]);

            $order = Order::find($payment->order_id);
            if ($order) {
                $order->update(['payment_status' => 'paid']);
                event(new \App\Events\OrderStatusUpdated($order));
            }

            // ─────────────────────────────────────────────────────────────────
            // NEW: Find the session that owns this payment and broadcast
            //      PaymentStatusUpdated so the React Native overlay dismisses
            //      automatically without the customer having to tap anything.
            //
            // This works for BOTH the Razorpay checkout flow AND the new
            // UPI Payment Link flow — one job handles both.
            // ─────────────────────────────────────────────────────────────────
            $sessionId = null;

            // Try QrSession first
            if ($order) {
                $qrSession = QrSession::where('id', $order->qr_session_id)->first();
                if ($qrSession) $sessionId = $qrSession->id;
            }

            // Fallback: look up by restaurant + payment
            if (!$sessionId) {
                $qrSession = QrSession::where('restaurant_id', $payment->restaurant_id)
                    ->whereHas('orders', fn($q) => $q->where('id', $payment->order_id))
                    ->latest()
                    ->first();
                if ($qrSession) $sessionId = $qrSession->id;
            }

            if ($sessionId) {
                // Broadcast to the session channel the frontend is listening on
                // Event name: .PaymentStatusUpdated (matches bills.tsx Pusher listener)
                event(new \App\Events\PaymentStatusUpdated($sessionId, $payment->restaurant_id, 'paid'));
            }

            // Notify manager dashboard
            event(new \App\Events\PaymentMethodSelected(
                $payment->restaurant_id,
                '?',
                'PAID — ' . strtoupper($payment->payment_method ?? 'ONLINE')
            ));
        });
    }
}