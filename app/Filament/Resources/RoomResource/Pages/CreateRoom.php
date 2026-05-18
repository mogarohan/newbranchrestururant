<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $restaurant = auth()->user()->restaurant;
        $currentRooms = \App\Models\Room::where('restaurant_id', $restaurant->id)->count();

        if ($currentRooms >= $restaurant->rooms_limit) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'room_number' => "Room limit of {$restaurant->rooms_limit} reached for your current plan."
            ]);
        }
        
        $data['restaurant_id'] = $restaurant->id;
        $data['branch_id'] = auth()->user()->branch_id;
        return $data;
    }
}
