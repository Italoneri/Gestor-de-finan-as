<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use PDO;

final class EmailVerificationService
{
    private const TTL_HOURS = 24;

    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepository $users,
    ) {
    }

    public function issue(int $userId): string
    {
        $delete = $this->pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?');
        $delete->execute([$userId]);

        $token = Tokens::generate();
        $insert = $this->pdo->prepare(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)'
        );
        $insert->execute([
            $userId,
            Tokens::hash($token),
            gmdate('Y-m-d H:i:s', time() + self::TTL_HOURS * 3600),
            gmdate('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function verify(string $token): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, expires_at FROM email_verifications WHERE token_hash = ?'
        );
        $stmt->execute([Tokens::hash($token)]);
        $row = $stmt->fetch();
        if ($row === false) {
            return false;
        }

        $delete = $this->pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?');
        $delete->execute([(int) $row['user_id']]);

        if ($row['expires_at'] < gmdate('Y-m-d H:i:s')) {
            return false;
        }

        $this->users->markEmailVerified((int) $row['user_id']);

        return true;
    }
}
