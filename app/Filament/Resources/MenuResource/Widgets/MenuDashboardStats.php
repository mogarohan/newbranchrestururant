<?php

namespace App\Filament\Resources\MenuResource\Widgets;

use App\Models\Category;
use App\Models\MenuItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class MenuDashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;

        $totalCategories = Category::where('restaurant_id', $restaurantId)->count();
        $totalItems = MenuItem::where('restaurant_id', $restaurantId)->count();

        // Styles
        $cardBaseStyle = 'background-color: transparent !important; border: 1px solid rgba(156, 163, 175, 0.3) !important; border-radius: 12px !important; padding: 1.5rem !important; position: relative !important; overflow: hidden !important; box-shadow: none !important;';
        $labelBaseStyle = 'font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; display: block !important; margin-bottom: 1rem !important;';
        $numberBaseStyle = 'font-size: 2.25rem !important; font-weight: 800 !important; line-height: 1 !important; display: block !important; margin-bottom: 0.5rem !important;';

        // 👇 Inject CSS to instantly hide the Category Widget Container on load
        $cssHack = "<style>.fi-wi:has(#category-manager-inner) { display: none; } .fi-wi:has(#category-manager-inner.force-show) { display: block !important; }</style>";

        // 1. Add Category Button
        $addCategoryBtn = "<button type='button' onclick=\"document.querySelector('.hidden-add-category').click()\" style='display: inline-block; margin-top: 10px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: rgba(234, 88, 12, 0.1); color: #ea580c; border: 1px solid rgba(234, 88, 12, 0.2); cursor: pointer;'>+ Add Category</button>";
        
        // 👇 UPDATED: Button now fires a native Event to open the table
// This is inside getStats() in MenuDashboardStats.php
        $manageCatBtn = "<button type='button' onclick=\"document.querySelector('.hidden-manage-category').click()\" style='display: inline-block; margin-top: 10px; margin-left: 8px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: transparent; color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.3); cursor: pointer; transition: 0.2s;' onmouseover=\"this.style.backgroundColor='rgba(107, 114, 128, 0.1)'\" onmouseout=\"this.style.backgroundColor='transparent'\">Manage Categories</button>";
        // 3. Add Item Button
        $addItemBtn = "<button type='button' onclick=\"document.querySelector('.hidden-add-item').click()\" style='display: inline-block; margin-top: 10px; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); cursor: pointer;'>+ Add Item</button>";

        if ($user->isBranchAdmin() || $user->isManager()) {
            $addCategoryBtn = '';
            $manageCatBtn = '';
            $addItemBtn = '';
        }

        // Icons
        $iconFolder = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(234, 88, 12, 0.1); color: #ea580c; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" /></svg></div>';
        $iconFood = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg></div>';

        return [
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ea580c !important;'>Total Categories</span>"),
                new HtmlString($cssHack . "<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalCategories}</span>" . $iconFolder)
            )
            ->description(new HtmlString($addCategoryBtn . $manageCatBtn))
            ->extraAttributes(['style' => $cardBaseStyle]),

            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #3b82f6 !important;'>Total Menu Items</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalItems}</span>" . $iconFood)
            )
            ->description(new HtmlString($addItemBtn))
            ->extraAttributes(['style' => $cardBaseStyle]),
        ];
    }
}