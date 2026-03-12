<?php

namespace App\Filament\Pages;

use App\Models\Restaurant;
use App\Models\User;
use App\Models\Order;       // 👈 Isko add karein
use App\Models\QrSession;
use App\Models\Payment; // 👈 Isko add karein
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SuperAdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.pages.super-admin-dashboard';

    protected static ?string $navigationLabel = 'Super Admin Dashboard';

    protected static ?string $title = '';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->is_super_admin === true;
    }

    public function getViewData(): array
    {
        // 1. Existing Restaurant Logic
        $restaurants = Restaurant::all()->map(function ($rest) {
            $activeUsers = User::where('restaurant_id', $rest->id)->count();
            $rest->active_users_count = $activeUsers;
            $rest->remaining_capacity = max(0, $rest->user_limits - $activeUsers);
            $rest->occupancy_percent = $rest->user_limits > 0 ? min(100, ($activeUsers / $rest->user_limits) * 100) : 0;
            return $rest;
        });

        // 2. DYNAMIC GROWTH CALCULATION LAYA GAYA HAI 👇

        // --- RESTAURANT GROWTH ---
        $totalRestaurants = Restaurant::count();
        // Pichle mahine ki shuruwat tak kitne restaurants the
        $oldRestaurants = Restaurant::where('created_at', '<', now()->startOfMonth())->count();

        $restaurantGrowth = $oldRestaurants > 0
            ? round((($totalRestaurants - $oldRestaurants) / $oldRestaurants) * 100, 1)
            : ($totalRestaurants > 0 ? 100 : 0);

        // --- USER GROWTH ---
        $totalUsers = User::count();
        // Pichle mahine ki shuruwat tak kitne users the
        $oldUsers = User::where('created_at', '<', now()->startOfMonth())->count();

        $userGrowth = $oldUsers > 0
            ? round((($totalUsers - $oldUsers) / $oldUsers) * 100, 1)
            : ($totalUsers > 0 ? 100 : 0);


        // --- EXISTING ORDERS & CUSTOMERS ---
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', today())->count();

        $totalCustomers = QrSession::count();
        $todayCustomers = QrSession::whereDate('created_at', today())->count();
        // --- TOTAL REVENUE CALCULATION ---
        // Sirf 'paid' status wale payments ka amount sum karega
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');

        // Aaj ki revenue
        $todayRevenue = Payment::where('status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('amount');
        return [
            'restaurants' => $restaurants,
            'totalRestaurants' => $totalRestaurants,
            'restaurantGrowth' => $restaurantGrowth, // 👈 Blade ke liye pass kiya

            'totalUsers' => $totalUsers,
            'userGrowth' => $userGrowth, // 👈 Blade ke liye pass kiya

            'totalOrders' => number_format($totalOrders),
            'todayOrders' => number_format($todayOrders),
            'totalCustomers' => number_format($totalCustomers),
            'todayCustomers' => number_format($todayCustomers),

            'totalRevenue' => number_format($totalRevenue, 2),
            'todayRevenue' => number_format($todayRevenue, 2),

            'currentDate' => now()->format('M d, Y'),
        ];
    }
}