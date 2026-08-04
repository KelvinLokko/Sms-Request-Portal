<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\MessageEncoding;
use App\Enums\RecipientListStatus;
use App\Enums\SenderIdStatus;
use App\Enums\SmsRequestStatus;
use App\Models\Company;
use App\Models\CompanyRate;
use App\Models\SenderId;
use App\Models\SmsRequest;
use App\Models\User;
use App\Support\Sms\CostEngine;
use App\Support\Sms\PlaceholderValidator;
use App\Support\Sms\SegmentCounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SmsRequestService
{
    public function __construct(
        private ActivityLogger $logger,
        private InvoiceService $invoices,
        private CampaignNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(User $user, Company $company, array $data): SmsRequest
    {
        $request = SmsRequest::query()->create([
            'company_id' => $company->id,
            'reference' => SmsRequest::generateReference(),
            'status' => SmsRequestStatus::Draft,
            'sender_id_id' => $data['sender_id_id'] ?? null,
            'name' => $data['name'] ?? null,
            'message_body' => $data['message_body'] ?? '',
            'encoding' => $data['encoding'] ?? MessageEncoding::Text->value,
            'flash_type' => $data['flash_type'] ?? 'text',
            'is_personalised' => (bool) ($data['is_personalised'] ?? false),
            'requested_send_at' => $data['requested_send_at'] ?? null,
            'hard_deadline_at' => $data['hard_deadline_at'] ?? null,
            'created_by' => $user->id,
            ...$this->derivedMessageFields((string) ($data['message_body'] ?? ''), MessageEncoding::from($data['encoding'] ?? 'text')),
        ]);

        $this->recalculateEstimate($request);

        $this->logger->log('sms_request.created', $request, [
            'reference' => $request->reference,
        ]);

        return $request->fresh() ?? $request;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(SmsRequest $request, array $data): SmsRequest
    {
        if (! $request->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or changes-requested campaigns can be edited.',
            ]);
        }

        $encoding = MessageEncoding::from($data['encoding'] ?? $request->encoding->value);
        $message = (string) ($data['message_body'] ?? $request->message_body ?? '');

        $request->fill([
            'sender_id_id' => $data['sender_id_id'] ?? $request->sender_id_id,
            'name' => $data['name'] ?? $request->name,
            'message_body' => $message,
            'encoding' => $encoding,
            'flash_type' => $data['flash_type'] ?? $request->flash_type->value,
            'is_personalised' => array_key_exists('is_personalised', $data)
                ? (bool) $data['is_personalised']
                : $request->is_personalised,
            'requested_send_at' => $data['requested_send_at'] ?? $request->requested_send_at,
            'hard_deadline_at' => $data['hard_deadline_at'] ?? $request->hard_deadline_at,
            ...$this->derivedMessageFields($message, $encoding),
        ])->save();

        $this->recalculateEstimate($request);

        $this->logger->log('sms_request.updated', $request);

        return $request->fresh() ?? $request;
    }

    public function recalculateEstimate(SmsRequest $request): void
    {
        $company = $request->company;
        $rate = CompanyRate::resolveFor($company);
        $rateValue = $rate !== null ? (string) $rate->rate_per_sms : '0.000000';
        $billable = (int) ($request->billable_recipients ?? 0);
        $message = (string) ($request->message_body ?? '');

        $quote = CostEngine::quote($message, $billable, (string) $rateValue);

        $request->forceFill([
            'pages' => $quote['pages'],
            'exceeds_621_warning' => $quote['exceeds_621_warning'],
            'rate_per_sms' => $rateValue,
            'estimated_cost_pesewas' => $quote['cost_pesewas'],
            'requires_manual_cost_review' => $request->encoding->requiresManualCostReview(),
        ])->save();
    }

    public function submit(SmsRequest $request): SmsRequest
    {
        if (! $request->status->canSubmit()) {
            throw ValidationException::withMessages([
                'status' => 'This campaign cannot be submitted in its current status.',
            ]);
        }

        $message = trim((string) $request->message_body);
        if ($message === '') {
            throw ValidationException::withMessages([
                'message_body' => 'A message body is required before submitting.',
            ]);
        }

        if (mb_strlen($message) > SegmentCounter::MAX_MESSAGE_LENGTH) {
            throw ValidationException::withMessages([
                'message_body' => 'Message cannot exceed '.SegmentCounter::MAX_MESSAGE_LENGTH.' characters.',
            ]);
        }

        $sender = $request->senderId;
        if (! $sender instanceof SenderId || $sender->status !== SenderIdStatus::Approved) {
            throw ValidationException::withMessages([
                'sender_id_id' => 'Select an approved sender ID before submitting.',
            ]);
        }

        $list = $request->recipientList;
        if ($list === null || $list->status !== RecipientListStatus::Completed) {
            throw ValidationException::withMessages([
                'recipient_list' => 'Upload and wait for recipient list validation to finish before submitting.',
            ]);
        }

        if ($list->billable_count < 1) {
            throw ValidationException::withMessages([
                'recipient_list' => 'At least one valid recipient is required.',
            ]);
        }

        if ($request->is_personalised) {
            $missing = PlaceholderValidator::missing($message, $list->headers ?? []);
            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'message_body' => 'Personalisation placeholders missing from file headers: '.implode(', ', $missing),
                ]);
            }
        }

        return DB::transaction(function () use ($request, $list, $message): SmsRequest {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $before = [
                'status' => $locked->status->value,
                'quoted_cost_pesewas' => $locked->quoted_cost_pesewas,
                'billable_recipients' => $locked->billable_recipients,
            ];

            $rate = CompanyRate::resolveFor($locked->company);
            if ($rate === null) {
                throw ValidationException::withMessages([
                    'rate' => 'No SMS rate is configured. Contact support before submitting.',
                ]);
            }

            $quote = CostEngine::quote($message, $list->billable_count, (string) $rate->rate_per_sms);

            $locked->forceFill([
                'status' => SmsRequestStatus::Submitted,
                'submitted_at' => now(),
                'billable_recipients' => $list->billable_count,
                'rate_per_sms' => $rate->rate_per_sms,
                'pages' => $quote['pages'],
                'exceeds_621_warning' => $quote['exceeds_621_warning'],
                'estimated_cost_pesewas' => $quote['cost_pesewas'],
                'quoted_cost_pesewas' => $quote['cost_pesewas'],
                'requires_manual_cost_review' => $locked->encoding->requiresManualCostReview(),
                'changes_requested_reason' => null,
            ])->save();

            $this->logger->logChange('sms_request.submitted', $locked, $before, [
                'status' => $locked->status->value,
                'quoted_cost_pesewas' => $locked->quoted_cost_pesewas,
                'billable_recipients' => $locked->billable_recipients,
                'pages' => $locked->pages,
            ]);

            DB::afterCommit(fn () => $this->notifier->submitted($locked));

            return $locked;
        });
    }

    public function cancel(SmsRequest $request): SmsRequest
    {
        if (! $request->status->canCancel()) {
            throw ValidationException::withMessages([
                'status' => 'This campaign cannot be cancelled in its current status.',
            ]);
        }

        return DB::transaction(function () use ($request): SmsRequest {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === SmsRequestStatus::Invoiced) {
                $invoice = $locked->invoice;
                if ($invoice !== null && $invoice->status === InvoiceStatus::Issued) {
                    $actor = auth()->user();
                    if ($actor instanceof User) {
                        $this->invoices->void($invoice, $actor, 'Campaign cancelled');
                    }
                }
            }

            $locked->forceFill([
                'status' => SmsRequestStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            $this->logger->log('sms_request.cancelled', $locked);

            return $locked;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function derivedMessageFields(string $message, MessageEncoding $encoding): array
    {
        $pages = SegmentCounter::pages(mb_strlen($message));

        return [
            'pages' => $pages,
            'exceeds_621_warning' => SegmentCounter::exceedsPracticalLimit(mb_strlen($message)),
            'requires_manual_cost_review' => $encoding->requiresManualCostReview(),
        ];
    }
}
