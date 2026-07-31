<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Persistent login via selector:validator cookie. The selector is an indexed
 * lookup key; the validator is the secret, stored only as a hash. Tokens are
 * single-use — the caller issues a fresh one after every successful consume,
 * so a stolen cookie stops working as soon as either side uses it.
 */
final class RememberMeService
{
    public const COOKIE_NAME = 'remember_me';
    public const TTL_DAYS = 30;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function issue(int $userId): string
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $selector,
            Tokens::hash($validator),
            gmdate('Y-m-d H:i:s', time() + self::TTL_DAYS * 86400),
            gmdate('Y-m-d H:i:s'),
        ]);

        return $selector . ':' . $validator;
    }

    public function consume(string $cookieValue): ?int
    {
        $parts = explode(':', $cookieValue);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }
        [$selector, $validator] = $parts;

        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, validator_hash, expires_at FROM remember_tokens WHERE selector = ?'
        );
        $stmt->execute([$selector]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        if ($row['expires_at'] < gmdate('Y-m-d H:i:s')) {
            $this->deleteToken((int) $row['id']);

            return null;
        }

        if (!hash_equals($row['validator_hash'], Tokens::hash($validator))) {
            // valid selector + wrong secret smells like cookie theft:
            // revoke every persistent login of this user
            $this->revokeAll((int) $row['user_id']);

            return null;
        }

        $this->deleteToken((int) $row['id']);

        return (int) $row['user_id'];
    }

    public function revokeAll(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    private function deleteToken(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE id = ?');
        $stmt->execute([$id]);
    }
}
