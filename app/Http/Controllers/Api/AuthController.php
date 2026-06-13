<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StaffAttendance;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $today = Carbon::today()->toDateString();

            // Check Attendance for everyone
            $attendance = StaffAttendance::where('staff_id', $user->id)
                ->where('date', $today)
                ->first();

            // Agar entry nahi hai, ya status pending/absent hai, toh LOGIN BLOCK karo
            if (!$attendance || !in_array($attendance->status, ['present', 'half_day'])) {
                $user->tokens()->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied 🛑 : Aaj aapki attendance "Present" mark nahi ki gayi hai.'
                ], 403);
            }

            // LOGIN SUCCESSFUL
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }
}