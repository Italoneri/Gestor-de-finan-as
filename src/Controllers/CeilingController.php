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
use App\Repositories\CeilingRepository;
use App\Services\AuthService;

/**
 * Writes only: /planning renders the ceiling list, so there is no index here.
 * Field errors go to their own flash key because the planning page shows two
 * forms at once and a shared 'errors' bag would light up the wrong one.
 */
final class CeilingController
{
    public function __construct(
        private readonly CeilingRepository $ceilings,
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

        // ceilings are spending limits: only own expense categories qualify
        $category = $this->categories->find($userId, (int) $request->input('category_id'));
        if ($category === null || $category->type !== CategoryType::Expense) {
            $validator->fail('category_id', 'Escolha uma categoria de despesa válida.');
        }

        $money = Money::parseBr($request->input('amount'));
        if ($money === null || $money->cents <= 0) {
            $validator->fail('amount', 'Informe um valor positivo no formato 1.234,56.');
        }

        $back = self::backTo($month ?? date('Y-m'));
        if ($validator->fails()) {
            $this->session->flash('ceilingErrors', $validator->errors());

            return Response::redirect($back);
        }

        $this->ceilings->upsert($userId, $category->id, $month, $money->cents);
        $this->session->flash('status', 'Teto salvo.');

        return Response::redirect($back);
    }

    public function destroy(Request $request, int $id): Response
    {
        if ($this->ceilings->delete($this->auth->requireUserId(), $id)) {
            $this->session->flash('status', 'Teto removido.');
        } else {
            $this->session->flash('error', 'Teto não encontrado.');
        }

        return Response::redirect(self::backTo(valid_month($request->input('month')) ?? date('Y-m')));
    }

    private static function backTo(string $month): string
    {
        return '/planning?month=' . urlencode($month) . '#tetos';
    }
}
