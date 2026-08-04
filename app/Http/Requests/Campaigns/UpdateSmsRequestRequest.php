<?php

namespace App\Http\Requests\Campaigns;

use App\Enums\MessageEncoding;
use App\Enums\MessageFlashType;
use App\Models\SmsRequest;
use App\Support\Sms\SegmentCounter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmsRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SmsRequest $smsRequest */
        $smsRequest = $this->route('campaign');

        return $this->user()?->can('update', $smsRequest) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $campaignType = $this->input('campaign_type');

        $this->merge([
            'encoding' => $this->input('encoding', MessageEncoding::Text->value),
            'flash_type' => $this->input('flash_type', MessageFlashType::Text->value),
            'is_personalised' => $campaignType === null
                ? $this->boolean('is_personalised')
                : $campaignType === 'personalised_bulk',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()?->currentCompanyId();

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'message_body' => ['nullable', 'string', 'max:'.SegmentCounter::MAX_MESSAGE_LENGTH],
            'campaign_type' => ['required', Rule::in(['bulk', 'personalised_bulk'])],
            'encoding' => ['required', Rule::enum(MessageEncoding::class)],
            'flash_type' => ['required', Rule::enum(MessageFlashType::class)],
            'is_personalised' => ['sometimes', 'boolean'],
            'sender_id_id' => [
                'nullable',
                'integer',
                Rule::exists('sender_ids', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId),
                ),
            ],
            'requested_send_at' => ['nullable', 'date'],
            'hard_deadline_at' => [
                'nullable',
                'date',
                Rule::when(
                    $this->filled('requested_send_at'),
                    ['after_or_equal:requested_send_at'],
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'campaign_type.required' => 'Choose Bulk or Personalised bulk.',
            'hard_deadline_at.after_or_equal' => 'Hard deadline must be on or after the requested send time.',
        ];
    }
}
