<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class LoginRateLimiter
{
    private const EMAIL_MAX_ATTEMPTS = 5;

    /** higher than the e-mail cap so one shared IP (NAT, offices) doesn't lock everyone out */
    private const IP_MAX_ATTEMPTS = 20;

    private const WINDOW_MINUTES = 15;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function tooManyAttempts(string $email, string $ip): bool
    {
        $cutoff = $this->cutoff();

        return $this->countByEmail($email, $cutoff) >= self::EMAIL_MAX_ATTEMPTS
            || $this->countByIp($ip, $cutoff) >= self::IP_MAX_ATTEMPTS;
    }

    public function recordFailure(string $email, string $ip): void
    {
        // expired attempts are dead weight; purge as we write
        $purge = $this->pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
        $purge->execute([$this->cutoff()]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, $ip, gmdate('Y-m-d H:i:s')]);
    }

    public function clear(string $email): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE email = ?');
        $stmt->execute([$email]);
    }

    private function countByEmail(string $email, string $cutoff): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at >= ?'
        );
        $stmt->execute([$email, $cutoff]);

        return (int) $stmt->fetchColumn();
    }

    private function countByIp(string $ip, string $cutoff): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?'
        );
        $stmt->execute([$ip, $cutoff]);

        return (int) $stmt->fetchColumn();
    }

    private function cutoff(): string
    {
        return gmdate('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
    }
}
