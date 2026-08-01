<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Models\CategoryType;
use App\Services\ReportService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ReportServiceTest extends TestCase
{
    private PDO $pdo;
    private ReportService $service;
    private int $anaId;
    private int $beaId;
    private int $account;
    private int $salaryCat;
    private int $marketCat;
    private int $leisureCat;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $this->anaId = $this->insertUser('ana@exemplo.com');
        $this->beaId = $this->insertUser('bea@exemplo.com');
        $this->account = $this->insertAccount($this->anaId);
        $this->salaryCat = $this->insertCategory($this->anaId, 'Salário', 'income');
        $this->marketCat = $this->insertCategory($this->anaId, 'Mercado', 'expense');
        $this->leisureCat = $this->insertCategory($this->anaId, 'Lazer', 'expense');
        $this->service = new ReportService($this->pdo);
    }

    public function testBalanceSumsIncomeMinusExpenseForOwnerOnly(): void
    {
        $this->insertTx($this->anaId, $this->salaryCat, 'income', 500000, '2026-07-05');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 120000, '2026-07-10');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 30000, '2026-06-01');

        $beaAccount = $this->insertAccount($this->beaId);
        $beaCat = $this->insertCategory($this->beaId, 'Dela', 'income');
        $this->insertTx($this->beaId, $beaCat, 'income', 999999, '2026-07-01', $beaAccount);

        $this->assertSame(350000, $this->service->balance($this->anaId));
    }

    public function testBalanceIsZeroWithoutTransactions(): void
    {
        $this->assertSame(0, $this->service->balance($this->anaId));
    }

    public function testMonthTotalsCountOnlySelectedMonth(): void
    {
        $this->insertTx($this->anaId, $this->salaryCat, 'income', 500000, '2026-07-05');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 120000, '2026-07-31');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 30000, '2026-06-30');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 40000, '2026-08-01');

        $totals = $this->service->monthTotals($this->anaId, '2026-07');

        $this->assertSame(500000, $totals['income']);
        $this->assertSame(120000, $totals['expense']);
    }

    public function testCategoryTotalsGroupAndSortDescending(): void
    {
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 20000, '2026-07-01');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 15000, '2026-07-10');
        $this->insertTx($this->anaId, $this->leisureCat, 'expense', 50000, '2026-07-15');
        $this->insertTx($this->anaId, $this->salaryCat, 'income', 500000, '2026-07-05');

        $totals = $this->service->categoryTotals($this->anaId, '2026-07', CategoryType::Expense);

        $this->assertCount(2, $totals);
        $this->assertSame('Lazer', $totals[0]->name);
        $this->assertSame(50000, $totals[0]->totalCents);
        $this->assertSame('Mercado', $totals[1]->name);
        $this->assertSame(35000, $totals[1]->totalCents);
    }

    public function testBudgetProgressComputesSpentInsideMonthOnly(): void
    {
        $this->insertBudget($this->anaId, $this->marketCat, '2026-07', 80000);
        $this->insertBudget($this->anaId, $this->leisureCat, '2026-07', 30000);
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 20000, '2026-07-01');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 70000, '2026-07-20');
        $this->insertTx($this->anaId, $this->marketCat, 'expense', 10000, '2026-06-15');

        $progress = $this->service->budgetProgress($this->anaId, '2026-07');

        $this->assertCount(2, $progress);
        $byName = [];
        foreach ($progress as $item) {
            $byName[$item->categoryName] = $item;
        }
        $this->assertSame(90000, $byName['Mercado']->spentCents);
        $this->assertSame(80000, $byName['Mercado']->limitCents);
        $this->assertSame(113, $byName['Mercado']->percent());
        $this->assertTrue($byName['Mercado']->overLimit());
        $this->assertSame(0, $byName['Lazer']->spentCents);
        $this->assertFalse($byName['Lazer']->overLimit());
    }

    private function insertUser(string $email): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES ('U', ?, 'h', 'x', 'x')"
        );
        $stmt->execute([$email]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertAccount(int $userId): int
    {
        $this->pdo->exec(
            "INSERT INTO accounts (user_id, name, type, created_at) VALUES ({$userId}, 'Conta', 'wallet', 'x')"
        );

        return (int) $this->pdo->lastInsertId();
    }

    private function insertCategory(int $userId, string $name, string $type): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (user_id, name, type, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $name, $type, 'x']);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertTx(
        int $userId,
        int $categoryId,
        string $type,
        int $cents,
        string $date,
        ?int $accountId = null,
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions
                (user_id, account_id, category_id, type, amount_cents, description, date, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $accountId ?? $this->account, $categoryId, $type, $cents, 'x', $date, 'x', 'x']);
    }

    private function insertBudget(int $userId, int $categoryId, string $month, int $limitCents): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO budgets (user_id, category_id, month, limit_cents) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $categoryId, $month, $limitCents]);
    }
}
