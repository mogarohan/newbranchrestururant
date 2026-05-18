<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Http\Request;

class RoomQrController extends Controller
{
    public function validateScan($restaurantId, $roomId, $token)
    {
        // 1. Find the room and ensure the token matches the one currently assigned
        $room = Room::where('restaurant_id', $restaurantId)
            ->where('id', $roomId)
            ->where('qr_token', $token) // 👈 Matches the dynamic token generated at check-in
            ->first();

        if (!$room) {
            return response()->json(['message' => 'Invalid or Expired Room QR Code'], 404);
        }

        // 2. Check if there is an active session
        $session = $room->activeSession; // Uses the relationship

        if ($room->status !== 'occupied' || !$session || $session->status !== 'active') {
            return response()->json([
                'message' => 'This room is not currently checked in.',
                'room_number' => $room->room_number
            ], 403);
        }

        // 3. Auto-expire check (Timezone safety)
        if (now()->greaterThan($session->check_out_at)) {
            $session->update(['status' => 'expired']);
            $room->update([
                'status' => 'cleaning', 
                'guest_name' => null, 
                'active_room_session_id' => null,
                'qr_token' => null,
                'qr_path' => null
            ]);
            return response()->json(['message' => 'Your session has expired.'], 403);
        }

        // 👇 THIS DATA MUST BE EXACT FOR THE APP TO SKIP THE NAME SCREEN 👇
        return response()->json([
            'valid' => true,
            'is_room' => true,
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'guest_name' => $session->guest_name,
            'session_token' => $session->session_token, // The App saves this to use in the Menu
            'check_out_at' => $session->check_out_at,
        ]);
    }
}