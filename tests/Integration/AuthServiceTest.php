<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\LoginRateLimiter;
use App\Services\LoginResult;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $this->auth = new AuthService(
            new UserRepository($this->pdo),
            new LoginRateLimiter($this->pdo),
            new Session('test_session'),
        );
    }

    public function testRegistersUserWithHashedPasswordWithoutLoggingIn(): void
    {
        $userId = $this->auth->register('Ana Souza', 'ana@exemplo.com', 'Senha@123');

        $this->assertIsInt($userId);
        $this->assertFalse($this->auth->check());

        $row = $this->pdo->query('SELECT password_hash, email_verified_at FROM users')->fetch();
        $this->assertNull($row['email_verified_at']);
        $this->assertTrue(password_verify('Senha@123', $row['password_hash']));
    }

    public function testReturnsNullForDuplicateEmailOnRegister(): void
    {
        $this->auth->register('Ana Souza', 'ana@exemplo.com', 'Senha@123');

        $this->assertNull($this->auth->register('Outra Ana', 'ana@exemplo.com', 'Outra@456'));
    }

    public function testRejectsLoginWhenEmailNotVerified(): void
    {
        $this->auth->register('Ana Souza', 'ana@exemplo.com', 'Senha@123');

        $result = $this->auth->attemptLogin('ana@exemplo.com', 'Senha@123', '10.0.0.1');

        $this->assertSame(LoginResult::EmailNotVerified, $result);
        $this->assertFalse($this->auth->check());
    }

    public function testLogsInWithValidCredentials(): void
    {
        $this->registerVerifiedUser();

        $result = $this->auth->attemptLogin('ana@exemplo.com', 'Senha@123', '10.0.0.1');

        $this->assertSame(LoginResult::Success, $result);
        $this->assertTrue($this->auth->check());
        $this->assertNotNull($this->auth->userId());
    }

    public function testLoginUsingIdAuthenticatesSession(): void
    {
        $userId = $this->registerVerifiedUser();

        $this->auth->loginUsingId($userId);

        $this->assertTrue($this->auth->check());
        $this->assertSame($userId, $this->auth->userId());
    }

    public function testRejectsWrongPassword(): void
    {
        $this->registerVerifiedUser();

        $result = $this->auth->attemptLogin('ana@exemplo.com', 'Errada@123', '10.0.0.1');

        $this->assertSame(LoginResult::InvalidCredentials, $result);
        $this->assertFalse($this->auth->check());
    }

    public function testRejectsUnknownEmailWithSameResultAsWrongPassword(): void
    {
        $result = $this->auth->attemptLogin('ninguem@exemplo.com', 'Senha@123', '10.0.0.1');

        $this->assertSame(LoginResult::InvalidCredentials, $result);
    }

    public function testBlocksEmailAfterFiveFailedAttempts(): void
    {
        $this->registerVerifiedUser();

        for ($i = 0; $i < 5; $i++) {
            $this->auth->attemptLogin('ana@exemplo.com', 'Errada@123', '10.0.0.1');
        }
        $result = $this->auth->attemptLogin('ana@exemplo.com', 'Senha@123', '10.0.0.1');

        $this->assertSame(LoginResult::RateLimited, $result);
        $this->assertFalse($this->auth->check());
    }

    public function testBlocksIpAfterTwentyFailedAttemptsAcrossEmails(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->auth->attemptLogin("bot{$i}@exemplo.com", 'Errada@123', '203.0.113.7');
        }

        $result = $this->auth->attemptLogin('outra@exemplo.com', 'Senha@123', '203.0.113.7');

        $this->assertSame(LoginResult::RateLimited, $result);
    }

    public function testSuccessfulLoginClearsFailedAttemptsForEmail(): void
    {
        $this->registerVerifiedUser();

        for ($i = 0; $i < 4; $i++) {
            $this->auth->attemptLogin('ana@exemplo.com', 'Errada@123', '10.0.0.1');
        }
        $this->auth->attemptLogin('ana@exemplo.com', 'Senha@123', '10.0.0.1');

        $count = $this->pdo
            ->query("SELECT COUNT(*) FROM login_attempts WHERE email = 'ana@exemplo.com'")
            ->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    public function testLogoutRemovesAuthenticatedUser(): void
    {
        $this->registerVerifiedUser();
        $this->auth->attemptLogin('ana@exemplo.com', 'Senha@123', '10.0.0.1');

        $this->auth->logout();

        $this->assertFalse($this->auth->check());
        $this->assertNull($this->auth->userId());
    }

    public function testIsolatesRateLimitBetweenDifferentEmailsAndIps(): void
    {
        $this->registerVerifiedUser();

        for ($i = 0; $i < 5; $i++) {
            $this->auth->attemptLogin('outro@exemplo.com', 'Errada@123', '198.51.100.9');
        }

        $result = $this->auth->attemptLogin('ana@exemplo.com', 'Senha@123', '10.0.0.1');

        $this->assertSame(LoginResult::Success, $result);
    }

    private function registerVerifiedUser(): int
    {
        $userId = $this->auth->register('Ana Souza', 'ana@exemplo.com', 'Senha@123');
        assert($userId !== null);
        $this->pdo->exec("UPDATE users SET email_verified_at = '2026-01-01 00:00:00' WHERE id = {$userId}");

        return $userId;
    }
}
