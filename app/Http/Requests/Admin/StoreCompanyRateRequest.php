<?php

namespace App\Http\Requests\Admin;

use App\Models\CompanyRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CompanyRate::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'rate_per_sms' => ['required', 'numeric', 'min:0.000001', 'max:999999.999999'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
