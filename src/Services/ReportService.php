<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BudgetProgress;
use App\Models\CategoryTotal;
use App\Models\CategoryType;
use PDO;

/**
 * Read-only aggregations for dashboard and reports. Month filters use
 * [first day, first day of next month) bounds so the (user_id, date) index
 * stays usable — no per-row date formatting.
 */
final class ReportService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function balance(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type = 'income' THEN amount_cents ELSE -amount_cents END), 0)
             FROM transactions WHERE user_id = ?"
        );
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{income: int, expense: int}
     */
    public function monthTotals(int $userId, string $month): array
    {
        [$start, $end] = self::monthBounds($month);
        $stmt = $this->pdo->prepare(
            'SELECT type, COALESCE(SUM(amount_cents), 0) AS total
             FROM transactions
             WHERE user_id = ? AND date >= ? AND date < ?
             GROUP BY type'
        );
        $stmt->execute([$userId, $start, $end]);

        $totals = ['income' => 0, 'expense' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $totals[$row['type']] = (int) $row['total'];
        }

        return $totals;
    }

    /**
     * @return list<CategoryTotal>
     */
    public function categoryTotals(int $userId, string $month, CategoryType $type): array
    {
        [$start, $end] = self::monthBounds($month);
        $stmt = $this->pdo->prepare(
            'SELECT c.id AS category_id, c.name, SUM(t.amount_cents) AS total_cents
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = ? AND t.type = ? AND t.date >= ? AND t.date < ?
             GROUP BY c.id, c.name
             ORDER BY total_cents DESC'
        );
        $stmt->execute([$userId, $type->value, $start, $end]);

        return array_map(
            fn (array $row): CategoryTotal => new CategoryTotal(
                (int) $row['category_id'],
                (string) $row['name'],
                (int) $row['total_cents'],
            ),
            $stmt->fetchAll(),
        );
    }

    /**
     * @return list<BudgetProgress>
     */
    public function budgetProgress(int $userId, string $month): array
    {
        [$start, $end] = self::monthBounds($month);
        $stmt = $this->pdo->prepare(
            'SELECT b.id, c.name AS category_name, b.limit_cents,
                    COALESCE(SUM(t.amount_cents), 0) AS spent_cents
             FROM budgets b
             JOIN categories c ON c.id = b.category_id
             LEFT JOIN transactions t
                    ON t.category_id = b.category_id
                   AND t.user_id = b.user_id
                   AND t.type = ?
                   AND t.date >= ? AND t.date < ?
             WHERE b.user_id = ? AND b.month = ?
             GROUP BY b.id, c.name, b.limit_cents
             ORDER BY c.name'
        );
        $stmt->execute([CategoryType::Expense->value, $start, $end, $userId, $month]);

        return array_map(
            fn (array $row): BudgetProgress => new BudgetProgress(
                (int) $row['id'],
                (string) $row['category_name'],
                (int) $row['limit_cents'],
                (int) $row['spent_cents'],
            ),
            $stmt->fetchAll(),
        );
    }

    /**
     * @return array{string, string} [first day of month, first day of next month)
     */
    private static function monthBounds(string $month): array
    {
        $start = $month . '-01';
        $end = date('Y-m-01', (int) strtotime($start . ' +1 month'));

        return [$start, $end];
    }
}
