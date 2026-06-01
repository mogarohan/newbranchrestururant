<?php

namespace App\Filament\Resources\ParcelQrCodeResource\Pages;

use App\Filament\Resources\ParcelQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParcelQrCodes extends ListRecords
{
    protected static string $resource = ParcelQrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
