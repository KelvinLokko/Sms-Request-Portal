<?php

namespace App\Services;

use App\Enums\RecipientRowStatus;
use App\Enums\SmsRequestStatus;
use App\Models\SmsRecipient;
use App\Models\SmsRequest;
use App\Models\User;
use App\Support\CsvFormulaEscaper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FulfilmentService
{
    public function __construct(
        private ActivityLogger $logger,
        private CampaignNotifier $notifier,
    ) {}

    public function portalCampaignName(SmsRequest $request): string
    {
        $slug = $request->company->slug ?: 'company';

        return 'PORTAL-'.$request->reference.'-'.$slug;
    }

    public function start(SmsRequest $request, User $actor): SmsRequest
    {
        if ($request->status === SmsRequestStatus::AwaitingFulfilment) {
            return $request;
        }

        if ($request->status !== SmsRequestStatus::Paid) {
            throw ValidationException::withMessages([
                'status' => 'Only paid campaigns can enter fulfilment.',
            ]);
        }

        return DB::transaction(function () use ($request, $actor): SmsRequest {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === SmsRequestStatus::AwaitingFulfilment) {
                return $locked;
            }

            $locked->forceFill([
                'status' => SmsRequestStatus::AwaitingFulfilment,
            ])->save();

            $this->logger->log('sms_request.awaiting_fulfilment', $locked, [], $actor);

            return $locked;
        });
    }

    public function markFulfilled(SmsRequest $request, User $actor, ?string $deywuroJobReference = null): SmsRequest
    {
        if (! $request->status->canFulfil()) {
            throw ValidationException::withMessages([
                'status' => 'Only paid or awaiting-fulfilment campaigns can be marked fulfilled.',
            ]);
        }

        return DB::transaction(function () use ($request, $actor, $deywuroJobReference): SmsRequest {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canFulfil()) {
                throw ValidationException::withMessages([
                    'status' => 'Only paid or awaiting-fulfilment campaigns can be marked fulfilled.',
                ]);
            }

            $before = [
                'status' => $locked->status->value,
                'fulfilled_at' => $locked->fulfilled_at?->toIso8601String(),
                'deywuro_job_reference' => $locked->deywuro_job_reference,
            ];

            $locked->forceFill([
                'status' => SmsRequestStatus::Fulfilled,
                'fulfilled_at' => now(),
                'fulfilled_by' => $actor->id,
                'deywuro_job_reference' => $deywuroJobReference !== null && $deywuroJobReference !== ''
                    ? $deywuroJobReference
                    : null,
            ])->save();

            $this->logger->logChange('sms_request.fulfilled', $locked, $before, [
                'status' => $locked->status->value,
                'fulfilled_at' => $locked->fulfilled_at?->toIso8601String(),
                'fulfilled_by' => $locked->fulfilled_by,
                'deywuro_job_reference' => $locked->deywuro_job_reference,
            ], [], $actor);

            DB::afterCommit(fn () => $this->notifier->fulfilled($locked));

            return $locked;
        });
    }

    public function downloadCleanedRecipients(SmsRequest $request): StreamedResponse
    {
        $list = $request->recipientList;
        if ($list === null) {
            throw ValidationException::withMessages([
                'recipient_list' => 'No recipient list is available for this campaign.',
            ]);
        }

        $filename = 'PORTAL-'.$request->reference.'-recipients.csv';
        $isPersonalised = $request->is_personalised;
        $headers = $list->headers ?? [];

        return response()->streamDownload(function () use ($request, $list, $isPersonalised, $headers): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                throw new \RuntimeException('Unable to open output stream.');
            }

            $columnHeaders = ['msisdn'];
            if ($isPersonalised) {
                foreach ($headers as $header) {
                    if (strtolower((string) $header) === 'phone' || strtolower((string) $header) === 'msisdn') {
                        continue;
                    }
                    $columnHeaders[] = (string) $header;
                }
            }

            fputcsv($handle, $columnHeaders);

            SmsRecipient::query()
                ->where('recipient_list_id', $list->id)
                ->where('sms_request_id', $request->id)
                ->where('status', RecipientRowStatus::Valid)
                ->whereNotNull('normalised_msisdn')
                ->orderBy('id')
                ->cursor()
                ->each(function (SmsRecipient $recipient) use ($handle, $isPersonalised, $columnHeaders): void {
                    $row = [CsvFormulaEscaper::escape((string) $recipient->normalised_msisdn)];

                    if ($isPersonalised) {
                        $data = $recipient->personalisation_data ?? [];
                        foreach (array_slice($columnHeaders, 1) as $header) {
                            $value = $data[$header] ?? $data[strtolower($header)] ?? '';
                            $row[] = CsvFormulaEscaper::escape((string) $value);
                        }
                    }

                    fputcsv($handle, $row);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
