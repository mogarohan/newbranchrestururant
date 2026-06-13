<?php

namespace App\Filament\Resources\GroceryItemResource\Pages;

use App\Filament\Resources\GroceryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroceryItem extends EditRecord
{
    protected static string $resource = GroceryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
