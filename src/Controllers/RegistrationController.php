<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\AuthService;

final class RegistrationController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly View $view,
        private readonly Session $session,
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
            return $this->backToForm($validator->errors(), ['name' => $name, 'email' => $email]);
        }

        $userId = $this->auth->register($name, $email, $password);
        if ($userId === null) {
            return $this->backToForm(
                ['email' => 'Este e-mail já está em uso.'],
                ['name' => $name, 'email' => $email],
            );
        }

        // straight into the app: the dashboard is the confirmation
        $this->auth->loginUsingId($userId);

        return Response::redirect('/');
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, string> $old
     */
    private function backToForm(array $errors, array $old): Response
    {
        $this->session->flash('errors', $errors);
        $this->session->flash('old', $old);

        return Response::redirect('/register');
    }
}
