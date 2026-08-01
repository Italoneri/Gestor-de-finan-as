<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string, list<array{regex: string, handler: callable}>>
     */
    private array $routes = ['GET' => [], 'POST' => []];

    /** @var ?callable */
    private $notFound = null;

    public function setNotFound(callable $handler): void
    {
        $this->notFound = $handler;
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes[$request->method] ?? [] as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }
            $params = array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);

            return ($route['handler'])($request, $params);
        }

        return $this->notFound !== null
            ? ($this->notFound)($request)
            : Response::html('<h1>404 — Página não encontrada</h1>', 404);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?<$1>[^/]+)', $pattern) . '$#';
        $this->routes[$method][] = ['regex' => $regex, 'handler' => $handler];
    }
}
