<?php

declare(strict_types=1);

namespace App\Services;

/**
 * One-time token helpers shared by remember-me, password reset and e-mail
 * verification. Only hashes touch the database: a leaked DB dump must not
 * hand out working tokens.
 */
final class Tokens
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * sha256 (fast) instead of bcrypt (slow) on purpose: these tokens carry
     * 256 bits of randomness, so brute-forcing the hash is not feasible —
     * slow hashing only makes sense for low-entropy secrets like passwords.
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
