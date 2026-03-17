<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\User;
use App\Models\Role; // 👈 Role model ko import kiya gaya hai
use Illuminate\Support\Facades\Hash;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // 1. FIX: Hardcoded role_id ki jagah role ka naam search karke exact ID layenge
        $branchAdminRole = Role::where('name', 'branch_admin')->first();

        // Agar galti se role nahi mila to error dikhayega
        if (!$branchAdminRole) {
            $this->command->error('Branch Admin role nahi mila! Pehle RolePermissionSeeder run karein.');
            return;
        }

        $branches = [
            [
                'restaurant_id' => 1,
                'name' => 'Ahmedabad Branch',
                'phone' => '9876543210',
                'address' => 'CG Road',
                'email' => 'ahmedabad@restaurant.com',
            ],
            [
                'restaurant_id' => 1,
                'name' => 'Surat Branch',
                'phone' => '9876543211',
                'address' => 'Adajan',
                'email' => 'surat@restaurant.com',
            ],
            [
                'restaurant_id' => 1,
                'name' => 'Baroda Branch',
                'phone' => '9876543212',
                'address' => 'Alkapuri',
                'email' => 'baroda@restaurant.com',
            ],
        ];

        foreach ($branches as $branchData) {

            // 2. FIX: updateOrCreate use kiya taaki multiple times seeder chalane par error na aaye
            $branch = Branch::updateOrCreate(
                [
                    'restaurant_id' => $branchData['restaurant_id'],
                    'name' => $branchData['name'],
                ],
                [
                    'phone' => $branchData['phone'],
                    'address' => $branchData['address'],
                    'is_active' => true,
                ]
            );

            // 3. Create Branch Admin User
            User::updateOrCreate(
                ['email' => $branchData['email']], // Email se check karega ki user already hai ya nahi
                [
                    'restaurant_id' => $branch->restaurant_id,
                    'branch_id' => $branch->id,
                    'role_id' => $branchAdminRole->id, // 👈 Exact dynamic role ID assign hogi
                    'name' => $branch->name . ' Admin',
                    'password' => Hash::make('123'),
                    'is_active' => true,
                    // 'is_super_admin' => false, // Agar aapke user table me is_super_admin required hai to isko uncomment kar dena
                ]
            );
        }

        $this->command->info('Branches and Branch Admins seeded successfully!');
    }
}