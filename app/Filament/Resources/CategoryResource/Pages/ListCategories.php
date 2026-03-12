<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Category')
                ->icon('heroicon-o-plus')
                ->color('warning'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CategoryResource\Widgets\CategoryStats::class,
        ];
    }
}