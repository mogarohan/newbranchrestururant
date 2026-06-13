<?php

namespace App\Filament\Resources\GroceryItemResource\Pages;

use App\Filament\Resources\GroceryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroceryItems extends ListRecords
{
    protected static string $resource = GroceryItemResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            GroceryItemResource\Widgets\GroceryStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Raw Material')
                ->icon('heroicon-o-plus'),
        ];
    }
}
