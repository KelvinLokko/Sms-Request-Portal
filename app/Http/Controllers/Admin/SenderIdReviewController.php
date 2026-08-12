<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SenderIdStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectSenderIdRequest;
use App\Models\SenderId;
use App\Services\ActivityLogger;
use App\Services\CampaignNotifier;
use App\Support\ListFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class SenderIdReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SenderId::class);

        $filters = ListFilters::fromRequest($request);
        $status = $filters['status'];

        $senderIds = SenderId::query()
            ->withoutGlobalScopes()
            ->with([
                'company:id,name',
                'requester:id,name,email',
                'reviewer:id,name',
            ])
            ->when(
                $status !== null && SenderIdStatus::tryFrom($status),
                fn ($query) => $query->where('status', $status),
            )
            ->tap(fn ($query) => ListFilters::applyDateRange(
                $query,
                $filters['from'],
                $filters['to'],
            ))
            ->when(
                $filters['q'] !== null,
                function ($query) use ($filters) {
                    $term = '%'.$filters['q'].'%';

                    $query->where(function ($inner) use ($term): void {
                        $inner->where('value', 'like', $term)
                            ->orWhereHas('company', fn ($company) => $company->where('name', 'like', $term))
                            ->orWhereHas('requester', function ($user) use ($term): void {
                                $user->where('name', 'like', $term)
                                    ->orWhere('email', 'like', $term);
                            });
                    });
                },
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SenderId $senderId) => [
                'id' => $senderId->id,
                'value' => $senderId->value,
                'status' => $senderId->status->value,
                'status_label' => $senderId->status->label(),
                'rejection_reason' => $senderId->rejection_reason,
                'company' => [
                    'id' => $senderId->company->id,
                    'name' => $senderId->company->name,
                ],
                'requester' => [
                    'name' => $senderId->requester->name,
                    'email' => $senderId->requester->email,
                ],
                'reviewer' => $senderId->reviewer
                    ? ['name' => $senderId->reviewer->name]
                    : null,
                'has_document' => $senderId->hasDocument(),
                'uses_company_letterhead' => $senderId->uses_company_letterhead,
                'created_at' => $senderId->created_at?->toIso8601String(),
                'reviewed_at' => $senderId->reviewed_at?->toIso8601String(),
                'document_url' => $senderId->hasDocument()
                    ? URL::temporarySignedRoute(
                        'sender-ids.document',
                        now()->addMinutes(30),
                        ['sender_id' => $senderId->id],
                        absolute: false,
                    )
                    : null,
            ]);

        return Inertia::render('admin/sender-ids/Index', [
            'senderIds' => $senderIds,
            'filters' => $filters,
            'statusOptions' => collect(SenderIdStatus::cases())
                ->map(fn (SenderIdStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function approve(
        SenderId $senderId,
        ActivityLogger $logger,
        CampaignNotifier $notifier,
    ): RedirectResponse {
        $this->authorize('review', $senderId);

        $senderId->forceFill([
            'status' => SenderIdStatus::Approved,
            'rejection_reason' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        $logger->log('sender_id.approved', $senderId);
        $notifier->senderIdApproved($senderId);

        return back()->with('success', "Sender ID \"{$senderId->value}\" approved.");
    }

    public function reject(
        RejectSenderIdRequest $request,
        SenderId $senderId,
        ActivityLogger $logger,
    ): RedirectResponse {
        $senderId->forceFill([
            'status' => SenderIdStatus::Rejected,
            'rejection_reason' => $request->validated('rejection_reason'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        $logger->log('sender_id.rejected', $senderId, [
            'reason' => $senderId->rejection_reason,
        ]);

        return back()->with('success', "Sender ID \"{$senderId->value}\" rejected.");
    }
}
