<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Models\CategoryType;
use App\Models\TransactionInput;
use App\Repositories\TransactionRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class TransactionRepositoryTest extends TestCase
{
    private PDO $pdo;
    private TransactionRepository $repo;
    private int $anaId;
    private int $beaId;
    private int $anaAccount;
    private int $anaCategory;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $this->anaId = $this->insertUser('ana@exemplo.com');
        $this->beaId = $this->insertUser('bea@exemplo.com');
        $this->anaAccount = $this->insertAccount($this->anaId, 'Carteira');
        $this->anaCategory = $this->insertCategory($this->anaId, 'Mercado');
        $this->repo = new TransactionRepository($this->pdo);
    }

    public function testCreatesAndListsWithJoinedNames(): void
    {
        $this->repo->create($this->anaId, $this->input(amountCents: 5990, description: 'Feira'));

        $transactions = $this->repo->allForUser($this->anaId);

        $this->assertCount(1, $transactions);
        $tx = $transactions[0];
        $this->assertSame(5990, $tx->amountCents);
        $this->assertSame('Feira', $tx->description);
        $this->assertSame('Mercado', $tx->categoryName);
        $this->assertSame('Carteira', $tx->accountName);
        $this->assertSame(CategoryType::Expense, $tx->type);
    }

    public function testListsOnlyOwnTransactionsNewestFirst(): void
    {
        $beaAccount = $this->insertAccount($this->beaId, 'Da Bea');
        $beaCategory = $this->insertCategory($this->beaId, 'Dela');
        $this->repo->create($this->anaId, $this->input(date: '2026-07-01', description: 'antiga'));
        $this->repo->create($this->anaId, $this->input(date: '2026-07-20', description: 'recente'));
        $this->repo->create($this->beaId, new TransactionInput(
            accountId: $beaAccount,
            categoryId: $beaCategory,
            type: CategoryType::Expense,
            amountCents: 100,
            description: 'da bea',
            date: '2026-07-10',
        ));

        $transactions = $this->repo->allForUser($this->anaId);

        $this->assertSame(['recente', 'antiga'], array_map(fn ($t) => $t->description, $transactions));
    }

    public function testRespectsListLimit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repo->create($this->anaId, $this->input(date: sprintf('2026-07-%02d', $i)));
        }

        $this->assertCount(3, $this->repo->allForUser($this->anaId, limit: 3));
    }

    public function testFindReturnsNullForOtherUsersTransaction(): void
    {
        $id = $this->repo->create($this->anaId, $this->input());

        $this->assertNull($this->repo->find($this->beaId, $id));
        $this->assertNotNull($this->repo->find($this->anaId, $id));
    }

    public function testUpdatesOnlyOwnTransaction(): void
    {
        $id = $this->repo->create($this->anaId, $this->input(description: 'original'));

        $this->assertFalse($this->repo->update($this->beaId, $id, $this->input(description: 'invadida')));
        $this->assertTrue($this->repo->update($this->anaId, $id, $this->input(
            amountCents: 7500,
            description: 'corrigida',
        )));

        $tx = $this->repo->find($this->anaId, $id);
        $this->assertSame('corrigida', $tx->description);
        $this->assertSame(7500, $tx->amountCents);
    }

    public function testDeletesOnlyOwnTransaction(): void
    {
        $id = $this->repo->create($this->anaId, $this->input());

        $this->assertFalse($this->repo->delete($this->beaId, $id));
        $this->assertTrue($this->repo->delete($this->anaId, $id));
        $this->assertNull($this->repo->find($this->anaId, $id));
    }

    private function input(
        int $amountCents = 1000,
        string $description = 'Compra',
        string $date = '2026-07-15',
    ): TransactionInput {
        return new TransactionInput(
            accountId: $this->anaAccount,
            categoryId: $this->anaCategory,
            type: CategoryType::Expense,
            amountCents: $amountCents,
            description: $description,
            date: $date,
        );
    }

    private function insertUser(string $email): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES ('U', ?, 'h', 'x', 'x')"
        );
        $stmt->execute([$email]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertAccount(int $userId, string $name): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO accounts (user_id, name, type, created_at) VALUES (?, ?, 'wallet', 'x')"
        );
        $stmt->execute([$userId, $name]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertCategory(int $userId, string $name): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categories (user_id, name, type, created_at) VALUES (?, ?, 'expense', 'x')"
        );
        $stmt->execute([$userId, $name]);

        return (int) $this->pdo->lastInsertId();
    }
}
