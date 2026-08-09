<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlatformPermission;
use App\Enums\PlatformRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(function (Role $role) {
                $permissionNames = $role->permissions->pluck('name')->values()->all();

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => PlatformRole::tryLabel($role->name),
                    'description' => PlatformRole::tryDescription($role->name),
                    'permissions' => $permissionNames,
                    'permission_labels' => collect($permissionNames)
                        ->map(fn (string $name) => PlatformPermission::tryFrom($name)?->label() ?? $name)
                        ->values()
                        ->all(),
                    'users_count' => $role->users_count,
                    'is_system' => PlatformRole::isSystem($role->name),
                ];
            });

        return Inertia::render('admin/roles/Index', [
            'roles' => $roles,
            'permissionCatalog' => PlatformPermission::catalog(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $role = Role::findOrCreate($data['name'], 'web');
        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role created.');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('update', $role);

        $data = $request->validated();

        if (! PlatformRole::isSystem($role->name) && isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        if ($role->users()->exists()) {
            return back()->with('error', 'Remove all users from this role before deleting it.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role deleted.');
    }
}
