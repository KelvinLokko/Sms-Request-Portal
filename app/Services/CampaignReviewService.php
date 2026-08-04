<?php

namespace App\Services;

use App\Enums\SmsRequestStatus;
use App\Models\SmsRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignReviewService
{
    public function __construct(
        private ActivityLogger $logger,
        private CampaignNotifier $notifier,
    ) {}

    public function startReview(SmsRequest $request, User $actor): SmsRequest
    {
        if ($request->status !== SmsRequestStatus::Submitted) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted campaigns can be taken into review.',
            ]);
        }

        return DB::transaction(function () use ($request, $actor): SmsRequest {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $before = ['status' => $locked->status->value];

            $locked->forceFill([
                'status' => SmsRequestStatus::UnderReview,
            ])->save();

            $this->logger->logChange('sms_request.under_review', $locked, $before, [
                'status' => $locked->status->value,
            ], [], $actor);

            return $locked;
        });
    }

    public function requestChanges(SmsRequest $request, User $actor, string $reason): SmsRequest
    {
        if (! in_array($request->status, [SmsRequestStatus::Submitted, SmsRequestStatus::UnderReview], true)) {
            throw ValidationException::withMessages([
                'status' => 'Changes can only be requested on submitted or under-review campaigns.',
            ]);
        }

        return DB::transaction(function () use ($request, $actor, $reason): SmsRequest {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $before = [
                'status' => $locked->status->value,
                'changes_requested_reason' => $locked->changes_requested_reason,
            ];

            $locked->forceFill([
                'status' => SmsRequestStatus::ChangesRequested,
                'changes_requested_reason' => $reason,
            ])->save();

            $this->logger->logChange('sms_request.changes_requested', $locked, $before, [
                'status' => $locked->status->value,
                'changes_requested_reason' => $reason,
            ], [], $actor);

            DB::afterCommit(fn () => $this->notifier->changesRequested($locked));

            return $locked;
        });
    }

    public function reject(SmsRequest $request, User $actor, string $reason): SmsRequest
    {
        if (! in_array($request->status, [SmsRequestStatus::Submitted, SmsRequestStatus::UnderReview], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted or under-review campaigns can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($request, $actor, $reason): SmsRequest {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $before = [
                'status' => $locked->status->value,
                'rejection_reason' => $locked->rejection_reason,
            ];

            $locked->forceFill([
                'status' => SmsRequestStatus::Rejected,
                'rejection_reason' => $reason,
            ])->save();

            $this->logger->logChange('sms_request.rejected', $locked, $before, [
                'status' => $locked->status->value,
                'rejection_reason' => $reason,
            ], [], $actor);

            return $locked;
        });
    }
}
