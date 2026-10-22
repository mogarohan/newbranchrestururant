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
use Filament\Actions; // 👈 Import Filament Actions
use Filament\Forms;   // 👈 Import Filament Forms

class AdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.admin-dashboard';
    protected static ?string $navigationLabel = 'Admin Dashboard';
    protected static ?string $title = 'Admin Dashboard Control';


    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role?->name, ['restaurant_admin', 'branch_admin']);
    }

    // 👇 ADD THESE ACTIONS TO THE DASHBOARD 👇
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         // 1. ADD CATEGORY SLIDE-OVER
    //         Actions\Action::make('addCategory')
    //             ->label('Add Category')
    //             ->model(Category::class)

    //             ->form([
    //                 Forms\Components\Hidden::make('restaurant_id')->default(auth()->user()->restaurant_id),
    //                 Forms\Components\Hidden::make('branch_id')->default(auth()->user()->branch_id),
    //                 Forms\Components\TextInput::make('name')->required()->maxLength(100),
    //                 Forms\Components\Toggle::make('is_active')->default(true)->label('Active'),
    //             ])
    //             ->action(function (array $data) {
    //                 Category::create($data);
    //                 \Filament\Notifications\Notification::make()->title('Category Added')->success()->send();
    //             })
    //             // Only Restaurant Admins can see/use this
    //             ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),

    //         // 2. ADD ITEM SLIDE-OVER (Requires MenuItem form fields)
    //         Actions\CreateAction::make('addItem')
    //             ->label('Add Item')
    //             ->model(MenuItem::class)

    //             // We use your exact form from MenuResource
    //             ->form(\App\Filament\Resources\MenuResource::form(new \Filament\Forms\Form($this))->getComponents())
    //             ->action(function (array $data) {
    //                 $data['restaurant_id'] = auth()->user()->restaurant_id;
    //                 MenuItem::create($data);
    //                 \Filament\Notifications\Notification::make()->title('Item Added')->success()->send();
    //             })
    //             // Only Restaurant Admins can see/use this
    //             ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
    //     ];
    // }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $rid = $user->restaurant_id;
        $bid = $user->branch_id;
        $isBranchAdmin = $user->isBranchAdmin();
        $isRestaurantAdmin = $user->isRestaurantAdmin();
        $restaurant = $user->restaurant;

        $hasBranchesEnabled = $restaurant ? (bool) $restaurant->has_branches : false;
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
        $totalItems = MenuItem::withoutGlobalScopes()->where('restaurant_id', $rid)->count();

        // REVENUE METRICS
        $basePaymentQuery = Payment::whereHas('order', function ($query) use ($rid, $bid, $isBranchAdmin) {
            $query->where('restaurant_id', $rid);
            if ($isBranchAdmin) {
                $query->where('branch_id', $bid);
            }
        })->where('status', 'paid');

        $totalRevenue = (clone $basePaymentQuery)->sum('amount');
        $todayRevenue = (clone $basePaymentQuery)->whereDate('paid_at', Carbon::today())->sum('amount');
        $monthRevenue = (clone $basePaymentQuery)->where('paid_at', '>=', Carbon::now()->startOfMonth())->sum('amount');

        // ORDERS METRICS
        $baseOrderQuery = Order::where('restaurant_id', $rid);
        if ($isBranchAdmin) {
            $baseOrderQuery->where('branch_id', $bid);
        }
        $totalOrdersCount = (clone $baseOrderQuery)->count();
        $todayOrders = (clone $baseOrderQuery)->whereDate('created_at', Carbon::today())->count();
        $monthOrders = (clone $baseOrderQuery)->where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        // TABLES METRICS
        $baseTableQuery = \App\Models\RestaurantTable::where('restaurant_id', $rid);
        if ($isBranchAdmin) {
            $baseTableQuery->where('branch_id', $bid);
        }
        $totalTablesCount = (clone $baseTableQuery)->count();
        $activeTablesCount = (clone $baseTableQuery)->whereIn('status', ['occupied', 'bill_pending'])->count();

        // ---------------------------------------------------
        // MULTI-BRANCH BREAKDOWN STATS
        // ---------------------------------------------------
        $branchStats = [];
        $donutSeries = [];
        $donutLabels = [];

        if ($showBranchesWidget) {
            $branches = Branch::where('restaurant_id', $rid)->get();

            foreach ($branches as $branch) {
                $bTodayRev = Payment::whereHas('order', fn($q) => $q->where('restaurant_id', $rid)->where('branch_id', $branch->id))
                    ->where('status', 'paid')->whereDate('paid_at', Carbon::today())->sum('amount');

                $bMonthRev = Payment::whereHas('order', fn($q) => $q->where('restaurant_id', $rid)->where('branch_id', $branch->id))
                    ->where('status', 'paid')->where('paid_at', '>=', Carbon::now()->startOfMonth())->sum('amount');

                $bTotalRev = Payment::whereHas('order', fn($q) => $q->where('restaurant_id', $rid)->where('branch_id', $branch->id))
                    ->where('status', 'paid')->sum('amount');

                $bTodayOrders = Order::where('restaurant_id', $rid)->where('branch_id', $branch->id)->whereDate('created_at', Carbon::today())->count();
                $bTotalOrders = Order::where('restaurant_id', $rid)->where('branch_id', $branch->id)->count();

                $bTablesCount = \App\Models\RestaurantTable::where('restaurant_id', $rid)->where('branch_id', $branch->id)->count();
                $bActiveTables = \App\Models\RestaurantTable::where('restaurant_id', $rid)->where('branch_id', $branch->id)->whereIn('status', ['occupied', 'bill_pending'])->count();
                $bStaffCount = User::where('restaurant_id', $rid)->where('branch_id', $branch->id)->count();
                $bItemsCount = MenuItem::withoutGlobalScopes()->where('restaurant_id', $rid)->where('branch_id', $branch->id)->count();

                $branchStats[] = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'phone' => $branch->phone ?? '-',
                    'address' => $branch->address ?? '-',
                    'logo_path' => $branch->logo_path,
                    'is_active' => (bool) $branch->is_active,
                    'inherit_menu' => (bool) $branch->inherit_restaurant_menu,
                    'today_revenue' => $bTodayRev,
                    'month_revenue' => $bMonthRev,
                    'total_revenue' => $bTotalRev,
                    'today_orders' => $bTodayOrders,
                    'total_orders' => $bTotalOrders,
                    'tables_count' => $bTablesCount,
                    'active_tables' => $bActiveTables,
                    'staff_count' => $bStaffCount,
                    'items_count' => $bItemsCount,
                ];

                $donutSeries[] = (float) $bTotalRev;
                $donutLabels[] = $branch->name;
            }

            // Central outlet revenue share
            $centralRev = Payment::whereHas('order', fn($q) => $q->where('restaurant_id', $rid)->whereNull('branch_id'))
                ->where('status', 'paid')->sum('amount');
            if ($centralRev > 0) {
                $donutSeries[] = (float) $centralRev;
                $donutLabels[] = 'Main Restaurant (Central)';
            }
        }

        // ---------------------------------------------------
        // CHART DATA: 30-Day Revenue Trend (Line Graph)
        // ---------------------------------------------------
        $dates = [];
        $startDate = Carbon::today()->subDays(29);
        for ($i = 0; $i < 30; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d');
        }

        $chartSeries = [];

        if ($showBranchesWidget) {
            $branches = Branch::where('restaurant_id', $rid)->get();

            foreach ($branches as $branch) {
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

        $formattedDates = array_map(function ($date) {
            return Carbon::parse($date)->format('d M');
        }, $dates);

        // ---------------------------------------------------
        // RECENT ORDERS LIVE FEED (Latest 8)
        // ---------------------------------------------------
        $recentOrders = Order::where('restaurant_id', $rid)
            ->when($isBranchAdmin, fn($q) => $q->where('branch_id', $bid))
            ->with(['items', 'table', 'branch'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // ---------------------------------------------------
        // TOP SELLING MENU ITEMS (Top 5)
        // ---------------------------------------------------
        $topSellingItems = \App\Models\OrderItem::whereHas('order', function ($q) use ($rid, $bid, $isBranchAdmin) {
            $q->where('restaurant_id', $rid);
            if ($isBranchAdmin) {
                $q->where('branch_id', $bid);
            }
        })
            ->select('item_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * unit_price) as total_sales'))
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'restaurant' => $restaurant,
            'totalBranches' => $totalBranches,
            'isRestaurantAdmin' => $isRestaurantAdmin,
            'isBranchAdmin' => $isBranchAdmin,
            'showBranchesWidget' => $showBranchesWidget,
            'totalStaff' => $totalStaff,
            'totalCategories' => $totalCategories,
            'totalItems' => $totalItems,
            'totalRevenue' => $totalRevenue,
            'todayRevenue' => $todayRevenue,
            'monthRevenue' => $monthRevenue,
            'totalOrdersCount' => $totalOrdersCount,
            'todayOrders' => $todayOrders,
            'monthOrders' => $monthOrders,
            'totalTablesCount' => $totalTablesCount,
            'activeTablesCount' => $activeTablesCount,
            'branchStats' => $branchStats,
            'donutSeries' => $donutSeries,
            'donutLabels' => $donutLabels,
            'chartDates' => $formattedDates,
            'chartSeries' => $chartSeries,
            'recentOrders' => $recentOrders,
            'topSellingItems' => $topSellingItems,
        ];
    }
}