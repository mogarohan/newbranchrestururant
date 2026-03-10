<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Services\Restaurant\MenuSessionValidator;

class PublicMenuController extends Controller
{
    public function show(
        Restaurant $restaurant,
        RestaurantTable $table,
        string $token,
        Request $request,
        MenuSessionValidator $validator
    ) {
        // 🔐 QR SECURITY
        abort_unless($table->restaurant_id === $restaurant->id, 404);
        abort_unless($table->qr_token === $token, 403);
        abort_unless($table->is_active, 403);
        abort_unless($restaurant->is_active ?? true, 403);

        // 🔐 SESSION SECURITY
        $request->validate([
            'session_token' => ['required', 'string'],
        ]);

        $session = $validator->validate(
            $table,
            $request->session_token
        );

        // 🔥 CRITICAL FIX: STOP HERE IF NOT APPROVED 🔥
        if (!$session->is_primary && $session->join_status !== 'approved') {
            return response()->json([
                'message' => 'You are waiting for approval.',
                'join_status' => $session->join_status,
                // MUST return the session ID even on 403 so the guest can connect to WebSockets!
                'session' => [
                    'id' => $session->id 
                ]
            ], 403); 
        }

        // 🔥 GET HOST SESSION FOR DYNAMIC UI
        $hostSession = \App\Models\QrSession::where('restaurant_table_id', $table->id)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->first();

        // If we get here, the user is allowed to see the menu
        return response()->json([
            'session' => [
                'id' => $session->id, 
                'token' => $session->session_token,
                'expires_at' => $session->expires_at,
                'join_status' => $session->join_status, 
                'is_primary' => $session->is_primary,
                'host_name' => $hostSession ? $hostSession->customer_name : 'Unknown',
            ],

            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'logo' => $restaurant->logo_path
                    ? asset('storage/' . $restaurant->logo_path)
                    : null,
            ],

            'table' => [
                'id' => $table->id,
                'number' => $table->table_number,
                'capacity' => $table->seating_capacity, 
            ],

            'categories' => $restaurant->categories()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with([
                    'menuItems' => fn ($q) =>
                        $q->where('is_available', true)
                        ->orderBy('name')
                ])
                ->get()
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'items' => $category->menuItems->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'price' => $item->price,
                        'image' => $item->image_path
                            ? asset('storage/' . $item->image_path)
                            : null,
                    ]),
                ]),
        ]);
    }
}