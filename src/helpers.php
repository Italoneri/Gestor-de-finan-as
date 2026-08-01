<?php

declare(strict_types=1);

/**
 * Escapes a value for safe HTML output. Every dynamic value printed in a view
 * must go through this helper — output escaping is the XSS boundary.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validates a YYYY-MM month string; null when malformed (parse, don't trust).
 */
function valid_month(string $value): ?string
{
    return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1 ? $value : null;
}

/**
 * YYYY-MM to Brazilian display ("Julho/2026").
 */
function br_month(string $month): string
{
    $names = [
        1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];
    [$year, $monthNumber] = explode('-', $month);

    return $names[(int) $monthNumber] . '/' . $year;
}

/**
 * ISO date (Y-m-d) to Brazilian display format (d/m/Y).
 */
function br_date(string $isoDate): string
{
    return implode('/', array_reverse(explode('-', $isoDate)));
}

/**
 * Hidden CSRF input for state-changing forms. The field name must match
 * what the csrf middleware reads (App\Core\Csrf::FIELD).
 */
function csrf_field(string $token): string
{
    return '<input type="hidden" name="_token" value="' . e($token) . '">';
}

/**
 * Lucide icon (ISC) rendered inline. Inlining keeps icons on the same request
 * as the page, so there is no sprite CDN to whitelist and no flash of missing
 * glyphs. An unknown name renders nothing rather than breaking the layout.
 */
function icon(string $name, string $class = 'icon'): string
{
    $shape = match ($name) {
        'waves' => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>'
            . '<path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>'
            . '<path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 '
            . '1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'trending-up' => '<path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/>',
        'trending-down' => '<path d="M16 17h6v-6"/><path d="m22 17-8.5-8.5-5 5L2 7"/>',
        'target' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/>'
            . '<circle cx="12" cy="12" r="2"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 '
            . '2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/>'
            . '<path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/>',
        default => null,
    };

    if ($shape === null) {
        return '';
    }

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $shape . '</svg>';
}
