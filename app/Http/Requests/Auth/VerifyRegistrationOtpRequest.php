<?php

namespace App\Http\Requests\Auth;

use App\Support\Honeypot;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRegistrationOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            Honeypot::FIELD => ['nullable', 'string', 'max:0'],
        ];
    }
}
