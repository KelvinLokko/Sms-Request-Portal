<?php

namespace App\Support\Sms;

/**
 * Deywuro 8-tier segment table (§6) — verbatim.
 */
final class SegmentCounter
{
    public const MAX_MESSAGE_LENGTH = 1528;

    public const WARN_THRESHOLD = 621;

    public static function pages(int $characterCount): int
    {
        return match (true) {
            $characterCount <= 0 => 0,
            $characterCount <= 160 => 1,
            $characterCount <= 306 => 2,
            $characterCount <= 459 => 3,
            $characterCount <= 621 => 4,
            $characterCount <= 766 => 5,
            $characterCount <= 919 => 6,
            $characterCount <= 1072 => 7,
            default => 8,
        };
    }

    public static function exceedsPracticalLimit(int $characterCount): bool
    {
        return $characterCount > self::WARN_THRESHOLD;
    }
}
