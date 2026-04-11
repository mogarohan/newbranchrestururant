<?php

namespace App\Filament\Resources\TableBillingResource\Widgets;

use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class TableBillingStats extends BaseWidget
{
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        // 1. Total Bills Today
        $totalBillsToday = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->count();

        // 2. Total Revenue
        $totalRevenue = Payment::whereHas('order', function ($query) use ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        })->where('status', 'paid')->sum('amount');

        // 3. Unsettled Balance
        $unsettledOrdersTotal = Order::where('restaurant_id', $restaurantId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'completed')
            ->sum('total_amount');

        $unsettledPaidAmount = Payment::whereHas('order', function ($query) use ($restaurantId) {
            $query->where('restaurant_id', $restaurantId)
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'completed');
        })->where('status', 'paid')->sum('amount');

        $unsettledBalance           = max(0, $unsettledOrdersTotal - $unsettledPaidAmount);
        $totalRevenueFormatted      = '₹' . number_format($totalRevenue, 0);
        $unsettledBalanceFormatted  = '₹' . number_format($unsettledBalance, 0);

        // ── Brand palette ──────────────────────────────────────────
        // #fe9a54  light orange
        // #f16b3f  primary orange
        // #456aba  light blue
        // #2a4795  dark blue (navy)
        // ──────────────────────────────────────────────────────────

        $customCss = "
        <style>
            .fi-wi-stats-overview-stats-ctn {
                display: grid !important;
                grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
                gap: 1.5rem !important;
            }
            @media (min-width: 1024px) {
                .fi-wi-stats-overview-stats-ctn {
                    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                }
            }

            .sa-stat-card {
                background-color: #ffffff;
                border-radius: 1rem;
                padding: 1.5rem;
                position: relative;
                overflow: hidden;
                transition: transform 0.2s, box-shadow 0.2s;
                display: flex;
                flex-direction: column;
                min-height: 160px;
                width: 90%;
                z-index: 1;
            }

            .sa-stat-card:hover {
                transform: translateY(-4px);
            }

            .watermark-icon {
                position: absolute;
                right: -10%;
                bottom: -15%;
                width: 140px;
                height: 140px;
                opacity: 0.07;
                transform: rotate(-15deg);
                z-index: -1;
                pointer-events: none;
            }

            .card-top-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 1.5rem;
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
                margin-bottom: 0.5rem;
                color: #64748b;
            }

            .sa-stat-value {
                font-family: 'Poppins', sans-serif;
                font-size: 2.25rem;
                font-weight: 700;
                line-height: 1;
                display: block;
            }

            /* ── CARD 1: Bills Today — Navy blue ── */
            .card-navy {
                border: 1.5px solid rgba(42, 71, 149, 0.25);
                box-shadow: 0 4px 18px rgba(42, 71, 149, 0.12);
            }
            .card-navy:hover {
                box-shadow: 0 10px 28px rgba(42, 71, 149, 0.20);
            }
            .card-navy .watermark-icon,
            .card-navy .main-icon-wrapper { color: #2a4795 !important; }
            .card-navy .main-icon-wrapper { background-color: rgba(42, 71, 149, 0.10) !important; }
            .card-navy .sa-stat-value     { color: #2a4795; }

            /* ── CARD 2: Unsettled — Primary orange ── */
            .card-orange {
                border: 1.5px solid rgba(241, 107, 63, 0.28);
                box-shadow: 0 4px 18px rgba(241, 107, 63, 0.12);
            }
            .card-orange:hover {
                box-shadow: 0 10px 28px rgba(241, 107, 63, 0.22);
            }
            .card-orange .watermark-icon,
            .card-orange .main-icon-wrapper { color: #f16b3f !important; }
            .card-orange .main-icon-wrapper { background-color: rgba(241, 107, 63, 0.10) !important; }
            .card-orange .sa-stat-value     { color: #f16b3f; }

            /* ── CARD 3: Revenue — Light blue ── */
            .card-blue {
                border: 1.5px solid rgba(69, 106, 186, 0.28);
                box-shadow: 0 4px 18px rgba(69, 106, 186, 0.12);
            }
            .card-blue:hover {
                box-shadow: 0 10px 28px rgba(69, 106, 186, 0.22);
            }
            .card-blue .watermark-icon,
            .card-blue .main-icon-wrapper { color: #456aba !important; }
            .card-blue .main-icon-wrapper { background-color: rgba(69, 106, 186, 0.10) !important; }
            .card-blue .sa-stat-value     { color: #456aba; }
        </style>
        ";

        // ── CARD 1: Total Bills Today ──────────────────────────────
        $htmlBills = "
            {$customCss}
            <div class='sa-stat-card card-navy'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='watermark-icon'>
                    <path stroke-linecap='round' stroke-linejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z' />
                </svg>
                <div class='card-top-row'>
                    <div class='main-icon-wrapper'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width:24px;height:24px;'>
                            <path stroke-linecap='round' stroke-linejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z' />
                        </svg>
                    </div>
                </div>
                <div>
                    <span class='sa-stat-label'>Total Bills Today</span>
                    <span class='sa-stat-value'>{$totalBillsToday}</span>
                </div>
            </div>
        ";

        // ── CARD 2: Unsettled Balance ──────────────────────────────
        $htmlUnsettled = "
            <div class='sa-stat-card card-orange'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='watermark-icon'>
                    <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' />
                </svg>
                <div class='card-top-row'>
                    <div class='main-icon-wrapper'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width:24px;height:24px;'>
                            <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' />
                        </svg>
                    </div>
                </div>
                <div>
                    <span class='sa-stat-label'>Unsettled Balance</span>
                    <span class='sa-stat-value'>{$unsettledBalanceFormatted}</span>
                </div>
            </div>
        ";

        // ── CARD 3: Total Revenue ──────────────────────────────────
        $htmlRevenue = "
            <div class='sa-stat-card card-blue'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='watermark-icon'>
                    <path stroke-linecap='round' stroke-linejoin='round' d='M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z' />
                </svg>
                <div class='card-top-row'>
                    <div class='main-icon-wrapper'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width:24px;height:24px;'>
                            <path stroke-linecap='round' stroke-linejoin='round' d='M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z' />
                        </svg>
                    </div>
                </div>
                <div>
                    <span class='sa-stat-label'>Total Revenue</span>
                    <span class='sa-stat-value'>{$totalRevenueFormatted}</span>
                </div>
            </div>
        ";

        return [
            Stat::make('', new HtmlString($htmlBills))
                ->extraAttributes(['style' => 'padding:0 !important;background:transparent !important;border:none !important;box-shadow:none !important;']),

            Stat::make('', new HtmlString($htmlUnsettled))
                ->extraAttributes(['style' => 'padding:0 !important;background:transparent !important;border:none !important;box-shadow:none !important;']),

            Stat::make('', new HtmlString($htmlRevenue))
                ->extraAttributes(['style' => 'padding:0 !important;background:transparent !important;border:none !important;box-shadow:none !important;']),
        ];
    }
}