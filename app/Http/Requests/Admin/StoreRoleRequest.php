<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlatformPermission;
use App\Enums\PlatformRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permissions' => array_values(array_unique($this->input('permissions', []))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('roles', 'name')->where(fn ($q) => $q->where('guard_name', 'web')),
                Rule::notIn(PlatformRole::values()),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(PlatformPermission::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Use a lowercase slug with hyphens only (e.g. ops-lead).',
            'name.not_in' => 'That name is reserved for a built-in system role.',
        ];
    }
}
