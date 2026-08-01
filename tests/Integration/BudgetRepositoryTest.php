<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Repositories\BudgetRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class BudgetRepositoryTest extends TestCase
{
    private PDO $pdo;
    private BudgetRepository $repo;
    private int $anaId;
    private int $beaId;
    private int $marketCat;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $this->anaId = $this->insertUser('ana@exemplo.com');
        $this->beaId = $this->insertUser('bea@exemplo.com');
        $this->marketCat = $this->insertCategory($this->anaId, 'Mercado');
        $this->repo = new BudgetRepository($this->pdo);
    }

    public function testUpsertInsertsThenUpdatesSameCategoryAndMonth(): void
    {
        $this->repo->upsert($this->anaId, $this->marketCat, '2026-07', 80000);
        $this->repo->upsert($this->anaId, $this->marketCat, '2026-07', 95000);

        $budgets = $this->repo->forMonth($this->anaId, '2026-07');

        $this->assertCount(1, $budgets);
        $this->assertSame(95000, $budgets[0]->limitCents);
        $this->assertSame('Mercado', $budgets[0]->categoryName);
    }

    public function testForMonthListsOnlyOwnBudgetsOfThatMonth(): void
    {
        $leisure = $this->insertCategory($this->anaId, 'Lazer');
        $beaCat = $this->insertCategory($this->beaId, 'Dela');
        $this->repo->upsert($this->anaId, $this->marketCat, '2026-07', 80000);
        $this->repo->upsert($this->anaId, $leisure, '2026-08', 30000);
        $this->repo->upsert($this->beaId, $beaCat, '2026-07', 10000);

        $budgets = $this->repo->forMonth($this->anaId, '2026-07');

        $this->assertCount(1, $budgets);
        $this->assertSame('Mercado', $budgets[0]->categoryName);
    }

    public function testDeletesOnlyOwnBudget(): void
    {
        $this->repo->upsert($this->anaId, $this->marketCat, '2026-07', 80000);
        $id = $this->repo->forMonth($this->anaId, '2026-07')[0]->id;

        $this->assertFalse($this->repo->delete($this->beaId, $id));
        $this->assertTrue($this->repo->delete($this->anaId, $id));
        $this->assertSame([], $this->repo->forMonth($this->anaId, '2026-07'));
    }

    private function insertUser(string $email): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES ('U', ?, 'h', 'x', 'x')"
        );
        $stmt->execute([$email]);

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
