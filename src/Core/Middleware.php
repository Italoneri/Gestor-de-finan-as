<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;

final class Middleware
{
    public static function auth(AuthService $auth, callable $handler): callable
    {
        return function (Request $request, array $params) use ($auth, $handler): Response {
            if (!$auth->check()) {
                return Response::redirect('/login');
            }

            return $handler($request, $params);
        };
    }

    public static function guest(AuthService $auth, callable $handler): callable
    {
        return fn (Request $request, array $params): Response => $auth->check()
            ? Response::redirect('/')
            : $handler($request, $params);
    }

    public static function csrf(Csrf $csrf, callable $handler): callable
    {
        return function (Request $request, array $params) use ($csrf, $handler): Response {
            if (!$csrf->validate($request->input(Csrf::FIELD))) {
                return Response::html(
                    '<h1>419 — Sessão expirada</h1><p>Volte à página anterior e tente novamente.</p>',
                    419,
                );
            }

            return $handler($request, $params);
        };
    }
}
