<?php

declare(strict_types=1);

namespace App\Models;

final class IncomeGoalProgress
{
    public function __construct(
        public readonly int $goalId,
        public readonly string $categoryName,
        public readonly int $targetCents,
        public readonly int $receivedCents,
    ) {
    }

    public function percent(): int
    {
        if ($this->targetCents <= 0) {
            return 0;
        }

        return (int) round($this->receivedCents * 100 / $this->targetCents);
    }

    /**
     * Inclusive: earning exactly the target hits the goal. CeilingProgress uses
     * a strict comparison because spending exactly the limit does not blow it.
     */
    public function reached(): bool
    {
        return $this->receivedCents >= $this->targetCents;
    }

    public function remainingCents(): int
    {
        return max(0, $this->targetCents - $this->receivedCents);
    }

    public function target(): Money
    {
        return Money::fromCents($this->targetCents);
    }

    public function received(): Money
    {
        return Money::fromCents($this->receivedCents);
    }

    public function remaining(): Money
    {
        return Money::fromCents($this->remainingCents());
    }
}
