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
        $key = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        // 🔥 DEBUG: Check if these actually contain values
        if (empty($key) || empty($secret)) {
            \Log::error("RAZORPAY_KEY_ID or RAZORPAY_KEY_SECRET is NULL!");
        }

        $this->api = new Api($key, $secret);
    }

    /**
     * Helper to safely extract the restaurant_id from the customer's session
     */
    private function getRestaurantIdFromSession(Request $request)
    {
        // The React Native app sends the session_token in the Authorization header
        $token = $request->bearerToken() ?: $request->input('session_token');
        if (!$token)
            throw new \Exception('No session token provided.');

        // Check Table Session
        $qrSession = QrSession::where('session_token', $token)->first();
        if ($qrSession)
            return $qrSession->restaurant_id;

        // Check Room Session
        $roomSession = RoomSession::where('session_token', $token)->first();
        if ($roomSession)
            return $roomSession->restaurant_id;

        throw new \Exception('Invalid session.');
    }

    public function createOrder(Request $request)
    {
        $request->validate(['payment_id' => 'required|exists:payments,id']);

        try {
            // Get the restaurant ID belonging to this specific customer's session
            $restaurantId = $this->getRestaurantIdFromSession($request);

            $response = DB::transaction(function () use ($request, $restaurantId) {
                // Ensure the payment belongs to the restaurant of the current session
                $payment = Payment::lockForUpdate()
                    ->where('id', $request->payment_id)
                    ->where('restaurant_id', $restaurantId) // ✅ SECURE & WORKS FOR CUSTOMERS
                    ->firstOrFail();

                if (in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_PROCESSING])) {
                    return ['status' => 'error', 'message' => 'Payment already processed.', 'code' => 409];
                }

                // Initialize Paise amount if not set
                if ($payment->amount_paise === 0) {
                    $payment->amount_paise = (int) round($payment->amount * 100);
                }

                $payment->increment('attempts');

                // Create Razorpay Order
                $razorpayOrder = $this->api->order->create([
                    'receipt' => (string) $payment->id,
                    'amount' => $payment->amount_paise,
                    'currency' => 'INR',
                    'payment_capture' => 1
                ]);

                $payment->update([
                    'gateway_order_id' => $razorpayOrder['id'],
                    'gateway_status' => Payment::STATUS_INITIATED,
                    'expires_at' => now()->addMinutes(15)
                ]);

                return [
                    'status' => 'success',
                    'data' => [
                        'razorpay_order_id' => $razorpayOrder['id'],
                        'amount' => $payment->amount_paise,
                        'currency' => 'INR',
                        'key' => config('services.razorpay.key')
                    ]
                ];
            });

            if ($response['status'] === 'error')
                return response()->json(['message' => $response['message']], $response['code']);
            return response()->json($response['data']);

        } catch (\Exception $e) {
            Log::channel('single')->error('Create Razorpay Order Failed: ' . $e->getMessage()); // Logs to storage/logs/laravel.log
            //return response()->json(['message' => 'Failed to initiate payment.'], 500);
            return response()->json([
                'message' => 'Debug Error: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $restaurantId = $this->getRestaurantIdFromSession($request);

            DB::transaction(function () use ($request, $restaurantId) {
                $payment = Payment::lockForUpdate()
                    ->where('id', $request->payment_id)
                    ->where('restaurant_id', $restaurantId) // ✅ SECURE
                    ->firstOrFail();

                if ($payment->expires_at && $payment->expires_at->isPast()) {
                    throw new \Exception('Payment window expired.');
                }

                if (Payment::where('gateway_payment_id', $request->razorpay_payment_id)->where('id', '!=', $payment->id)->exists()) {
                    throw new \Exception('Replay attack detected.');
                }

                // Verify Signature
                $this->api->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature
                ]);

                // Verify with Gateway
                $razorpayPayment = $this->api->payment->fetch($request->razorpay_payment_id);

                if ($razorpayPayment->status !== 'captured')
                    throw new \Exception('Payment not captured.');
                if ($razorpayPayment->amount != $payment->amount_paise)
                    throw new \Exception('Amount mismatch.');
                if ($razorpayPayment->order_id !== $payment->gateway_order_id)
                    throw new \Exception('Order ID mismatch.');

                // Mark Processing
                $payment->update([
                    'gateway_payment_id' => $request->razorpay_payment_id,
                    'gateway_signature' => $request->razorpay_signature,
                    'gateway_status' => Payment::STATUS_PROCESSING,
                    'verified_at' => now(),
                    'payment_method' => 'online',
                ]);
            });

            return response()->json(['message' => 'Payment verified. Awaiting final confirmation.']);

        } catch (\Exception $e) {
            Log::channel('single')->error('Razorpay Frontend Verify Failed', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Payment verification failed.'], 400);
        }
    }
    // Called silently by Razorpay Servers
    public function webhook(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature');

        try {
            $this->api->utility->verifyWebhookSignature($request->getContent(), $signature, config('services.razorpay.webhook_secret'));
            $payload = $request->all();

            if ($payload['event'] === 'payment.captured') {
                $paymentEntity = $payload['payload']['payment']['entity'];
                $eventId = $paymentEntity['id'] . '_' . $payload['event'];

                // 1. Log the Webhook to prevent duplicate processing
                try {
                    RazorpayWebhookLog::create([
                        'event_id' => $eventId,
                        'event_type' => $payload['event'],
                        'payload' => $payload,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062)
                        return response()->json(['status' => 'duplicate_ignored']);
                }

                $razorpayOrderId = $paymentEntity['order_id'];

                $paymentId = DB::transaction(function () use ($razorpayOrderId) {
                    $payment = Payment::lockForUpdate()->where('gateway_order_id', $razorpayOrderId)->first();

                    if ($payment && $payment->status !== Payment::STATUS_PAID) {
                        return $payment->id;
                    }
                    return null;
                });

                // 2. Safely process the payment in the background
                if ($paymentId) {
                    ProcessSuccessfulPayment::dispatch($paymentId);
                }
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Webhook Signature Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }
}