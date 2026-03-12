<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use App\Models\Restaurant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StaffStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $baseQuery = User::query();

        // Restaurant isolation and fetching limits
        $userLimit = 0;
        if (!$user->isSuperAdmin()) {
            $baseQuery->where('restaurant_id', $user->restaurant_id);
            $userLimit = $user->restaurant?->user_limits ?? 0;
        }

        $totalStaff = (clone $baseQuery)->count();
        $activeNow = (clone $baseQuery)->where('is_active', true)->count();

        // 🎨 EXACT MATCH WITH SA-STAT-CARD
        $cardBaseStyle = 'background-color: transparent !important; border: 1px solid rgba(156, 163, 175, 0.3) !important; border-radius: 12px !important; padding: 1.5rem !important; position: relative !important; overflow: hidden !important; box-shadow: none !important;';

        // Label Style: Colored, Uppercase, Bold (Matches .sa-stat-label)
        $labelBaseStyle = 'font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; display: block !important; margin-bottom: 1rem !important;';
        
        // Value Style: Large, Bold (Matches .sa-stat-value)
        $numberBaseStyle = 'font-size: 2.25rem !important; font-weight: 800 !important; line-height: 1 !important; display: block !important; margin-bottom: 0.5rem !important;';

        // 🖼️ CUSTOM SVG ICONS (Matches .sa-stat-icon)
        // Orange Icon
        $iconStaff = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(234, 88, 12, 0.1); color: #ea580c; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></div>';
        
        // Blue Icon
        $iconActive = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>';
        
        // Green Icon
        $iconLimit = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';

        return [
            // Widget 1: TOTAL MANAGERS (Orange Label, Green Desc)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ea580c !important;'>Total Managers</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalStaff}</span>" . $iconStaff)
            )
            ->description(new HtmlString('<span style="color: #10b981 !important; font-size: 0.75rem; font-weight: 500;">↑ Managers created by you</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

            // Widget 2: ACTIVE NOW (Blue Label, Blue Desc)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #3b82f6 !important;'>Total Users</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$activeNow}</span>" . $iconActive)
            )
            ->description(new HtmlString('<span style="color: #3b82f6 !important; font-size: 0.75rem; font-weight: 500;">↑ Users created by you</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

            // Widget 3: TOTAL LIMIT (Green Label, Green Desc)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #10b981 !important;'>Total Limit</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$userLimit}</span>" . $iconLimit)
            )
            ->description(new HtmlString("<span style='color: #10b981 !important; font-size: 0.75rem; font-weight: 500;'>• You've used {$totalStaff} of {$userLimit}</span>"))
            ->extraAttributes(['style' => $cardBaseStyle]),
        ];
    }
}