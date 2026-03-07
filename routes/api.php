<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicMenuController;
use App\Http\Controllers\Public\QrSessionController;
use App\Http\Controllers\Api\PlaceOrderController;
use App\Http\Controllers\Api\WaiterAppController;
use App\Models\QrSession;
use Pusher\Pusher; // 🔥 CRITICAL: Make sure to import the Pusher class!

Route::post('/pusher/auth', function (Request $request) {
    if (!$request->channel_name || !$request->socket_id) {
        return response()->json(['message' => 'Missing channel or socket ID'], 400);
    }

    $sessionToken = $request->header('Authorization');
    if (!$sessionToken) {
        return response()->json(['message' => 'No Authorization header found'], 403);
    }

    $session = QrSession::where('session_token', $sessionToken)->first();

    if (!$session) {
        return response()->json(['message' => 'Invalid session token'], 403);
    }

    try {
        // 🔥 BULLETPROOF FIX: Manually instantiate Pusher to bypass Laravel config crashes
        $pusher = new Pusher(
            env('PUSHER_APP_KEY', config('broadcasting.connections.pusher.key')),
            env('PUSHER_APP_SECRET', config('broadcasting.connections.pusher.secret')),
            env('PUSHER_APP_ID', config('broadcasting.connections.pusher.app_id')),
            [
                'cluster' => env('PUSHER_APP_CLUSTER', config('broadcasting.connections.pusher.options.cluster')),
                'useTLS' => true
            ]
        );
        
        // Generate the secure signature
        $authString = method_exists($pusher, 'authorizeChannel') 
            ? $pusher->authorizeChannel($request->channel_name, $request->socket_id)
            : $pusher->socket_auth($request->channel_name, $request->socket_id);

        return response($authString)->header('Content-Type', 'application/json');

    } catch (\Exception $e) {
        // 🔥 Log the exact error so we can see it if it fails again
        \Illuminate\Support\Facades\Log::error('Pusher Auth Error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Pusher error.', 
            'error' => $e->getMessage()
        ], 500);
    }
});


// Waiter App Auth
Route::post('/waiter/login', [WaiterAppController::class, 'login']);

// Protected Waiter Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/waiter/orders/ready', [WaiterAppController::class, 'getReadyOrders']);
    Route::post('/waiter/orders/{id}/serve', [WaiterAppController::class, 'markAsServed']);
});

Route::post('/orders', [PlaceOrderController::class, 'store']);
Route::get('/orders/session/{token}', [PlaceOrderController::class, 'getSessionOrders']);
Route::get('/table/{tableId}/pending-requests', [QrSessionController::class, 'getPendingRequests']);
Route::post('/session/{sessionId}/respond', [QrSessionController::class, 'respondToJoin']);

Route::prefix('qr')->group(function () {
    Route::get('/validate/{restaurant}/{table}/{token}', [QrSessionController::class, 'validateQr']);
    Route::post('/session/leave', [QrSessionController::class, 'leaveSession']);
    Route::post('/session/start/{restaurant}/{table}/{token}', [QrSessionController::class, 'startSession']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/menu/{restaurant}/{table}/{token}', [PublicMenuController::class, 'show'])->name('menu.view');
    
    
    
    