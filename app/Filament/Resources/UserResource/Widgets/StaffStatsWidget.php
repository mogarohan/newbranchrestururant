<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString; // 👈 Ise top par add karna zaroori hai

class StaffStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $baseQuery = User::query();
        
        // Respect restaurant isolation for stats
        if (!auth()->user()->isSuperAdmin()) {
            $baseQuery->where('restaurant_id', auth()->user()->restaurant_id);
        }

        $totalStaff = (clone $baseQuery)->count();
        $activeNow = (clone $baseQuery)->where('is_active', true)->count();

        return [
            // 👇 Yahan $totalStaff ko HtmlString me wrap karke dark orange (#ea580c) style diya hai
            Stat::make('TOTAL STAFF', new HtmlString('<span style="color: #ea580c !important;">' . $totalStaff . '</span>'))
                ->extraAttributes([
                    'style' => 'background-color: #fff7ed !important; border: 1px solid #fed7aa !important;',
                ]),
            
            // 👇 Same isi tarah $activeNow ko bhi update kiya hai
            Stat::make('ACTIVE NOW', new HtmlString('<span style="color: #ea580c !important;">' . $activeNow . '</span>'))
                ->extraAttributes([
                    'style' => 'background-color: #fff7ed !important; border: 1px solid #fed7aa !important;',
                ]),
        ];
    }
}