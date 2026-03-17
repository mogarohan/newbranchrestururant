<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Category;
use App\Models\Branch; // 👈 Naya Import: Branch model add kiya
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.admin-dashboard';
    protected static ?string $title = 'Restaurant Admin Dashboard';

    public static function canAccess(): bool
    {
        // Safely check role name using null safe operator (?->)
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role?->name, ['restaurant_admin', 'branch_admin']);
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $rid = $user->restaurant_id;
        $bid = $user->branch_id;
        $isBranchAdmin = $user->isBranchAdmin();
        $isRestaurantAdmin = $user->isRestaurantAdmin(); // 👈 Naya check add kiya

        /* ---------------------------------------------------
         | 1. STATS DATA (Isolated for Branch Admin)
         |---------------------------------------------------*/

        // TOTAL BRANCHES (Only for Restaurant Admin)
        $hasBranchesEnabled = $user->restaurant ? $user->restaurant->has_branches : false;

        // Widget sirf tab dikhega jab user Restaurant Admin ho AND toggle ON ho
        $showBranchesWidget = $isRestaurantAdmin && $hasBranchesEnabled;

        // TOTAL BRANCHES 
        $totalBranches = 0;
        if ($showBranchesWidget) {
            $totalBranches = Branch::where('restaurant_id', $rid)->count();
        }
        // STAFF COUNT
        $staffQuery = User::where('restaurant_id', $rid);
        if ($isBranchAdmin) {
            $staffQuery->where('branch_id', $bid);
        }
        $totalStaff = $staffQuery->count();

        // MENU ITEMS COUNT 
        $itemQuery = MenuItem::where('restaurant_id', $rid);
        /* if ($isBranchAdmin) {
            $itemQuery->where('branch_id', $bid); 
        } */
        $totalItems = $itemQuery->count();

        // TOTAL REVENUE
        $totalRevenue = Payment::whereHas('order', function ($query) use ($rid, $bid, $isBranchAdmin) {
            $query->where('restaurant_id', $rid);
            // Sirf uski branch ke orders ka paisa count hoga
            if ($isBranchAdmin) {
                $query->where('branch_id', $bid);
            }
        })->where('status', 'paid')->sum('amount');

        // TODAY'S ORDERS
        $orderQuery = Order::where('restaurant_id', $rid)
            ->whereDate('created_at', Carbon::today());
        if ($isBranchAdmin) {
            $orderQuery->where('branch_id', $bid);
        }
        $todayOrders = $orderQuery->count();

        /* ---------------------------------------------------
         | 2. CHART DATA: Orders Volume (Isolated)
         |---------------------------------------------------*/

        $hourlyQuery = Order::where('restaurant_id', $rid)
            ->where('created_at', '>=', Carbon::now()->subDay());

        if ($isBranchAdmin) {
            $hourlyQuery->where('branch_id', $bid);
        }

        $hourlyData = $hourlyQuery
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        // Fill missing hours with 0 to ensure ApexCharts receives a consistent array
        $hourlyOrders = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyOrders[] = $hourlyData[$i] ?? 0;
        }

        /* ---------------------------------------------------
         | 3. CHART DATA: Top Categories
         |---------------------------------------------------*/

        $categoryQuery = Category::where('restaurant_id', $rid);

        $topCategories = $categoryQuery
            ->withCount([
                'menuItems' => function ($query) use ($isBranchAdmin, $bid) {
                    // Agar menuItems branch-specific hain, toh count bhi wahi filter hoga
                    /* if ($isBranchAdmin) {
                        $query->where('branch_id', $bid);
                    } */
                }
            ])
            ->orderByDesc('menu_items_count')
            ->take(6) // Showing top 6 categories
            ->get();

        return [
            'totalBranches' => $totalBranches,       // 👈 Data pass kiya blade me
            'isRestaurantAdmin' => $isRestaurantAdmin,
            'showBranchesWidget' => $showBranchesWidget,// 👈 Blade me IF condition lagane ke liye
            'totalStaff' => $totalStaff,
            'totalItems' => $totalItems,
            'totalRevenue' => $totalRevenue,
            'todayOrders' => $todayOrders,
            'hourlyOrders' => $hourlyOrders,
            'categoryNames' => $topCategories->pluck('name')->toArray(),
            'categoryCounts' => $topCategories->pluck('menu_items_count')->toArray(),
            'currentDate' => Carbon::now()->format('d M, Y'),
        ];
    }
}