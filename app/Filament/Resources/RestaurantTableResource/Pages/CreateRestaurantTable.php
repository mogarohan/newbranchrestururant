<?php

namespace App\Filament\Resources\RestaurantTableResource\Pages;

use App\Filament\Resources\RestaurantTableResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use App\Services\Restaurant\QrCodeService;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Filament\Notifications\Notification;

class CreateRestaurantTable extends CreateRecord
{
    protected static string $resource = RestaurantTableResource::class;

    // Reject users trying to bypass the Generate button and accessing the URL directly
    public function mount(): void
    {
        parent::mount();

        $info = RestaurantTableResource::getTableLimitInfo();

        if ($info['isLimitReached']) {
            Notification::make()
                ->warning()
                ->title('Action Blocked')
                ->body("You have reached your limit of {$info['limit']} tables. Please contact {$info['contactPerson']} to increase it.")
                ->send();

            $this->redirect(RestaurantTableResource::getUrl('index'));
        }
    }

    // Failsafe in case a direct POST request bypasses Mount restrictions
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $info = RestaurantTableResource::getTableLimitInfo();

        if ($info['isLimitReached']) {
            Notification::make()
                ->danger()
                ->title('Table Limit Exceeded')
                ->body("You can only create up to {$info['limit']} tables. Please contact {$info['contactPerson']}.")
                ->send();

            $this->halt(); // Stop execution safely
        }

        $user = auth()->user();
        $data['restaurant_id'] = $user->restaurant_id;
        $data['branch_id'] = $info['branchId'];
        $data['qr_token'] = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        app(QrCodeService::class)->generate($this->record);
    }
}