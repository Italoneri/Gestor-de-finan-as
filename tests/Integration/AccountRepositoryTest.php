<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Models\AccountType;
use App\Repositories\AccountRepository;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class AccountRepositoryTest extends TestCase
{
    private PDO $pdo;
    private AccountRepository $repo;
    private int $anaId;
    private int $beaId;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $this->anaId = $this->insertUser('ana@exemplo.com');
        $this->beaId = $this->insertUser('bea@exemplo.com');
        $this->repo = new AccountRepository($this->pdo);
    }

    public function testListsOnlyOwnAccounts(): void
    {
        $this->repo->create($this->anaId, 'Carteira', AccountType::Wallet);
        $this->repo->create($this->anaId, 'Nubank', AccountType::Checking);
        $this->repo->create($this->beaId, 'Da Bea', AccountType::Savings);

        $accounts = $this->repo->allForUser($this->anaId);

        $this->assertCount(2, $accounts);
        $this->assertSame(['Carteira', 'Nubank'], array_map(fn ($a) => $a->name, $accounts));
    }

    public function testFindReturnsNullForOtherUsersAccount(): void
    {
        $id = $this->repo->create($this->beaId, 'Da Bea', AccountType::Wallet);

        $this->assertNull($this->repo->find($this->anaId, $id));
    }

    public function testUpdatesNameAndTypeOfOwnAccountOnly(): void
    {
        $id = $this->repo->create($this->anaId, 'Carteira', AccountType::Wallet);

        $this->assertFalse($this->repo->update($this->beaId, $id, 'Invadida', AccountType::Savings));
        $this->assertTrue($this->repo->update($this->anaId, $id, 'Poupança Caixa', AccountType::Savings));

        $account = $this->repo->find($this->anaId, $id);
        $this->assertSame('Poupança Caixa', $account->name);
        $this->assertSame(AccountType::Savings, $account->type);
    }

    public function testDeletesOnlyOwnAccount(): void
    {
        $id = $this->repo->create($this->anaId, 'Carteira', AccountType::Wallet);

        $this->assertFalse($this->repo->delete($this->beaId, $id));
        $this->assertTrue($this->repo->delete($this->anaId, $id));
    }

    public function testDeleteRejectsAccountInUseByTransactions(): void
    {
        $accountId = $this->repo->create($this->anaId, 'Carteira', AccountType::Wallet);
        $this->pdo->exec(
            "INSERT INTO categories (user_id, name, type, created_at) VALUES ({$this->anaId}, 'M', 'expense', 'x')"
        );
        $categoryId = (int) $this->pdo->lastInsertId();
        $this->pdo->exec(
            "INSERT INTO transactions
                (user_id, account_id, category_id, type, amount_cents, description, date, created_at, updated_at)
             VALUES ({$this->anaId}, {$accountId}, {$categoryId}, 'expense', 100, 'x', '2026-01-01', 'x', 'x')"
        );

        $this->expectException(PDOException::class);
        $this->repo->delete($this->anaId, $accountId);
    }

    private function insertUser(string $email): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES ('U', ?, 'h', 'x', 'x')"
        );
        $stmt->execute([$email]);

        return (int) $this->pdo->lastInsertId();
    }
}
