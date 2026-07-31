<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\AuthService;
use App\Services\EmailVerificationService;
use App\Services\MailerInterface;

final class RegistrationController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly EmailVerificationService $verification,
        private readonly MailerInterface $mailer,
        private readonly View $view,
        private readonly Session $session,
        private readonly string $appUrl,
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

        $this->sendVerificationLink($email, $userId);
        $this->session->flash('status', 'Conta criada! Enviamos um link de confirmação para o seu e-mail.');

        return Response::redirect('/login');
    }

    public function verifyEmail(Request $request): Response
    {
        if ($this->verification->verify($request->query('token'))) {
            $this->session->flash('status', 'E-mail verificado! Faça login para entrar.');
        } else {
            $this->session->flash('error', 'Link de verificação inválido ou expirado.');
        }

        return Response::redirect('/login');
    }

    private function sendVerificationLink(string $email, int $userId): void
    {
        $link = rtrim($this->appUrl, '/') . '/verify-email?token=' . $this->verification->issue($userId);
        $this->mailer->send(
            $email,
            'Confirme seu e-mail',
            "Olá! Confirme seu e-mail acessando o link abaixo:\n{$link}\n\nO link expira em 24 horas.",
        );
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
