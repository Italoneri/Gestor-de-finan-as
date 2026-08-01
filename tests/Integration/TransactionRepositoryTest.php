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

    public function testSearchFiltersByPeriod(): void
    {
        $this->repo->create($this->anaId, $this->input(date: '2026-06-30', description: 'fora'));
        $this->repo->create($this->anaId, $this->input(date: '2026-07-10', description: 'dentro'));
        $this->repo->create($this->anaId, $this->input(date: '2026-08-01', description: 'depois'));

        $filter = \App\Models\TransactionFilter::fromQuery(['from' => '2026-07-01', 'to' => '2026-07-31']);
        $found = $this->repo->search($this->anaId, $filter, perPage: 10, page: 1);

        $this->assertSame(['dentro'], array_map(fn ($t) => $t->description, $found));
        $this->assertSame(1, $this->repo->countFor($this->anaId, $filter));
    }

    public function testSearchFiltersByCategoryAndType(): void
    {
        $incomeCat = $this->insertIncomeCategory($this->anaId, 'Salário');
        $this->repo->create($this->anaId, $this->input(description: 'despesa mercado'));
        $this->repo->create($this->anaId, new TransactionInput(
            accountId: $this->anaAccount,
            categoryId: $incomeCat,
            type: CategoryType::Income,
            amountCents: 500000,
            description: 'salário',
            date: '2026-07-05',
        ));

        $byCategory = $this->repo->search(
            $this->anaId,
            \App\Models\TransactionFilter::fromQuery(['category_id' => (string) $incomeCat]),
            perPage: 10,
            page: 1,
        );
        $byType = $this->repo->search(
            $this->anaId,
            \App\Models\TransactionFilter::fromQuery(['type' => 'expense']),
            perPage: 10,
            page: 1,
        );

        $this->assertSame(['salário'], array_map(fn ($t) => $t->description, $byCategory));
        $this->assertSame(['despesa mercado'], array_map(fn ($t) => $t->description, $byType));
    }

    public function testSearchMatchesDescriptionSubstring(): void
    {
        $this->repo->create($this->anaId, $this->input(description: 'Compras no mercado'));
        $this->repo->create($this->anaId, $this->input(description: 'Cinema'));

        $found = $this->repo->search(
            $this->anaId,
            \App\Models\TransactionFilter::fromQuery(['q' => 'mercado']),
            perPage: 10,
            page: 1,
        );

        $this->assertSame(['Compras no mercado'], array_map(fn ($t) => $t->description, $found));
    }

    public function testSearchEscapesLikeWildcards(): void
    {
        $this->repo->create($this->anaId, $this->input(description: 'Desconto 100% aplicado'));
        $this->repo->create($this->anaId, $this->input(description: 'Sem desconto nenhum'));

        $found = $this->repo->search(
            $this->anaId,
            \App\Models\TransactionFilter::fromQuery(['q' => '100%']),
            perPage: 10,
            page: 1,
        );

        $this->assertSame(['Desconto 100% aplicado'], array_map(fn ($t) => $t->description, $found));
    }

    public function testSearchSortsByAmountAscending(): void
    {
        $this->repo->create($this->anaId, $this->input(amountCents: 300, description: 'c'));
        $this->repo->create($this->anaId, $this->input(amountCents: 100, description: 'a'));
        $this->repo->create($this->anaId, $this->input(amountCents: 200, description: 'b'));

        $found = $this->repo->search(
            $this->anaId,
            \App\Models\TransactionFilter::fromQuery(['sort' => 'amount', 'dir' => 'asc']),
            perPage: 10,
            page: 1,
        );

        $this->assertSame(['a', 'b', 'c'], array_map(fn ($t) => $t->description, $found));
    }

    public function testSearchPaginatesWithOffset(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->repo->create($this->anaId, $this->input(
                date: sprintf('2026-07-%02d', $i),
                description: "tx{$i}",
            ));
        }

        $filter = \App\Models\TransactionFilter::fromQuery(['page' => '2']);
        $pageTwo = $this->repo->search($this->anaId, $filter, perPage: 3, page: $filter->page);

        $this->assertSame(['tx4', 'tx3', 'tx2'], array_map(fn ($t) => $t->description, $pageTwo));
        $this->assertSame(7, $this->repo->countFor($this->anaId, $filter));
    }

    private function insertIncomeCategory(int $userId, string $name): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categories (user_id, name, type, created_at) VALUES (?, ?, 'income', 'x')"
        );
        $stmt->execute([$userId, $name]);

        return (int) $this->pdo->lastInsertId();
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
