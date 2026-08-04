<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyUserRole;
use App\Enums\PlatformRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $type = $request->string('type')->toString();
        $search = $request->string('q')->trim()->toString();

        $users = User::query()
            ->with(['roles:id,name', 'currentCompany:id,name'])
            ->withCount('companies')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($type === 'staff', fn ($q) => $q->role(PlatformRole::values()))
            ->when($type === 'client', fn ($q) => $q->whereDoesntHave('roles'))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => $this->summary($user));

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => [
                'type' => in_array($type, ['staff', 'client'], true) ? $type : null,
                'q' => $search !== '' ? $search : null,
            ],
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'platformRoles' => Role::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Role $role) => [
                    'value' => $role->name,
                    'label' => PlatformRole::tryLabel($role->name),
                    'is_system' => PlatformRole::isSystem($role->name),
                ])
                ->values()
                ->all(),
            'companyRoles' => collect(CompanyUserRole::cases())->map(fn (CompanyUserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values()->all(),
            'authUserId' => $request->user()?->id,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $this->syncAccess($user, $data);
        });

        return back()->with('success', 'User created.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($user, $data): void {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $this->syncAccess($user, $data);
        });

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncAccess(User $user, array $data): void
    {
        if ($data['type'] === 'staff') {
            $user->companies()->detach();
            $user->forceFill(['current_company_id' => null])->save();
            $user->syncRoles([(string) $data['platform_role']]);

            return;
        }

        $user->syncRoles([]);
        $companyId = (int) $data['company_id'];
        $role = CompanyUserRole::from((string) $data['company_role']);

        $user->companies()->sync([
            $companyId => ['role' => $role->value],
        ]);
        $user->forceFill(['current_company_id' => $companyId])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(User $user): array
    {
        $platformRoles = $user->getRoleNames()->values()->all();
        $isStaff = $platformRoles !== [];
        $companyRole = null;

        if (! $isStaff && $user->currentCompany !== null) {
            $companyRole = $user->companyRole()?->value;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $isStaff ? 'staff' : 'client',
            'platform_roles' => $platformRoles,
            'company' => $user->currentCompany
                ? [
                    'id' => $user->currentCompany->id,
                    'name' => $user->currentCompany->name,
                ]
                : null,
            'company_role' => $companyRole,
            'companies_count' => $user->companies_count,
            'created_at' => $user->created_at?->toIso8601String(),
            'is_self' => $user->id === request()->user()?->id,
        ];
    }
}
