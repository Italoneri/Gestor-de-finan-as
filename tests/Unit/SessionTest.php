<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->session = new Session('test_session');
    }

    public function testStoresAndRemovesValues(): void
    {
        $this->session->set('user_id', 42);

        $this->assertSame(42, $this->session->get('user_id'));

        $this->session->remove('user_id');
        $this->assertNull($this->session->get('user_id'));
    }

    public function testPullFlashReturnsValueOnceThenNull(): void
    {
        $this->session->flash('status', 'Salvo.');

        $this->assertSame('Salvo.', $this->session->pullFlash('status'));
        $this->assertNull($this->session->pullFlash('status'));
    }

    public function testPullFlashReturnsNullWhenNothingFlashed(): void
    {
        $this->assertNull($this->session->pullFlash('nunca_setado'));
    }

    public function testDestroyClearsAllData(): void
    {
        $this->session->set('user_id', 42);
        $this->session->flash('status', 'x');

        $this->session->destroy();

        $this->assertNull($this->session->get('user_id'));
        $this->assertNull($this->session->pullFlash('status'));
    }
}
