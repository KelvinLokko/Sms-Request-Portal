<?php

namespace App\Http\Requests\Campaigns;

use App\Support\Sms\SegmentCounter;
use Illuminate\Foundation\Http\FormRequest;

class EstimateCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message_body' => ['required', 'string', 'max:'.SegmentCounter::MAX_MESSAGE_LENGTH],
            'billable_recipients' => ['nullable', 'integer', 'min:0'],
            'encoding' => ['nullable', 'string', 'in:text,unicode'],
        ];
    }
}
