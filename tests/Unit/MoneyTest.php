<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function brazilianAmounts(): array
    {
        return [
            'grouped thousands' => ['1.234,56', 123456],
            'plain with cents' => ['1234,56', 123456],
            'under one real' => ['0,99', 99],
            'no cents' => ['1234', 123400],
            'grouped no cents' => ['1.000.000', 100000000],
            'with surrounding spaces' => [' 52,00 ', 5200],
            'zero' => ['0', 0],
        ];
    }

    #[DataProvider('brazilianAmounts')]
    public function testParsesBrazilianFormatIntoCents(string $input, int $cents): void
    {
        $money = Money::parseBr($input);

        $this->assertNotNull($money);
        $this->assertSame($cents, $money->cents);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'empty' => [''],
            'letters' => ['abc'],
            'dot as decimal' => ['12.34'],
            'us format' => ['1,234.56'],
            'negative' => ['-10,00'],
            'single decimal digit' => ['10,5'],
            'three decimal digits' => ['10,500'],
            'broken grouping' => ['1.23.456,78'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function testRejectsInvalidAmounts(string $input): void
    {
        $this->assertNull(Money::parseBr($input));
    }

    public function testFormatsCentsAsBrazilianString(): void
    {
        $this->assertSame('1.234,56', Money::fromCents(123456)->formatBr());
        $this->assertSame('0,99', Money::fromCents(99)->formatBr());
        $this->assertSame('1.000.000,00', Money::fromCents(100000000)->formatBr());
    }
}
