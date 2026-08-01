<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Money;
use App\Models\TransactionFilter;
use App\Models\TransactionInput;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use App\Services\AuthService;
use App\Services\TransactionCsvExporter;

final class TransactionController
{
    private const PER_PAGE = 15;
    private const EXPORT_LIMIT = 5000;

    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly CategoryRepository $categories,
        private readonly AccountRepository $accounts,
        private readonly AuthService $auth,
        private readonly View $view,
        private readonly Session $session,
    ) {
    }

    public function index(Request $request): Response
    {
        $userId = $this->auth->requireUserId();
        $filter = TransactionFilter::fromQuery($request->queryAll());
        $total = $this->transactions->countFor($userId, $filter);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($filter->page, $totalPages);

        return $this->view->render('transactions/index', [
            'title' => 'Transações',
            'transactions' => $this->transactions->search($userId, $filter, self::PER_PAGE, $page),
            'filter' => $filter,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'categories' => $this->categories->allForUser($userId),
            'accounts' => $this->accounts->allForUser($userId),
            'status' => $this->session->pullFlash('status'),
            'error' => $this->session->pullFlash('error'),
            'errors' => $this->session->pullFlash('errors') ?? [],
            'old' => $this->session->pullFlash('old') ?? [],
        ]);
    }

    public function export(Request $request): Response
    {
        $userId = $this->auth->requireUserId();
        $filter = TransactionFilter::fromQuery($request->queryAll());
        $rows = $this->transactions->search($userId, $filter, self::EXPORT_LIMIT, 1);

        return Response::download(
            TransactionCsvExporter::export($rows),
            'transacoes-' . date('Y-m-d') . '.csv',
            'text/csv; charset=UTF-8',
        );
    }

    public function store(Request $request): Response
    {
        $userId = $this->auth->requireUserId();
        $validator = new Validator();
        $input = $this->buildInput($request, $userId, $validator);

        if ($input === null) {
            $this->flashFormState($request, $validator);

            return Response::redirect('/transactions');
        }

        $this->transactions->create($userId, $input);
        $this->session->flash('status', 'Lançamento adicionado.');

        return Response::redirect('/transactions');
    }

    public function edit(int $id): Response
    {
        $userId = $this->auth->requireUserId();
        $transaction = $this->transactions->find($userId, $id);
        if ($transaction === null) {
            $this->session->flash('error', 'Lançamento não encontrado.');

            return Response::redirect('/transactions');
        }

        return $this->view->render('transactions/edit', [
            'title' => 'Editar lançamento',
            'transaction' => $transaction,
            'categories' => $this->categories->allForUser($userId),
            'accounts' => $this->accounts->allForUser($userId),
            'errors' => $this->session->pullFlash('errors') ?? [],
            'old' => $this->session->pullFlash('old') ?? [],
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $userId = $this->auth->requireUserId();
        $validator = new Validator();
        $input = $this->buildInput($request, $userId, $validator);

        if ($input === null) {
            $this->flashFormState($request, $validator);

            return Response::redirect("/transactions/{$id}/edit");
        }

        if ($this->transactions->update($userId, $id, $input)) {
            $this->session->flash('status', 'Lançamento atualizado.');
        } else {
            $this->session->flash('error', 'Lançamento não encontrado.');
        }

        return Response::redirect('/transactions');
    }

    public function destroy(int $id): Response
    {
        if ($this->transactions->delete($this->auth->requireUserId(), $id)) {
            $this->session->flash('status', 'Lançamento excluído.');
        } else {
            $this->session->flash('error', 'Lançamento não encontrado.');
        }

        return Response::redirect('/transactions');
    }

    /**
     * All rules in one place: format checks, positive amount, and ownership
     * of the chosen category/account (a foreign id fails exactly like a
     * nonexistent one). The transaction type is derived from the category,
     * so type/category mismatch is impossible by construction.
     */
    private function buildInput(Request $request, int $userId, Validator $validator): ?TransactionInput
    {
        $description = trim($request->input('description'));
        $date = $request->input('date');

        $validator->description('description', $description);
        $validator->date('date', $date);

        $money = Money::parseBr($request->input('amount'));
        if ($money === null || $money->cents <= 0) {
            $validator->fail('amount', 'Informe um valor positivo no formato 1.234,56.');
        }

        $account = $this->accounts->find($userId, (int) $request->input('account_id'));
        if ($account === null) {
            $validator->fail('account_id', 'Escolha uma conta válida.');
        }

        $category = $this->categories->find($userId, (int) $request->input('category_id'));
        if ($category === null) {
            $validator->fail('category_id', 'Escolha uma categoria válida.');
        }

        if ($validator->fails()) {
            return null;
        }

        return new TransactionInput(
            accountId: $account->id,
            categoryId: $category->id,
            type: $category->type,
            amountCents: $money->cents,
            description: $description,
            date: $date,
        );
    }

    private function flashFormState(Request $request, Validator $validator): void
    {
        $this->session->flash('errors', $validator->errors());
        $this->session->flash('old', [
            'description' => $request->input('description'),
            'amount' => $request->input('amount'),
            'date' => $request->input('date'),
            'account_id' => $request->input('account_id'),
            'category_id' => $request->input('category_id'),
        ]);
    }
}
