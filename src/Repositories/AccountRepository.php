<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Account;
use App\Models\AccountType;
use PDO;

/**
 * Every query is scoped by user_id — cross-user access is impossible at the
 * data layer, not just in controllers.
 */
final class AccountRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<Account>
     */
    public function allForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, name, type FROM accounts WHERE user_id = ? ORDER BY name'
        );
        $stmt->execute([$userId]);

        return array_map(Account::fromRow(...), $stmt->fetchAll());
    }

    public function find(int $userId, int $id): ?Account
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, name, type FROM accounts WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Account::fromRow($row);
    }

    public function create(int $userId, string $name, AccountType $type): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accounts (user_id, name, type, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $name, $type->value, date('c')]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $userId, int $id, string $name, AccountType $type): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE accounts SET name = ?, type = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$name, $type->value, $id, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Accounts referenced by transactions are protected by ON DELETE RESTRICT
     * and surface as PDOException SQLSTATE 23000.
     */
    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM accounts WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
