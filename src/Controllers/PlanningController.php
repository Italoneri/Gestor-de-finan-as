<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\CategoryType;
use App\Repositories\CategoryRepository;
use App\Repositories\CeilingRepository;
use App\Repositories\IncomeGoalRepository;
use App\Services\AuthService;

/**
 * Read-only composition of the two planning concepts: ceilings cap what goes
 * out of an expense category, goals target what comes into an income one. The
 * writes live in CeilingController and IncomeGoalController.
 */
final class PlanningController
{
    public function __construct(
        private readonly CeilingRepository $ceilings,
        private readonly IncomeGoalRepository $goals,
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

        $expenseCategories = [];
        $incomeCategories = [];
        foreach ($this->categories->allForUser($userId) as $category) {
            if ($category->type === CategoryType::Expense) {
                $expenseCategories[] = $category;
            } else {
                $incomeCategories[] = $category;
            }
        }

        return $this->view->render('planning/index', [
            'title' => 'Planejamento',
            'month' => $month,
            'ceilings' => $this->ceilings->forMonth($userId, $month),
            'goals' => $this->goals->forMonth($userId, $month),
            'expenseCategories' => $expenseCategories,
            'incomeCategories' => $incomeCategories,
            'status' => $this->session->pullFlash('status'),
            'error' => $this->session->pullFlash('error'),
            'ceilingErrors' => $this->session->pullFlash('ceilingErrors') ?? [],
            'goalErrors' => $this->session->pullFlash('goalErrors') ?? [],
        ]);
    }
}
