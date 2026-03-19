<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use App\Models\Role;
use App\Filament\Resources\UserResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StaffStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $baseQuery = User::query();

        // --- ISOLATION LOGIC --- //
        if (!$user->isSuperAdmin()) {
            if ($user->isRestaurantAdmin()) {
                $baseQuery->where('restaurant_id', $user->restaurant_id);
            } elseif ($user->isBranchAdmin() || $user->isManager()) {
                $baseQuery->where('restaurant_id', $user->restaurant_id)
                    ->where('branch_id', $user->branch_id);
            }
        }

        // --- GET ROLE IDs --- //
        $restAdminRoleId = Role::where('name', 'restaurant_admin')->value('id');
        $branchAdminRoleId = Role::where('name', 'branch_admin')->value('id');
        $managerRoleId = Role::where('name', 'manager')->value('id');
        $chefRoleId = Role::where('name', 'chef')->value('id');
        $waiterRoleId = Role::where('name', 'waiter')->value('id');

        // --- CALCULATION --- //
        $totalRestAdmins = (clone $baseQuery)->where('role_id', $restAdminRoleId)->count();
        $totalBranchAdmins = (clone $baseQuery)->where('role_id', $branchAdminRoleId)->count();
        $totalManagers = (clone $baseQuery)->where('role_id', $managerRoleId)->count();
        $totalChefs = (clone $baseQuery)->where('role_id', $chefRoleId)->count();
        $totalWaiters = (clone $baseQuery)->where('role_id', $waiterRoleId)->count();

        // --- STYLES --- //
        $cardBaseStyle = 'background-color: transparent !important; border: 1px solid rgba(156, 163, 175, 0.3) !important; border-radius: 12px !important; padding: 1.5rem !important; position: relative !important; overflow: hidden !important; box-shadow: none !important;';
        $labelBaseStyle = 'font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; display: block !important; margin-bottom: 1rem !important;';
        $numberBaseStyle = 'font-size: 2.25rem !important; font-weight: 800 !important; line-height: 1 !important; display: block !important; margin-bottom: 0.5rem !important;';

        // --- CUSTOM BUTTONS --- //
        $createUrl = UserResource::getUrl('create');
        
        $addRestAdminBtn = "<a href='{$createUrl}?role=restaurant_admin' style='display: inline-block; margin-top: 10px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: rgba(239, 68, 68, 0.1); color: #ef4444; text-decoration: none; border: 1px solid rgba(239, 68, 68, 0.2);'>+ Add Rest. Admin</a>";
        $addBranchAdminBtn = "<a href='{$createUrl}?role=branch_admin' style='display: inline-block; margin-top: 10px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6; text-decoration: none; border: 1px solid rgba(139, 92, 246, 0.2);'>+ Add Branch Admin</a>";
        $addManagerBtn = "<a href='{$createUrl}?role=manager' style='display: inline-block; margin-top: 10px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: rgba(234, 88, 12, 0.1); color: #ea580c; text-decoration: none; border: 1px solid rgba(234, 88, 12, 0.2);'>+ Add Manager</a>";
        $addChefBtn = "<a href='{$createUrl}?role=chef' style='display: inline-block; margin-top: 10px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: rgba(234, 179, 8, 0.1); color: #eab308; text-decoration: none; border: 1px solid rgba(234, 179, 8, 0.2);'>+ Add Chef</a>";
        $addWaiterBtn = "<a href='{$createUrl}?role=waiter' style='display: inline-block; margin-top: 10px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; text-decoration: none; border: 1px solid rgba(59, 130, 246, 0.2);'>+ Add Waiter</a>";

        // --- ICONS --- //
        $iconRestAdmin = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg></div>'; // Red Building
        $iconBranchAdmin = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg></div>'; // Purple Storefront
        $iconManager = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(234, 88, 12, 0.1); color: #ea580c; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></div>';
        $iconChef = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(234, 179, 8, 0.1); color: #eab308; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" /></svg></div>';
        $iconWaiter = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>';

        // --- BUILD STATS DYNAMICALLY --- //
        $stats = [];

        // 1. Restaurant Admin Widget (SUPER ADMIN ONLY)
        if ($user->isSuperAdmin()) {
            $stats[] = Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ef4444 !important;'>Rest. Admins</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalRestAdmins}</span>" . $iconRestAdmin)
            )
                ->description(new HtmlString($addRestAdminBtn))
                ->extraAttributes(['style' => $cardBaseStyle]);
        }

        // 2. Branch Admin Widget (SUPER ADMIN & RESTAURANT ADMIN ONLY)
        if ($user->isSuperAdmin() || $user->isRestaurantAdmin()) {
            $stats[] = Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #8b5cf6 !important;'>Branch Admins</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalBranchAdmins}</span>" . $iconBranchAdmin)
            )
                ->description(new HtmlString($addBranchAdminBtn))
                ->extraAttributes(['style' => $cardBaseStyle]);
        }

        // 3. Manager Widget (EVERYONE)
        $stats[] = Stat::make(
            new HtmlString("<span style='{$labelBaseStyle} color: #ea580c !important;'>Total Managers</span>"),
            new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalManagers}</span>" . $iconManager)
        )
            ->description(new HtmlString($addManagerBtn))
            ->extraAttributes(['style' => $cardBaseStyle]);

        // 4. Chef Widget (EVERYONE)
        $stats[] = Stat::make(
            new HtmlString("<span style='{$labelBaseStyle} color: #eab308 !important;'>Total Chefs</span>"),
            new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalChefs}</span>" . $iconChef)
        )
            ->description(new HtmlString($addChefBtn))
            ->extraAttributes(['style' => $cardBaseStyle]);

        // 5. Waiter Widget (EVERYONE)
        $stats[] = Stat::make(
            new HtmlString("<span style='{$labelBaseStyle} color: #3b82f6 !important;'>Total Waiters</span>"),
            new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalWaiters}</span>" . $iconWaiter)
        )
            ->description(new HtmlString($addWaiterBtn))
            ->extraAttributes(['style' => $cardBaseStyle]);

        return $stats;
    }
}