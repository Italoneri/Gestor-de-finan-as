<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CategoryType;
use App\Models\TransactionFilter;
use PHPUnit\Framework\TestCase;

final class TransactionFilterTest extends TestCase
{
    public function testDefaultsWhenQueryIsEmpty(): void
    {
        $filter = TransactionFilter::fromQuery([]);

        $this->assertNull($filter->dateFrom);
        $this->assertNull($filter->dateTo);
        $this->assertNull($filter->categoryId);
        $this->assertNull($filter->type);
        $this->assertNull($filter->search);
        $this->assertSame('date', $filter->sort);
        $this->assertSame('desc', $filter->direction);
        $this->assertSame(1, $filter->page);
    }

    public function testParsesValidValues(): void
    {
        $filter = TransactionFilter::fromQuery([
            'from' => '2026-07-01',
            'to' => '2026-07-31',
            'category_id' => '7',
            'type' => 'income',
            'q' => ' mercado ',
            'sort' => 'amount',
            'dir' => 'asc',
            'page' => '3',
        ]);

        $this->assertSame('2026-07-01', $filter->dateFrom);
        $this->assertSame('2026-07-31', $filter->dateTo);
        $this->assertSame(7, $filter->categoryId);
        $this->assertSame(CategoryType::Income, $filter->type);
        $this->assertSame('mercado', $filter->search);
        $this->assertSame('amount', $filter->sort);
        $this->assertSame('asc', $filter->direction);
        $this->assertSame(3, $filter->page);
    }

    public function testIgnoresInvalidValuesFallingBackToDefaults(): void
    {
        $filter = TransactionFilter::fromQuery([
            'from' => '2026-02-30',
            'to' => 'ontem',
            'category_id' => 'abc',
            'type' => 'hack',
            'q' => '',
            'sort' => 'password_hash',
            'dir' => 'sideways',
            'page' => '-5',
        ]);

        $this->assertNull($filter->dateFrom);
        $this->assertNull($filter->dateTo);
        $this->assertNull($filter->categoryId);
        $this->assertNull($filter->type);
        $this->assertNull($filter->search);
        $this->assertSame('date', $filter->sort);
        $this->assertSame('desc', $filter->direction);
        $this->assertSame(1, $filter->page);
    }

    public function testTruncatesOverlongSearchTerm(): void
    {
        $filter = TransactionFilter::fromQuery(['q' => str_repeat('a', 150)]);

        $this->assertSame(100, mb_strlen($filter->search));
    }

    public function testToQueryOmitsUnsetFiltersAndPage(): void
    {
        $filter = TransactionFilter::fromQuery([
            'from' => '2026-07-01',
            'type' => 'expense',
            'page' => '4',
        ]);

        $this->assertSame(
            ['from' => '2026-07-01', 'type' => 'expense', 'sort' => 'date', 'dir' => 'desc'],
            $filter->toQuery(),
        );
    }
}
