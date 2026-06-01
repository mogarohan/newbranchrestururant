<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\QrSession;
use App\Models\RoomSession;
use App\Models\ParcelQrSession;
use App\Models\Room;
use App\Models\ParcelQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrSessionController extends Controller
{
    /**
     * Unified QR Validation for Tables, Rooms, and Parcels
     */
    public function validateQr(Request $request)
    {
        $restaurantId = $request->input('r') ?? $request->route('restaurant');
        $id = $request->input('id') ?? $request->route('table'); 
        $token = $request->input('token') ?? $request->route('token');
        $type = $request->input('type', 'table'); 

        abort_unless($restaurantId, 404, 'Restaurant ID missing');
        abort_unless($id, 404, 'Entity ID missing');
        abort_unless($token, 403, 'Token missing');

        // ── TABLE VALIDATION ──
        if ($type === 'table') {
            $table = RestaurantTable::where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->whereNull('deleted_at')
                ->first();

            abort_unless($table, 404, 'Table not found');
            abort_unless($table->qr_token === $token, 403, 'Invalid QR Token');
            abort_unless($table->is_active, 403, 'This table is currently inactive or unavailable.');

            $host = QrSession::where('restaurant_table_id', $table->id)
                ->where('is_primary', true)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            $currentOccupancy = QrSession::where('restaurant_table_id', $table->id)
                ->whereIn('join_status', ['active', 'approved'])
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->count();

            return response()->json([
                'valid' => true,
                'type' => 'table',
                'has_active_host' => (bool) $host,
                'host_name' => $host ? $host->customer_name : null,
                'is_full' => $currentOccupancy >= $table->seating_capacity,
                'is_reserved' => $table->status === 'reserved',
                'capacity' => $table->seating_capacity,
                'occupancy' => $currentOccupancy,
                'table_number' => $table->table_number ?? $table->number ?? $table->id,
            ]);
        }

        // ── ROOM VALIDATION ──
        if ($type === 'room') {
            $room = Room::where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->whereNull('deleted_at')
                ->first();

            abort_unless($room, 404, 'Room not found');
            abort_unless($room->qr_token === $token, 403, 'Invalid QR Token');
            
            // Check if there is an active session for this room
            $activeSession = RoomSession::where('room_id', $room->id)
                ->where('session_token', $token)
                ->where('status', 'active')
                ->first();

            return response()->json([
                'valid' => true, 
                'type' => 'room', 
                'name' => 'Room ' . $room->room_number,
                'has_active_session' => (bool) $activeSession
            ]);
        }

        // ── PARCEL VALIDATION ──
        if ($type === 'parcel') {
            $parcel = ParcelQrCode::where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->where('qr_token', $token)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->first();

            abort_unless($parcel, 404, 'Invalid or Inactive Parcel QR');

            return response()->json([
                'valid' => true, 
                'type' => 'parcel', 
                'name' => $parcel->name
            ]);
        }

        abort(400, 'Invalid request type');
    }

    /**
     * Unified Session Starter
     */
    public function startSession(Request $request)
    {
        $restaurantId = $request->input('r') ?? $request->route('restaurant');
        $id = $request->input('id') ?? $request->route('table'); 
        $token = $request->input('token') ?? $request->route('token');
        $type = $request->input('type', 'table');

        $sessionToken = Str::uuid()->toString();

        // ── TABLE SESSION ──
        if ($type === 'table') {
            $request->validate(['customer_name' => 'required|string|max:255']);
            $customerName = $request->input('customer_name');

            $table = RestaurantTable::where('id', $id)->where('restaurant_id', $restaurantId)->whereNull('deleted_at')->firstOrFail();
            abort_unless($table->qr_token === $token, 403, 'Invalid token');

            $mode = $request->input('mode');
            $existingHost = QrSession::where('restaurant_table_id', $table->id)
                ->where('is_primary', true)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if (!$existingHost || $mode === 'new') {
                if ($table->status === 'available' || $table->status === 'cleaning') {
                    $table->update(['status' => 'occupied']);
                    event(new \App\Events\TableStatusUpdated($table->id, 'occupied', $table->restaurant_id));
                }

                return QrSession::create([
                    'restaurant_id' => $restaurantId,
                    'branch_id' => $table->branch_id,
                    'restaurant_table_id' => $table->id,
                    'customer_name' => $customerName,
                    'session_token' => $sessionToken,
                    'is_primary' => true,
                    'join_status' => 'active',
                    'is_active' => true,
                    'host_session_id' => null,
                    'expires_at' => now()->addHours(3),
                ]);
            }

            $guestSession = QrSession::create([
                'restaurant_id' => $restaurantId,
                'branch_id' => $table->branch_id,
                'restaurant_table_id' => $table->id,
                'customer_name' => $customerName,
                'session_token' => $sessionToken,
                'is_primary' => false,
                'join_status' => 'pending',
                'is_active' => true,
                'host_session_id' => $existingHost->id,
                'expires_at' => now()->addHours(3),
            ]);

            \App\Events\GuestJoinRequested::dispatch($guestSession);
            return response()->json($guestSession, 201);
        }

        // ── ROOM SESSION ──
        if ($type === 'room') {
            // FIX: Rooms do not need to create a new session, they resume the active one created by the Manager
            $room = Room::where('id', $id)->where('restaurant_id', $restaurantId)->where('qr_token', $token)->whereNull('deleted_at')->firstOrFail();
            
            $activeSession = RoomSession::where('room_id', $room->id)
                ->where('session_token', $token) // The Manager generates the token for the room
                ->where('status', 'active')
                ->first();

            if (!$activeSession) {
                return response()->json(['message' => 'No active guest checked into this room.'], 403);
            }
            
            return response()->json([
                'session_token' => $activeSession->session_token,
                'service_type' => 'room',
                'customer_name' => $activeSession->guest_name
            ]);
        }

        // ── PARCEL SESSION ──
        if ($type === 'parcel') {
            $request->validate(['customer_name' => 'required|string|max:255']);
            $customerName = $request->input('customer_name');

            $parcelQr = ParcelQrCode::where('id', $id)->where('restaurant_id', $restaurantId)->where('qr_token', $token)->whereNull('deleted_at')->firstOrFail();
            
            $existingSession = ParcelQrSession::where('parcel_qr_code_id', $parcelQr->id)
                ->where('customer_name', $customerName)
                ->where('status', 'active')
                ->where('created_at', '>=', now()->subHours(2))
                ->first();

            if ($existingSession) {
                $existingSession->update(['last_activity_at' => now()]);
                return response()->json([
                    'session_token' => $existingSession->session_token,
                    'service_type' => 'parcel',
                    'customer_name' => $existingSession->customer_name
                ]);
            }

            $session = ParcelQrSession::create([
                'restaurant_id' => $parcelQr->restaurant_id,
                'branch_id' => $parcelQr->branch_id,
                'parcel_qr_code_id' => $parcelQr->id,
                'customer_name' => $customerName,
                'session_token' => $sessionToken,
                'status' => 'active',
                'last_activity_at' => now(),
            ]);

            return response()->json([
                'session_token' => $session->session_token,
                'service_type' => 'parcel',
                'customer_name' => $session->customer_name
            ]);
        }

        abort(400, 'Invalid request type');
    }

    /**
     * Unified Session Check
     */
    public function checkSession(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        if (!$token) return response()->json(['message' => 'TOKEN_MISSING'], 401);

        // Check Table
        $tableSession = QrSession::where('session_token', $token)->where('is_active', true)->first();
        if ($tableSession) {
            return response()->json([
                'valid' => true, 
                'type' => 'table', 
                'session' => $tableSession,
                'session_id' => $tableSession->id,
                'join_status' => $tableSession->join_status
            ]);
        }

        // Check Room
        $roomSession = RoomSession::where('session_token', $token)->where('status', 'active')->first();
        if ($roomSession) {
            return response()->json([
                'valid' => true, 
                'type' => 'room', 
                'session' => $roomSession,
                'session_id' => $roomSession->id
            ]);
        }

        // Check Parcel
        $parcelSession = ParcelQrSession::where('session_token', $token)->where('status', 'active')->first();
        if ($parcelSession) {
            if ($parcelSession->last_activity_at && $parcelSession->last_activity_at->diffInHours(now()) >= 3) {
                $parcelSession->update(['status' => 'expired']);
                return response()->json(['valid' => false, 'message' => 'Session expired'], 401);
            }
            $parcelSession->update(['last_activity_at' => now()]);
            return response()->json([
                'valid' => true, 
                'type' => 'parcel', 
                'session' => $parcelSession,
                'session_id' => $parcelSession->id
            ]);
        }

        return response()->json(['message' => 'SESSION_NOT_FOUND_OR_CLOSED'], 403);
    }

    /**
     * Legacy / existing methods below 
     * (These are untouched and rely on the token/type check)
     */
    public function validateSession(Request $request)
    {
        // Handled entirely by checkSession now, but kept for legacy endpoint compatibility
        return $this->checkSession($request);
    }

    public function getPendingRequests($tableId)
    {
        $pending = QrSession::where('restaurant_table_id', $tableId)
            ->where('join_status', 'pending')
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->get();

        $guests = QrSession::where('restaurant_table_id', $tableId)
            ->where('join_status', 'approved')
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->whereNotNull('host_session_id')
            ->get();

        return response()->json([
            'pending' => $pending,
            'guests' => $guests
        ]);
    }
    
    public function callWaiter(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $type = $request->input('type');
        
        if ($type === 'room') {
            $session = \App\Models\RoomSession::where('session_token', $token)->first();
            $entity = \App\Models\Room::find($session->room_id);
            $number = $entity ? $entity->room_number : '?';
            $customerName = $session->guest_name;
        } elseif ($type === 'parcel') {
            return response()->json(['message' => 'Parcel counters do not support calling a waiter.'], 400);
        } else {
            $session = \App\Models\QrSession::where('session_token', $token)->first();
            $entity = \App\Models\RestaurantTable::find($session->restaurant_table_id);
            $number = $entity ? ($entity->number ?? $entity->table_number) : '?';
            $customerName = $session->customer_name;
        }

        if (!$session) return response()->json(['message' => 'Invalid session token provided.'], 404);

        try {
            event(new \App\Events\WaiterCalled(
                $session->restaurant_id,
                $entity->id,
                $number,
                $customerName
            ));
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Waiter has been notified']);
    }

    public function requestBill(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $type = $request->input('type');

        if ($type === 'room') {
            $session = \App\Models\RoomSession::where('session_token', $token)->first();
            $entity = \App\Models\Room::find($session->room_id);
            $number = $entity ? $entity->room_number : '?';
            $customerName = $session->guest_name;
        } elseif ($type === 'parcel') {
            return response()->json(['message' => 'Parcel orders bill automatically.'], 400);
        } else {
            $session = \App\Models\QrSession::where('session_token', $token)->first();
            $entity = \App\Models\RestaurantTable::find($session->restaurant_table_id);
            $number = $entity ? ($entity->number ?? $entity->table_number) : '?';
            $customerName = $session->customer_name;
        }

        if (!$session) return response()->json(['message' => 'Invalid session.'], 404);

        try {
            event(new \App\Events\BillRequested(
                $session->restaurant_id,
                $entity->id,
                $number,
                $customerName
            ));
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Bill requested successfully.']);
    }

    public function respondToJoin(Request $request, $sessionId)
    {
        $session = QrSession::findOrFail($sessionId);

        $hostToken = $request->bearerToken()
            ?: $request->header('Authorization')
            ?: $request->input('session_token');

        $hostSession = QrSession::where('session_token', $hostToken)
            ->where('is_primary', true)
            ->first();

        if (!$hostSession || $session->host_session_id !== $hostSession->id) {
            return response()->json([
                'message' => 'Unauthorized. Invalid Host Token.'
            ], 403);
        }

        $status = $request->input('action') === 'approve' ? 'approved' : 'rejected';

        $session->update([
            'join_status' => $status,
            'is_active' => $status === 'approved'
        ]);

        \App\Events\JoinRequestResponded::dispatch($session, $status);

        return response()->json(['message' => 'Join request updated']);
    }

    public function leaveSession(Request $request)
    {
        $request->validate(['session_token' => 'required|string']);
        $token = $request->session_token;

        // ── 1. Check Table Session ──
        $tableSession = QrSession::where('session_token', $token)->first();
        if ($tableSession) {
            $tableSession->update(['is_active' => false]);
            if ($tableSession->is_primary) {
                QrSession::where('host_session_id', $tableSession->id)->update(['is_active' => false]);
                $table = \App\Models\RestaurantTable::find($tableSession->restaurant_table_id);
                if ($table) {
                    $table->update(['status' => 'cleaning']);
                    event(new \App\Events\TableStatusUpdated($table->id, 'cleaning', $table->restaurant_id));
                }
            }
            return response()->json(['message' => 'Session ended']);
        }

        // ── 2. Check Parcel Session ──
        $parcelSession = ParcelQrSession::where('session_token', $token)->first();
        if ($parcelSession) {
            $parcelSession->update(['status' => 'completed']);
            return response()->json(['message' => 'Session ended']);
        }

        // ── 3. Check Room Session ──
        $roomSession = RoomSession::where('session_token', $token)->first();
        if ($roomSession) {
            // FIX: Status changed from 'completed' to 'active' to match DB Schema
            $roomSession->update(['status' => 'active']);
            return response()->json(['message' => 'Customer Exits']);
        }

        return response()->json(['message' => 'Session ended']);
    }
}