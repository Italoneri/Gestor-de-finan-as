<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use PDO;

final class PasswordResetService
{
    private const TTL_MINUTES = 60;

    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * Returns the plain token for a known e-mail, null otherwise. The caller
     * shows the same message either way — unknown e-mails must be
     * indistinguishable from known ones (no user enumeration).
     */
    public function request(string $email): ?string
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return null;
        }

        $delete = $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $delete->execute([$user->id]);

        $token = Tokens::generate();
        $insert = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)'
        );
        $insert->execute([
            $user->id,
            Tokens::hash($token),
            gmdate('Y-m-d H:i:s', time() + self::TTL_MINUTES * 60),
            gmdate('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function reset(string $token, string $newPassword): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, expires_at FROM password_resets WHERE token_hash = ?'
        );
        $stmt->execute([Tokens::hash($token)]);
        $row = $stmt->fetch();
        if ($row === false) {
            return false;
        }

        $delete = $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $delete->execute([(int) $row['user_id']]);

        if ($row['expires_at'] < gmdate('Y-m-d H:i:s')) {
            return false;
        }

        $this->users->updatePassword((int) $row['user_id'], password_hash($newPassword, PASSWORD_DEFAULT));

        // a password change must kill every persistent login of the account
        $revoke = $this->pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $revoke->execute([(int) $row['user_id']]);

        return true;
    }
}
