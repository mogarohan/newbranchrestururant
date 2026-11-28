<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\QrSession;
use App\Models\RoomSession;
use Illuminate\Support\Facades\Log;

// 1. Staff Authorization (Filament Managers, Chefs, Waiters)
Broadcast::channel('restaurant.{restaurantId}', function ($user, $restaurantId) {
    // Only allow staff members who belong to this specific restaurant
    return (int) $user->restaurant_id === (int) $restaurantId;
});

Broadcast::channel('restaurant.{restaurantId}.alerts', function ($user, $restaurantId) {
    return (int) $user->restaurant_id === (int) $restaurantId;
});

// 2. Customer Authorization (Tables & Rooms)
Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    // Since guests aren't logged in as standard Laravel users, we authenticate them via their Bearer token.
    $token = request()->bearerToken();

    if (!$token) {
        Log::warning("Pusher Auth Failed: No Bearer Token provided for Session: {$sessionId}");
        return false;
    }

    // A. Check if it's a Table Session (QrSession)
    $qrSession = QrSession::where('session_token', $token)->first();
    if ($qrSession) {
        // Diner can authorize on their own session channel OR their host session channel
        return (int) $qrSession->id === (int) $sessionId ||
               ($qrSession->host_session_id && (int) $qrSession->host_session_id === (int) $sessionId);
    }

    // B. Check if it's a Room Session (RoomSession)
    $roomSession = RoomSession::where('session_token', $token)->first();
    if ($roomSession) {
        return (int) $roomSession->id === (int) $sessionId;
    }

    // Token provided, but matches absolutely nothing in the DB
    Log::warning("Pusher Auth Failed: Invalid Token {$token} for Session: {$sessionId}");
    return false;
});