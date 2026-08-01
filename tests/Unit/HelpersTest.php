<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEscapesHtmlSpecialCharsAndQuotes(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;',
            e("<script>alert('xss')</script>"),
        );
        $this->assertSame('a&quot;b', e('a"b'));
        $this->assertSame('', e(null));
    }

    public function testCsrfFieldEmbedsEscapedToken(): void
    {
        $field = csrf_field('abc"><script>');

        $this->assertStringContainsString('name="_token"', $field);
        $this->assertStringContainsString('abc&quot;&gt;&lt;script&gt;', $field);
        $this->assertStringNotContainsString('"><script>', $field);
    }

    public function testFormatsBrazilianDateAndMonth(): void
    {
        $this->assertSame('15/07/2026', br_date('2026-07-15'));
        $this->assertSame('Julho/2026', br_month('2026-07'));
        $this->assertSame('Dezembro/2025', br_month('2025-12'));
    }

    public function testValidMonthAcceptsOnlyRealMonths(): void
    {
        $this->assertSame('2026-07', valid_month('2026-07'));
        $this->assertNull(valid_month('2026-13'));
        $this->assertNull(valid_month('2026-7'));
        $this->assertNull(valid_month('hack'));
        $this->assertNull(valid_month(''));
    }
}
