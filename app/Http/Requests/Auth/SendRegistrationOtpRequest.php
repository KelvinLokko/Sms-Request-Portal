<?php

namespace App\Http\Requests\Auth;

use App\Concerns\ProfileValidationRules;
use App\Support\Honeypot;
use Illuminate\Foundation\Http\FormRequest;

class SendRegistrationOtpRequest extends FormRequest
{
    use ProfileValidationRules;

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
            ...$this->profileRules(),
            Honeypot::FIELD => ['nullable', 'string', 'max:0'],
            'cf-turnstile-response' => ['nullable', 'string'],
        ];
    }
}
