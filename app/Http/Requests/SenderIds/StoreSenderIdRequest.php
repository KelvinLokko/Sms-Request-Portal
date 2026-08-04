<?php

namespace App\Http\Requests\SenderIds;

use App\Models\SenderId;
use App\Rules\GsmAlphanumericSenderId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSenderIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SenderId::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()?->currentCompanyId();

        return [
            'value' => [
                'required',
                'string',
                'max:11',
                new GsmAlphanumericSenderId,
                Rule::unique('sender_ids', 'value')->where(
                    fn ($query) => $query->where('company_id', $companyId),
                ),
            ],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:5120',
            ],
            'uses_company_letterhead' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('value') && is_string($this->input('value'))) {
            $this->merge(['value' => trim($this->input('value'))]);
        }
    }
}
