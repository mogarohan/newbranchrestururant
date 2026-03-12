<?php

namespace App\Filament\Resources\TableBillingResource\Pages;

use App\Filament\Resources\TableBillingResource;
use App\Filament\Resources\TableBillingResource\Widgets\TableBillingStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTableBillings extends ListRecords
{
    protected static string $resource = TableBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TableBillingStats::class,
        ];
    }

    // 🔥 Listen to the Restaurant's Private Channel for Order Updates
    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            // Re-render the billing grid when an order is updated anywhere in the restaurant
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
            
            // Optional: Also refresh if a guest joins or leaves!
            // "echo-private:restaurant.{$restaurantId},.GuestJoinRequested" => '$refresh',
        ];
    }
}