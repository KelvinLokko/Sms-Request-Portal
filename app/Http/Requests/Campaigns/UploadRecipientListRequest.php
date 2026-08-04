<?php

namespace App\Http\Requests\Campaigns;

use App\Models\SmsRequest;
use Illuminate\Foundation\Http\FormRequest;

class UploadRecipientListRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SmsRequest $smsRequest */
        $smsRequest = $this->route('campaign');

        return $this->user()?->can('uploadRecipients', $smsRequest) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx',
                'max:51200', // 50 MB
            ],
            'phone_column' => ['nullable', 'string', 'max:64'],
        ];
    }
}
