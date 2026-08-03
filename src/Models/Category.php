<?php

declare(strict_types=1);

namespace App\Models;

final class Category
{
    /**
     * Swatches offered in the category form. Ten well-separated hues, all at a
     * mid step so they hold up as chart fills on both the near-black and the
     * white surface. Users can still pick any colour — this is the shortcut,
     * not the limit.
     */
    public const PALETTE = [
        '#0ea5e9',
        '#3b82f6',
        '#6366f1',
        '#8b5cf6',
        '#06b6d4',
        '#14b8a6',
        '#22c55e',
        '#f59e0b',
        '#f97316',
        '#f43f5e',
    ];

    public const DEFAULT_COLOR = '#0ea5e9';

    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $name,
        public readonly CategoryType $type,
        public readonly string $color,
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
            (string) $row['color'],
        );
    }
}
