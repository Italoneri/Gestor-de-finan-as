<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Last line of defense: log everything server-side, show users nothing
 * technical. Deliberately does not depend on View — if rendering itself is
 * what broke, this must still produce a page.
 */
final class ErrorHandler
{
    public static function register(Config $config): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $config->basePath('storage/app.log'));

        set_exception_handler(function (Throwable $e) use ($config): void {
            self::logThrowable($e);
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            if ($config->bool('APP_DEBUG')) {
                echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';

                return;
            }
            echo self::friendlyPage();
        });
    }

    public static function friendlyPage(): string
    {
        return <<<'HTML'
            <!doctype html>
            <html lang="pt-BR">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Erro interno</title>
                <style>
                    body { font-family: system-ui, sans-serif; background: #f6f7f9; color: #1f2933;
                           display: grid; place-items: center; min-height: 100vh; margin: 0; }
                    main { background: #fff; padding: 2.5rem 3rem; border-radius: 10px;
                           box-shadow: 0 1px 3px rgb(0 0 0 / 0.08); text-align: center; }
                    a { color: #2563eb; }
                </style>
            </head>
            <body>
                <main>
                    <h1>Algo deu errado</h1>
                    <p>Já registramos o problema. Tente novamente em instantes.</p>
                    <p><a href="/">Voltar ao início</a></p>
                </main>
            </body>
            </html>
            HTML;
    }

    private static function logThrowable(Throwable $e): void
    {
        $context = json_encode([
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'cli',
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
        ]);
        error_log(sprintf('%s: %s %s', $e::class, $e->getMessage(), (string) $context));
        error_log($e->getTraceAsString());
    }
}
