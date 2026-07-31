<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\PasswordResetController;
use App\Controllers\RegistrationController;
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
use App\Services\EmailVerificationService;
use App\Services\LoginRateLimiter;
use App\Services\LogMailer;
use App\Services\PasswordResetService;
use App\Services\RememberMeCookie;
use App\Services\RememberMeService;

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

$secureCookies = $config->get('APP_ENV') === 'prod';
$appUrl = $config->get('APP_URL', 'http://localhost:8000');

$session = new Session($config->get('SESSION_NAME', 'finance_session'));
$session->start(secureCookie: $secureCookies);

$pdo = Database::connect($config);
$csrf = new Csrf($session);
$users = new UserRepository($pdo);
$auth = new AuthService($users, new LoginRateLimiter($pdo), $session);
$rememberMe = new RememberMeService($pdo);
$verification = new EmailVerificationService($pdo, $users);
$resets = new PasswordResetService($pdo, $users);
$mailer = new LogMailer($config->basePath('storage/mail.log'));

// persistent login: single-use cookie token, rotated on every successful use
if (!$auth->check() && isset($_COOKIE[RememberMeService::COOKIE_NAME])) {
    $cookie = $_COOKIE[RememberMeService::COOKIE_NAME];
    $userId = is_string($cookie) ? $rememberMe->consume($cookie) : null;
    if ($userId !== null) {
        $auth->loginUsingId($userId);
        RememberMeCookie::set($rememberMe->issue($userId), $secureCookies);
    } else {
        RememberMeCookie::clear();
    }
}

$view = new View(dirname(__DIR__) . '/src/Views');
$view->share('csrfToken', $csrf->token());
$view->share('userName', $auth->user()?->name);

$authController = new AuthController($auth, $rememberMe, $view, $session, $secureCookies);
$registration = new RegistrationController($auth, $verification, $mailer, $view, $session, $appUrl);
$passwordReset = new PasswordResetController($resets, $mailer, $view, $session, $appUrl);

$router = new Router();

$router->get('/', Middleware::auth($auth, fn (): Response => $view->render('home', [
    'title' => 'Painel',
])));

$router->get('/register', Middleware::guest($auth, fn (): Response => $registration->showRegister()));
$router->post('/register', Middleware::csrf($csrf, fn (Request $r): Response => $registration->register($r)));
$router->get('/verify-email', fn (Request $r): Response => $registration->verifyEmail($r));

$router->get('/login', Middleware::guest($auth, fn (): Response => $authController->showLogin()));
$router->post('/login', Middleware::csrf($csrf, fn (Request $r): Response => $authController->login($r)));
$router->post('/logout', Middleware::csrf($csrf, fn (): Response => $authController->logout()));

$router->get('/forgot-password', Middleware::guest(
    $auth,
    fn (): Response => $passwordReset->showForgotForm(),
));
$router->post('/forgot-password', Middleware::csrf(
    $csrf,
    fn (Request $r): Response => $passwordReset->sendLink($r),
));
$router->get('/reset-password', Middleware::guest(
    $auth,
    fn (Request $r): Response => $passwordReset->showResetForm($r),
));
$router->post('/reset-password', Middleware::csrf(
    $csrf,
    fn (Request $r): Response => $passwordReset->resetPassword($r),
));

$router->dispatch(Request::fromGlobals())->send();
