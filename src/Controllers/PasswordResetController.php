<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\MailerInterface;
use App\Services\PasswordResetService;

final class PasswordResetController
{
    public function __construct(
        private readonly PasswordResetService $resets,
        private readonly MailerInterface $mailer,
        private readonly View $view,
        private readonly Session $session,
        private readonly string $appUrl,
    ) {
    }

    public function showForgotForm(): Response
    {
        return $this->view->render('auth/forgot-password', [
            'title' => 'Recuperar senha',
            'status' => $this->session->pullFlash('status'),
            'error' => $this->session->pullFlash('error'),
            'errors' => $this->session->pullFlash('errors') ?? [],
        ]);
    }

    public function sendLink(Request $request): Response
    {
        $email = strtolower(trim($request->input('email')));

        $validator = new Validator();
        $validator->email('email', $email);
        if ($validator->fails()) {
            $this->session->flash('errors', $validator->errors());

            return Response::redirect('/forgot-password');
        }

        $token = $this->resets->request($email);
        if ($token !== null) {
            $link = rtrim($this->appUrl, '/') . '/reset-password?token=' . $token;
            $this->mailer->send(
                $email,
                'Recuperação de senha',
                "Olá! Redefina sua senha acessando o link abaixo:\n{$link}\n\nO link expira em 60 minutos."
                . " Se você não pediu a redefinição, ignore este e-mail.",
            );
        }

        // same message whether the e-mail exists or not: no user enumeration
        $this->session->flash('status', 'Se o e-mail estiver cadastrado, enviamos um link de recuperação.');

        return Response::redirect('/forgot-password');
    }

    public function showResetForm(Request $request): Response
    {
        return $this->view->render('auth/reset-password', [
            'title' => 'Redefinir senha',
            'token' => $request->query('token'),
            'errors' => $this->session->pullFlash('errors') ?? [],
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $token = $request->input('token');
        $password = $request->input('password');

        $validator = new Validator();
        $validator->password('password', $password);
        $validator->confirmation('password_confirmation', $password, $request->input('password_confirmation'));
        if ($validator->fails()) {
            $this->session->flash('errors', $validator->errors());

            return Response::redirect('/reset-password?token=' . urlencode($token));
        }

        if (!$this->resets->reset($token, $password)) {
            $this->session->flash('error', 'Link inválido ou expirado. Solicite um novo.');

            return Response::redirect('/forgot-password');
        }

        $this->session->flash('status', 'Senha alterada com sucesso. Faça login com a nova senha.');

        return Response::redirect('/login');
    }
}
