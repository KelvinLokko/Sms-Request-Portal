<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarkCampaignFulfilledRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fulfil', $this->route('campaign')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'deywuro_job_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
