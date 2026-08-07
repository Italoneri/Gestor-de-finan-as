<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\IncomeGoalProgress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IncomeGoalProgressTest extends TestCase
{
    /**
     * @return array<string, array{int, int, int, bool, int}>
     */
    public static function goals(): array
    {
        return [
            //                    target, received, percent, reached, remaining
            'nothing received' => [500000, 0, 0, false, 500000],
            'halfway' => [500000, 250000, 50, false, 250000],
            'one cent short' => [500000, 499999, 100, false, 1],
            'exactly on target' => [500000, 500000, 100, true, 0],
            'overshot' => [500000, 620000, 124, true, 0],
            'target not set' => [0, 120000, 0, true, 0],
        ];
    }

    #[DataProvider('goals')]
    public function testReportsProgressAgainstTarget(
        int $targetCents,
        int $receivedCents,
        int $percent,
        bool $reached,
        int $remainingCents,
    ): void {
        $progress = new IncomeGoalProgress(1, 'Vendas', $targetCents, $receivedCents);

        $this->assertSame($percent, $progress->percent());
        $this->assertSame($reached, $progress->reached());
        $this->assertSame($remainingCents, $progress->remainingCents());
    }

    public function testFormatsAmountsInBrazilianFormat(): void
    {
        $progress = new IncomeGoalProgress(1, 'Vendas', 500000, 320000);

        $this->assertSame('5.000,00', $progress->target()->formatBr());
        $this->assertSame('3.200,00', $progress->received()->formatBr());
        $this->assertSame('1.800,00', $progress->remaining()->formatBr());
    }
}
