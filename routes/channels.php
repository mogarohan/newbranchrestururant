<?php

use Illuminate\Support\Facades\Broadcast;

// 1. Staff Authorization (Filament Managers, Chefs, Waiters)
Broadcast::channel('restaurant.{restaurantId}', function ($user, $restaurantId) {
    // Only allow staff members who belong to this specific restaurant
    return (int) $user->restaurant_id === (int) $restaurantId;
});

Broadcast::channel('restaurant.{restaurantId}.alerts', function ($user, $restaurantId) {
    return (int) $user->restaurant_id === (int) $restaurantId;
});

// 2. Customer Authorization Fallback
// Note: React Native uses the custom /pusher/auth endpoint in api.php.
// This is just a fallback for default Laravel broadcasting.
Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    // Logged-in staff shouldn't be listening to direct guest sessions here
    return false; 
});