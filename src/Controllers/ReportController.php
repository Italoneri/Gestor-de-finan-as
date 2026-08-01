<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\CategoryType;
use App\Services\AuthService;
use App\Services\ReportService;

final class ReportController
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly AuthService $auth,
        private readonly View $view,
    ) {
    }

    public function index(Request $request): Response
    {
        $userId = $this->auth->requireUserId();
        $month = valid_month($request->query('month')) ?? date('Y-m');
        $totals = $this->reports->monthTotals($userId, $month);

        return $this->view->render('reports/index', [
            'title' => 'Relatório mensal',
            'month' => $month,
            'incomeCents' => $totals['income'],
            'expenseCents' => $totals['expense'],
            'incomeByCategory' => $this->reports->categoryTotals($userId, $month, CategoryType::Income),
            'expensesByCategory' => $this->reports->categoryTotals($userId, $month, CategoryType::Expense),
            'budgets' => $this->reports->budgetProgress($userId, $month),
        ]);
    }
}
