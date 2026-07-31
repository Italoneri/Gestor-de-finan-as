<?php

declare(strict_types=1);

namespace App\Models;

enum AccountType: string
{
    case Wallet = 'wallet';
    case Checking = 'checking';
    case Savings = 'savings';

    public function label(): string
    {
        return match ($this) {
            self::Wallet => 'Carteira',
            self::Checking => 'Conta Corrente',
            self::Savings => 'Poupança',
        };
    }
}
