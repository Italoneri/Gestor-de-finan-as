<?php

declare(strict_types=1);

namespace App\Models;

final class BudgetProgress
{
    public function __construct(
        public readonly int $budgetId,
        public readonly string $categoryName,
        public readonly int $limitCents,
        public readonly int $spentCents,
    ) {
    }

    public function percent(): int
    {
        if ($this->limitCents <= 0) {
            return 0;
        }

        return (int) round($this->spentCents * 100 / $this->limitCents);
    }

    public function overLimit(): bool
    {
        return $this->spentCents > $this->limitCents;
    }

    public function limit(): Money
    {
        return Money::fromCents($this->limitCents);
    }

    public function spent(): Money
    {
        return Money::fromCents($this->spentCents);
    }
}
