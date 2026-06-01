<?php

namespace App\Filament\Resources\ParcelQrCodeResource\Pages;

use App\Filament\Resources\ParcelQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Services\Restaurant\ParcelQrCodeService;
use Illuminate\Support\Str;

class CreateParcelQrCode extends CreateRecord
{
    protected static string $resource = ParcelQrCodeResource::class;

    /**
     * Mutate the form data before creating the record in the database.
     * We auto-inject the restaurant ID, branch ID, and a secure UUID token.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['restaurant_id'] = auth()->user()->restaurant_id;
        $data['branch_id'] = auth()->user()->branch_id;
        $data['qr_token'] = Str::uuid()->toString();
        
        return $data;
    }

    /**
     * After the database record is created, generate the physical QR code SVG.
     */
    protected function afterCreate(): void
    {
        (new ParcelQrCodeService())->generate($this->record);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}