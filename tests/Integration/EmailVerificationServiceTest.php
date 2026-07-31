<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Repositories\UserRepository;
use App\Services\EmailVerificationService;
use PDO;
use PHPUnit\Framework\TestCase;

final class EmailVerificationServiceTest extends TestCase
{
    private PDO $pdo;
    private EmailVerificationService $service;
    private int $userId;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $this->pdo->exec(
            "INSERT INTO users (name, email, password_hash, created_at, updated_at)
             VALUES ('Ana', 'ana@exemplo.com', 'hash', '2026-01-01', '2026-01-01')"
        );
        $this->userId = (int) $this->pdo->lastInsertId();
        $this->service = new EmailVerificationService($this->pdo, new UserRepository($this->pdo));
    }

    public function testVerifyMarksEmailVerifiedAndConsumesToken(): void
    {
        $token = $this->service->issue($this->userId);

        $this->assertTrue($this->service->verify($token));

        $verifiedAt = $this->pdo->query('SELECT email_verified_at FROM users')->fetchColumn();
        $this->assertNotNull($verifiedAt);

        $count = $this->pdo->query('SELECT COUNT(*) FROM email_verifications')->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    public function testIssueStoresOnlyHashAndReplacesPreviousToken(): void
    {
        $first = $this->service->issue($this->userId);
        $second = $this->service->issue($this->userId);

        $this->assertNotSame($first, $second);

        $rows = $this->pdo->query('SELECT token_hash FROM email_verifications')->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertNotSame($second, $rows[0]['token_hash']);
    }

    public function testVerifyRejectsExpiredToken(): void
    {
        $token = $this->service->issue($this->userId);
        $this->pdo->exec("UPDATE email_verifications SET expires_at = '2020-01-01 00:00:00'");

        $this->assertFalse($this->service->verify($token));

        $verifiedAt = $this->pdo->query('SELECT email_verified_at FROM users')->fetchColumn();
        $this->assertNull($verifiedAt);
    }

    public function testVerifyRejectsUnknownToken(): void
    {
        $this->assertFalse($this->service->verify(str_repeat('a', 64)));
    }
}
