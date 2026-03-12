<?php

namespace App\Filament\Resources\CategoryResource\Widgets;

use App\Models\Category;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class CategoryStats extends BaseWidget
{
    protected function getStats(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        $total = Category::where('restaurant_id', $restaurantId)->count();
        $active = Category::where('restaurant_id', $restaurantId)->where('is_active', true)->count();
        $inactive = Category::where('restaurant_id', $restaurantId)->where('is_active', false)->count();

        return [
            Stat::make('Total Categories', $total)
                ->extraAttributes([
                    'style' => 'background-color: #fffaf5 !important; border: 1px solid #ffedd5 !important;',
                ]),

            Stat::make('Active Categories', new HtmlString('<span style="color: #22c55e !important;">' . $active . '</span>'))
                ->extraAttributes([
                    'style' => 'background-color: #f0fdf4 !important; border: 1px solid #dcfce7 !important;',
                ]),

            Stat::make('Inactive Categories', new HtmlString('<span style="color: #ef4444 !important;">' . $inactive . '</span>'))
                ->extraAttributes([
                    'style' => 'background-color: #fef2f2 !important; border: 1px solid #fee2e2 !important;',
                ]),
        ];
    }
}