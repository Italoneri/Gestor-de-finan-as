<?php

declare(strict_types=1);

namespace App\Models;

final class CategoryTotal
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $name,
        public readonly int $totalCents,
        public readonly string $color,
    ) {
    }

    public function total(): Money
    {
        return Money::fromCents($this->totalCents);
    }
}
