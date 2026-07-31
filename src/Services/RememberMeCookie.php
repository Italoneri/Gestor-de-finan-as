<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Browser-side half of the remember-me flow. Kept out of RememberMeService so
 * the token logic stays testable without emitting headers.
 */
final class RememberMeCookie
{
    public static function set(string $value, bool $secure): void
    {
        setcookie(RememberMeService::COOKIE_NAME, $value, [
            'expires' => time() + RememberMeService::TTL_DAYS * 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $secure,
        ]);
    }

    public static function clear(): void
    {
        setcookie(RememberMeService::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
