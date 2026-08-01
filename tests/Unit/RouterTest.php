<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesMatchingRouteWithParams(): void
    {
        $router = new Router();
        $router->get(
            '/transactions/{id}',
            fn (Request $request, array $params): Response => Response::html('tx ' . $params['id'])
        );

        $response = $router->dispatch(Request::create('GET', '/transactions/42'));

        $this->assertSame(200, $response->status);
        $this->assertSame('tx 42', $response->body);
    }

    public function testMatchesTrailingSlashAsSamePath(): void
    {
        $router = new Router();
        $router->get('/login', fn (): Response => Response::html('login'));

        $response = $router->dispatch(Request::create('GET', '/login/'));

        $this->assertSame('login', $response->body);
    }

    public function testReturns404ForUnknownPath(): void
    {
        $router = new Router();

        $response = $router->dispatch(Request::create('GET', '/nope'));

        $this->assertSame(404, $response->status);
    }

    public function testUsesCustomNotFoundHandlerWhenSet(): void
    {
        $router = new Router();
        $router->setNotFound(fn (Request $request): Response => Response::html('404 bonita', 404));

        $response = $router->dispatch(Request::create('GET', '/nada'));

        $this->assertSame(404, $response->status);
        $this->assertSame('404 bonita', $response->body);
    }

    public function testDistinguishesMethods(): void
    {
        $router = new Router();
        $router->post('/login', fn (): Response => Response::html('posted'));

        $response = $router->dispatch(Request::create('GET', '/login'));

        $this->assertSame(404, $response->status);
    }
}
