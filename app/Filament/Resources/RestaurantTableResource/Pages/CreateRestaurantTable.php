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

        $user = auth()->user();

        if ($user->restaurant_id) {
            $restaurant = Restaurant::find($user->restaurant_id);
            
            if ($restaurant && $restaurant->table_limits > 0) {
                $count = RestaurantTable::where('restaurant_id', $restaurant->id)->count();
                
                if ($count >= $restaurant->table_limits) {
                    Notification::make()
                        ->warning()
                        ->title('Action Blocked')
                        ->body("You have reached your limit of {$restaurant->table_limits} tables. Please contact the Super Admin to increase it.")
                        ->send();
                    
                    $this->redirect(RestaurantTableResource::getUrl('index'));
                }
            }
        }
    }

    // Failsafe in case a direct POST request bypasses Mount restrictions
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $restaurant = $user->restaurant;

        if ($restaurant && $restaurant->table_limits > 0) {
            $count = RestaurantTable::where('restaurant_id', $restaurant->id)->count();
            
            if ($count >= $restaurant->table_limits) {
                Notification::make()
                    ->danger()
                    ->title('Table Limit Exceeded')
                    ->body("You can only create up to {$restaurant->table_limits} tables. Please contact the Super Admin.")
                    ->send();
                
                $this->halt(); // Stop execution safely
            }
        }

        $data['restaurant_id'] = $restaurant->id;
        $data['qr_token'] = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $restaurant = auth()->user()->restaurant;

        QrCodeService::generateTableQr(
            $restaurant->slug,
            $this->record->table_number,
            $this->record->qr_token
        );
    }
}