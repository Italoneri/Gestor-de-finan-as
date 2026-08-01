<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Listing filters parsed from the query string. Invalid values fall back to
 * defaults silently — filters are navigation, not forms, so a tampered URL
 * degrades to "no filter" instead of an error page.
 */
final class TransactionFilter
{
    private const SORTS = ['date', 'amount'];
    private const DIRECTIONS = ['asc', 'desc'];
    private const SEARCH_MAX_LENGTH = 100;

    public function __construct(
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
        public readonly ?int $categoryId,
        public readonly ?CategoryType $type,
        public readonly ?string $search,
        public readonly string $sort,
        public readonly string $direction,
        public readonly int $page,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $categoryId = (int) self::str($query, 'category_id');
        $search = mb_substr(trim(self::str($query, 'q')), 0, self::SEARCH_MAX_LENGTH);
        $sort = self::str($query, 'sort');
        $direction = self::str($query, 'dir');

        return new self(
            dateFrom: self::validDate(self::str($query, 'from')),
            dateTo: self::validDate(self::str($query, 'to')),
            categoryId: $categoryId > 0 ? $categoryId : null,
            type: CategoryType::tryFrom(self::str($query, 'type')),
            search: $search === '' ? null : $search,
            sort: in_array($sort, self::SORTS, true) ? $sort : 'date',
            direction: in_array($direction, self::DIRECTIONS, true) ? $direction : 'desc',
            page: max(1, (int) self::str($query, 'page')),
        );
    }

    /**
     * Active filters as query params for pagination/export links. Page is
     * intentionally left out — each link sets its own.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        $params = [
            'from' => $this->dateFrom,
            'to' => $this->dateTo,
            'category_id' => $this->categoryId === null ? null : (string) $this->categoryId,
            'type' => $this->type?->value,
            'q' => $this->search,
            'sort' => $this->sort,
            'dir' => $this->direction,
        ];

        return array_filter($params, fn (?string $value) => $value !== null);
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function str(array $query, string $key): string
    {
        $value = $query[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private static function validDate(string $value): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        [$year, $month, $day] = array_map(intval(...), explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }
}
