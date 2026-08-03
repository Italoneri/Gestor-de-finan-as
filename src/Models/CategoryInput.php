<?php

declare(strict_types=1);

namespace App\Models;

/**
 * The editable half of a category. Type is deliberately absent: it is fixed at
 * creation because transactions must always match their category's type, so it
 * is passed to the repository separately instead of riding along on updates.
 */
final class CategoryInput
{
    public function __construct(
        public readonly string $name,
        public readonly string $color,
    ) {
    }
}
