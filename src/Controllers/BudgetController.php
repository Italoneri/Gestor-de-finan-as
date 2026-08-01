<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\CategoryType;
use App\Models\Money;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Services\AuthService;

final class BudgetController
{
    public function __construct(
        private readonly BudgetRepository $budgets,
        private readonly CategoryRepository $categories,
        private readonly AuthService $auth,
        private readonly View $view,
        private readonly Session $session,
    ) {
    }

    public function index(Request $request): Response
    {
        $userId = $this->auth->requireUserId();
        $month = valid_month($request->query('month')) ?? date('Y-m');
        $expenseCategories = array_values(array_filter(
            $this->categories->allForUser($userId),
            fn ($category) => $category->type === CategoryType::Expense,
        ));

        return $this->view->render('budgets/index', [
            'title' => 'Metas de gasto',
            'month' => $month,
            'budgets' => $this->budgets->forMonth($userId, $month),
            'expenseCategories' => $expenseCategories,
            'status' => $this->session->pullFlash('status'),
            'error' => $this->session->pullFlash('error'),
            'errors' => $this->session->pullFlash('errors') ?? [],
        ]);
    }

    public function store(Request $request): Response
    {
        $userId = $this->auth->requireUserId();
        $month = valid_month($request->input('month'));
        $validator = new Validator();

        if ($month === null) {
            $validator->fail('month', 'Mês inválido.');
        }

        // budgets are spending limits: only own expense categories qualify
        $category = $this->categories->find($userId, (int) $request->input('category_id'));
        if ($category === null || $category->type !== CategoryType::Expense) {
            $validator->fail('category_id', 'Escolha uma categoria de despesa válida.');
        }

        $money = Money::parseBr($request->input('amount'));
        if ($money === null || $money->cents <= 0) {
            $validator->fail('amount', 'Informe um valor positivo no formato 1.234,56.');
        }

        $back = '/budgets?month=' . urlencode($month ?? date('Y-m'));
        if ($validator->fails()) {
            $this->session->flash('errors', $validator->errors());

            return Response::redirect($back);
        }

        $this->budgets->upsert($userId, $category->id, $month, $money->cents);
        $this->session->flash('status', 'Meta salva.');

        return Response::redirect($back);
    }

    public function destroy(Request $request, int $id): Response
    {
        if ($this->budgets->delete($this->auth->requireUserId(), $id)) {
            $this->session->flash('status', 'Meta removida.');
        } else {
            $this->session->flash('error', 'Meta não encontrada.');
        }

        $month = valid_month($request->input('month')) ?? date('Y-m');

        return Response::redirect('/budgets?month=' . urlencode($month));
    }
}
