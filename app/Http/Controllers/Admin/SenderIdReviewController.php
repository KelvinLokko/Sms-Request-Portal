<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SenderIdStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectSenderIdRequest;
use App\Models\SenderId;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class SenderIdReviewController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SenderId::class);

        $senderIds = SenderId::query()
            ->withoutGlobalScopes()
            ->with(['company:id,name', 'requester:id,name,email'])
            ->where('status', SenderIdStatus::Pending)
            ->latest()
            ->paginate(20)
            ->through(fn (SenderId $senderId) => [
                'id' => $senderId->id,
                'value' => $senderId->value,
                'company' => [
                    'id' => $senderId->company->id,
                    'name' => $senderId->company->name,
                ],
                'requester' => [
                    'name' => $senderId->requester->name,
                    'email' => $senderId->requester->email,
                ],
                'has_document' => $senderId->hasDocument(),
                'uses_company_letterhead' => $senderId->uses_company_letterhead,
                'created_at' => $senderId->created_at?->toIso8601String(),
                'document_url' => $senderId->hasDocument()
                    ? URL::temporarySignedRoute(
                        'sender-ids.document',
                        now()->addMinutes(30),
                        ['sender_id' => $senderId->id],
                    )
                    : null,
            ]);

        return Inertia::render('admin/sender-ids/Index', [
            'senderIds' => $senderIds,
        ]);
    }

    public function approve(SenderId $senderId, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('review', $senderId);

        $senderId->forceFill([
            'status' => SenderIdStatus::Approved,
            'rejection_reason' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        $logger->log('sender_id.approved', $senderId);

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
