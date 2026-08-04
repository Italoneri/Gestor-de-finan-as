<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Repositories\IncomeGoalRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class IncomeGoalRepositoryTest extends TestCase
{
    private PDO $pdo;
    private IncomeGoalRepository $repo;
    private int $anaId;
    private int $beaId;
    private int $salaryCat;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Migrator::run($this->pdo, __DIR__ . '/../../database/migrations');

        $this->anaId = $this->insertUser('ana@exemplo.com');
        $this->beaId = $this->insertUser('bea@exemplo.com');
        $this->salaryCat = $this->insertCategory($this->anaId, 'Salário');
        $this->repo = new IncomeGoalRepository($this->pdo);
    }

    public function testUpsertInsertsThenUpdatesSameCategoryAndMonth(): void
    {
        $this->repo->upsert($this->anaId, $this->salaryCat, '2026-07', 500000);
        $this->repo->upsert($this->anaId, $this->salaryCat, '2026-07', 620000);

        $goals = $this->repo->forMonth($this->anaId, '2026-07');

        $this->assertCount(1, $goals);
        $this->assertSame(620000, $goals[0]->targetCents);
        $this->assertSame('Salário', $goals[0]->categoryName);
    }

    public function testForMonthListsOnlyOwnGoalsOfThatMonth(): void
    {
        $freelance = $this->insertCategory($this->anaId, 'Freelance');
        $beaCat = $this->insertCategory($this->beaId, 'Dela');
        $this->repo->upsert($this->anaId, $this->salaryCat, '2026-07', 500000);
        $this->repo->upsert($this->anaId, $freelance, '2026-08', 150000);
        $this->repo->upsert($this->beaId, $beaCat, '2026-07', 10000);

        $goals = $this->repo->forMonth($this->anaId, '2026-07');

        $this->assertCount(1, $goals);
        $this->assertSame('Salário', $goals[0]->categoryName);
    }

    public function testDeletesOnlyOwnGoal(): void
    {
        $this->repo->upsert($this->anaId, $this->salaryCat, '2026-07', 500000);
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
            "INSERT INTO categories (user_id, name, type, created_at) VALUES (?, ?, 'income', 'x')"
        );
        $stmt->execute([$userId, $name]);

        return (int) $this->pdo->lastInsertId();
    }
}
