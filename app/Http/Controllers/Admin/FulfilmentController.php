<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SmsRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkCampaignFulfilledRequest;
use App\Models\SmsRequest;
use App\Services\FulfilmentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FulfilmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SmsRequest::class);

        $filter = $request->string('filter')->toString();
        $overdueOnly = $filter === 'overdue';

        $campaigns = SmsRequest::query()
            ->with(['company:id,name,slug', 'senderId:id,value'])
            ->whereIn('status', [
                SmsRequestStatus::Paid,
                SmsRequestStatus::AwaitingFulfilment,
            ])
            ->when($overdueOnly, function ($query): void {
                $query->whereNotNull('hard_deadline_at')
                    ->where('hard_deadline_at', '<=', now()->addDay());
            })
            ->orderByRaw('hard_deadline_at is null')
            ->orderBy('hard_deadline_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SmsRequest $campaign) => $this->summary($campaign));

        return Inertia::render('admin/fulfilment/Index', [
            'campaigns' => $campaigns,
            'filters' => [
                'filter' => $overdueOnly ? 'overdue' : 'all',
            ],
            'overdue_count' => SmsRequest::query()
                ->whereIn('status', [
                    SmsRequestStatus::Paid,
                    SmsRequestStatus::AwaitingFulfilment,
                ])
                ->whereNotNull('hard_deadline_at')
                ->where('hard_deadline_at', '<=', now()->addDay())
                ->count(),
        ]);
    }

    public function show(SmsRequest $campaign, FulfilmentService $service): Response
    {
        $this->authorize('fulfil', $campaign);

        if ($campaign->status === SmsRequestStatus::Paid) {
            $campaign = $service->start($campaign, request()->user());
        }

        $campaign->load(['company:id,name,slug', 'senderId:id,value', 'recipientList', 'invoice']);

        return Inertia::render('admin/fulfilment/Show', [
            'campaign' => [
                ...$this->summary($campaign),
                'message_body' => $campaign->message_body,
                'encoding' => $campaign->encoding->value,
                'flash_type' => $campaign->flash_type->value,
                'is_personalised' => $campaign->is_personalised,
                'pages' => $campaign->pages,
                'rate_per_sms' => $campaign->rate_per_sms,
                'quoted_cost' => $campaign->quoted_cost_pesewas !== null
                    ? Money::format($campaign->quoted_cost_pesewas)
                    : null,
                'requested_send_at' => $campaign->requested_send_at?->toIso8601String(),
                'hard_deadline_at' => $campaign->hard_deadline_at?->toIso8601String(),
                'portal_campaign_name' => $service->portalCampaignName($campaign),
                'recipient_list' => $campaign->recipientList ? [
                    'billable_count' => $campaign->recipientList->billable_count,
                    'valid_count' => $campaign->recipientList->valid_count,
                ] : null,
                'invoice_number' => $campaign->invoice?->number,
                'cleaned_recipients_url' => URL::temporarySignedRoute(
                    'admin.fulfilment.recipients',
                    now()->addMinutes(30),
                    ['campaign' => $campaign->id],
                    absolute: false,
                ),
            ],
            'can' => [
                'fulfil' => request()->user()?->can('fulfil', $campaign) ?? false,
            ],
        ]);
    }

    public function markFulfilled(
        MarkCampaignFulfilledRequest $request,
        SmsRequest $campaign,
        FulfilmentService $service,
    ): RedirectResponse {
        $service->markFulfilled(
            $campaign,
            $request->user(),
            $request->validated('deywuro_job_reference'),
        );

        return redirect()
            ->route('admin.fulfilment.index')
            ->with('success', "Campaign {$campaign->reference} marked as sent.");
    }

    public function downloadRecipients(
        SmsRequest $campaign,
        FulfilmentService $service,
    ): StreamedResponse {
        $this->authorize('downloadCleanedRecipients', $campaign);

        return $service->downloadCleanedRecipients($campaign);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(SmsRequest $campaign): array
    {
        $deadline = $campaign->hard_deadline_at;
        $isOverdue = $deadline !== null && $deadline->lte(now());
        $isApproaching = $deadline !== null && ! $isOverdue && $deadline->lte(now()->addDay());

        return [
            'id' => $campaign->id,
            'reference' => $campaign->reference,
            'name' => $campaign->name,
            'status' => $campaign->status->value,
            'status_label' => $campaign->status->label(),
            'company' => [
                'id' => $campaign->company->id,
                'name' => $campaign->company->name,
                'slug' => $campaign->company->slug,
            ],
            'sender_id' => $campaign->senderId?->value,
            'billable_recipients' => $campaign->billable_recipients,
            'requested_send_at' => $campaign->requested_send_at?->toIso8601String(),
            'hard_deadline_at' => $campaign->hard_deadline_at?->toIso8601String(),
            'is_overdue' => $isOverdue,
            'is_approaching_deadline' => $isApproaching,
        ];
    }
}
