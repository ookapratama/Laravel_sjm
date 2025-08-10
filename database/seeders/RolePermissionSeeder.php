<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $roles = ['super-admin', 'admin', 'finance', 'member'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $permissions = [
            'view-dashboard',
            'manage-members',
            'view-bonus',
            'approve-withdraw',
            'view-finance-dashboard',
            'edit-network',
            'view-reward',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::findByName('super-admin')->syncPermissions($permissions);
        Role::findByName('admin')->syncPermissions([
            'view-dashboard',
            'manage-members',
            'view-bonus',
            'edit-network',
        ]);
        Role::findByName('finance')->syncPermissions([
            'view-dashboard',
            'approve-withdraw',
            'view-finance-dashboard',
        ]);
        Role::findByName('member')->syncPermissions([
            'view-dashboard',
            'view-reward',
        ]);
    }
}
