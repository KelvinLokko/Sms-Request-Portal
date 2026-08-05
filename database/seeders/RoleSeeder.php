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
        $registrar = app()[PermissionRegistrar::class];
        $registrar->forgetCachedPermissions();

        foreach (PlatformPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // Refresh the cache so syncPermissions can resolve newly created names.
        $registrar->forgetCachedPermissions();

        foreach (PlatformRole::cases() as $platformRole) {
            $role = Role::findOrCreate($platformRole->value, 'web');
            $role->syncPermissions(
                array_map(
                    fn (PlatformPermission $permission) => $permission->value,
                    PlatformPermission::forRole($platformRole),
                ),
            );
        }

        $registrar->forgetCachedPermissions();
    }
}
