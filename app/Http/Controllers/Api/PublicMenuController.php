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

        $branchItemStatuses = DB::table('branch_menu_item_status')
            ->where('branch_id', $table->branch_id)
            ->pluck('is_available', 'menu_item_id');

        $branchCatStatuses = DB::table('branch_category_status')
            ->where('branch_id', $table->branch_id)
            ->pluck('is_active', 'category_id');

        $categories = $restaurant->categories()
            ->where(function($q) use ($table) {
                $q->whereNull('branch_id'); 
                if ($table->branch_id) {
                    $q->orWhere('branch_id', $table->branch_id); 
                }
            })
            ->orderBy('sort_order')
            ->with([
                'menuItems' => fn ($q) => $q->where(function($query) use ($table) {
                    $query->whereNull('branch_id'); 
                    if ($table->branch_id) {
                        $query->orWhere('branch_id', $table->branch_id); 
                    }
                })->orderBy('name')
            ])
            ->get()
            ->filter(function($category) use ($branchCatStatuses) {
                // If Main Category, check branch override
                if ($category->branch_id === null && $branchCatStatuses->has($category->id)) {
                    return (bool) $branchCatStatuses->get($category->id);
                }
                // If Branch Category, use standard status
                return (bool) $category->is_active; 
            })
            ->map(function ($category) use ($branchItemStatuses) {
                
                $filteredItems = $category->menuItems->filter(function($item) use ($branchItemStatuses) {
                    // If Main Item, check branch override
                    if ($item->branch_id === null && $branchItemStatuses->has($item->id)) {
                        return (bool) $branchItemStatuses->get($item->id);
                    }
                    // If Branch Item, use standard status
                    return (bool) $item->is_available;
                })->values();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'items' => $filteredItems->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'price' => $item->price,
                        'type' => $item->type ?? 'veg', // 👈 Dietary Type Included for App Filtering
                        'image' => $item->image_path
                            ? asset('storage/' . $item->image_path)
                            : null,
                    ]),
                ];
            })
            ->filter(fn($cat) => count($cat['items']) > 0)
            ->values();

        // Fetch the correct UPI ID and Address for this table's location
        $branch = $table->branch_id ? \App\Models\Branch::find($table->branch_id) : null;
        
        $finalUpiId = $branch && $branch->upi_id ? $branch->upi_id : $restaurant->upi_id;
        
        // EXTRACT ADDRESS: Check if branch has an address, otherwise fallback to main restaurant address
        $finalAddress = $branch && $branch->address ? $branch->address : $restaurant->address;

        // 👇 BASE64 LOGO CONVERSION LOGIC 👇
        $logoPayload = null;
        if ($restaurant->logo_path) {
            // Find the physical file on the server
            $fullPath = storage_path('app/public/' . $restaurant->logo_path);
            
            if (file_exists($fullPath)) {
                // Convert to Base64 String
                $mime = mime_content_type($fullPath);
                $b64 = base64_encode(file_get_contents($fullPath));
                $logoPayload = 'data:' . $mime . ';base64,' . $b64;
            } else {
                // Fallback to standard URL if file isn't found locally
                $logoPayload = asset('storage/' . $restaurant->logo_path); 
            }
        }

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
                'address' => $finalAddress,
                'currency_symbol' => $restaurant->currency_symbol ?? '₹',
                'upi_id' => $finalUpiId, 
                // 👇 SEND THE BASE64 STRING TO THE APP
                'logo' => $logoPayload,
                'is_pay_first' => (bool) $restaurant->is_pay_first,
            ],
            'table' => [
                'id' => $table->id,
                'number' => $table->table_number ?? $table->number ?? $table->name ?? $table->id,
                'capacity' => $table->seating_capacity, 
            ],
            'categories' => $categories,
        ]);
    }
}