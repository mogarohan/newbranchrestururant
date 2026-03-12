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

        // 🎨 EXACT MATCH WITH PREMIUM DESIGN (Transparent & Theme Aware)
        $cardBaseStyle = 'background-color: transparent !important; border: 1px solid rgba(156, 163, 175, 0.3) !important; border-radius: 12px !important; padding: 1.5rem !important; position: relative !important; overflow: hidden !important; box-shadow: none !important;';

        // Label Style: Colored, Uppercase, Bold
        $labelBaseStyle = 'font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; display: block !important; margin-bottom: 1rem !important;';
        
        // Value Style: Large, Bold
        $numberBaseStyle = 'font-size: 2.25rem !important; font-weight: 800 !important; line-height: 1 !important; display: block !important; margin-bottom: 0.5rem !important;';

        // 🖼️ CUSTOM SVG ICONS
        // Orange List Icon (Total)
        $iconTotal = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(234, 88, 12, 0.1); color: #ea580c; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg></div>';
        
        // Green Check Icon (Active)
        $iconActive = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';
        
        // Red X Icon (Out of Stock)
        $iconOut = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';

        // Blue Currency Icon (Avg Price)
        $iconAvg = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 8.25H9m6 3H9m3 6l-3-3h1.5a3 3 0 100-6M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';

        return [
            // Widget 1: TOTAL ITEMS (Orange)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ea580c !important;'>Total Items</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$total}</span>" . $iconTotal)
            )
            ->description(new HtmlString('<span style="color: #ea580c !important; font-size: 0.75rem; font-weight: 500;">• Total Menu Catalog</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

            // Widget 2: ACTIVE ITEMS (Green)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #10b981 !important;'>Active Items</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$active}</span>" . $iconActive)
            )
            ->description(new HtmlString('<span style="color: #10b981 !important; font-size: 0.75rem; font-weight: 500;">↑ Available for Order</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

            // Widget 3: OUT OF STOCK (Red)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ef4444 !important;'>Out of Stock</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$out}</span>" . $iconOut)
            )
            ->description(new HtmlString('<span style="color: #ef4444 !important; font-size: 0.75rem; font-weight: 500;">↓ Unavailable Items</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

            // Widget 4: AVERAGE PRICE (Blue)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #3b82f6 !important;'>Average Price</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>₹" . number_format($avg, 0) . "</span>" . $iconAvg)
            )
            ->description(new HtmlString('<span style="color: #3b82f6 !important; font-size: 0.75rem; font-weight: 500;">• Per Item Value</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),
        ];
    }
}