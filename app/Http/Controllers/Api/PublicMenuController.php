<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\QrSession;
use App\Models\RoomSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicMenuController extends Controller
{
    public function show(
        Restaurant $restaurant,
        $tableOrRoomId,
        string $token,
        Request $request
    ) {

        $isRoom = $request->query('type') === 'room';

        // ─────────────────────────────────────────────────────────────
        // Resolve Entity + Session
        // ─────────────────────────────────────────────────────────────

        if ($isRoom) {

            $entity = Room::findOrFail($tableOrRoomId);

            $session = RoomSession::where('session_token', $request->session_token)
                ->where('room_id', $entity->id)
                ->first();

            $hostSession = $session;

        } else {

            $entity = RestaurantTable::findOrFail($tableOrRoomId);

            $session = QrSession::where('session_token', $request->session_token)
                ->where('restaurant_table_id', $entity->id)
                ->first();

            $hostSession = QrSession::where('restaurant_table_id', $entity->id)
                ->where('is_primary', true)
                ->where('is_active', true)
                ->first();
        }

        // ─────────────────────────────────────────────────────────────
        // Basic Validation
        // ─────────────────────────────────────────────────────────────

        abort_unless($entity->restaurant_id === $restaurant->id, 404);

        abort_unless($entity->qr_token === $token, 403);

        abort_unless($restaurant->is_active ?? true, 403);

        $request->validate([
            'session_token' => ['required', 'string']
        ]);

        if (!$session) {

            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        // ─────────────────────────────────────────────────────────────
        // Session Expiry
        // ─────────────────────────────────────────────────────────────

        if ($isRoom) {

            $isExpired =
                $session->status !== 'active' ||
                Carbon::parse($session->check_out_at)->isPast();

        } else {

            $isExpired =
                !$session->is_active ||
                Carbon::parse($session->expires_at)->isPast();
        }

        if ($isExpired) {

            return response()->json([
                'message' => 'Session expired'
            ], 403);
        }

        // ─────────────────────────────────────────────────────────────
        // Join Approval
        // ─────────────────────────────────────────────────────────────

        if (
            !$isRoom &&
            !$session->is_primary &&
            $session->join_status !== 'approved'
        ) {

            return response()->json([
                'message' => 'You are waiting for approval.',
                'join_status' => $session->join_status,
                'session' => [
                    'id' => $session->id
                ],
            ], 403);
        }

        // ─────────────────────────────────────────────────────────────
        // Branch Overrides
        // ─────────────────────────────────────────────────────────────

        $branchItemStatuses = DB::table('branch_menu_item_status')
            ->where('branch_id', $entity->branch_id)
            ->pluck('is_available', 'menu_item_id');

        $branchCatStatuses = DB::table('branch_category_status')
            ->where('branch_id', $entity->branch_id)
            ->pluck('is_active', 'category_id');

        // ─────────────────────────────────────────────────────────────
        // Categories + Items
        // ─────────────────────────────────────────────────────────────

        $categories = $restaurant->categories()

            ->where(function ($q) use ($entity) {

                $q->whereNull('branch_id');

                if ($entity->branch_id) {
                    $q->orWhere('branch_id', $entity->branch_id);
                }
            })

            ->orderBy('sort_order')

            ->with([

                'menuItems' => fn($q) => $q
                    ->where(function ($query) use ($entity) {

                        $query->whereNull('branch_id');

                        if ($entity->branch_id) {
                            $query->orWhere('branch_id', $entity->branch_id);
                        }
                    })
                    ->orderBy('name'),

            ])

            ->get()

            // ─────────────────────────────────────────────────────────
            // Category visibility
            // ─────────────────────────────────────────────────────────

            ->filter(function ($category) use ($branchCatStatuses) {

                if (
                    $category->branch_id === null &&
                    $branchCatStatuses->has($category->id)
                ) {

                    return (bool) $branchCatStatuses->get($category->id);
                }

                return (bool) $category->is_active;
            })

            // ─────────────────────────────────────────────────────────
            // Category mapping
            // ─────────────────────────────────────────────────────────

            ->map(function ($category) use ($branchItemStatuses) {

                // Keep even out-of-stock items visible
                $filteredItems = $category->menuItems

                    ->filter(function ($item) use ($branchItemStatuses) {

                    // Branch-level override
                    if (
                        $item->branch_id === null &&
                        $branchItemStatuses->has($item->id)
                    ) {

                        return true;
                    }

                    return true;
                })

                    ->values();

                return [

                    'id' => $category->id,

                    'name' => $category->name,

                    'items' => $filteredItems->map(function ($item) use ($branchItemStatuses) {

                        // ─────────────────────────────────────────────
                        // Branch Availability
                        // ─────────────────────────────────────────────
        
                        $branchAvailable = null;

                        if (
                            $item->branch_id === null &&
                            $branchItemStatuses->has($item->id)
                        ) {

                            $branchAvailable = (bool) $branchItemStatuses->get($item->id);
                        }

                        $baseAvailable = (bool) $item->is_available;

                        $finalAvailable = $branchAvailable !== null
                            ? $branchAvailable
                            : $baseAvailable;

                        // ─────────────────────────────────────────────
                        // Stock Logic
                        // ─────────────────────────────────────────────
        
                        $trackStock = (bool) ($item->track_stock ?? false);

                        $stockQty = $item->stock_quantity;

                        $lowThreshold = (int) ($item->low_stock_threshold ?? 5);

                        // Out Of Stock
                        $isOutOfStock =
                            !$finalAvailable ||

                            (
                                $trackStock &&
                                $stockQty !== null &&
                                (int) $stockQty <= 0
                            );

                        // Limited Stock
                        $isLimitedStock =
                            !$isOutOfStock &&
                            $trackStock &&
                            $stockQty !== null &&
                            (int) $stockQty > 0 &&
                            (int) $stockQty <= $lowThreshold;

                        // Stock Label
                        $stockLabel = null;

                        if ($isOutOfStock) {

                            $stockLabel = 'Out of Stock';

                        } elseif ($isLimitedStock) {

                            $stockLabel = 'Only ' . (int) $stockQty . ' left!';
                        }

                        return [

                            'id' => $item->id,

                            'name' => $item->name,

                            'description' => $item->description,

                            'price' => $item->price,

                            'type' => $item->type ?? 'veg',

                            'image' => $item->image_path
                                ? asset('storage/' . $item->image_path)
                                : null,

                            // ─────────────────────────────────────
                            // Availability
                            // ─────────────────────────────────────
        
                            'is_available' => !$isOutOfStock,

                            'is_out_of_stock' => $isOutOfStock,

                            'is_limited_stock' => $isLimitedStock,

                            // ─────────────────────────────────────
                            // Stock
                            // ─────────────────────────────────────
        
                            'track_stock' => $trackStock,

                            'stock_quantity' => $trackStock
                                ? (int) ($stockQty ?? 0)
                                : null,

                            'low_stock_threshold' => $lowThreshold,

                            'stock_label' => $stockLabel,
                        ];
                    }),
                ];
            })

            // Keep categories if they have items
            ->filter(fn($cat) => count($cat['items']) > 0)

            ->values();

        // ─────────────────────────────────────────────────────────────
        // Branch Meta
        // ─────────────────────────────────────────────────────────────

        $branch = $entity->branch_id
            ? \App\Models\Branch::find($entity->branch_id)
            : null;

        $finalUpiId =
            $branch && $branch->upi_id
            ? $branch->upi_id
            : $restaurant->upi_id;

        $finalAddress =
            $branch && $branch->address
            ? $branch->address
            : $restaurant->address;

        // ─────────────────────────────────────────────────────────────
        // Logo
        // ─────────────────────────────────────────────────────────────

        $logoPayload = null;

        if ($restaurant->logo_path) {

            $fullPath = storage_path(
                'app/public/' . $restaurant->logo_path
            );

            if (file_exists($fullPath)) {

                $mime = mime_content_type($fullPath);

                $b64 = base64_encode(file_get_contents($fullPath));

                $logoPayload = 'data:' . $mime . ';base64,' . $b64;

            } else {

                $logoPayload = asset('storage/' . $restaurant->logo_path);
            }
        }

        // ─────────────────────────────────────────────────────────────
        // Final Response
        // ─────────────────────────────────────────────────────────────

        return response()->json([

            'session' => [

                'id' => $session->id,

                'token' => $session->session_token,

                'expires_at' => $isRoom
                    ? $session->check_out_at
                    : $session->expires_at,

                'join_status' => $isRoom
                    ? 'active'
                    : $session->join_status,

                'is_primary' => $isRoom
                    ? true
                    : $session->is_primary,

                'host_name' => $isRoom
                    ? $session->guest_name
                    : ($hostSession
                        ? $hostSession->customer_name
                        : 'Unknown'),
            ],

            'restaurant' => [

                'id' => $restaurant->id,

                'name' => $restaurant->name,

                'address' => $finalAddress,

                'currency_symbol' => $restaurant->currency_symbol ?? '₹',

                'upi_id' => $finalUpiId,

                'logo' => $logoPayload,

                'is_pay_first' => (bool) $restaurant->is_pay_first,
            ],

            'table' => [

                'id' => $entity->id,

                'number' => $isRoom
                    ? $entity->room_number
                    : (
                        $entity->table_number ??
                        $entity->number ??
                        $entity->name ??
                        $entity->id
                    ),

                'capacity' => $isRoom
                    ? $entity->max_guests
                    : $entity->seating_capacity,
            ],

            'categories' => $categories,
        ]);
    }
}