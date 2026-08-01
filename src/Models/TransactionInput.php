<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Validated payload for creating/updating a transaction. Built only after
 * ownership and format checks pass — the type always mirrors the chosen
 * category's type by construction.
 */
final class TransactionInput
{
    public function __construct(
        public readonly int $accountId,
        public readonly int $categoryId,
        public readonly CategoryType $type,
        public readonly int $amountCents,
        public readonly string $description,
        public readonly string $date,
    ) {
    }
}
