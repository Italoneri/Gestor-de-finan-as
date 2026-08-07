<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Ceiling;
use PDO;
use PDOException;

/**
 * Every query is scoped by user_id — cross-user access is impossible at the
 * data layer, not just in controllers.
 */
final class CeilingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<Ceiling>
     */
    public function forMonth(int $userId, string $month): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ce.id, ce.user_id, ce.category_id, ce.month, ce.limit_cents, c.name AS category_name
             FROM ceilings ce
             JOIN categories c ON c.id = ce.category_id
             WHERE ce.user_id = ? AND ce.month = ?
             ORDER BY c.name'
        );
        $stmt->execute([$userId, $month]);

        return array_map(Ceiling::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Insert-then-update on the (user, category, month) unique key instead of
     * dialect-specific ON CONFLICT clauses — keeps the SQL portable.
     */
    public function upsert(int $userId, int $categoryId, string $month, int $limitCents): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO ceilings (user_id, category_id, month, limit_cents) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $categoryId, $month, $limitCents]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
            $stmt = $this->pdo->prepare(
                'UPDATE ceilings SET limit_cents = ? WHERE user_id = ? AND category_id = ? AND month = ?'
            );
            $stmt->execute([$limitCents, $userId, $categoryId, $month]);
        }
    }

    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM ceilings WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
