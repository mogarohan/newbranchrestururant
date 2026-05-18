<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use App\Models\QrSession;
use Illuminate\Support\Str;

class QrSessionController extends Controller
{
    public function validateQr(Restaurant $restaurant, RestaurantTable $table, $token)
    {
        // Eloquent automatically returns 404 if the table is soft-deleted
        //$table = RestaurantTable::findOrFail($tableId);

        abort_unless($table->restaurant_id === $restaurant->id, 404);
        abort_unless($table->qr_token === $token, 403, 'Invalid QR Token');
        abort_unless($table->is_active, 403, 'This table is currently inactive or unavailable.');


        // 1. Find the primary host
        $host = QrSession::where('restaurant_table_id', $table->id)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        // 2. Count occupancy
        $currentOccupancy = QrSession::where('restaurant_table_id', $table->id)
            ->whereIn('join_status', ['active', 'approved'])
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->count();

        $isFull = $currentOccupancy >= $table->seating_capacity;
        
        // 👇 NEW: Check if the table is explicitly reserved in the DB 👇
        $isReserved = $table->status === 'reserved';

        return response()->json([
            'has_active_host' => (bool) $host,
            'host_name' => $host ? $host->customer_name : null,
            'is_full' => $isFull,
            'is_reserved' => $isReserved, // 👈 PASS THIS FLAG TO THE APP
            'capacity' => $table->seating_capacity,
            'occupancy' => $currentOccupancy,
            'table_number' => $table->table_number ?? $table->number ?? $table->id,
        ]);
    }

    public function startSession(Request $request, Restaurant $restaurant, RestaurantTable $table, string $token)
    {
        abort_unless($table->restaurant_id === $restaurant->id, 404);
        abort_unless($table->qr_token === $token, 403);

        $customerName = $request->input('customer_name');
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
                'restaurant_id' => $restaurant->id,
                'branch_id' => $table->branch_id,
                'restaurant_table_id' => $table->id,
                'customer_name' => $customerName,
                'session_token' => \Illuminate\Support\Str::uuid(),
                'is_primary' => true,
                'join_status' => 'active',
                'is_active' => true,
                'host_session_id' => null,
                'expires_at' => now()->addHours(3),
            ]);
        }

        $guestSession = QrSession::create([
            'restaurant_id' => $restaurant->id,
            'branch_id' => $table->branch_id,
            'restaurant_table_id' => $table->id,
            'customer_name' => $customerName,
            'session_token' => \Illuminate\Support\Str::uuid(),
            'is_primary' => false,
            'join_status' => 'pending',
            'is_active' => true,
            'host_session_id' => $existingHost->id,
            'expires_at' => now()->addHours(3),
        ]);

        \App\Events\GuestJoinRequested::dispatch($guestSession);

        return response()->json($guestSession, 201);
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
        $isRoom = $request->input('type') === 'room';
        
        if ($isRoom) {
            $session = \App\Models\RoomSession::where('session_token', $token)->first();
            $entity = \App\Models\Room::find($session->room_id);
            $number = $entity ? $entity->room_number : '?';
        } else {
            $session = \App\Models\QrSession::where('session_token', $token)->first();
            $entity = \App\Models\RestaurantTable::find($session->restaurant_table_id);
            $number = $entity ? ($entity->number ?? $entity->table_number) : '?';
        }

        if (!$session) return response()->json(['message' => 'Invalid session token provided.'], 404);

        try {
            event(new \App\Events\WaiterCalled(
                $session->restaurant_id,
                $entity->id,
                $number,
                $isRoom ? $session->guest_name : $session->customer_name
            ));
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Waiter has been notified']);
    }

    public function requestBill(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('session_token');
        $isRoom = $request->input('type') === 'room';

        if ($isRoom) {
            $session = \App\Models\RoomSession::where('session_token', $token)->first();
            $entity = \App\Models\Room::find($session->room_id);
            $number = $entity ? $entity->room_number : '?';
        } else {
            $session = \App\Models\QrSession::where('session_token', $token)->first();
            $entity = \App\Models\RestaurantTable::find($session->restaurant_table_id);
            $number = $entity ? ($entity->number ?? $entity->table_number) : '?';
        }

        if (!$session) return response()->json(['message' => 'Invalid session.'], 404);

        try {
            event(new \App\Events\BillRequested(
                $session->restaurant_id,
                $entity->id,
                $number,
                $isRoom ? $session->guest_name : $session->customer_name
            ));
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Bill requested successfully.']);
    }
    
    // ... keep the rest of the file

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
        $session = QrSession::where('session_token', $request->session_token)->first();

        if ($session) {
            $session->update(['is_active' => false]);

            if ($session->is_primary) {
                QrSession::where('host_session_id', $session->id)->update(['is_active' => false]);

                $table = \App\Models\RestaurantTable::find($session->restaurant_table_id);

                if ($table) {
                    $table->update(['status' => 'cleaning']);
                    event(new \App\Events\TableStatusUpdated(
                        $table->id,
                        'cleaning',
                        $table->restaurant_id
                    ));
                }
            }
        }

        return response()->json(['message' => 'Session ended']);
    }

    public function validateSession(\Illuminate\Http\Request $request)
    {
        // Safely extract token from Bearer header or JSON body
        $token = $request->bearerToken();
        
        if (!$token && $request->hasHeader('Authorization')) {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
        }

        if (!$token) {
            $token = $request->query('session_token');
        }

        if (!$token) {
            return response()->json(['message' => 'TOKEN_MISSING'], 401);
        }

        $session = \App\Models\QrSession::where('session_token', $token)->first();

        if (!$session) {
            return response()->json(['message' => 'SESSION_NOT_FOUND'], 404);
        }

        if (!$session->is_active || $session->status === 'closed') {
            return response()->json(['message' => 'SESSION_CLOSED'], 403);
        }

        return response()->json([
            'valid' => true,
            'session_id' => $session->id,
            'join_status' => $session->join_status,
        ]);
    }
}