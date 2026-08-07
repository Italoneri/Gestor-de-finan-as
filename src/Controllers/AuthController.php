<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\LoginResult;
use App\Services\RememberMeCookie;
use App\Services\RememberMeService;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly RememberMeService $rememberMe,
        private readonly View $view,
        private readonly Session $session,
        private readonly bool $secureCookies,
    ) {
    }

    public function showLogin(): Response
    {
        return $this->view->render('auth/login', [
            'title' => 'Entrar',
            'status' => $this->session->pullFlash('status'),
            'error' => $this->session->pullFlash('error'),
            'errors' => $this->session->pullFlash('errors') ?? [],
            'old' => $this->session->pullFlash('old') ?? [],
        ]);
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim($request->input('email')));
        $result = $this->auth->attemptLogin($email, $request->input('password'), $request->ip);

        if ($result === LoginResult::Success) {
            $userId = $this->auth->userId();
            if ($userId !== null && $request->input('remember') === '1') {
                RememberMeCookie::set($this->rememberMe->issue($userId), $this->secureCookies);
            }

            return Response::redirect('/');
        }

        // one generic message for wrong password AND unknown e-mail: no user enumeration
        $message = match ($result) {
            LoginResult::InvalidCredentials => 'Credenciais inválidas.',
            LoginResult::RateLimited => 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.',
            LoginResult::Success => '',
        };
        $this->session->flash('errors', ['credentials' => $message]);
        $this->session->flash('old', ['email' => $email]);

        return Response::redirect('/login');
    }

    public function logout(): Response
    {
        $userId = $this->auth->userId();
        if ($userId !== null) {
            $this->rememberMe->revokeAll($userId);
        }
        RememberMeCookie::clear();
        $this->auth->logout();

        return Response::redirect('/login');
    }
}
