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
