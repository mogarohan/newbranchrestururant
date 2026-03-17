<?php

namespace App\Services\Restaurant;

use App\Models\QrSession;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QrSessionService
{
    /**
     * Validate QR + create/resume session
     */
    public function startSession(
        Restaurant $restaurant,
        RestaurantTable $table,
        ?string $customerName
    ): QrSession {

        // Expire old sessions
        QrSession::where('restaurant_table_id', $table->id)
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        // Resume active session if exists
        $existing = QrSession::where('restaurant_table_id', $table->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        if ($existing) {
            if ($customerName && !$existing->customer_name) {
                $existing->update(['customer_name' => $customerName]);
            }

            return $existing;
        }

        // Create fresh session
        return QrSession::create([
            'restaurant_id' => $restaurant->id,
            'branch_id' => $table->branch_id, // 👈 YAHAN BRANCH ID ADD KIYA GAYA HAI
            'restaurant_table_id' => $table->id,
            'session_token' => Str::uuid(),
            'customer_name' => $customerName,
            'is_primary' => true,
            'is_active' => true,
            'expires_at' => now()->addHours(3),
        ]);
    }
}