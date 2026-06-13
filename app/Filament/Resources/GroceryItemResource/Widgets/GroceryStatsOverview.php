<?php

namespace App\Filament\Resources\GroceryItemResource\Widgets;

use App\Models\GroceryItem;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GroceryStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $query = GroceryItem::where('restaurant_id', $user->restaurant_id);

        if ($user->branch_id) {
            $query->where(fn($q) => $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id'));
        } else {
            $query->whereNull('branch_id');
        }

        $total = (clone $query)->count();
        $lowStock = (clone $query)->lowStock()->count();
        $outOfStock = (clone $query)->outOfStock()->count();
        $totalValue = (clone $query)->whereNotNull('cost_per_unit')
            ->get()
            ->sum(fn($item) => $item->current_stock * $item->cost_per_unit);

        return [
            Stat::make('Total Raw Materials', $total)
                ->icon('heroicon-o-cube')
                ->color('primary'),
            Stat::make('Low Stock', $lowStock)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make('Out of Stock', $outOfStock)
                ->icon('heroicon-o-x-circle')
                ->color($outOfStock > 0 ? 'danger' : 'success'),
            Stat::make('Inventory Value', '₹' . number_format($totalValue, 2))
                ->icon('heroicon-o-currency-rupee')
                ->color('info'),
        ];
    }
}
