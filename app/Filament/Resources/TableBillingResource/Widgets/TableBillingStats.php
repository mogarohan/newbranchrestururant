<?php

namespace App\Filament\Resources\TableBillingResource\Widgets;

use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class TableBillingStats extends BaseWidget
{
    protected function getStats(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        // 1. Total Bills Today (Count of completed orders today)
        $totalBillsToday = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->count();

        // 2. Total Revenue (Sum of all paid payments for this restaurant)
        // Ensure you join orders to filter by restaurant_id
        $totalRevenue = Payment::whereHas('order', function ($query) use ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        })->where('status', 'paid')->sum('amount');

        // 3. Unsettled Balance (Total Order Amount - Total Paid Amount for active sessions)
        $unsettledOrdersTotal = Order::where('restaurant_id', $restaurantId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'completed')
            ->sum('total_amount');

        $unsettledPaidAmount = Payment::whereHas('order', function ($query) use ($restaurantId) {
            $query->where('restaurant_id', $restaurantId)
                  ->where('status', '!=', 'cancelled')
                  ->where('status', '!=', 'completed');
        })->where('status', 'paid')->sum('amount');

        $unsettledBalance = max(0, $unsettledOrdersTotal - $unsettledPaidAmount);

        // 🎨 EXACT MATCH WITH PREMIUM DESIGN (Transparent & Theme Aware)
        $cardBaseStyle = 'background-color: transparent !important; border: 1px solid rgba(156, 163, 175, 0.3) !important; border-radius: 12px !important; padding: 1.5rem !important; position: relative !important; overflow: hidden !important; box-shadow: none !important;';
        $labelBaseStyle = 'font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; display: block !important; margin-bottom: 1rem !important;';
        $numberBaseStyle = 'font-size: 2.25rem !important; font-weight: 800 !important; line-height: 1 !important; display: block !important; margin-bottom: 0.5rem !important;';

        // 🖼️ CUSTOM SVG ICONS
        $iconBills = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg></div>';
        $iconRevenue = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';
        $iconUnsettled = '<div style="position: absolute; top: 1.25rem; right: 1.25rem; background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div>';

        return [
            // Widget 1: TOTAL BILLS TODAY (Blue)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #3b82f6 !important;'>Total Bills Today</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>{$totalBillsToday}</span>" . $iconBills)
            )
            ->description(new HtmlString('<span style="color: #3b82f6 !important; font-size: 0.75rem; font-weight: 500;">• Cleared Tables Today</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),


             // Widget 3: UNSETTLED BALANCE (Red)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #ef4444 !important;'>Unsettled Balance</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>₹" . number_format($unsettledBalance, 0) . "</span>" . $iconUnsettled)
            )
            ->description(new HtmlString('<span style="color: #ef4444 !important; font-size: 0.75rem; font-weight: 500;">↓ Active Tables Pending Payment</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),
        
            // Widget 2: TOTAL REVENUE (Green)
            Stat::make(
                new HtmlString("<span style='{$labelBaseStyle} color: #10b981 !important;'>Total Revenue</span>"),
                new HtmlString("<span style='{$numberBaseStyle}' class='text-gray-900 dark:text-white'>₹" . number_format($totalRevenue, 0) . "</span>" . $iconRevenue)
            )
            ->description(new HtmlString('<span style="color: #10b981 !important; font-size: 0.75rem; font-weight: 500;">↑ Lifetime Income</span>'))
            ->extraAttributes(['style' => $cardBaseStyle]),

           ];
    }
}