<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\ErrorHandler;
use PHPUnit\Framework\TestCase;

final class ErrorHandlerTest extends TestCase
{
    public function testFriendlyPageRevealsNothingTechnical(): void
    {
        $page = ErrorHandler::friendlyPage();

        $this->assertStringContainsString('Algo deu errado', $page);
        $this->assertStringNotContainsString('Exception', $page);
        $this->assertStringNotContainsString('Stack trace', $page);
        $this->assertStringNotContainsString('<?php', $page);
    }
}
