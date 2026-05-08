<?php

namespace App\Filament\Resources\RestaurantTableResource\Pages;

use App\Filament\Resources\RestaurantTableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use App\Models\RestaurantTable;

class EditRestaurantTable extends EditRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. SOFT DELETE ACTION (Edit Page)
            Actions\DeleteAction::make()
                ->before(function ($record, Actions\DeleteAction $action) {
                    
                    
                    // Delete physical QR from server
                    if ($record->qr_path && Storage::disk('public')->exists($record->qr_path)) {
                        Storage::disk('public')->delete($record->qr_path);
                    }
                    
                    // Invalidate old QR code scans
                    $record->update([
                        'qr_token' => null,
                        'qr_path' => null,
                        'is_active' => false,
                    ]);
                }),

            // 2. FORCE DELETE ACTION (Edit Page)
           
            Actions\RestoreAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
