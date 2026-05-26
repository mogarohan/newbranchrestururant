<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Models\MenuItem;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions;

class ManageInventory extends ManageRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            InventoryResource\Widgets\InventoryStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return []; // No create — items are managed via Menu, stock set here
    }
}