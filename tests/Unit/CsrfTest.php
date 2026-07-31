<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    private Csrf $csrf;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->csrf = new Csrf(new Session('test_session'));
    }

    public function testGeneratesStableTokenWithinSession(): void
    {
        $first = $this->csrf->token();

        $this->assertSame($first, $this->csrf->token());
        $this->assertSame(64, strlen($first));
    }

    public function testAcceptsMatchingToken(): void
    {
        $token = $this->csrf->token();

        $this->assertTrue($this->csrf->validate($token));
    }

    public function testRejectsMismatchedToken(): void
    {
        $this->csrf->token();

        $this->assertFalse($this->csrf->validate(str_repeat('0', 64)));
    }

    public function testRejectsNullAndEmptyToken(): void
    {
        $this->csrf->token();

        $this->assertFalse($this->csrf->validate(null));
        $this->assertFalse($this->csrf->validate(''));
    }

    public function testRejectsWhenNoTokenWasIssued(): void
    {
        $this->assertFalse($this->csrf->validate('anything'));
    }

    public function testRotateIssuesNewToken(): void
    {
        $old = $this->csrf->token();
        $this->csrf->rotate();

        $this->assertNotSame($old, $this->csrf->token());
        $this->assertFalse($this->csrf->validate($old));
    }
}
