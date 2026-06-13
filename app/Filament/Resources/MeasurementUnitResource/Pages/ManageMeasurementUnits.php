<?php

namespace App\Filament\Resources\MeasurementUnitResource\Pages;

use App\Filament\Resources\MeasurementUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMeasurementUnits extends ManageRecords
{
    protected static string $resource = MeasurementUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Unit')
                ->icon('heroicon-o-plus'),
        ];
    }
}
