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

        // 🎨 EXACT MATCH WITH PREMIUM DESIGN (Transparent & Theme Aware)
        $cardBaseStyle = 'background-color: transparent !important; border: 1px solid rgba(156, 163, 175, 0.3) !important; border-radius: 12px !important; padding: 1.5rem !important; position: relative !important; overflow: hidden !important; box-shadow: none !important;';

        // Label Style: Colored, Uppercase, Bold
        $labelBaseStyle = 'font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; display: block !important; margin-bottom: 1rem !important;';
        
        // Value Style: Large, Bold
        $numberBaseStyle = 'font-size: 2.25rem !important; font-weight: 800 !important; line-height: 1 !important; display: block !important; margin-bottom: 0.5rem !important;';

        // 🖼️ CUSTOM SVG ICONS
        // Orange Folder Icon (Total)
        $iconTotal = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(234, 88, 12, 0.1); color: #ea580c; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" /></svg></div>';
        
        // Green Check Icon (Active)
        $iconActive = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';
        
        // Red X Icon (Inactive)
        $iconInactive = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';

        return [
            // Widget 1: TOTAL CATEGORIES (Orange Theme)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ea580c !important;'>Total Categories</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$total}</span>" . $iconTotal)
            )
            ->description(new HtmlString('<span style="color: #ea580c !important; font-size: 0.75rem; font-weight: 500;">📁 Menu Structure</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

            // Widget 2: ACTIVE CATEGORIES (Green Theme)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #10b981 !important;'>Active Categories</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$active}</span>" . $iconActive)
            )
            ->description(new HtmlString('<span style="color: #10b981 !important; font-size: 0.75rem; font-weight: 500;">↑ Visible on Menu</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

            // Widget 3: INACTIVE CATEGORIES (Red Theme)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ef4444 !important;'>Inactive Categories</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$inactive}</span>" . $iconInactive)
            )
            ->description(new HtmlString('<span style="color: #ef4444 !important; font-size: 0.75rem; font-weight: 500;">↓ Hidden from Menu</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),
        ];
    }
}