<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\QrSession;
use Illuminate\Support\Facades\DB;

class PublicMenuController extends Controller
{
    public function show(
        Restaurant $restaurant,
        RestaurantTable $table,
        string $token,
        Request $request
    ) {
        abort_unless($table->restaurant_id === $restaurant->id, 404);
        abort_unless($table->qr_token === $token, 403);
        abort_unless($table->is_active, 403);
        abort_unless($restaurant->is_active ?? true, 403);

        $request->validate(['session_token' => ['required', 'string']]);

        $session = QrSession::where('session_token', $request->session_token)
            ->where('restaurant_table_id', $table->id)
            ->first();

        if (!$session || !$session->is_active || $session->expires_at < now()) {
            return response()->json(['message' => 'Session expired'], 403);
        }

        if (!$session->is_primary && $session->join_status !== 'approved') {
            return response()->json([
                'message' => 'You are waiting for approval.',
                'join_status' => $session->join_status,
                'session' => [ 'id' => $session->id ]
            ], 403); 
        }

        $hostSession = QrSession::where('restaurant_table_id', $table->id)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->first();

        // 👇 1. FETCH BRANCH SPECIFIC OFF/ON STATUSES FOR BOTH ITEMS AND CATEGORIES
        $branchItemStatuses = DB::table('branch_menu_item_status')
            ->where('branch_id', $table->branch_id)
            ->pluck('is_available', 'menu_item_id');

        $branchCatStatuses = DB::table('branch_category_status')
            ->where('branch_id', $table->branch_id)
            ->pluck('is_active', 'category_id');

        // 👇 2. FETCH AND FILTER MENU
        $categories = $restaurant->categories()
            ->where('is_active', true)
            ->whereNull('branch_id') // Main Menu Only
            ->orderBy('sort_order')
            ->with([
                'menuItems' => fn ($q) => $q->whereNull('branch_id')->orderBy('name')
            ])
            ->get()
            // 👇 3. FILTER CATEGORIES BASED ON BRANCH OVERRIDE
            ->filter(function($category) use ($branchCatStatuses) {
                // Agar branch ne category OFF ki hai toh hide kar do
                if ($branchCatStatuses->has($category->id)) {
                    return (bool) $branchCatStatuses->get($category->id);
                }
                return (bool) $category->is_active; // Warna original default chalne do
            })
            ->map(function ($category) use ($branchItemStatuses) {
                
                // 👇 4. FILTER ITEMS INSIDE CATEGORY BASED ON BRANCH OVERRIDE
                $filteredItems = $category->menuItems->filter(function($item) use ($branchItemStatuses) {
                    return $branchItemStatuses->has($item->id) 
                        ? (bool) $branchItemStatuses->get($item->id) 
                        : (bool) $item->is_available;
                })->values();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'items' => $filteredItems->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'price' => $item->price,
                        'image' => $item->image_path
                            ? asset('storage/' . $item->image_path)
                            : null,
                    ]),
                ];
            })
            // Remove categories that have 0 items available
            ->filter(fn($cat) => count($cat['items']) > 0)
            ->values();

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
            'categories' => $categories,
        ]);
    }
}