<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyUserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::in(['staff', 'client'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'platform_role' => [
                Rule::requiredIf($type === 'staff'),
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
            'company_id' => [
                Rule::requiredIf($type === 'client'),
                'nullable',
                'integer',
                'exists:companies,id',
            ],
            'company_role' => [
                Rule::requiredIf($type === 'client'),
                'nullable',
                Rule::enum(CompanyUserRole::class),
            ],
        ];
    }
}
