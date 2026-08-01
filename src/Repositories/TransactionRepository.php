<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\TransactionInput;
use PDO;

/**
 * Every query is scoped by user_id — cross-user access is impossible at the
 * data layer, not just in controllers.
 */
final class TransactionRepository
{
    private const SELECT = 'SELECT t.id, t.user_id, t.account_id, t.category_id, t.type,
            t.amount_cents, t.description, t.date,
            c.name AS category_name, a.name AS account_name
        FROM transactions t
        JOIN categories c ON c.id = t.category_id
        JOIN accounts a ON a.id = t.account_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<Transaction>
     */
    public function allForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            self::SELECT . ' WHERE t.user_id = :user_id ORDER BY t.date DESC, t.id DESC LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(Transaction::fromRow(...), $stmt->fetchAll());
    }

    public function find(int $userId, int $id): ?Transaction
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE t.id = ? AND t.user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Transaction::fromRow($row);
    }

    public function create(int $userId, TransactionInput $input): int
    {
        $now = date('c');
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions
                (user_id, account_id, category_id, type, amount_cents, description, date, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $input->accountId,
            $input->categoryId,
            $input->type->value,
            $input->amountCents,
            $input->description,
            $input->date,
            $now,
            $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $userId, int $id, TransactionInput $input): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE transactions
             SET account_id = ?, category_id = ?, type = ?, amount_cents = ?,
                 description = ?, date = ?, updated_at = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([
            $input->accountId,
            $input->categoryId,
            $input->type->value,
            $input->amountCents,
            $input->description,
            $input->date,
            date('c'),
            $id,
            $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
