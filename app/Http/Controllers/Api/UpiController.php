<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\QrSession;
use App\Models\Restaurant;
use App\Models\RoomSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpiController extends Controller
{
    /**
     * Get Session & Restaurant
     */
    private function getSessionAndRestaurant(Request $request): array
    {
        $token = $request->bearerToken()
            ?: $request->input('session_token');

        if (!$token) {
            throw new \Exception('No session token provided.');
        }

        // Table Session
        $qrSession = QrSession::where('session_token', $token)->first();

        if ($qrSession) {
            $restaurant = Restaurant::find($qrSession->restaurant_id);

            if (!$restaurant) {
                throw new \Exception('Restaurant not found.');
            }

            return [
                'session' => $qrSession,
                'restaurant' => $restaurant,
                'restaurant_id' => $restaurant->id,
                'is_room' => false,
            ];
        }

        // Room Session
        $roomSession = RoomSession::where('session_token', $token)->first();

        if ($roomSession) {
            $restaurant = Restaurant::find($roomSession->restaurant_id);

            if (!$restaurant) {
                throw new \Exception('Restaurant not found.');
            }

            return [
                'session' => $roomSession,
                'restaurant' => $restaurant,
                'restaurant_id' => $restaurant->id,
                'is_room' => true,
            ];
        }

        throw new \Exception('Invalid or expired session.');
    }

    /**
     * Initiate UPI Payment
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
        ]);

        try {

            $ctx = $this->getSessionAndRestaurant($request);

            $restaurant = $ctx['restaurant'];

            $payment = Payment::where('id', $request->payment_id)
                ->where('restaurant_id', $ctx['restaurant_id'])
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.',
                ], 404);
            }

            if ($payment->status === Payment::STATUS_PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already completed.',
                ], 400);
            }

            $upiId = trim($restaurant->upi_id ?? '');

            if (empty($upiId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Restaurant UPI ID not configured.',
                ], 400);
            }

            $amount = number_format((float) $payment->amount, 2, '.', '');

            /*
             |--------------------------------------------------------------------------
             | Standard UPI Deep Link
             |--------------------------------------------------------------------------
             */
            $params = [
                'pa' => $upiId,
                'pn' => $restaurant->name,
                'am' => $amount,
                'cu' => 'INR',
                'tr' => 'PAY-' . $payment->id,
                'tn' => 'Order Payment #' . $payment->order_id,
            ];

            $upiDeepLink =
                'upi://pay?' .
                http_build_query($params);

            $payment->update([
                'payment_method' => 'upi',
                'gateway' => 'upi_direct',
                'gateway_status' => 'initiated',
                'status' => Payment::STATUS_INITIATED,
                'attempts' => ($payment->attempts ?? 0) + 1,
            ]);

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'upi_id' => $upiId,
                'restaurant' => $restaurant->name,
                'upi_deep_link' => $upiDeepLink,
            ]);
        } catch (\Exception $e) {

            Log::error('UPI Initiate Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm Payment
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'status' => 'required|in:SUCCESS,FAILED',
            'upi_txn_id' => 'nullable|string|max:255',
        ]);

        try {

            $ctx = $this->getSessionAndRestaurant($request);

            $payment = Payment::where('id', $request->payment_id)
                ->where('restaurant_id', $ctx['restaurant_id'])
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.',
                ], 404);
            }

            if ($request->status === 'SUCCESS') {

                $payment->update([
                    'status' => Payment::STATUS_PROCESSING,
                    'gateway' => 'upi_direct',
                    'gateway_status' => 'verification_pending',
                    'transaction_reference' => $request->upi_txn_id,
                    'gateway_payment_id' => $request->upi_txn_id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment submitted for verification.',
                ]);
            }

            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'gateway' => 'upi_direct',
                'gateway_status' => 'failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment failed.',
            ]);
        } catch (\Exception $e) {

            Log::error('UPI Confirm Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check Payment Status
     */
    public function status($paymentId)
    {
        $payment = Payment::find($paymentId);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'gateway_status' => $payment->gateway_status,
            'amount' => $payment->amount,
        ]);
    }
}