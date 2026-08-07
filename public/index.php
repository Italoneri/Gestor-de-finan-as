<?php

declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\CeilingController;
use App\Controllers\DashboardController;
use App\Controllers\IncomeGoalController;
use App\Controllers\ReportController;
use App\Controllers\PasswordResetController;
use App\Controllers\PlanningController;
use App\Controllers\RegistrationController;
use App\Controllers\TransactionController;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\Theme;
use App\Core\View;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CeilingRepository;
use App\Repositories\IncomeGoalRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\LoginRateLimiter;
use App\Services\LogMailer;
use App\Services\ReportService;
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

App\Core\ErrorHandler::register($config);

// baseline security headers on every response
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$secureCookies = $config->get('APP_ENV') === 'prod';
$appUrl = $config->get('APP_URL', 'http://localhost:8000');

$session = new Session($config->get('SESSION_NAME', 'finance_session'));
$session->start(secureCookie: $secureCookies);

$pdo = Database::connect($config);
$csrf = new Csrf($session);
$users = new UserRepository($pdo);
$auth = new AuthService($users, new LoginRateLimiter($pdo), $session);
$rememberMe = new RememberMeService($pdo);
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
$view->share('currentPath', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$view->share('theme', Theme::fromCookie($_COOKIE[Theme::COOKIE_NAME] ?? null));

$authController = new AuthController($auth, $rememberMe, $view, $session, $secureCookies);
$registration = new RegistrationController($auth, $view, $session);
$passwordReset = new PasswordResetController($resets, $mailer, $view, $session, $appUrl);
$categoryRepo = new CategoryRepository($pdo);
$accountRepo = new AccountRepository($pdo);
$categoryCtrl = new CategoryController($categoryRepo, $auth, $view, $session);
$accountCtrl = new AccountController($accountRepo, $auth, $view, $session);
$transactionRepo = new TransactionRepository($pdo);
$transactionCtrl = new TransactionController(
    $transactionRepo,
    $categoryRepo,
    $accountRepo,
    $auth,
    $view,
    $session,
);
$reportService = new ReportService($pdo);
$dashboardCtrl = new DashboardController($reportService, $transactionRepo, $auth, $view);
$reportCtrl = new ReportController($reportService, $auth, $view);
$ceilingRepo = new CeilingRepository($pdo);
$goalRepo = new IncomeGoalRepository($pdo);
$ceilingCtrl = new CeilingController($ceilingRepo, $categoryRepo, $auth, $session);
$goalCtrl = new IncomeGoalController($goalRepo, $categoryRepo, $auth, $session);
$planningCtrl = new PlanningController($ceilingRepo, $goalRepo, $categoryRepo, $auth, $view, $session);

$router = new Router();
$router->setNotFound(fn (Request $r): Response => $view->render('errors/404', [
    'title' => 'Página não encontrada',
], 404));

$router->get('/', Middleware::auth($auth, fn (): Response => $dashboardCtrl->index()));

$router->get('/register', Middleware::guest($auth, fn (): Response => $registration->showRegister()));
$router->post('/register', Middleware::csrf($csrf, fn (Request $r): Response => $registration->register($r)));

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

$router->get('/categories', Middleware::auth($auth, fn (): Response => $categoryCtrl->index()));
$router->post('/categories', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r): Response => $categoryCtrl->store($r),
)));
$router->get('/categories/{id}/edit', Middleware::auth(
    $auth,
    fn (Request $r, array $p): Response => $categoryCtrl->edit((int) $p['id']),
));
$router->post('/categories/{id}', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $categoryCtrl->update($r, (int) $p['id']),
)));
$router->post('/categories/{id}/delete', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $categoryCtrl->destroy((int) $p['id']),
)));

$router->get('/accounts', Middleware::auth($auth, fn (): Response => $accountCtrl->index()));
$router->post('/accounts', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r): Response => $accountCtrl->store($r),
)));
$router->get('/accounts/{id}/edit', Middleware::auth(
    $auth,
    fn (Request $r, array $p): Response => $accountCtrl->edit((int) $p['id']),
));
$router->post('/accounts/{id}', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $accountCtrl->update($r, (int) $p['id']),
)));
$router->post('/accounts/{id}/delete', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $accountCtrl->destroy((int) $p['id']),
)));

$router->get('/transactions', Middleware::auth(
    $auth,
    fn (Request $r): Response => $transactionCtrl->index($r),
));
$router->get('/transactions/export', Middleware::auth(
    $auth,
    fn (Request $r): Response => $transactionCtrl->export($r),
));
$router->post('/transactions', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r): Response => $transactionCtrl->store($r),
)));
$router->get('/transactions/{id}/edit', Middleware::auth(
    $auth,
    fn (Request $r, array $p): Response => $transactionCtrl->edit((int) $p['id']),
));
$router->post('/transactions/{id}', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $transactionCtrl->update($r, (int) $p['id']),
)));
$router->post('/transactions/{id}/delete', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $transactionCtrl->destroy((int) $p['id']),
)));

$router->get('/reports', Middleware::auth($auth, fn (Request $r): Response => $reportCtrl->index($r)));

$router->get('/planning', Middleware::auth($auth, fn (Request $r): Response => $planningCtrl->index($r)));

$router->post('/ceilings', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r): Response => $ceilingCtrl->store($r),
)));
$router->post('/ceilings/{id}/delete', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $ceilingCtrl->destroy($r, (int) $p['id']),
)));

$router->post('/goals', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r): Response => $goalCtrl->store($r),
)));
$router->post('/goals/{id}/delete', Middleware::auth($auth, Middleware::csrf(
    $csrf,
    fn (Request $r, array $p): Response => $goalCtrl->destroy($r, (int) $p['id']),
)));

$router->dispatch(Request::fromGlobals())->send();
