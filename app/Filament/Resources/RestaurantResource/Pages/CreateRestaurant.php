<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 👇 JAISE HI RESTAURANT SAVE HOGA, YEH FUNCTION CHALEGA 👇
    protected function afterCreate(): void
    {
        // Form mein jo bhi data dala tha (including dehydrated(false) fields) usko get karo
        $data = $this->form->getRawState();

        // Database se 'restaurant_admin' role dhundho
        $role = Role::where('name', 'restaurant_admin')->first();

        // Agar form me email mili hai aur role exists karta hai, toh user bana do
        if ($role && !empty($data['admin_email'])) {
            User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role_id' => $role->id, // Automatically Restaurant Admin set ho gaya
                'restaurant_id' => $this->record->id, // 🔥 Naye Restaurant ka ID lag gaya
                'branch_id' => null, // Main admin ka koi specific branch nahi hota
                'is_active' => true,
            ]);
        }
    }
}