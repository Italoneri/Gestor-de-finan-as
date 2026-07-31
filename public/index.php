<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\LoginRateLimiter;

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

$session = new Session($config->get('SESSION_NAME', 'finance_session'));
$session->start(secureCookie: $config->get('APP_ENV') === 'prod');

$pdo = Database::connect($config);
$csrf = new Csrf($session);
$auth = new AuthService(new UserRepository($pdo), new LoginRateLimiter($pdo), $session);

$view = new View(dirname(__DIR__) . '/src/Views');
$view->share('csrfToken', $csrf->token());
$view->share('userName', $auth->user()?->name);

$authController = new AuthController($auth, $view, $session, $csrf);

$router = new Router();

$router->get('/', Middleware::auth($auth, fn (): Response => $view->render('home', [
    'title' => 'Painel',
])));

$router->get('/register', Middleware::guest($auth, fn (): Response => $authController->showRegister()));
$router->post('/register', Middleware::csrf($csrf, fn (Request $r): Response => $authController->register($r)));

$router->get('/login', Middleware::guest($auth, fn (): Response => $authController->showLogin()));
$router->post('/login', Middleware::csrf($csrf, fn (Request $r): Response => $authController->login($r)));

$router->post('/logout', Middleware::csrf($csrf, fn (): Response => $authController->logout()));

$router->dispatch(Request::fromGlobals())->send();
