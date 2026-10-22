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
        $info = RestaurantTableResource::getTableLimitInfo();

        return [
            Actions\CreateAction::make()
                ->disabled($info['isLimitReached'])
                ->tooltip($info['isLimitReached'] ? "Table limit ({$info['limit']}) reached. Please contact {$info['contactPerson']} to increase your limits." : null),
        ];
    }
}