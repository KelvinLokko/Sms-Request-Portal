<?php

namespace App\Jobs;

/**
 * Mutable processing context for ValidateRecipientListJob.
 *
 * @phpstan-type RejectedRow array{0: int, 1: string, 2: string}
 */
final class RecipientValidationContext
{
    /** @var list<string> */
    public array $headers = [];

    public ?string $phoneColumn = null;

    public int $phoneIndex = 0;

    /** @var array<string, int> */
    public array $seen = [];

    /** @var list<array<string, mixed>> */
    public array $buffer = [];

    public int $total = 0;

    public int $valid = 0;

    public int $invalid = 0;

    public int $duplicates = 0;

    /** @var list<RejectedRow> */
    public array $rejectedRows = [];
}
