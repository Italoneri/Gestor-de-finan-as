<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\CategoryType;
use App\Models\Money;
use App\Repositories\CategoryRepository;
use App\Repositories\IncomeGoalRepository;
use App\Services\AuthService;

/**
 * Writes only: /planning renders the goal list, so there is no index here.
 * Field errors go to their own flash key because the planning page shows two
 * forms at once and a shared 'errors' bag would light up the wrong one.
 */
final class IncomeGoalController
{
    public function __construct(
        private readonly IncomeGoalRepository $goals,
        private readonly CategoryRepository $categories,
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function store(Request $request): Response
    {
        $userId = $this->auth->requireUserId();
        $month = valid_month($request->input('month'));
        $validator = new Validator();

        if ($month === null) {
            $validator->fail('month', 'Mês inválido.');
        }

        // goals are income targets: only own income categories qualify
        $category = $this->categories->find($userId, (int) $request->input('category_id'));
        if ($category === null || $category->type !== CategoryType::Income) {
            $validator->fail('category_id', 'Escolha uma categoria de receita válida.');
        }

        $money = Money::parseBr($request->input('amount'));
        if ($money === null || $money->cents <= 0) {
            $validator->fail('amount', 'Informe um valor positivo no formato 1.234,56.');
        }

        $back = self::backTo($month ?? date('Y-m'));
        if ($validator->fails()) {
            $this->session->flash('goalErrors', $validator->errors());

            return Response::redirect($back);
        }

        $this->goals->upsert($userId, $category->id, $month, $money->cents);
        $this->session->flash('status', 'Meta salva.');

        return Response::redirect($back);
    }

    public function destroy(Request $request, int $id): Response
    {
        if ($this->goals->delete($this->auth->requireUserId(), $id)) {
            $this->session->flash('status', 'Meta removida.');
        } else {
            $this->session->flash('error', 'Meta não encontrada.');
        }

        return Response::redirect(self::backTo(valid_month($request->input('month')) ?? date('Y-m')));
    }

    private static function backTo(string $month): string
    {
        return '/planning?month=' . urlencode($month) . '#metas';
    }
}
