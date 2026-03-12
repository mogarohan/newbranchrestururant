<?php

namespace App\Filament\Resources\MenuItemResource\Widgets;

use App\Models\MenuItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class MenuItemStats extends BaseWidget
{
    protected function getStats(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        $total = MenuItem::where('restaurant_id', $restaurantId)->count();
        $active = MenuItem::where('restaurant_id', $restaurantId)->where('is_available', true)->count();
        $out = MenuItem::where('restaurant_id', $restaurantId)->where('is_available', false)->count();
        $avg = MenuItem::where('restaurant_id', $restaurantId)->avg('price') ?? 0;

        return [
            Stat::make('Total Items', $total)
                ->extraAttributes(['style' => 'background-color: #fffaf5 !important; border: 1px solid #ffedd5 !important;']),

            Stat::make('Active Items', new HtmlString('<span style="color: #22c55e !important;">' . $active . '</span>'))
                ->extraAttributes(['style' => 'background-color: #f0fdf4 !important; border: 1px solid #dcfce7 !important;']),

            Stat::make('Out of Stock', new HtmlString('<span style="color: #ef4444 !important;">' . $out . '</span>'))
                ->extraAttributes(['style' => 'background-color: #fef2f2 !important; border: 1px solid #fee2e2 !important;']),

            Stat::make('Average Price', '₹' . number_format($avg, 2))
                ->extraAttributes(['style' => 'background-color: #fffaf5 !important; border: 1px solid #ffedd5 !important;']),
        ];
    }
}