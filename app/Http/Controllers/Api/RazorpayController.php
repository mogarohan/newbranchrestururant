<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Payment;
use App\Models\QrSession;
use App\Models\RoomSession;
use App\Models\RazorpayWebhookLog;
use App\Jobs\ProcessSuccessfulPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayController extends Controller
{
    private $api;

    public function __construct()
    {
        $key    = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        if (empty($key) || empty($secret)) {
            Log::error('RAZORPAY_KEY_ID or RAZORPAY_KEY_SECRET is NULL!');
        }

        $this->api = new Api($key, $secret);
    }

    // ─── Shared Auth Helper ───────────────────────────────────────────────────
    private function getRestaurantIdFromSession(Request $request): int
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        if (!$token) throw new \Exception('No session token provided.');

        $qrSession = QrSession::where('session_token', $token)->first();
        if ($qrSession) return $qrSession->restaurant_id;

        $roomSession = RoomSession::where('session_token', $token)->first();
        if ($roomSession) return $roomSession->restaurant_id;

        throw new \Exception('Invalid session.');
    }

    // ─── Existing: Razorpay Checkout (Card / Netbanking / Wallet) ────────────
    public function createOrder(Request $request)
    {
        $request->validate(['payment_id' => 'required|exists:payments,id']);

        try {
            $restaurantId = $this->getRestaurantIdFromSession($request);

            $response = DB::transaction(function () use ($request, $restaurantId) {
                $payment = Payment::lockForUpdate()
                    ->where('id', $request->payment_id)
                    ->where('restaurant_id', $restaurantId)
                    ->firstOrFail();

                if (in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_PROCESSING])) {
                    return ['status' => 'error', 'message' => 'Payment already processed.', 'code' => 409];
                }

                if ($payment->amount_paise === 0) {
                    $payment->amount_paise = (int) round($payment->amount * 100);
                }

                $payment->increment('attempts');

                $razorpayOrder = $this->api->order->create([
                    'receipt'         => (string) $payment->id,
                    'amount'          => $payment->amount_paise,
                    'currency'        => 'INR',
                    'payment_capture' => 1,
                ]);

                $payment->update([
                    'gateway_order_id' => $razorpayOrder['id'],
                    'gateway_status'   => Payment::STATUS_INITIATED,
                    'expires_at'       => now()->addMinutes(15),
                ]);

                return [
                    'status' => 'success',
                    'data'   => [
                        'razorpay_order_id' => $razorpayOrder['id'],
                        'amount'            => $payment->amount_paise,
                        'currency'          => 'INR',
                        'key'               => config('services.razorpay.key'),
                    ],
                ];
            });

            if ($response['status'] === 'error') {
                return response()->json(['message' => $response['message']], $response['code']);
            }

            return response()->json($response['data']);

        } catch (\Exception $e) {
            Log::channel('single')->error('Create Razorpay Order Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to initiate payment.'], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'payment_id'          => 'required|exists:payments,id',
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        try {
            $restaurantId = $this->getRestaurantIdFromSession($request);

            DB::transaction(function () use ($request, $restaurantId) {
                $payment = Payment::lockForUpdate()
                    ->where('id', $request->payment_id)
                    ->where('restaurant_id', $restaurantId)
                    ->firstOrFail();

                if ($payment->expires_at && $payment->expires_at->isPast()) {
                    throw new \Exception('Payment window expired.');
                }

                if (Payment::where('gateway_payment_id', $request->razorpay_payment_id)
                    ->where('id', '!=', $payment->id)->exists()) {
                    throw new \Exception('Replay attack detected.');
                }

                $this->api->utility->verifyPaymentSignature([
                    'razorpay_order_id'   => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature'  => $request->razorpay_signature,
                ]);

                $razorpayPayment = $this->api->payment->fetch($request->razorpay_payment_id);

                if ($razorpayPayment->status !== 'captured') throw new \Exception('Payment not captured.');
                if ($razorpayPayment->amount != $payment->amount_paise) throw new \Exception('Amount mismatch.');
                if ($razorpayPayment->order_id !== $payment->gateway_order_id) throw new \Exception('Order ID mismatch.');

                $payment->update([
                    'gateway_payment_id' => $request->razorpay_payment_id,
                    'gateway_signature'  => $request->razorpay_signature,
                    'gateway_status'     => Payment::STATUS_PROCESSING,
                    'verified_at'        => now(),
                    'payment_method'     => 'online',
                ]);
            });

            return response()->json(['message' => 'Payment verified. Awaiting final confirmation.']);

        } catch (\Exception $e) {
            Log::channel('single')->error('Razorpay Frontend Verify Failed', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Payment verification failed.'], 400);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NEW: Generate UPI Payment Link
    //
    // How this works:
    //   1. Customer's app calls this endpoint with their payment_id.
    //   2. We look up the restaurant's UPI ID from the database.
    //   3. We create a Razorpay Payment Link with upi_link: true so that
    //      Razorpay generates a short link that opens ANY UPI app directly.
    //   4. The link is returned to the frontend which opens it via Linking.openURL().
    //   5. The customer pays — money goes DIRECTLY to the restaurant's bank account
    //      (no Razorpay settlement delay, no Razorpay wallet involved).
    //   6. Razorpay fires a webhook (payment_link.paid) which we handle below.
    //      We verify it and dispatch ProcessSuccessfulPayment → auto-marks paid.
    //
    // Prerequisites in Razorpay Dashboard:
    //   - Enable "Payment Links" under Products.
    //   - Register the webhook event: payment_link.paid
    //   - The restaurant's UPI ID must be stored in restaurants.upi_id column.
    // ─────────────────────────────────────────────────────────────────────────
    public function createUpiLink(Request $request)
    {
        $request->validate(['payment_id' => 'required|exists:payments,id']);

        try {
            $restaurantId = $this->getRestaurantIdFromSession($request);

            $result = DB::transaction(function () use ($request, $restaurantId) {
                $payment = Payment::lockForUpdate()
                    ->where('id', $request->payment_id)
                    ->where('restaurant_id', $restaurantId)
                    ->firstOrFail();

                if (in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_PROCESSING])) {
                    return ['status' => 'error', 'message' => 'Payment already processed.', 'code' => 409];
                }

                // Load the restaurant's UPI ID from DB
                $restaurant = \App\Models\Restaurant::find($restaurantId);
                if (!$restaurant || empty($restaurant->upi_id)) {
                    return ['status' => 'error', 'message' => 'Restaurant UPI ID not configured.', 'code' => 422];
                }

                if ($payment->amount_paise === 0) {
                    $payment->amount_paise = (int) round($payment->amount * 100);
                }

                $payment->increment('attempts');

                // Build a unique, NPCI-compliant transaction reference
                // Format: RESTXXX-TABYYY-PZZZ  (alphanumeric + hyphens, max 35 chars)
                $txnRef = strtoupper(
                    'REST' . $restaurantId .
                    '-TAB' . ($payment->order?->qr_session?->table_id ?? '0') .
                    '-P'   . $payment->id
                );

                // If a link already exists for this payment (e.g. customer navigated away
                // and came back), reuse it so we don't create duplicate Razorpay links.
                if ($payment->gateway_payment_link_id) {
                    $existingLink = $this->api->paymentLink->fetch($payment->gateway_payment_link_id);

                    // Razorpay link statuses: created, partially_paid, expired, cancelled, paid
                    if (in_array($existingLink->status, ['created', 'partially_paid'])) {
                        return [
                            'status' => 'success',
                            'data'   => [
                                'upi_link'      => $existingLink->short_url,
                                'amount'        => $payment->amount_paise,
                                'currency'      => 'INR',
                                'txn_reference' => $txnRef,
                            ],
                        ];
                    }
                }

                // Create a new Razorpay UPI Payment Link
                // upi_link: true  →  Razorpay generates a UPI-specific short link.
                //                    When opened on mobile, it shows a UPI app chooser
                //                    (GPay, PhonePe, Paytm, BHIM, etc.).
                //                    Payment settles directly to the restaurant's UPI VPA.
                $paymentLink = $this->api->paymentLink->create([
                    'upi_link'    => true,
                    'amount'      => $payment->amount_paise,
                    'currency'    => 'INR',
                    'description' => 'Bill Payment — ' . ($restaurant->name ?? 'Restaurant'),
                    'reference_id'=> $txnRef,

                    // ✅ This is the restaurant's UPI ID from YOUR database.
                    //    Money goes directly to this VPA — no Razorpay wallet.
                    'customer'    => [
                        'name'  => 'Diner',
                        'email' => 'diner@annsathi.com',
                    ],

                    // Tell Razorpay which UPI ID to collect payment for.
                    // This uses Razorpay's "UPI collect" flow internally.
                    'options'     => [
                        'checkout' => [
                            'name'        => $restaurant->name ?? 'Restaurant',
                            'description' => 'Table/Room Bill',
                            'prefill'     => [
                                'vpa' => $restaurant->upi_id, // restaurant's UPI ID
                            ],
                        ],
                    ],

                    // Razorpay will call this webhook when paid
                    'notify'      => ['sms' => false, 'email' => false],
                    'reminder_enable' => false,
                    'expire_by'   => now()->addMinutes(30)->timestamp,
                ]);

                // Store the link ID so we can look it up in the webhook
                $payment->update([
                    'gateway_payment_link_id' => $paymentLink->id,
                    'gateway_status'          => Payment::STATUS_INITIATED,
                    'transaction_reference'   => $txnRef,
                    'expires_at'              => now()->addMinutes(30),
                    'payment_method'          => 'upi_direct',
                ]);

                return [
                    'status' => 'success',
                    'data'   => [
                        'upi_link'      => $paymentLink->short_url,  // e.g. https://rzp.io/i/abc123
                        'amount'        => $payment->amount_paise,
                        'currency'      => 'INR',
                        'txn_reference' => $txnRef,
                    ],
                ];
            });

            if ($result['status'] === 'error') {
                return response()->json(['message' => $result['message']], $result['code']);
            }

            return response()->json($result['data']);

        } catch (\Exception $e) {
            Log::channel('single')->error('Create UPI Link Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate UPI payment link.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unified Webhook Handler
    //
    // Handles both:
    //   - payment.captured  (standard Razorpay Checkout flow)
    //   - payment_link.paid (new UPI Payment Link flow)
    //
    // Register BOTH events in your Razorpay Dashboard → Webhooks.
    // ─────────────────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature');

        try {
            $this->api->utility->verifyWebhookSignature(
                $request->getContent(),
                $signature,
                config('services.razorpay.webhook_secret')
            );

            $payload = $request->all();
            $event   = $payload['event'] ?? '';

            // ── Standard checkout: payment.captured ──────────────────────────
            if ($event === 'payment.captured') {
                $paymentEntity = $payload['payload']['payment']['entity'];
                $this->processWebhookEvent($paymentEntity['id'] . '_captured', $event, $payload, function () use ($paymentEntity) {
                    $razorpayOrderId = $paymentEntity['order_id'];
                    return DB::transaction(function () use ($razorpayOrderId) {
                        $payment = Payment::lockForUpdate()
                            ->where('gateway_order_id', $razorpayOrderId)
                            ->first();
                        return ($payment && $payment->status !== Payment::STATUS_PAID) ? $payment->id : null;
                    });
                });
            }

            // ── UPI Payment Link: payment_link.paid ──────────────────────────
            if ($event === 'payment_link.paid') {
                $linkEntity    = $payload['payload']['payment_link']['entity'];
                $paymentEntity = $payload['payload']['payment']['entity'];

                $this->processWebhookEvent($paymentEntity['id'] . '_link_paid', $event, $payload, function () use ($linkEntity) {
                    $linkId = $linkEntity['id'];
                    return DB::transaction(function () use ($linkId) {
                        $payment = Payment::lockForUpdate()
                            ->where('gateway_payment_link_id', $linkId)
                            ->first();
                        if ($payment && $payment->status !== Payment::STATUS_PAID) {
                            // Store the actual UPI transaction ID for records
                            $payment->update([
                                'gateway_payment_id' => $linkId,
                                'gateway_status'     => Payment::STATUS_PROCESSING,
                                'verified_at'        => now(),
                            ]);
                            return $payment->id;
                        }
                        return null;
                    });
                });
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Webhook Signature Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }

    /**
     * Shared idempotent webhook processor.
     * Logs the event, prevents duplicates, then dispatches the background job.
     */
    private function processWebhookEvent(string $eventId, string $eventType, array $payload, callable $getPaymentId): void
    {
        try {
            RazorpayWebhookLog::create([
                'event_id'   => $eventId,
                'event_type' => $eventType,
                'payload'    => $payload,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) return; // Duplicate — already processed
        }

        $paymentId = $getPaymentId();
        if ($paymentId) {
            ProcessSuccessfulPayment::dispatch($paymentId);
        }
    }
}