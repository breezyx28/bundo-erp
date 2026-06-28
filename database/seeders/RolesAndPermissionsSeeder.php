<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        App::make(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = config('permissions.permissions');

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (config('permissions.roles') as $roleName => $abilities) {
            $role = Role::findOrCreate($roleName, 'web');

            $grant = in_array('*', $abilities, true) ? $permissions : $abilities;

            // admin also sees consolidated multi-branch views.
            if ($roleName === 'admin') {
                $grant = array_unique(array_merge($grant, ['branches.view_all']));
            }

            $role->syncPermissions($grant);
        }

        // super_admin role exists for assignment; Gate::before grants it everything.
        Role::findOrCreate('super_admin', 'web');

        App::make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
