<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 👇 JAISE HI BRANCH SAVE HOGI, YEH FUNCTION CHALEGA 👇
    protected function afterCreate(): void
    {
        // Form mein jo bhi data dala tha (unme dehydrated fields bhi hote hain) usko get karo
        $data = $this->form->getRawState();

        // Database se 'branch_admin' role dhundho
        $role = Role::where('name', 'branch_admin')->first();

        // Agar form me email mili hai aur role exists karta hai, toh user bana do
        if ($role && isset($data['admin_email'])) {
            User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role_id' => $role->id, // Automatically Branch Admin set ho gaya
                'restaurant_id' => $this->record->restaurant_id, // Same restaurant
                'branch_id' => $this->record->id, // 🔥 Nayi Branch ka ID lag gaya
                'is_active' => true,
            ]);
        }
    }
}