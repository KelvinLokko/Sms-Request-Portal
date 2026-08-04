<?php

namespace App\Http\Controllers;

use App\Enums\RecipientListStatus;
use App\Enums\SenderIdStatus;
use App\Http\Requests\Campaigns\EstimateCostRequest;
use App\Http\Requests\Campaigns\StoreSmsRequestRequest;
use App\Http\Requests\Campaigns\UpdateSmsRequestRequest;
use App\Http\Requests\Campaigns\UploadRecipientListRequest;
use App\Jobs\ValidateRecipientListJob;
use App\Models\CompanyRate;
use App\Models\RecipientList;
use App\Models\SenderId;
use App\Models\SmsRequest;
use App\Services\SmsRequestService;
use App\Support\Money;
use App\Support\Sms\CostEngine;
use App\Support\Sms\SegmentCounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SmsRequest::class);

        $campaigns = SmsRequest::query()
            ->with(['senderId:id,value,status'])
            ->latest()
            ->paginate(15)
            ->through(fn (SmsRequest $campaign) => $this->summary($campaign));

        return Inertia::render('campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', SmsRequest::class);

        return Inertia::render('campaigns/Create', [
            'senderIds' => $this->approvedSenderIds($request),
            'maxMessageLength' => SegmentCounter::MAX_MESSAGE_LENGTH,
            'warnThreshold' => SegmentCounter::WARN_THRESHOLD,
        ]);
    }

    public function store(StoreSmsRequestRequest $request, SmsRequestService $service): RedirectResponse
    {
        $user = $request->user();
        $company = $user->currentCompany;
        abort_if($company === null, 403);

        $campaign = $service->createDraft(
            $user,
            $company,
            collect($request->validated())
                ->except(['file', 'campaign_type', 'phone_column'])
                ->all(),
        );

        $this->storeRecipientList(
            $campaign,
            $request->file('file'),
            $request->validated('phone_column'),
        );

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Draft created. Recipient list uploaded — validation is running.');
    }

    public function show(SmsRequest $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load(['senderId', 'recipientList', 'creator:id,name', 'invoice']);

        return Inertia::render('campaigns/Show', [
            'campaign' => $this->detail($campaign),
            'senderIds' => $campaign->status->isEditable()
                ? $this->approvedSenderIds(request())
                : [],
            'maxMessageLength' => SegmentCounter::MAX_MESSAGE_LENGTH,
            'warnThreshold' => SegmentCounter::WARN_THRESHOLD,
            'can' => [
                'update' => request()->user()?->can('update', $campaign) ?? false,
                'submit' => request()->user()?->can('submit', $campaign) ?? false,
                'cancel' => request()->user()?->can('cancel', $campaign) ?? false,
                'upload' => request()->user()?->can('uploadRecipients', $campaign) ?? false,
            ],
        ]);
    }

    public function edit(SmsRequest $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        return redirect()->route('campaigns.show', $campaign);
    }

    public function update(
        UpdateSmsRequestRequest $request,
        SmsRequest $campaign,
        SmsRequestService $service,
    ): RedirectResponse {
        $service->updateDraft($campaign, $request->validated());

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated.');
    }

    public function submit(SmsRequest $campaign, SmsRequestService $service): RedirectResponse
    {
        $this->authorize('submit', $campaign);
        $service->submit($campaign);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign submitted for review.');
    }

    public function cancel(SmsRequest $campaign, SmsRequestService $service): RedirectResponse
    {
        $this->authorize('cancel', $campaign);
        $service->cancel($campaign);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign cancelled.');
    }

    public function uploadRecipients(
        UploadRecipientListRequest $request,
        SmsRequest $campaign,
    ): RedirectResponse {
        $this->storeRecipientList(
            $campaign,
            $request->file('file'),
            $request->validated('phone_column'),
        );

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Recipient list uploaded. Validation is running.');
    }

    /**
     * Persist an uploaded recipient file and queue validation.
     */
    private function storeRecipientList(
        SmsRequest $campaign,
        UploadedFile $file,
        ?string $phoneColumn = null,
    ): RecipientList {
        $companyId = $campaign->company_id;
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs(
            'recipient-lists/'.$companyId.'/'.$campaign->id,
            Str::uuid()->toString().'.'.$extension,
            'local',
        );

        // Replace any previous list on re-upload from the detail page.
        RecipientList::query()
            ->where('sms_request_id', $campaign->id)
            ->delete();

        $list = RecipientList::query()->create([
            'sms_request_id' => $campaign->id,
            'company_id' => $companyId,
            'original_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'status' => RecipientListStatus::Pending,
            'phone_column' => $phoneColumn,
        ]);

        ValidateRecipientListJob::dispatch($list->id);

        return $list;
    }

    public function downloadRejected(SmsRequest $campaign): StreamedResponse
    {
        $this->authorize('downloadRejected', $campaign);

        $list = $campaign->recipientList;
        abort_if($list === null || ! $list->hasRejectedExport(), 404);
        abort_unless(Storage::disk('local')->exists($list->rejected_export_path), 404);

        return Storage::disk('local')->download(
            $list->rejected_export_path,
            'rejected-'.$campaign->reference.'.csv',
        );
    }

    public function downloadTemplate(string $type): StreamedResponse
    {
        $this->authorize('create', SmsRequest::class);

        $templates = [
            'bulk' => [
                'path' => resource_path('templates/recipients/bulk.csv'),
                'filename' => 'bulk-recipients-template.csv',
            ],
            'personalised-bulk' => [
                'path' => resource_path('templates/recipients/personalised-bulk.csv'),
                'filename' => 'personalised-bulk-recipients-template.csv',
            ],
        ];

        abort_unless(isset($templates[$type]), 404);

        $path = $templates[$type]['path'];
        abort_unless(is_file($path), 404);

        return response()->streamDownload(
            static function () use ($path): void {
                $handle = fopen($path, 'rb');
                abort_if($handle === false, 500);
                fpassthru($handle);
                fclose($handle);
            },
            $templates[$type]['filename'],
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function estimate(EstimateCostRequest $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->currentCompany;
        $rate = CompanyRate::resolveFor($company);
        $rateValue = $rate !== null ? (string) $rate->rate_per_sms : '0.000000';
        $billable = (int) ($request->validated('billable_recipients') ?? 0);

        $quote = CostEngine::quote(
            $request->validated('message_body'),
            $billable,
            (string) $rateValue,
        );

        return response()->json([
            ...$quote,
            'rate_per_sms' => $rateValue,
            'formatted_cost' => Money::format($quote['cost_pesewas']),
            'requires_manual_cost_review' => ($request->validated('encoding') ?? 'text') === 'unicode',
        ]);
    }

    /**
     * @return list<array{id: int, value: string}>
     */
    private function approvedSenderIds(Request $request): array
    {
        return SenderId::query()
            ->where('status', SenderIdStatus::Approved)
            ->orderBy('value')
            ->get(['id', 'value'])
            ->map(fn (SenderId $senderId) => [
                'id' => $senderId->id,
                'value' => $senderId->value,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(SmsRequest $campaign): array
    {
        return [
            'id' => $campaign->id,
            'reference' => $campaign->reference,
            'name' => $campaign->name,
            'status' => $campaign->status->value,
            'status_label' => $campaign->status->label(),
            'sender_id' => $campaign->senderId?->value,
            'billable_recipients' => $campaign->billable_recipients,
            'quoted_cost' => $campaign->quoted_cost_pesewas !== null
                ? Money::format($campaign->quoted_cost_pesewas)
                : null,
            'estimated_cost' => $campaign->estimated_cost_pesewas !== null
                ? Money::format($campaign->estimated_cost_pesewas)
                : null,
            'requested_send_at' => $campaign->requested_send_at?->toIso8601String(),
            'created_at' => $campaign->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(SmsRequest $campaign): array
    {
        $list = $campaign->recipientList;

        return [
            ...$this->summary($campaign),
            'message_body' => $campaign->message_body,
            'encoding' => $campaign->encoding->value,
            'flash_type' => $campaign->flash_type->value,
            'is_personalised' => $campaign->is_personalised,
            'pages' => $campaign->pages,
            'exceeds_621_warning' => $campaign->exceeds_621_warning,
            'requires_manual_cost_review' => $campaign->requires_manual_cost_review,
            'rate_per_sms' => $campaign->rate_per_sms,
            'sender_id_id' => $campaign->sender_id_id,
            'hard_deadline_at' => $campaign->hard_deadline_at?->toIso8601String(),
            'submitted_at' => $campaign->submitted_at?->toIso8601String(),
            'changes_requested_reason' => $campaign->changes_requested_reason,
            'rejection_reason' => $campaign->rejection_reason,
            'invoice' => $campaign->invoice ? [
                'id' => $campaign->invoice->id,
                'number' => $campaign->invoice->number,
                'status' => $campaign->invoice->status->value,
                'total' => Money::format($campaign->invoice->total_pesewas),
            ] : null,
            'recipient_list' => $list ? [
                'id' => $list->id,
                'status' => $list->status->value,
                'status_label' => $list->status->label(),
                'original_filename' => $list->original_filename,
                'headers' => $list->headers,
                'phone_column' => $list->phone_column,
                'total_rows' => $list->total_rows,
                'valid_count' => $list->valid_count,
                'invalid_count' => $list->invalid_count,
                'duplicate_count' => $list->duplicate_count,
                'billable_count' => $list->billable_count,
                'has_rejected_export' => $list->hasRejectedExport(),
                'error_message' => $list->error_message,
                'processed_at' => $list->processed_at?->toIso8601String(),
            ] : null,
        ];
    }
}
