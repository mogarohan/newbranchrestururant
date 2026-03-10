<?php

use Illuminate\Support\Facades\Broadcast;

// 1. Staff Authorization (Filament Managers, Chefs, Waiters)
Broadcast::channel('restaurant.{restaurantId}', function ($user, $restaurantId) {
    // Only allow staff members who belong to this specific restaurant
    return (int) $user->restaurant_id === (int) $restaurantId;
});
// 2. Customer Authorization Fallback (Optional but good practice)
// Note: Your React Native app uses the custom /pusher/auth endpoint in api.php, 
// but if you ever build a standard web frontend for guests, this will protect it.
Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    // Standard logged-in users generally shouldn't be listening to guest sessions
    return false; 
});