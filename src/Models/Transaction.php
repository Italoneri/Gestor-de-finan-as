<?php

declare(strict_types=1);

namespace App\Models;

final class Transaction
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $accountId,
        public readonly int $categoryId,
        public readonly CategoryType $type,
        public readonly int $amountCents,
        public readonly string $description,
        public readonly string $date,
        public readonly string $categoryName,
        public readonly string $accountName,
    ) {
    }

    /**
     * @param array<string, mixed> $row row with category_name/account_name joined in
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['account_id'],
            (int) $row['category_id'],
            CategoryType::from((string) $row['type']),
            (int) $row['amount_cents'],
            (string) $row['description'],
            (string) $row['date'],
            (string) $row['category_name'],
            (string) $row['account_name'],
        );
    }

    public function amount(): Money
    {
        return Money::fromCents($this->amountCents);
    }
}
