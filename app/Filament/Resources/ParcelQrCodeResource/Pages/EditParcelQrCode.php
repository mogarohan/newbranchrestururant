<?php

namespace App\Filament\Resources\ParcelQrCodeResource\Pages;

use App\Filament\Resources\ParcelQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParcelQrCode extends EditRecord
{
    protected static string $resource = ParcelQrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
