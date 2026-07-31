<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\AuthService;
use App\Services\LoginResult;
use App\Services\RegisterResult;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly View $view,
        private readonly Session $session,
        private readonly Csrf $csrf,
    ) {
    }

    public function showRegister(): Response
    {
        return $this->view->render('auth/register', [
            'title' => 'Criar conta',
            'errors' => $this->session->pullFlash('errors') ?? [],
            'old' => $this->session->pullFlash('old') ?? [],
        ]);
    }

    public function register(Request $request): Response
    {
        $name = trim($request->input('name'));
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        $validator = new Validator();
        $validator->name('name', $name);
        $validator->email('email', $email);
        $validator->password('password', $password);
        $validator->confirmation('password_confirmation', $password, $request->input('password_confirmation'));

        if ($validator->fails()) {
            return $this->backToForm('/register', $validator->errors(), ['name' => $name, 'email' => $email]);
        }

        if ($this->auth->register($name, $email, $password) === RegisterResult::EmailTaken) {
            return $this->backToForm(
                '/register',
                ['email' => 'Este e-mail já está em uso.'],
                ['name' => $name, 'email' => $email],
            );
        }

        return Response::redirect('/');
    }

    public function showLogin(): Response
    {
        return $this->view->render('auth/login', [
            'title' => 'Entrar',
            'errors' => $this->session->pullFlash('errors') ?? [],
            'old' => $this->session->pullFlash('old') ?? [],
        ]);
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim($request->input('email')));
        $result = $this->auth->attemptLogin($email, $request->input('password'), $request->ip);

        return match ($result) {
            LoginResult::Success => Response::redirect('/'),
            // one generic message for wrong password AND unknown e-mail: no user enumeration
            LoginResult::InvalidCredentials => $this->backToForm(
                '/login',
                ['credentials' => 'Credenciais inválidas.'],
                ['email' => $email],
            ),
            LoginResult::RateLimited => $this->backToForm(
                '/login',
                ['credentials' => 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.'],
                ['email' => $email],
            ),
        };
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return Response::redirect('/login');
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, string> $old
     */
    private function backToForm(string $path, array $errors, array $old): Response
    {
        $this->session->flash('errors', $errors);
        $this->session->flash('old', $old);

        return Response::redirect($path);
    }
}
