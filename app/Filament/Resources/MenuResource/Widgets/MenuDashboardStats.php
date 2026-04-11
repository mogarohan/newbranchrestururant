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
    protected static ?string $pollingInterval = '3s';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;

        // Accurate Category Count
        $totalCategories = Category::where('restaurant_id', $restaurantId)
            ->where(function($q) use ($user) {
                $q->whereNull('branch_id');
                if ($user->branch_id) {
                    $q->orWhere('branch_id', $user->branch_id);
                }
            })
            ->get()
            ->filter(function ($cat) use ($user) {
                if ($user->branch_id && $cat->branch_id === null) {
                    $status = DB::table('branch_category_status')
                        ->where('category_id', $cat->id)
                        ->where('branch_id', $user->branch_id)
                        ->first();
                    return $status ? (bool) $status->is_active : (bool) $cat->is_active;
                }
                return (bool) $cat->is_active;
            })->count();

        // Accurate Menu Item Count
        $totalItems = MenuItem::where('restaurant_id', $restaurantId)
            ->where(function($q) use ($user) {
                $q->whereNull('branch_id');
                if ($user->branch_id) {
                    $q->orWhere('branch_id', $user->branch_id);
                }
            })
            ->get()
            ->filter(function ($item) use ($user) {
                if ($user->branch_id && $item->branch_id === null) {
                    $status = DB::table('branch_menu_item_status')
                        ->where('menu_item_id', $item->id)
                        ->where('branch_id', $user->branch_id)
                        ->first();
                    return $status ? (bool) $status->is_available : (bool) $item->is_available;
                }
                return (bool) $item->is_available;
            })->count();

        // 🎨 CUSTOM CSS FOR GLASS EFFECT AND BLACK BORDER
        $customCss = "
        <style>
            .fi-wi-stats-overview-stats-ctn { display: grid !important; grid-template-columns: repeat(1, minmax(0, 1fr)) !important; gap: 1.5rem !important; }
            @media (min-width: 768px) { .fi-wi-stats-overview-stats-ctn { grid-template-columns: repeat(2, minmax(0, 400px)) !important; } }
            
            .sa-stat-card { 
                background: rgba(255, 255, 255, 0.45) !important;
                backdrop-filter: blur(16px) saturate(140%) !important;
                -webkit-backdrop-filter: blur(16px) saturate(140%) !important;
                border: 1.5px solid #000000 !important; /* BLACK BORDER */
                border-radius: 1.25rem !important;
                padding: 1.5rem !important;
                position: relative !important;
                overflow: hidden !important;
                box-shadow: 0 8px 32px rgba(42, 71, 149, 0.08) !important;
                transition: transform 0.3s ease, box-shadow 0.3s ease !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                z-index: 1 !important;
                min-height: 180px !important;
            }
            .dark .sa-stat-card { background: rgba(15, 15, 20, 0.7) !important; }
            .sa-stat-card:hover { transform: translateY(-5px) !important; box-shadow: 0 12px 40px rgba(42, 71, 149, 0.15) !important; }

            .watermark-icon { position: absolute !important; right: -10% !important; bottom: -15% !important; width: 140px !important; height: 140px !important; opacity: 0.1 !important; transform: rotate(-15deg) !important; z-index: -1 !important; pointer-events: none !important; }
            .card-top-row { display: flex !important; justify-content: space-between !important; align-items: flex-start !important; margin-bottom: 1rem !important; }
            .main-icon-wrapper { padding: 0.75rem !important; border-radius: 12px !important; display: flex !important; align-items: center !important; justify-content: center !important; backdrop-filter: blur(4px) !important; }
            
            .sa-stat-label { font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; display: block !important; margin-bottom: 0.5rem !important; color: #64748b !important; }
            .sa-stat-value { font-family: 'Poppins', sans-serif !important; font-size: 2.5rem !important; font-weight: 700 !important; line-height: 1 !important; display: block !important; }
            
            .card-orange .sa-stat-value { color: #f16b3f !important; }
            .card-orange .main-icon-wrapper { background: rgba(241, 107, 63, 0.15) !important; color: #f16b3f !important; border: 1px solid rgba(241, 107, 63, 0.3) !important; }
            .card-orange .watermark-icon { color: #f16b3f !important; }

            .card-blue .sa-stat-value { color: #2a4795 !important; }
            .card-blue .main-icon-wrapper { background: rgba(42, 71, 149, 0.15) !important; color: #2a4795 !important; border: 1px solid rgba(42, 71, 149, 0.3) !important; }
            .card-blue .watermark-icon { color: #2a4795 !important; }

            .sa-action-btn { display: inline-flex !important; align-items: center !important; gap: 6px !important; font-size: 0.75rem !important; font-weight: 700 !important; padding: 8px 16px !important; border-radius: 8px !important; cursor: pointer !important; transition: 0.2s !important; border: 1px solid #000000 !important; }
            .btn-orange { background: #f16b3f !important; color: #ffffff !important; }
            .btn-blue { background: #2a4795 !important; color: #ffffff !important; }
            .btn-outline { background: rgba(255, 255, 255, 0.5) !important; color: #000000 !important; }
            
            .animate-spin { animation: spin 1s linear infinite; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        </style>
        ";

        $loaderIcon = '<svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-show="loading"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        $alpineLogic = 'x-data="{ loading: false, init() { const observer = new MutationObserver(() => { if (document.querySelector(\'.fi-modal\')) { this.loading = false; } }); observer.observe(document.body, { childList: true, subtree: true }); } }"';

        $addCategoryBtn = "<button type='button' $alpineLogic x-on:click=\"loading = true; document.querySelector('.hidden-add-category').click()\" :disabled='loading' class='sa-action-btn btn-orange'>$loaderIcon <span x-text=\"loading ? 'Loading...' : '+ Add Category'\"></span></button>";
        $manageCatBtn = "<button type='button' $alpineLogic x-on:click=\"loading = true; document.querySelector('.hidden-manage-category').click()\" :disabled='loading' class='sa-action-btn btn-outline' style='margin-left: 6px;'>$loaderIcon <span x-text=\"loading ? '...' : 'Manage Categories'\"></span></button>";
        $addItemBtn = "<button type='button' $alpineLogic x-on:click=\"loading = true; document.querySelector('.hidden-add-item').click()\" :disabled='loading' class='sa-action-btn btn-blue'>$loaderIcon <span x-text=\"loading ? 'Loading...' : '+ Add Item'\"></span></button>";

        $htmlCategories = "
            {$customCss}
            <div class='sa-stat-card card-orange'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='watermark-icon'><path stroke-linecap='round' stroke-linejoin='round' d='M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776' /></svg>
                <div class='card-top-row'>
                    <div class='main-icon-wrapper'><svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width: 24px; height: 24px;'><path stroke-linecap='round' stroke-linejoin='round' d='M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776' /></svg></div>
                </div>
                <div>
                    <span class='sa-stat-label'>Active Categories</span>
                    <span class='sa-stat-value'>{$totalCategories}</span>
                    <div style='margin-top: 1rem;'>{$addCategoryBtn} {$manageCatBtn}</div>
                </div>
            </div>
        ";

        $htmlItems = "
            <div class='sa-stat-card card-blue'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='watermark-icon'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z' /></svg>
                <div class='card-top-row'>
                    <div class='main-icon-wrapper'><svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width: 24px; height: 24px;'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z' /></svg></div>
                </div>
                <div>
                    <span class='sa-stat-label'>Active Menu Items</span>
                    <span class='sa-stat-value'>{$totalItems}</span>
                    <div style='margin-top: 1rem;'>{$addItemBtn}</div>
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