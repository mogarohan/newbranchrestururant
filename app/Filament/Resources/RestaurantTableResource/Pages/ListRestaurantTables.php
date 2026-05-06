<?php

namespace App\Filament\Resources\RestaurantTableResource\Pages;

use App\Filament\Resources\RestaurantTableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Restaurant;
use App\Models\RestaurantTable;

class ListRestaurantTables extends ListRecords
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isLimitReached = false;
        $limit = 0;

        // Check if the user belongs to a restaurant (is a Manager/Admin)
        if ($user->restaurant_id) {
            $restaurant = Restaurant::find($user->restaurant_id);
            
            // If table_limits is greater than 0, enforce the check
            if ($restaurant && $restaurant->table_limits > 0) {
                $limit = $restaurant->table_limits;
                $currentTableCount = RestaurantTable::where('restaurant_id', $restaurant->id)->count();
                
                $isLimitReached = $currentTableCount >= $limit;
            }
        }

        return [
            Actions\CreateAction::make()
                ->disabled($isLimitReached)
                ->tooltip($isLimitReached ? "Table limit ({$limit}) reached. Please contact the Super Admin to increase your limits." : null),
        ];
    }
}