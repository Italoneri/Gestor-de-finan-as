<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;
use App\Models\CategoryType;
use App\Repositories\TransactionRepository;
use App\Services\AuthService;
use App\Services\ReportService;

final class DashboardController
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly TransactionRepository $transactions,
        private readonly AuthService $auth,
        private readonly View $view,
    ) {
    }

    public function index(): Response
    {
        $userId = $this->auth->requireUserId();
        $month = date('Y-m');
        $totals = $this->reports->monthTotals($userId, $month);

        return $this->view->render('dashboard', [
            'title' => 'Painel',
            'month' => $month,
            'balanceCents' => $this->reports->balance($userId),
            'incomeCents' => $totals['income'],
            'expenseCents' => $totals['expense'],
            'expensesByCategory' => $this->reports->categoryTotals($userId, $month, CategoryType::Expense),
            'budgets' => $this->reports->budgetProgress($userId, $month),
            'recent' => $this->transactions->allForUser($userId, 5),
        ]);
    }
}
