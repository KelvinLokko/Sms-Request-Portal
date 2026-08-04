<?php

namespace App\Enums;

enum SmsRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ChangesRequested = 'changes_requested';
    case Invoiced = 'invoiced';
    case Paid = 'paid';
    case AwaitingFulfilment = 'awaiting_fulfilment';
    case Fulfilled = 'fulfilled';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::ChangesRequested => 'Changes requested',
            self::Invoiced => 'Invoiced',
            self::Paid => 'Paid',
            self::AwaitingFulfilment => 'Awaiting fulfilment',
            self::Fulfilled => 'Sent by our team',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isEditable(): bool
    {
        return match ($this) {
            self::Draft, self::ChangesRequested => true,
            default => false,
        };
    }

    public function canSubmit(): bool
    {
        return match ($this) {
            self::Draft, self::ChangesRequested => true,
            default => false,
        };
    }

    public function canCancel(): bool
    {
        return match ($this) {
            self::Draft, self::Submitted, self::Invoiced => true,
            default => false,
        };
    }

    public function canFulfil(): bool
    {
        return match ($this) {
            self::Paid, self::AwaitingFulfilment => true,
            default => false,
        };
    }
}
