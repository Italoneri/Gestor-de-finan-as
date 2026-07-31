<?php

declare(strict_types=1);

namespace App\Models;

final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $name,
        public readonly CategoryType $type,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['name'],
            CategoryType::from((string) $row['type']),
        );
    }
}
