<?php

declare(strict_types=1);

namespace App\Models;

final class CeilingProgress
{
    public function __construct(
        public readonly int $ceilingId,
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

    /**
     * Strict: spending exactly the ceiling has not crossed it. The mirror
     * boundary in IncomeGoalProgress::reached() is inclusive on purpose.
     */
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
