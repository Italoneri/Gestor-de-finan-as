<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\IncomeGoal;
use PDO;
use PDOException;

/**
 * Every query is scoped by user_id — cross-user access is impossible at the
 * data layer, not just in controllers.
 */
final class IncomeGoalRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<IncomeGoal>
     */
    public function forMonth(int $userId, string $month): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT g.id, g.user_id, g.category_id, g.month, g.target_cents, c.name AS category_name
             FROM income_goals g
             JOIN categories c ON c.id = g.category_id
             WHERE g.user_id = ? AND g.month = ?
             ORDER BY c.name'
        );
        $stmt->execute([$userId, $month]);

        return array_map(IncomeGoal::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Insert-then-update on the (user, category, month) unique key instead of
     * dialect-specific ON CONFLICT clauses — keeps the SQL portable.
     */
    public function upsert(int $userId, int $categoryId, string $month, int $targetCents): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO income_goals (user_id, category_id, month, target_cents) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $categoryId, $month, $targetCents]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
            $stmt = $this->pdo->prepare(
                'UPDATE income_goals SET target_cents = ? WHERE user_id = ? AND category_id = ? AND month = ?'
            );
            $stmt->execute([$targetCents, $userId, $categoryId, $month]);
        }
    }

    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM income_goals WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
