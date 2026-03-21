<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Category;
use App\Models\Branch;
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
        $isRestaurantAdmin = $user->isRestaurantAdmin();

        $hasBranchesEnabled = $user->restaurant ? $user->restaurant->has_branches : false;
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

        // CATEGORIES COUNT
        $totalCategories = Category::where('restaurant_id', $rid)->count();

        // MENU ITEMS COUNT 
        $totalItems = MenuItem::where('restaurant_id', $rid)->count();

        // TOTAL REVENUE
        $totalRevenue = Payment::whereHas('order', function ($query) use ($rid, $bid, $isBranchAdmin) {
            $query->where('restaurant_id', $rid);
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

        // ---------------------------------------------------
        // CHART DATA: 30-Day Revenue Trend (Line Graph)
        // ---------------------------------------------------
        
        // 1. Generate the last 30 days as an array of dates (Y-m-d)
        $dates = [];
        $startDate = Carbon::today()->subDays(29);
        for ($i = 0; $i < 30; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d');
        }

        $chartSeries = [];

        if ($showBranchesWidget) {
            // MULTI-BRANCH: One line per branch
            $branches = Branch::where('restaurant_id', $rid)->get();
            
            foreach ($branches as $branch) {
                // Get daily revenue for this specific branch
                $dailyRevenue = Payment::whereHas('order', function ($query) use ($rid, $branch) {
                        $query->where('restaurant_id', $rid)
                              ->where('branch_id', $branch->id);
                    })
                    ->where('status', 'paid')
                    ->where('paid_at', '>=', $startDate->startOfDay())
                    ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
                    ->groupBy('date')
                    ->pluck('total', 'date')
                    ->toArray();

                // Map it to the 30-day timeline
                $dataPoints = [];
                foreach ($dates as $date) {
                    $dataPoints[] = $dailyRevenue[$date] ?? 0;
                }

                $chartSeries[] = [
                    'name' => $branch->name,
                    'data' => $dataPoints
                ];
            }
        } else {
            // SINGLE RESTAURANT or BRANCH ADMIN: Just one "Total Revenue" line
            $dailyRevenueQuery = Payment::whereHas('order', function ($query) use ($rid, $bid, $isBranchAdmin) {
                    $query->where('restaurant_id', $rid);
                    if ($isBranchAdmin) {
                        $query->where('branch_id', $bid);
                    }
                })
                ->where('status', 'paid')
                ->where('paid_at', '>=', $startDate->startOfDay())
                ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            $dataPoints = [];
            foreach ($dates as $date) {
                $dataPoints[] = $dailyRevenueQuery[$date] ?? 0;
            }

            $chartSeries[] = [
                'name' => 'Total Revenue',
                'data' => $dataPoints
            ];
        }

        // Format dates nicely for the X-axis (e.g., "12 Oct")
        $formattedDates = array_map(function($date) {
            return Carbon::parse($date)->format('d M');
        }, $dates);

        return [
            'totalBranches' => $totalBranches,
            'isRestaurantAdmin' => $isRestaurantAdmin,
            'showBranchesWidget' => $showBranchesWidget,
            'totalStaff' => $totalStaff,
            'totalCategories' => $totalCategories, 
            'totalItems' => $totalItems,
            'totalRevenue' => $totalRevenue,
            'todayOrders' => $todayOrders,
            
            // Passing the new line chart data to Blade
            'chartDates' => $formattedDates,
            'chartSeries' => $chartSeries,
        ];
    }
}