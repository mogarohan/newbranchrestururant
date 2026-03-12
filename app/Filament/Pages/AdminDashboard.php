<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.admin-dashboard';
    protected static ?string $title = 'Restaurant Admin Dashboard';

    protected function getViewData(): array
    {
        $rid = Auth::user()->restaurant_id;

        // 1. Stats Data
        $totalStaff = User::where('restaurant_id', $rid)->count();

        $totalItems = MenuItem::where('restaurant_id', $rid)->count();

        $totalRevenue = Payment::whereHas('order', function ($query) use ($rid) {
            $query->where('restaurant_id', $rid);
        })->where('status', 'paid')->sum('amount');

        $todayOrders = Order::where('restaurant_id', $rid)
            ->whereDate('created_at', Carbon::today())
            ->count();

        // 2. Chart Data: Orders Volume (Last 24 Hours)
        // Fetch raw data from DB
        $hourlyData = Order::where('restaurant_id', $rid)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        // Fill missing hours with 0 to ensure ApexCharts receives a consistent array
        $hourlyOrders = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyOrders[] = $hourlyData[$i] ?? 0;
        }

        // 3. Chart Data: Top Categories
        // Added sorting and limit to make the Donut Chart look clean
        $topCategories = Category::where('restaurant_id', $rid)
            ->withCount('menuItems')
            ->orderByDesc('menu_items_count')
            ->take(6) // Showing top 6 categories
            ->get();

        return [
            'totalStaff' => $totalStaff,
            'totalItems' => $totalItems,
            'totalRevenue' => $totalRevenue, // Will format this in blade using number_format()
            'todayOrders' => $todayOrders,
            'hourlyOrders' => $hourlyOrders,
            'categoryNames' => $topCategories->pluck('name')->toArray(),
            'categoryCounts' => $topCategories->pluck('menu_items_count')->toArray(),
            'currentDate' => Carbon::now()->format('d M, Y'),
        ];
    }
}