<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Services\RememberMeService;
use PDO;
use PHPUnit\Framework\TestCase;

final class RememberMeServiceTest extends TestCase
{
    private PDO $pdo;
    private RememberMeService $service;
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
        $this->service = new RememberMeService($this->pdo);
    }

    public function testIssueReturnsSelectorValidatorPairAndStoresOnlyHash(): void
    {
        $cookie = $this->service->issue($this->userId);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}:[0-9a-f]{64}$/', $cookie);

        [, $validator] = explode(':', $cookie);
        $stored = $this->pdo->query('SELECT validator_hash FROM remember_tokens')->fetchColumn();
        $this->assertNotSame($validator, $stored);
    }

    public function testConsumeReturnsUserIdAndDeletesTokenForValidCookie(): void
    {
        $cookie = $this->service->issue($this->userId);

        $this->assertSame($this->userId, $this->service->consume($cookie));

        $count = $this->pdo->query('SELECT COUNT(*) FROM remember_tokens')->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    public function testConsumeRejectsTamperedValidatorAndRevokesAllUserTokens(): void
    {
        $cookie = $this->service->issue($this->userId);
        $this->service->issue($this->userId);
        [$selector] = explode(':', $cookie);

        $result = $this->service->consume($selector . ':' . str_repeat('0', 64));

        $this->assertNull($result);
        $count = $this->pdo->query('SELECT COUNT(*) FROM remember_tokens')->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    public function testConsumeRejectsExpiredToken(): void
    {
        $cookie = $this->service->issue($this->userId);
        $this->pdo->exec("UPDATE remember_tokens SET expires_at = '2020-01-01 00:00:00'");

        $this->assertNull($this->service->consume($cookie));
    }

    public function testConsumeRejectsMalformedCookie(): void
    {
        $this->assertNull($this->service->consume('sem-separador'));
        $this->assertNull($this->service->consume(''));
        $this->assertNull($this->service->consume('a:b:c'));
    }

    public function testConsumeRejectsUnknownSelector(): void
    {
        $this->assertNull(
            $this->service->consume(str_repeat('a', 32) . ':' . str_repeat('b', 64))
        );
    }

    public function testRevokeAllDeletesEveryTokenOfUser(): void
    {
        $this->service->issue($this->userId);
        $this->service->issue($this->userId);

        $this->service->revokeAll($this->userId);

        $count = $this->pdo->query('SELECT COUNT(*) FROM remember_tokens')->fetchColumn();
        $this->assertSame(0, (int) $count);
    }
}
