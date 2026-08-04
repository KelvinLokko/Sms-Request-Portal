<?php

namespace Database\Seeders;

use App\Enums\PlatformPermission;
use App\Enums\PlatformRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PlatformPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (PlatformRole::cases() as $platformRole) {
            $role = Role::findOrCreate($platformRole->value, 'web');
            $role->syncPermissions(
                array_map(
                    fn (PlatformPermission $permission) => $permission->value,
                    PlatformPermission::forRole($platformRole),
                ),
            );
        }
    }
}
