<?php

namespace App\Filament\Resources\InventoryResource\Widgets;

use App\Models\MenuItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InventoryStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $user = auth()->user();

        $base = MenuItem::where('restaurant_id', $user->restaurant_id)
            ->where('track_stock', true)
            ->when(
                $user->branch_id,
                fn($q) => $q->where(fn($q2) => $q2->where('branch_id', $user->branch_id)->orWhereNull('branch_id')),
                fn($q) => $q->whereNull('branch_id')
            );

        $outOfStock = (clone $base)->where('stock_quantity', '<=', 0)->count();
        $lowStock = (clone $base)->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
        $healthy = (clone $base)->whereColumn('stock_quantity', '>', 'low_stock_threshold')->count();
        $untracked = MenuItem::where('restaurant_id', $user->restaurant_id)
            ->where('track_stock', false)->count();

        return [
            Stat::make('🔴 Out of Stock', $outOfStock)
                ->description('Items with 0 units — hidden from menu')
                ->color('danger'),

            Stat::make('🟡 Low Stock', $lowStock)
                ->description('At or below alert threshold')
                ->color('warning'),

            Stat::make('🟢 Sufficient Stock', $healthy)
                ->description('Above threshold, available')
                ->color('success'),

            Stat::make('⚪ Not Tracked', $untracked)
                ->description('Stock tracking not enabled')
                ->color('gray'),
        ];
    }
}