<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyUserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $subject */
        $subject = $this->route('user');

        return $this->user()?->can('update', $subject) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $subject */
        $subject = $this->route('user');
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::in(['staff', 'client'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($subject->id),
            ],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
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
