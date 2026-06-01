<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\ParcelQrCode;
use App\Models\QrSession;
use App\Models\RoomSession;
use App\Models\ParcelQrSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicMenuController extends Controller
{
    public function show(
        Restaurant $restaurant,
        $entityId,
        string $token,
        Request $request
    ) {

        $type = $request->query('type', 'table'); // Default to table for backwards compatibility
        $isRoom = $type === 'room';
        $isParcel = $type === 'parcel';
        $isTable = $type === 'table';

        // ─────────────────────────────────────────────────────────────
        // Resolve Entity + Session Based on Type
        // ─────────────────────────────────────────────────────────────

        if ($isRoom) {

            $entity = Room::findOrFail($entityId);

            $session = RoomSession::where('session_token', $request->session_token)
                ->where('room_id', $entity->id)
                ->first();

            $hostSession = $session;

        } elseif ($isParcel) {

            // 👇 Enterprise Fix: Soft-delete safe lookup
            $entity = ParcelQrCode::where('id', $entityId)->whereNull('deleted_at')->firstOrFail();

            $session = ParcelQrSession::where('session_token', $request->session_token)
                ->where('parcel_qr_code_id', $entity->id)
                ->first();

            $hostSession = $session;

        } else { // Table

            $entity = RestaurantTable::findOrFail($entityId);

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
        // Session Expiry Checks
        // ─────────────────────────────────────────────────────────────

        $isExpired = false;

        if ($isRoom) {
            $isExpired =
                $session->status !== 'active' ||
                Carbon::parse($session->check_out_at)->isPast();
        } elseif ($isParcel) {
            // Parcels Auto-Expire on status or inactive time
            $isExpired = 
                $session->status !== 'active' || 
                ($session->last_activity_at && $session->last_activity_at->diffInHours(now()) >= 3);
        } else {
            $isExpired =
                !$session->is_active ||
                Carbon::parse($session->expires_at)->isPast();
        }

        if ($isExpired) {
            // Auto update status if we caught it here
            if ($isParcel) $session->update(['status' => 'expired']);

            return response()->json([
                'message' => 'Session expired'
            ], 403);
        }

        // ─────────────────────────────────────────────────────────────
        // Join Approval (Tables Only)
        // ─────────────────────────────────────────────────────────────

        if (
            $isTable &&
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
                        $finalAvailable = $branchAvailable !== null ? $branchAvailable : $baseAvailable;

                        // ─────────────────────────────────────────────
                        // Stock Logic
                        // ─────────────────────────────────────────────
                        $trackStock = (bool) ($item->track_stock ?? false);
                        $stockQty = $item->stock_quantity;
                        $lowThreshold = (int) ($item->low_stock_threshold ?? 5);

                        // Out Of Stock
                        $isOutOfStock = !$finalAvailable || ($trackStock && $stockQty !== null && (int) $stockQty <= 0);

                        // Limited Stock
                        $isLimitedStock = !$isOutOfStock && $trackStock && $stockQty !== null && (int) $stockQty > 0 && (int) $stockQty <= $lowThreshold;

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
                            'image' => $item->image_path ? asset('storage/' . $item->image_path) : null,
                            
                            // Availability
                            'is_available' => !$isOutOfStock,
                            'is_out_of_stock' => $isOutOfStock,
                            'is_limited_stock' => $isLimitedStock,

                            // Stock
                            'track_stock' => $trackStock,
                            'stock_quantity' => $trackStock ? (int) ($stockQty ?? 0) : null,
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

        $branch = $entity->branch_id ? \App\Models\Branch::find($entity->branch_id) : null;
        $finalUpiId = $branch && $branch->upi_id ? $branch->upi_id : $restaurant->upi_id;
        $finalAddress = $branch && $branch->address ? $branch->address : $restaurant->address;

        // ─────────────────────────────────────────────────────────────
        // Logo
        // ─────────────────────────────────────────────────────────────

        $logoPayload = null;

        if ($restaurant->logo_path) {
            $fullPath = storage_path('app/public/' . $restaurant->logo_path);

            if (file_exists($fullPath)) {
                $mime = mime_content_type($fullPath);
                $b64 = base64_encode(file_get_contents($fullPath));
                $logoPayload = 'data:' . $mime . ';base64,' . $b64;
            } else {
                $logoPayload = asset('storage/' . $restaurant->logo_path);
            }
        }

        // ─────────────────────────────────────────────────────────────
        // Host Name resolution
        // ─────────────────────────────────────────────────────────────
        $finalHostName = 'Customer';
        if ($isRoom) {
            $finalHostName = $session->guest_name;
        } elseif ($isParcel) {
            $finalHostName = $session->customer_name;
        } elseif ($hostSession) {
            $finalHostName = $hostSession->customer_name;
        }

        // ─────────────────────────────────────────────────────────────
        // Table Number / Display Name resolution
        // ─────────────────────────────────────────────────────────────
        $finalTableNumber = '-';
        $finalCapacity = 1;
        
        if ($isRoom) {
            $finalTableNumber = $entity->room_number;
            $finalCapacity = $entity->max_guests;
        } elseif ($isParcel) {
            $finalTableNumber = $entity->name; // e.g. "Main Counter"
            $finalCapacity = 100; // Arbitrary high limit for counters
        } else {
            $finalTableNumber = $entity->table_number ?? $entity->number ?? $entity->name ?? $entity->id;
            $finalCapacity = $entity->seating_capacity;
        }

        // ─────────────────────────────────────────────────────────────
        // Final Response
        // ─────────────────────────────────────────────────────────────

        return response()->json([

            'session' => [
                'id' => $session->id,
                'token' => $session->session_token,
                'expires_at' => $isRoom ? $session->check_out_at : ($isParcel ? null : $session->expires_at),
                'join_status' => $isTable ? $session->join_status : 'active',
                'is_primary' => $isTable ? $session->is_primary : true,
                'host_name' => $finalHostName,
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
                'number' => $finalTableNumber,
                'capacity' => $finalCapacity,
                'type' => $type, // Expose type to frontend so it knows if it's room/table/parcel
            ],

            'categories' => $categories,
        ]);
    }
}