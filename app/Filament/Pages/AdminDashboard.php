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

        return [
            'totalBranches' => $totalBranches,
            'isRestaurantAdmin' => $isRestaurantAdmin,
            'showBranchesWidget' => $showBranchesWidget,
            'totalStaff' => $totalStaff,
            'totalCategories' => $totalCategories,
            'totalItems' => $totalItems,
            'totalRevenue' => $totalRevenue,
            'todayOrders' => $todayOrders,
            'chartDates' => $formattedDates,
            'chartSeries' => $chartSeries,
        ];
    }
}