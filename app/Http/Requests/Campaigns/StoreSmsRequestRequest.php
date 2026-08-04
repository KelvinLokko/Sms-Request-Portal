<?php

namespace App\Http\Requests\Campaigns;

use App\Enums\MessageEncoding;
use App\Enums\MessageFlashType;
use App\Models\SmsRequest;
use App\Support\Sms\SegmentCounter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSmsRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SmsRequest::class) ?? false;
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
            'requested_send_at' => ['nullable', 'date', 'after_or_equal:now'],
            'hard_deadline_at' => ['nullable', 'date', 'after:requested_send_at'],
        ];
    }
}
