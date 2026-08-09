<?php

namespace App\Jobs;

use App\Enums\RecipientListStatus;
use App\Enums\RecipientRowStatus;
use App\Models\RecipientList;
use App\Models\SmsRecipient;
use App\Models\SmsRequest;
use App\Services\SmsRequestService;
use App\Support\CsvFormulaEscaper;
use App\Support\PrivateStorage;
use App\Support\Sms\GhanaNumberNormaliser;
use App\Support\Sms\RecipientFileReader;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ValidateRecipientListJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $recipientListId)
    {
        $this->onQueue('validation');
    }

    public function uniqueId(): string
    {
        return 'validate-recipient-list-'.$this->recipientListId;
    }

    public function handle(): void
    {
        $list = RecipientList::query()->withoutGlobalScopes()->find($this->recipientListId);

        if ($list === null) {
            return;
        }

        $list->forceFill([
            'status' => RecipientListStatus::Processing,
            'error_message' => null,
        ])->save();

        try {
            $this->process($list);
        } catch (Throwable $e) {
            $list->forceFill([
                'status' => RecipientListStatus::Failed,
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ])->save();

            throw $e;
        }
    }

    private function process(RecipientList $list): void
    {
        $extension = strtolower(pathinfo($list->original_filename, PATHINFO_EXTENSION));

        PrivateStorage::withLocalPath($list->original_path, function (string $absolutePath) use ($list, $extension): void {
            $this->processLocalFile($list, $absolutePath, $extension);
        });
    }

    private function processLocalFile(RecipientList $list, string $absolutePath, string $extension): void
    {
        // Clear previous recipients for re-uploads.
        SmsRecipient::query()
            ->withoutGlobalScopes()
            ->where('recipient_list_id', $list->id)
            ->delete();

        $ctx = new RecipientValidationContext;

        RecipientFileReader::each(
            $absolutePath,
            $extension,
            function (array $detectedHeaders) use ($ctx, $list): void {
                $ctx->headers = $detectedHeaders;
                $ctx->phoneColumn = $list->phone_column ?: RecipientFileReader::detectPhoneColumn($ctx->headers);
                $index = array_search($ctx->phoneColumn, $ctx->headers, true);
                if ($index === false) {
                    $ctx->phoneIndex = 0;
                    $ctx->phoneColumn = $ctx->headers[0] ?? 'phone';
                } else {
                    $ctx->phoneIndex = $index;
                }
            },
            function (int $rowNumber, array $values) use ($ctx, $list): void {
                $ctx->total++;
                $raw = $values[$ctx->phoneIndex] ?? '';
                $personalisation = [];
                foreach ($values as $i => $value) {
                    $header = $ctx->headers[$i] ?? 'column_'.$i;
                    $personalisation[$header] = $value;
                }

                $result = GhanaNumberNormaliser::normalise($raw);

                if (! $result['ok'] || $result['msisdn'] === null) {
                    $ctx->invalid++;
                    $reason = $result['reason'] ?? 'Invalid number';
                    $ctx->buffer[] = $this->rowPayload($list, $rowNumber, $raw, null, RecipientRowStatus::Invalid, $reason, $personalisation);
                    $ctx->rejectedRows[] = [$rowNumber, $raw, $reason];
                } elseif (isset($ctx->seen[$result['msisdn']])) {
                    $ctx->duplicates++;
                    $reason = 'Duplicate of row '.$ctx->seen[$result['msisdn']];
                    $ctx->buffer[] = $this->rowPayload($list, $rowNumber, $raw, $result['msisdn'], RecipientRowStatus::Duplicate, $reason, $personalisation);
                    $ctx->rejectedRows[] = [$rowNumber, $raw, $reason];
                } else {
                    $ctx->seen[$result['msisdn']] = $rowNumber;
                    $ctx->valid++;
                    $ctx->buffer[] = $this->rowPayload($list, $rowNumber, $raw, $result['msisdn'], RecipientRowStatus::Valid, null, $personalisation);
                }

                if (count($ctx->buffer) >= 500) {
                    $this->flush($ctx->buffer);
                    $ctx->buffer = [];
                }
            },
        );

        if ($ctx->buffer !== []) {
            $this->flush($ctx->buffer);
        }

        $rejectedPath = null;
        if ($ctx->rejectedRows !== []) {
            $rejectedPath = $this->writeRejectedExport($list, $ctx->rejectedRows);
        }

        $headers = $ctx->headers;
        $phoneColumn = $ctx->phoneColumn;
        $total = $ctx->total;
        $valid = $ctx->valid;
        $invalid = $ctx->invalid;
        $duplicates = $ctx->duplicates;

        DB::transaction(function () use ($list, $headers, $phoneColumn, $total, $valid, $invalid, $duplicates, $rejectedPath): void {
            $list->forceFill([
                'headers' => $headers,
                'phone_column' => $phoneColumn,
                'total_rows' => $total,
                'valid_count' => $valid,
                'invalid_count' => $invalid,
                'duplicate_count' => $duplicates,
                'billable_count' => $valid,
                'rejected_export_path' => $rejectedPath,
                'status' => RecipientListStatus::Completed,
                'processed_at' => now(),
                'error_message' => null,
            ])->save();

            $campaign = SmsRequest::query()
                ->withoutGlobalScopes()
                ->whereKey($list->sms_request_id)
                ->first();

            if ($campaign === null) {
                return;
            }

            $campaign->forceFill([
                'billable_recipients' => $valid,
            ])->save();
        });

        // Recalculate outside the write lock so rate resolution stays simple,
        // and always run against a freshly loaded campaign + list.
        $campaign = SmsRequest::query()
            ->withoutGlobalScopes()
            ->with(['company', 'recipientList'])
            ->find($list->sms_request_id);

        if ($campaign !== null) {
            app(SmsRequestService::class)->recalculateEstimate($campaign);
        }
    }

    /**
     * @param  array<string, mixed>  $personalisation
     * @return array<string, mixed>
     */
    private function rowPayload(
        RecipientList $list,
        int $rowNumber,
        string $raw,
        ?string $msisdn,
        RecipientRowStatus $status,
        ?string $reason,
        array $personalisation,
    ): array {
        return [
            'recipient_list_id' => $list->id,
            'sms_request_id' => $list->sms_request_id,
            'company_id' => $list->company_id,
            'row_number' => $rowNumber,
            'raw_value' => mb_substr($raw, 0, 64),
            'normalised_msisdn' => $msisdn,
            'status' => $status->value,
            'rejection_reason' => $reason,
            'personalisation_data' => $personalisation === [] ? null : json_encode($personalisation),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $buffer
     */
    private function flush(array $buffer): void
    {
        SmsRecipient::query()->insert($buffer);
    }

    /**
     * @param  list<array{0: int, 1: string, 2: string}>  $rejectedRows
     */
    private function writeRejectedExport(RecipientList $list, array $rejectedRows): string
    {
        $relative = 'recipient-lists/'.$list->company_id.'/'.$list->id.'-rejected-'.Str::uuid().'.csv';
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open temp stream for rejected export.');
        }

        fputcsv($handle, ['row_number', 'raw_value', 'reason']);
        foreach ($rejectedRows as [$rowNumber, $raw, $reason]) {
            // Escape formula injection on export.
            $safeRaw = CsvFormulaEscaper::escape($raw);
            $safeReason = CsvFormulaEscaper::escape($reason);
            fputcsv($handle, [$rowNumber, $safeRaw, $safeReason]);
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        PrivateStorage::disk()->put($relative, $contents);

        return $relative;
    }
}
