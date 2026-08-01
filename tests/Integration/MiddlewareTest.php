<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\LoginRateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

final class MiddlewareTest extends TestCase
{
    private AuthService $auth;
    private Csrf $csrf;

    protected function setUp(): void
    {
        $_SESSION = [];
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($pdo, __DIR__ . '/../../database/migrations');

        $session = new Session('test_session');
        $this->auth = new AuthService(new UserRepository($pdo), new LoginRateLimiter($pdo), $session);
        $this->csrf = new Csrf($session);
    }

    public function testAuthRedirectsGuestsWithoutCallingHandler(): void
    {
        $called = false;
        $handler = Middleware::auth($this->auth, function () use (&$called): Response {
            $called = true;

            return Response::html('secreto');
        });

        $response = $handler(Request::create('GET', '/'), []);

        $this->assertFalse($called);
        $this->assertSame(302, $response->status);
        $this->assertSame('/login', $response->headers['Location']);
    }

    public function testAuthPassesThroughAuthenticatedUsers(): void
    {
        $this->auth->loginUsingId(1);
        $handler = Middleware::auth($this->auth, fn (): Response => Response::html('secreto'));

        $response = $handler(Request::create('GET', '/'), []);

        $this->assertSame('secreto', $response->body);
    }

    public function testGuestRedirectsAuthenticatedUsersToDashboard(): void
    {
        $this->auth->loginUsingId(1);
        $handler = Middleware::guest($this->auth, fn (): Response => Response::html('login'));

        $response = $handler(Request::create('GET', '/login'), []);

        $this->assertSame(302, $response->status);
        $this->assertSame('/', $response->headers['Location']);
    }

    public function testCsrfRejectsMissingTokenWith419(): void
    {
        $this->csrf->token();
        $called = false;
        $handler = Middleware::csrf($this->csrf, function () use (&$called): Response {
            $called = true;

            return Response::html('ok');
        });

        $response = $handler(Request::create('POST', '/login', body: ['email' => 'x']), []);

        $this->assertFalse($called);
        $this->assertSame(419, $response->status);
    }

    public function testCsrfAcceptsValidToken(): void
    {
        $token = $this->csrf->token();
        $handler = Middleware::csrf($this->csrf, fn (): Response => Response::html('ok'));

        $response = $handler(Request::create('POST', '/login', body: ['_token' => $token]), []);

        $this->assertSame('ok', $response->body);
    }
}
