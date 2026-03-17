<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestaurantSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Roles Exist (Required for foreign keys)
        // FIX: Ab hum 'id' ki jagah 'name' se database check karenge taaki 1062 error na aaye.
        $roles = [
            ['name' => 'super_admin', 'label' => 'Super admin'],
            ['name' => 'restaurant_admin', 'label' => 'Restaurant admin'],
            ['name' => 'branch_admin', 'label' => 'Branch admin'], // Added this from our previous steps
            ['name' => 'manager', 'label' => 'Manager'],
            ['name' => 'chef', 'label' => 'Chef'],
            ['name' => 'waiter', 'label' => 'Waiter'],
            ['name' => 'customer', 'label' => 'Customer'],
        ];

        // Har role ka ID fetch karke is array mein store karenge
        $roleModels = [];
        foreach ($roles as $roleData) {
            $roleModels[$roleData['name']] = Role::updateOrCreate(
                ['name' => $roleData['name']], // Search by unique name
                ['label' => $roleData['label']]
            );
        }

        // 2. Create a Super Admin (Restaurants need a 'created_by' user)
        // $superAdmin = User::updateOrCreate(
        //     ['email' => 'superadmin@system.com'],
        //     [
        //         'name' => 'System Super Admin',
        //         'password' => Hash::make('password'),
        //         'role_id' => $roleModels['super_admin']->id, // Dynamic role ID
        //         'is_super_admin' => true,
        //         'is_active' => true,
        //     ]
        // );

        // 3. Create the Restaurant
        $restaurant = Restaurant::updateOrCreate(
            ['name' => 'RESTAURANT 1'],
            [
                'slug' => Str::slug('RESTAURANT 1'),
                'user_limits' => 20,
                'is_active' => true,
                'created_by' => 1,
            ]
        );

        // 4. Create the Staff Users
        // FIX: Hardcoded role_id (2, 3, 4, 5) hata kar dynamic $roleModels assign kiya gaya hai
        $staffUsers = [
            [
                'name' => 'Admin',
                'email' => 'admin1@user.com',
                'password' => Hash::make('123'),
                'role_id' => $roleModels['restaurant_admin']->id, // Dynamically gets correct ID
                'restaurant_id' => $restaurant->id,
                'is_super_admin' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Manager',
                'email' => 'manager1@user.com',
                'password' => Hash::make('123'),
                'role_id' => $roleModels['manager']->id, // Dynamically gets correct ID
                'restaurant_id' => $restaurant->id,
                'is_super_admin' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Chef',
                'email' => 'chef1@user.com',
                'password' => Hash::make('123'),
                'role_id' => $roleModels['chef']->id, // Dynamically gets correct ID
                'restaurant_id' => $restaurant->id,
                'is_super_admin' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Waiter',
                'email' => 'waiter1@user.com',
                'password' => Hash::make('123'),
                'role_id' => $roleModels['waiter']->id, // Dynamically gets correct ID
                'restaurant_id' => $restaurant->id,
                'is_super_admin' => false,
                'is_active' => true,
            ],
        ];

        foreach ($staffUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']], // Check by email so we don't duplicate
                $userData
            );
        }

        $this->command->info('Restaurant 1 and all staff (Admin, Manager, Chef, Waiter) created successfully!');
    }
}