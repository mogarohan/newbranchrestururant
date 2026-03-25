<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\PublicMenuController;
use App\Http\Controllers\Public\QrSessionController;
use App\Http\Controllers\Api\PlaceOrderController;
use App\Http\Controllers\Api\WaiterAppController;
use App\Models\QrSession;
use Laravel\Sanctum\PersonalAccessToken;
use Pusher\Pusher;

/*
|--------------------------------------------------------------------------
| REAL-TIME WEBSOCKET AUTHORIZATION (DUAL AUTH & TENANT SECURE)
|--------------------------------------------------------------------------
*/
Route::post('/pusher/auth', function (Request $request) {
    $rawChannelName = $request->input('channel_name');

    // Strict Regex Validation for Channel Name to prevent injection
    if (!$rawChannelName || !preg_match('/^(private|presence)-[a-zA-Z0-9\.\-_]+$/', $rawChannelName)) {
        return response()->json(['message' => 'Invalid channel name'], 400);
    }

    $channelName = str_replace(['private-', 'presence-'], '', $rawChannelName);
    $socketId = $request->input('socket_id');

    if (!$socketId) {
        return response()->json(['message' => 'Missing socket ID'], 400);
    }

    // Safer token extraction
    $token = $request->bearerToken();
    if (!$token && $request->hasHeader('Authorization')) {
        $token = str_replace('Bearer ', '', $request->header('Authorization'));
    }

    if (!$token) {
        return response()->json(['message' => 'Missing token'], 403);
    }

    $authorized = false;
    $user = null;
    $session = null;

    // 1. Customer QR Session Validation
    // (If token does not contain a pipe '|', it's a UUID QR Token, not a Sanctum Token)
    if (!str_contains((string) $token, '|')) {
        $session = QrSession::where('session_token', $token)->first();
        
        if ($session && str_starts_with($channelName, 'session.')) {
            $requestedId = str_replace('session.', '', $channelName);
            
            // 👇 FIX: Allow connection if the channel matches Session ID, Host ID, or Table ID
            if (
                $requestedId == $session->id || 
                $requestedId == $session->restaurant_table_id || 
                ($session->host_session_id && $requestedId == $session->host_session_id)
            ) {
                $authorized = true;
            }
        }
    }

    // 2. Waiter Auth (Sanctum) Validation
    if (!$authorized) {
        $user = PersonalAccessToken::findToken($token)?->tokenable;

        if ($user && str_starts_with($channelName, "restaurant." . $user->restaurant_id)) {
            $authorized = true;
        }
    }

    if (!$authorized) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    try {
        $pusher = app()->bound('pusher') ? app('pusher') : new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => true
            ]
        );

        if ($user && str_starts_with($rawChannelName, 'presence-')) {
            $presenceData = ['name' => $user->name, 'staff_id' => $user->staff_id ?? 'Unknown'];
            $authString = $pusher->presence_auth($rawChannelName, $socketId, $user->id, $presenceData);
        } else {
            $authString = method_exists($pusher, 'authorizeChannel')
                ? $pusher->authorizeChannel($rawChannelName, $socketId)
                : $pusher->socket_auth($rawChannelName, $socketId);
        }

        return response($authString)->header('Content-Type', 'application/json');

    } catch (\Exception $e) {
        Log::error('Pusher Auth Error', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Pusher error.'], 500);
    }
});


/*
|--------------------------------------------------------------------------
| WAITER APP ROUTES (Secured)
|--------------------------------------------------------------------------
*/
// Throttled login to stop brute force
Route::post('/waiter/login', [WaiterAppController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/waiter/profile', [WaiterAppController::class, 'getProfile']);

    // Order Management
    Route::prefix('waiter/orders')->group(function () {
        Route::get('/ready', [WaiterAppController::class, 'getReadyOrders']);
        Route::post('/{id}/serve', [WaiterAppController::class, 'markAsServed']);
        Route::post('/{id}/acknowledge', [WaiterAppController::class, 'acknowledgeOrder']);
    });

    // Table Management
    Route::prefix('waiter/tables')->group(function () {
        Route::get('/', [WaiterAppController::class, 'getTables']);
        Route::post('/{id}/status', [WaiterAppController::class, 'updateTableStatus']);
    });
});


/*
|--------------------------------------------------------------------------
| CUSTOMER APP ROUTES (QR System)
|--------------------------------------------------------------------------
*/
// Throttled Order Placement
Route::post('/orders', [PlaceOrderController::class, 'store'])->middleware('throttle:30,1');
Route::get('/orders/session/{token}', [PlaceOrderController::class, 'getSessionOrders']);

// Session Actions
Route::post('/session/call-waiter', [QrSessionController::class, 'callWaiter'])->middleware('throttle:15,1');
Route::get('/table/{tableId}/pending-requests', [QrSessionController::class, 'getPendingRequests'])->middleware('throttle:20,1');
Route::post('/session/{sessionId}/respond', [QrSessionController::class, 'respondToJoin'])->middleware('throttle:10,1');

Route::prefix('qr')->group(function () {
    Route::get('/validate/{restaurant}/{table}/{token}', [QrSessionController::class, 'validateQr']);
    Route::post('/session/leave', [QrSessionController::class, 'leaveSession'])->middleware('throttle:10,1');
    Route::post('/session/start/{restaurant}/{table}/{token}', [QrSessionController::class, 'startSession'])->middleware('throttle:10,1');
});

// Public Menu Access
Route::get('/menu/{restaurant}/{table}/{token}', [PublicMenuController::class, 'show'])->name('menu.view');