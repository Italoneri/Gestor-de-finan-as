<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

final class UserRepository
{
    private const COLUMNS = 'id, name, email, password_hash, email_verified_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromRow($row);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromRow($row);
    }

    /**
     * Duplicate e-mails surface as PDOException SQLSTATE 23000 (unique
     * constraint) — the caller decides how to translate that.
     */
    public function create(string $name, string $email, string $passwordHash): int
    {
        $now = date('c');
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $passwordHash, $now, $now]);

        return (int) $this->pdo->lastInsertId();
    }
}
