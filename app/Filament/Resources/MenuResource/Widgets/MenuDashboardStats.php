<?php

namespace App\Filament\Resources\MenuResource\Widgets;

use App\Models\Category;
use App\Models\MenuItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class MenuDashboardStats extends BaseWidget
{
    // 👇 FIX: This makes the widget refresh automatically every 3 seconds
    protected static ?string $pollingInterval = '3s';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;

        // 👇 FIX 1: Accurate Category Count (Only Active Categories)
        if ($user->isBranchAdmin() || $user->isManager()) {
            // For Branch Admin: Check Branch status override, fallback to main category active status
            $totalCategories = Category::where('restaurant_id', $restaurantId)
                ->whereNull('branch_id')
                ->get()
                ->filter(function ($cat) use ($user) {
                    $status = DB::table('branch_category_status')
                        ->where('category_id', $cat->id)
                        ->where('branch_id', $user->branch_id)
                        ->first();
                    return $status ? (bool) $status->is_active : (bool) $cat->is_active;
                })->count();
        } else {
            // For Main Admin: Count only active categories
            $totalCategories = Category::where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->count();
        }

        // 👇 FIX 2: Accurate Menu Item Count (Only Available Items)
        if ($user->isBranchAdmin() || $user->isManager()) {
            // For Branch Admin: Check Branch status override, fallback to main item available status
            $totalItems = MenuItem::where('restaurant_id', $restaurantId)
                ->whereNull('branch_id')
                ->get()
                ->filter(function ($item) use ($user) {
                    $status = DB::table('branch_menu_item_status')
                        ->where('menu_item_id', $item->id)
                        ->where('branch_id', $user->branch_id)
                        ->first();
                    return $status ? (bool) $status->is_available : (bool) $item->is_available;
                })->count();
        } else {
            // For Main Admin: Count only available items
            $totalItems = MenuItem::where('restaurant_id', $restaurantId)
                ->where('is_available', true)
                ->count();
        }

        // 👇 Inject CSS to instantly hide the Category Widget Container on load
        $cssHack = "<style>.fi-wi:has(#category-manager-inner) { display: none; } .fi-wi:has(#category-manager-inner.force-show) { display: block !important; }</style>";

        $customCss = "
        <style>
            .fi-wi-stats-overview-stats-ctn {
                display: grid !important;
                grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
                gap: 0.5rem !important;
            }
            @media (min-width: 768px) { 
                .fi-wi-stats-overview-stats-ctn { 
                    grid-template-columns: repeat(2, minmax(0, 320px)) !important; 
                } 
            }

            .sa-stat-card {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 1rem;
                padding: 1.25rem;
                position: relative;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                transition: transform 0.2s, box-shadow 0.2s;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                z-index: 1;
                width: 100%;
                height: 100%; 
            }

            .dark .sa-stat-card {
                background-color: #1e293b;
                border: 1px solid #334155;
                box-shadow: none;
            }

            .sa-stat-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            }

            .watermark-icon {
                position: absolute;
                right: -10%;
                bottom: -15%;
                width: 140px;
                height: 140px;
                opacity: 0.1;
                transform: rotate(-15deg);
                z-index: -1;
                pointer-events: none;
            }

            .card-top-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 0.5rem;
            }

            .main-icon-wrapper {
                padding: 0.75rem;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .sa-stat-label {
                font-size: 0.75rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                display: block;
                margin-bottom: 0.25rem;
                color: #64748b;
            }

            .sa-stat-value {
                font-family: 'Poppins', sans-serif;
                font-size: 2.25rem;
                font-weight: 700;
                line-height: 1.1 !important;
                display: block;
                color: #0f172a;
            }
            .dark .sa-stat-value { color: #f8fafc; }

            .card-orange .watermark-icon, .card-orange .main-icon-wrapper { color: #F47D20 !important; }
            .card-orange .main-icon-wrapper { background-color: rgba(244, 125, 32, 0.1) !important; }

            .card-blue .watermark-icon, .card-blue .main-icon-wrapper { color: #3B82F6 !important; }
            .card-blue .main-icon-wrapper { background-color: rgba(59, 130, 246, 0.1) !important; }

            .sa-action-btn {
                display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s; border: 1px solid transparent;
            }
            .btn-orange { background-color: #EA580C; color: #ffffff; border-color: rgba(244, 125, 32, 0.2); }
            .btn-blue { background-color: #3B82F6; color: #ffffff; border-color: rgba(59, 130, 246, 0.8); }
            .btn-outline { background-color: #faece5; color: #EC7232; border-color: rgba(107, 114, 128, 0.3); }
            
            .animate-spin { animation: spin 1s linear infinite; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        </style>
        ";

        // Loader Component
        $loaderIcon = '<svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-show="loading"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

        // Alpine Script to stop loader when modal opens
        $alpineLogic = 'x-data="{ 
            loading: false, 
            init() {
                const observer = new MutationObserver(() => {
                    if (document.querySelector(\'.fi-modal\')) { this.loading = false; }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }
        }"';

        $addCategoryBtn = "<button type='button' $alpineLogic x-on:click=\"loading = true; document.querySelector('.hidden-add-category').click()\" :disabled='loading' class='sa-action-btn btn-orange'>$loaderIcon <span x-text=\"loading ? 'Loading...' : '+ Add Category'\"></span></button>";
        $manageCatBtn = "<button type='button' $alpineLogic x-on:click=\"loading = true; document.querySelector('.hidden-manage-category').click()\" :disabled='loading' class='sa-action-btn btn-outline' style='margin-left: 4px;'>$loaderIcon <span x-text=\"loading ? '...' : 'Manage Categories'\"></span></button>";
        $addItemBtn = "<button type='button' $alpineLogic x-on:click=\"loading = true; document.querySelector('.hidden-add-item').click()\" :disabled='loading' class='sa-action-btn btn-blue'>$loaderIcon <span x-text=\"loading ? 'Loading...' : '+ Add Item'\"></span></button>";

        if (!is_null($user->branch_id)) {
            $addCategoryBtn = '';
            $manageCatBtn = '';
            $addItemBtn = '';
        }

        $htmlCategories = "
            {$customCss}
            {$cssHack}
            <div class='sa-stat-card card-orange'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='watermark-icon'>
                    <path stroke-linecap='round' stroke-linejoin='round' d='M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776' />
                </svg>
                <div class='card-top-row'>
                    <div class='main-icon-wrapper'><svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width: 24px; height: 24px;'><path stroke-linecap='round' stroke-linejoin='round' d='M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776' /></svg></div>
                </div>
                <div>
                    <span class='sa-stat-label'>Active Categories</span>
                    <span class='sa-stat-value'>{$totalCategories}</span>
                    <div style='margin-top: 0.5rem;'>{$addCategoryBtn} {$manageCatBtn}</div>
                </div>
            </div>
        ";

        $htmlItems = "
            <div class='sa-stat-card card-blue'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='watermark-icon'>
                    <path stroke-linecap='round' stroke-linejoin='round' d='M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z' />
                </svg>
                <div class='card-top-row'>
                    <div class='main-icon-wrapper'><svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width: 24px; height: 24px;'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z' /></svg></div>
                </div>
                <div>
                    <span class='sa-stat-label'>Active Menu Items</span>
                    <span class='sa-stat-value'>{$totalItems}</span>
                    <div style='margin-top: 0.5rem;'>{$addItemBtn}</div>
                </div>
            </div>
        ";

        return [
            Stat::make('', new HtmlString($htmlCategories))
                ->extraAttributes(['style' => 'padding: 0 !important; background: transparent !important; border: none !important; box-shadow: none !important;']),
            Stat::make('', new HtmlString($htmlItems))
                ->extraAttributes(['style' => 'padding: 0 !important; background: transparent !important; border: none !important; box-shadow: none !important;']),
        ];
    }
}