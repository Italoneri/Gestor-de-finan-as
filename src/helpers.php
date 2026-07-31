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
