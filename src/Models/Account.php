<?php

declare(strict_types=1);

namespace App\Models;

final class Account
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $name,
        public readonly AccountType $type,
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
            AccountType::from((string) $row['type']),
        );
    }
}
