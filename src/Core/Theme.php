<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Colour scheme for the current request. The browser writes the choice to a
 * readable cookie so the server can stamp it on the html tag — a localStorage
 * toggle would paint the default palette first and flash on every navigation.
 */
enum Theme: string
{
    case Dark = 'dark';
    case Light = 'light';

    public const COOKIE_NAME = 'theme';

    /**
     * Parses the cookie value. The result reaches an html attribute, so anything
     * unrecognised falls back to the default rather than being trusted.
     */
    public static function fromCookie(mixed $value): self
    {
        if (!is_string($value)) {
            return self::Dark;
        }

        return self::tryFrom($value) ?? self::Dark;
    }

    public function opposite(): self
    {
        return $this === self::Dark ? self::Light : self::Dark;
    }

    /**
     * Browser chrome colour (mobile address bar), matching --bg for this theme.
     */
    public function browserColor(): string
    {
        return match ($this) {
            self::Dark => '#0a0a0b',
            self::Light => '#f1f5f9',
        };
    }

    /**
     * Label for the control that switches *to* this theme.
     */
    public function switchLabel(): string
    {
        return match ($this) {
            self::Dark => 'Mudar para o tema escuro',
            self::Light => 'Mudar para o tema claro',
        };
    }
}
