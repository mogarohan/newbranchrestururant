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
    // 👇 FIX: Sirf 'int' return type lagaya aur 5 return kiya
    protected function getColumns(): int
    {
        return 5;
    }

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

        // --- SVG ICONS --- //
        $iconRestAdmin = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 100%; height: 100%;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>';
        $iconBranchAdmin = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 100%; height: 100%;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>';
        $iconManager = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 100%; height: 100%;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>';
        $iconChef = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 100%; height: 100%;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" /></svg>';
        $iconWaiter = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 100%; height: 100%;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>';

        $createUrl = UserResource::getUrl('create');

        // CSS INJECTED FOR THE IMAGE EXACT LOOK AND ALTERNATIVE COLORS
        $customCss = "
        <style>
            /* 👇 CARDS KO FORCE KARKE EK HI LINE (5 COLUMNS) MEIN SET KAREGA 👇 */
            .fi-wi-stats-overview-stats-ctn {
                display: grid !important;
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
                gap: 1rem !important; /* Proper spacing between cards */
                align-items: stretch !important;
            }

            @media (max-width: 1280px) {
                .fi-wi-stats-overview-stats-ctn { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
            }

            @media (max-width: 768px) {
                .fi-wi-stats-overview-stats-ctn { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
            }

            /* Light Theme Basics */
            .sa-stat-card {
                background-color: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 1rem !important;
                padding: 1.5rem !important;
                position: relative !important;
                overflow: hidden !important;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03) !important;
                transition: transform 0.2s, box-shadow 0.2s !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                min-height: 150px !important;
                height: 100% !important;
                width: 215px !important;
                z-index: 1 !important;
            }
            .sa-stat-card:hover {
                transform: translateY(-3px) !important;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08) !important;
            }

            /* 👇 DARK THEME (EXACT MATCH TO 2ND IMAGE) 👇 */
            .dark .sa-stat-card {
                background-color: #0b0f19 !important; /* Very dark/transparent feel */
                border: 1px solid rgba(255, 255, 255, 0.08) !important; /* Subtle thin border */
                box-shadow: none !important;
            }
            .dark .sa-stat-card:hover {
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
            }

            /* Colors & Layout inside Card */
            .card-top-row {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
            }

            .main-icon-wrapper {
                width: 42px !important;
                height: 42px !important;
                border-radius: 12px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .main-icon-wrapper svg {
                width: 22px !important;
                height: 22px !important;
            }

            .action-btn {
                width: 32px !important;
                height: 32px !important;
                border-radius: 8px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-decoration: none !important;
                transition: all 0.2s !important;
            }

            /* 🟠 ORANGE CARD LOGIC */
            .card-orange .main-icon-wrapper, .card-orange .action-btn { 
                background-color: rgba(244, 125, 32, 0.1) !important; 
                color: #F47D20 !important; 
            }
            .dark .card-orange .main-icon-wrapper, .dark .card-orange .action-btn {
                background-color: rgba(244, 125, 32, 0.06) !important; /* Dark mode tint */
            }
            .card-orange .action-btn:hover { background-color: #F47D20 !important; color: white !important; }

            /* 🔵 BLUE CARD LOGIC */
            .card-blue .main-icon-wrapper, .card-blue .action-btn { 
                background-color: rgba(59, 130, 246, 0.1) !important; 
                color: #3B82F6 !important; 
            }
            .dark .card-blue .main-icon-wrapper, .dark .card-blue .action-btn {
                background-color: rgba(59, 130, 246, 0.06) !important; /* Dark mode tint */
            }
            .card-blue .action-btn:hover { background-color: #3B82F6 !important; color: white !important; }

            /* Watermark Icon Logic */
            .card-orange .watermark-icon { color: #F47D20 !important; }
            .card-blue .watermark-icon { color: #3B82F6 !important; }
            .watermark-icon {
                position: absolute !important;
                right: -10% !important;
                bottom: -15% !important;
                width: 140px !important;
                height: 140px !important;
                opacity: 0.06 !important;
                transform: rotate(-15deg) !important;
                z-index: -1 !important;
                pointer-events: none !important;
            }
            .dark .watermark-icon {
                opacity: 0.04 !important; /* Very subtle in dark mode */
            }

            /* Labels and Values */
            .sa-stat-label {
                font-size: 0.75rem !important;
                font-weight: 800 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em !important;
                display: block !important;
                margin-top: 1.5rem !important;
                margin-bottom: 0.25rem !important;
                color: #64748b !important; /* Slate gray */
            }
            
            .sa-stat-value {
                font-family: 'Poppins', sans-serif !important;
                font-size: 2.25rem !important;
                font-weight: 700 !important;
                color: #0f172a !important; /* Dark Navy */
                line-height: 1 !important;
                display: block !important;
            }

            /* Dark Mode Labels and Values (Image 2 style) */
            .dark .sa-stat-label { color: #9ca3af !important; } /* Gray Text */
            .dark .sa-stat-value { color: #ffffff !important; } /* Big White Numbers */
        </style>
        ";

        // --- HELPER FUNCTION TO BUILD EXACT SA-STAT-CARD MATCH --- //
        $buildCard = function ($title, $count, $iconSvg, $roleQuery, $isSolidBlue = false, $isFirst = false) use ($createUrl, $customCss) {

            // 👇 MAGIC HERE: Static variable to alternate colors without changing bottom logic 👇
            static $colorIndex = 0;
            $altClass = ($colorIndex % 2 === 0) ? 'card-orange' : 'card-blue';
            $colorIndex++;

            $addUrl = $createUrl . "?role={$roleQuery}";
            $plusSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 16px; height: 16px; font-weight: bold;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>';

            // Sirf pehle card par CSS load karenge taaki repeat na ho
            $styleBlock = $isFirst ? $customCss : '';

            $html = "
                {$styleBlock}
                <div class='sa-scope' style='width: 100%; height: 100%;'>
                    <div class='sa-stat-card {$altClass}'>
                        <div class='watermark-icon'>
                            {$iconSvg}
                        </div>
                        <div class='card-top-row' style='width: 100%;'>
                            <div class='main-icon-wrapper'>
                                {$iconSvg}
                            </div>
                            <a href='{$addUrl}' class='action-btn' title='Add {$title}'>
                                {$plusSvg}
                            </a>
                        </div>
                        <div>
                            <span class='sa-stat-label'>{$title}</span>
                            <span class='sa-stat-value'>{$count}</span>
                        </div>
                    </div>
                </div>
            ";

            return Stat::make(new HtmlString($html), '')
                ->extraAttributes([
                    'style' => 'padding: 0 !important; background: transparent !important; border: none !important; box-shadow: none !important;'
                ]);
        };

        // --- BUILD STATS DYNAMICALLY --- //
        $stats = [];
        $isFirst = true;

        if ($user->isSuperAdmin()) {
            $stats[] = $buildCard('Rest. Admins', $totalRestAdmins, $iconRestAdmin, 'restaurant_admin', false, $isFirst);
            $isFirst = false; // CSS load ho gaya
        }

        if ($user->isSuperAdmin() || $user->isRestaurantAdmin()) {
            $stats[] = $buildCard('Branch Admins', $totalBranchAdmins, $iconBranchAdmin, 'branch_admin', false, $isFirst);
            $isFirst = false;
        }

        $stats[] = $buildCard('Total Managers', $totalManagers, $iconManager, 'manager', false, $isFirst);
        $isFirst = false;

        $stats[] = $buildCard('Total Chefs', $totalChefs, $iconChef, 'chef', false, $isFirst);
        $isFirst = false;

        $stats[] = $buildCard('Total Waiters', $totalWaiters, $iconWaiter, 'waiter', true, $isFirst);

        return $stats;
    }
}