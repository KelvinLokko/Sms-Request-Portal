<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SmsRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectCampaignRequest;
use App\Http\Requests\Admin\RequestCampaignChangesRequest;
use App\Models\SmsRequest;
use App\Services\CampaignReviewService;
use App\Services\InvoiceService;
use App\Support\ListFilters;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SmsRequest::class);

        $filters = ListFilters::fromRequest($request);
        $status = $filters['status'] ?? '';
        $allowed = array_map(
            fn (SmsRequestStatus $case) => $case->value,
            SmsRequestStatus::cases(),
        );
        $needsReview = [
            SmsRequestStatus::Submitted->value,
            SmsRequestStatus::UnderReview->value,
        ];
        $filterNeedsReview = $status === 'needs_review';
        $filterStatus = in_array($status, $allowed, true) ? $status : null;

        $campaigns = SmsRequest::query()
            ->with(['company:id,name', 'senderId:id,value', 'creator:id,name,email'])
            ->when(
                $filterNeedsReview,
                fn ($q) => $q->whereIn('status', $needsReview),
            )
            ->when(
                $filterStatus !== null,
                fn ($q) => $q->where('status', $filterStatus),
            )
            ->tap(fn ($query) => ListFilters::applyDateRange(
                $query,
                $filters['from'],
                $filters['to'],
                'submitted_at',
            ))
            ->when(
                $filters['q'] !== null,
                function ($query) use ($filters): void {
                    $term = '%'.$filters['q'].'%';

                    $query->where(function ($inner) use ($term): void {
                        $inner->where('reference', 'like', $term)
                            ->orWhere('name', 'like', $term)
                            ->orWhereHas('company', fn ($company) => $company->where('name', 'like', $term));
                    });
                },
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SmsRequest $campaign) => [
                'id' => $campaign->id,
                'reference' => $campaign->reference,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
                'status_label' => $campaign->status->label(),
                'company' => [
                    'id' => $campaign->company->id,
                    'name' => $campaign->company->name,
                ],
                'sender_id' => $campaign->senderId?->value,
                'billable_recipients' => $campaign->billable_recipients,
                'quoted_cost' => $campaign->quoted_cost_pesewas !== null
                    ? Money::format($campaign->quoted_cost_pesewas)
                    : null,
                'requires_manual_cost_review' => $campaign->requires_manual_cost_review,
                'exceeds_621_warning' => $campaign->exceeds_621_warning,
                'submitted_at' => $campaign->submitted_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => [
                ...$filters,
                'status' => $filterNeedsReview ? 'needs_review' : $filterStatus,
            ],
            'statusOptions' => [
                [
                    'value' => 'needs_review',
                    'label' => 'Needs review',
                ],
                ...collect(SmsRequestStatus::cases())
                    ->map(fn (SmsRequestStatus $status) => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function show(SmsRequest $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load(['company', 'senderId', 'creator:id,name,email', 'recipientList', 'invoice']);

        return Inertia::render('admin/campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'reference' => $campaign->reference,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
                'status_label' => $campaign->status->label(),
                'message_body' => $campaign->message_body,
                'encoding' => $campaign->encoding->value,
                'pages' => $campaign->pages,
                'is_personalised' => $campaign->is_personalised,
                'requires_manual_cost_review' => $campaign->requires_manual_cost_review,
                'exceeds_621_warning' => $campaign->exceeds_621_warning,
                'billable_recipients' => $campaign->billable_recipients,
                'rate_per_sms' => $campaign->rate_per_sms,
                'quoted_cost' => $campaign->quoted_cost_pesewas !== null
                    ? Money::format($campaign->quoted_cost_pesewas)
                    : null,
                'quoted_cost_pesewas' => $campaign->quoted_cost_pesewas,
                'requested_send_at' => $campaign->requested_send_at?->toIso8601String(),
                'hard_deadline_at' => $campaign->hard_deadline_at?->toIso8601String(),
                'submitted_at' => $campaign->submitted_at?->toIso8601String(),
                'company' => [
                    'id' => $campaign->company->id,
                    'name' => $campaign->company->name,
                    'email' => $campaign->company->email,
                ],
                'sender_id' => $campaign->senderId?->value,
                'creator' => [
                    'name' => $campaign->creator->name,
                    'email' => $campaign->creator->email,
                ],
                'recipient_list' => $campaign->recipientList ? [
                    'billable_count' => $campaign->recipientList->billable_count,
                    'valid_count' => $campaign->recipientList->valid_count,
                    'invalid_count' => $campaign->recipientList->invalid_count,
                    'duplicate_count' => $campaign->recipientList->duplicate_count,
                ] : null,
                'invoice_number' => $campaign->invoice?->number,
            ],
            'can' => [
                'start_review' => $campaign->status === SmsRequestStatus::Submitted
                    && (request()->user()?->can('review', $campaign) ?? false),
                'request_changes' => request()->user()?->can('requestChanges', $campaign) ?? false,
                'reject' => request()->user()?->can('reject', $campaign) ?? false,
                'issue_invoice' => request()->user()?->can('issueInvoice', $campaign) ?? false,
            ],
        ]);
    }

    public function startReview(
        SmsRequest $campaign,
        CampaignReviewService $service,
    ): RedirectResponse {
        $this->authorize('review', $campaign);
        $service->startReview($campaign, request()->user());

        return back()->with('success', 'Campaign moved to under review.');
    }

    public function requestChanges(
        RequestCampaignChangesRequest $request,
        SmsRequest $campaign,
        CampaignReviewService $service,
    ): RedirectResponse {
        $service->requestChanges($campaign, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Changes requested from the client.');
    }

    public function reject(
        RejectCampaignRequest $request,
        SmsRequest $campaign,
        CampaignReviewService $service,
    ): RedirectResponse {
        $service->reject($campaign, $request->user(), $request->validated('rejection_reason'));

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign rejected.');
    }

    public function issueInvoice(
        SmsRequest $campaign,
        InvoiceService $invoices,
    ): RedirectResponse {
        $this->authorize('issueInvoice', $campaign);
        $invoice = $invoices->issue($campaign, request()->user());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->number} issued.");
    }
}
