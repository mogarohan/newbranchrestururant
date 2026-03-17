<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- Define all permissions with human-readable labels ---
        $permissions = [
            // Restaurant management
            ['name' => 'restaurant.create', 'label' => 'Create Restaurant'],
            ['name' => 'restaurant.view', 'label' => 'View Restaurant'],
            ['name' => 'restaurant.update', 'label' => 'Update Restaurant'],
            ['name' => 'restaurant.delete', 'label' => 'Delete Restaurant'],

            // User management
            ['name' => 'user.manage', 'label' => 'Manage Users'],
            ['name' => 'role.manage', 'label' => 'Manage Roles'],
            ['name' => 'permission.manage', 'label' => 'Manage Permissions'],

            // Menu & Table
            ['name' => 'menu.manage', 'label' => 'Manage Menu'],
            ['name' => 'menu.view', 'label' => 'View Menu'],
            ['name' => 'menu.update_availability', 'label' => 'Update Menu Availability'],
            ['name' => 'table.manage', 'label' => 'Manage Tables'],
            ['name' => 'table.view', 'label' => 'View Tables'], // Added this as waiter uses it

            // Orders
            ['name' => 'order.view', 'label' => 'View Orders'],
            ['name' => 'order.update_status', 'label' => 'Update Order Status'],
            ['name' => 'order.view_kitchen', 'label' => 'View Kitchen Orders'],
            ['name' => 'order.create', 'label' => 'Create Orders'],
            ['name' => 'order.cancel', 'label' => 'Cancel Orders'],

            // Payment
            ['name' => 'payment.collect', 'label' => 'Collect Payment'],

            // Reports & Activity
            ['name' => 'report.view', 'label' => 'View Reports'],
            ['name' => 'activity.view', 'label' => 'View Activity Logs'],
            ['name' => 'kitchen.queue', 'label' => 'Manage Kitchen Queue'],
        ];

        // --- Create permissions ---
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['label' => $perm['label']]
            );
        }

        // --- Define default roles and their permissions ---
        $roles = [
            'super_admin' => array_column($permissions, 'name'), // Super Admin gets all

            'restaurant_admin' => [
                'user.manage',
                'role.manage',
                'permission.manage',
                'menu.manage',
                'table.manage',
                'order.view',
                'order.update_status',
                'payment.collect',
                'activity.view',
                'report.view'
            ],

            // --- ADDED: Branch Admin ---
            // Branch Admin can manage users in their branch, menu, tables, and view reports
            'branch_admin' => [
                'user.manage',
                'menu.manage',
                'table.manage',
                'order.view',
                'order.update_status',
                'order.create',
                'order.cancel',
                'payment.collect',
                'report.view',
                'activity.view'
            ],

            'manager' => [
                'order.view',
                'order.update_status',
                'menu.update_availability',
                'table.manage',
                'payment.collect',
                'report.view'
            ],

            'chef' => [
                'order.view_kitchen',
                'order.update_status',
                'menu.view',
                'kitchen.queue'
            ],

            'waiter' => [
                'table.view',
                'order.create',
                'order.update_status',
                'payment.collect',
                'order.view'
            ],

            'customer' => [
                'order.create',
                'order.view',
                'order.cancel',
                'menu.view'
            ],
        ];

        // --- Create roles and attach permissions ---
        foreach ($roles as $roleName => $permNames) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['label' => ucfirst(str_replace('_', ' ', $roleName))]
            );

            $rolePermissions = Permission::whereIn('name', $permNames)->pluck('id')->toArray();
            $role->permissions()->sync($rolePermissions);
        }

        $this->command->info('Roles and Permissions seeded successfully!');
    }
}