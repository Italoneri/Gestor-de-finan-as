<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\AccountType;
use App\Repositories\AccountRepository;
use App\Services\AuthService;
use PDOException;

final class AccountController
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly AuthService $auth,
        private readonly View $view,
        private readonly Session $session,
    ) {
    }

    public function index(): Response
    {
        return $this->view->render('accounts/index', [
            'title' => 'Contas',
            'accounts' => $this->accounts->allForUser($this->auth->requireUserId()),
            'status' => $this->session->pullFlash('status'),
            'error' => $this->session->pullFlash('error'),
            'errors' => $this->session->pullFlash('errors') ?? [],
            'old' => $this->session->pullFlash('old') ?? [],
        ]);
    }

    public function store(Request $request): Response
    {
        $name = trim($request->input('name'));
        $type = AccountType::tryFrom($request->input('type'));

        $validator = new Validator();
        $validator->label('name', $name);
        if ($type === null) {
            $validator->fail('type', 'Escolha um tipo válido.');
        }
        if ($validator->fails()) {
            $this->session->flash('errors', $validator->errors());
            $this->session->flash('old', ['name' => $name]);

            return Response::redirect('/accounts');
        }

        $this->accounts->create($this->auth->requireUserId(), $name, $type);
        $this->session->flash('status', 'Conta criada.');

        return Response::redirect('/accounts');
    }

    public function edit(int $id): Response
    {
        $account = $this->accounts->find($this->auth->requireUserId(), $id);
        if ($account === null) {
            $this->session->flash('error', 'Conta não encontrada.');

            return Response::redirect('/accounts');
        }

        return $this->view->render('accounts/edit', [
            'title' => 'Editar conta',
            'account' => $account,
            'errors' => $this->session->pullFlash('errors') ?? [],
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $name = trim($request->input('name'));
        $type = AccountType::tryFrom($request->input('type'));

        $validator = new Validator();
        $validator->label('name', $name);
        if ($type === null) {
            $validator->fail('type', 'Escolha um tipo válido.');
        }
        if ($validator->fails()) {
            $this->session->flash('errors', $validator->errors());

            return Response::redirect("/accounts/{$id}/edit");
        }

        if ($this->accounts->update($this->auth->requireUserId(), $id, $name, $type)) {
            $this->session->flash('status', 'Conta atualizada.');
        } else {
            $this->session->flash('error', 'Conta não encontrada.');
        }

        return Response::redirect('/accounts');
    }

    public function destroy(int $id): Response
    {
        try {
            if ($this->accounts->delete($this->auth->requireUserId(), $id)) {
                $this->session->flash('status', 'Conta excluída.');
            } else {
                $this->session->flash('error', 'Conta não encontrada.');
            }
        } catch (PDOException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
            $this->session->flash('error', 'Conta em uso por transações — mova os lançamentos antes de excluir.');
        }

        return Response::redirect('/accounts');
    }
}
