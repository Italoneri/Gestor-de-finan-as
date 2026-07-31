<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Repositories\UserRepository;
use App\Services\PasswordResetService;
use App\Services\RememberMeService;
use PDO;
use PHPUnit\Framework\TestCase;

final class PasswordResetServiceTest extends TestCase
{
    private PDO $pdo;
    private PasswordResetService $service;
    private RememberMeService $rememberMe;
    private int $userId;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $hash = password_hash('Antiga@123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash, created_at, updated_at)
             VALUES ('Ana', 'ana@exemplo.com', ?, '2026-01-01', '2026-01-01')"
        );
        $stmt->execute([$hash]);
        $this->userId = (int) $this->pdo->lastInsertId();

        $this->rememberMe = new RememberMeService($this->pdo);
        $this->service = new PasswordResetService($this->pdo, new UserRepository($this->pdo));
    }

    public function testRequestReturnsTokenForKnownEmail(): void
    {
        $token = $this->service->request('ana@exemplo.com');

        $this->assertNotNull($token);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);

        $stored = $this->pdo->query('SELECT token_hash FROM password_resets')->fetchColumn();
        $this->assertNotSame($token, $stored);
    }

    public function testRequestReturnsNullForUnknownEmail(): void
    {
        $this->assertNull($this->service->request('ninguem@exemplo.com'));
    }

    public function testRequestReplacesPreviousToken(): void
    {
        $this->service->request('ana@exemplo.com');
        $this->service->request('ana@exemplo.com');

        $count = $this->pdo->query('SELECT COUNT(*) FROM password_resets')->fetchColumn();
        $this->assertSame(1, (int) $count);
    }

    public function testResetUpdatesPasswordWithValidToken(): void
    {
        $token = $this->service->request('ana@exemplo.com');

        $this->assertTrue($this->service->reset($token, 'Nova@1234'));

        $hash = $this->pdo->query('SELECT password_hash FROM users')->fetchColumn();
        $this->assertTrue(password_verify('Nova@1234', $hash));
        $this->assertFalse(password_verify('Antiga@123', $hash));
    }

    public function testResetInvalidatesTokenAfterUse(): void
    {
        $token = $this->service->request('ana@exemplo.com');
        $this->service->reset($token, 'Nova@1234');

        $this->assertFalse($this->service->reset($token, 'Outra@1234'));
    }

    public function testResetRejectsExpiredToken(): void
    {
        $token = $this->service->request('ana@exemplo.com');
        $this->pdo->exec("UPDATE password_resets SET expires_at = '2020-01-01 00:00:00'");

        $this->assertFalse($this->service->reset($token, 'Nova@1234'));
    }

    public function testResetRejectsUnknownToken(): void
    {
        $this->assertFalse($this->service->reset(str_repeat('a', 64), 'Nova@1234'));
    }

    public function testResetRevokesRememberTokensOfUser(): void
    {
        $this->rememberMe->issue($this->userId);
        $token = $this->service->request('ana@exemplo.com');

        $this->service->reset($token, 'Nova@1234');

        $count = $this->pdo->query('SELECT COUNT(*) FROM remember_tokens')->fetchColumn();
        $this->assertSame(0, (int) $count);
    }
}
