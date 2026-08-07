<?php

declare(strict_types=1);

namespace App\Models;

final class Ceiling
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $categoryId,
        public readonly string $month,
        public readonly int $limitCents,
        public readonly string $categoryName,
    ) {
    }

    /**
     * @param array<string, mixed> $row row with category_name joined in
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['category_id'],
            (string) $row['month'],
            (int) $row['limit_cents'],
            (string) $row['category_name'],
        );
    }
}
