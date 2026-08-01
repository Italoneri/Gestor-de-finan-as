<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Monetary value as integer cents — float arithmetic on money is banned
 * (binary rounding). Parse, don't validate: user input becomes a Money or
 * null, never a "probably fine" string.
 */
final class Money
{
    private function __construct(public readonly int $cents)
    {
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Brazilian format only: optional dot-grouped thousands and optional
     * comma with exactly two decimals — "1.234,56", "1234,56", "1234".
     * First alternative demands correct 3-digit grouping; second allows
     * ungrouped digits. Anything else (US format, negatives, one decimal
     * digit) returns null.
     */
    private const PATTERN = '/^\d{1,3}(\.\d{3})*(,\d{2})?$|^\d+(,\d{2})?$/';

    public static function parseBr(string $value): ?self
    {
        $value = trim($value);
        if (preg_match(self::PATTERN, $value) !== 1) {
            return null;
        }

        $parts = explode(',', str_replace('.', '', $value));
        $reais = (int) $parts[0];
        $centavos = isset($parts[1]) ? (int) $parts[1] : 0;

        return new self($reais * 100 + $centavos);
    }

    public function formatBr(): string
    {
        // integer arithmetic all the way — no float division
        $reais = intdiv($this->cents, 100);
        $centavos = $this->cents % 100;

        return number_format($reais, 0, '', '.') . ',' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT);
    }
}
