<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Budget;
use PDO;
use PDOException;

/**
 * Every query is scoped by user_id — cross-user access is impossible at the
 * data layer, not just in controllers.
 */
final class BudgetRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<Budget>
     */
    public function forMonth(int $userId, string $month): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.user_id, b.category_id, b.month, b.limit_cents, c.name AS category_name
             FROM budgets b
             JOIN categories c ON c.id = b.category_id
             WHERE b.user_id = ? AND b.month = ?
             ORDER BY c.name'
        );
        $stmt->execute([$userId, $month]);

        return array_map(Budget::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Insert-then-update on the (user, category, month) unique key instead of
     * dialect-specific ON CONFLICT clauses — keeps the SQL portable.
     */
    public function upsert(int $userId, int $categoryId, string $month, int $limitCents): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO budgets (user_id, category_id, month, limit_cents) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $categoryId, $month, $limitCents]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
            $stmt = $this->pdo->prepare(
                'UPDATE budgets SET limit_cents = ? WHERE user_id = ? AND category_id = ? AND month = ?'
            );
            $stmt->execute([$limitCents, $userId, $categoryId, $month]);
        }
    }

    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM budgets WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
