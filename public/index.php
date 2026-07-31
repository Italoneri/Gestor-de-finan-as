<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;

// php -S router mode: serve existing files (css, js) directly
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

$config = Config::load(dirname(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(function (Throwable $e) use ($config): void {
    error_log((string) $e);
    http_response_code(500);
    if ($config->bool('APP_DEBUG')) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $e;

        return;
    }
    echo 'Algo deu errado. Tente novamente mais tarde.';
});

$view = new View(dirname(__DIR__) . '/src/Views');
$router = new Router();

$router->get('/', fn (Request $request, array $params): Response => $view->render('home', [
    'title' => 'Início',
]));

$router->dispatch(Request::fromGlobals())->send();
