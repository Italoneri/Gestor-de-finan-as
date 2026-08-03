<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use App\Models\Category;
use App\Models\CategoryInput;
use App\Models\CategoryType;
use App\Repositories\CategoryRepository;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class CategoryRepositoryTest extends TestCase
{
    private PDO $pdo;
    private CategoryRepository $repo;
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
        $this->repo = new CategoryRepository($this->pdo);
    }

    public function testListsOnlyOwnCategoriesOrderedByTypeAndName(): void
    {
        $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense);
        $this->createCategory($this->anaId, 'Salário', CategoryType::Income);
        $this->createCategory($this->anaId, 'Lazer', CategoryType::Expense);
        $this->createCategory($this->beaId, 'Da Bea', CategoryType::Expense);

        $categories = $this->repo->allForUser($this->anaId);

        $this->assertCount(3, $categories);
        $this->assertSame(['Lazer', 'Mercado', 'Salário'], array_map(fn ($c) => $c->name, $categories));
        $this->assertSame(CategoryType::Expense, $categories[0]->type);
    }

    public function testFindReturnsNullForOtherUsersCategory(): void
    {
        $id = $this->createCategory($this->beaId, 'Da Bea', CategoryType::Expense);

        $this->assertNull($this->repo->find($this->anaId, $id));
        $this->assertNotNull($this->repo->find($this->beaId, $id));
    }

    public function testUpdatesOnlyOwnCategory(): void
    {
        $id = $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense);

        $this->assertFalse($this->repo->update($this->beaId, $id, new CategoryInput('Invadido', '#f43f5e')));
        $this->assertTrue($this->repo->update($this->anaId, $id, new CategoryInput('Supermercado', '#f43f5e')));

        $category = $this->repo->find($this->anaId, $id);
        $this->assertSame('Supermercado', $category->name);
    }

    public function testDeletesOnlyOwnCategory(): void
    {
        $id = $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense);

        $this->assertFalse($this->repo->delete($this->beaId, $id));
        $this->assertTrue($this->repo->delete($this->anaId, $id));
        $this->assertNull($this->repo->find($this->anaId, $id));
    }

    public function testRejectsDuplicateNamePerUserAndType(): void
    {
        $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense);

        $this->expectException(PDOException::class);
        $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense);
    }

    public function testAllowsSameNameForDifferentUsersOrTypes(): void
    {
        $this->createCategory($this->anaId, 'Extra', CategoryType::Expense);
        $this->createCategory($this->anaId, 'Extra', CategoryType::Income);
        $this->createCategory($this->beaId, 'Extra', CategoryType::Expense);

        $this->assertCount(2, $this->repo->allForUser($this->anaId));
        $this->assertCount(1, $this->repo->allForUser($this->beaId));
    }

    public function testStoresTheChosenColor(): void
    {
        $id = $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense, '#f97316');

        $this->assertSame('#f97316', $this->repo->find($this->anaId, $id)->color);
    }

    public function testUpdatesColorAlongsideName(): void
    {
        $id = $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense, '#f97316');

        $this->repo->update($this->anaId, $id, new CategoryInput('Supermercado', '#22c55e'));

        $category = $this->repo->find($this->anaId, $id);
        $this->assertSame('Supermercado', $category->name);
        $this->assertSame('#22c55e', $category->color);
    }

    public function testFallsBackToTheDefaultColorWhenTheColumnIsNotWritten(): void
    {
        $this->pdo->exec(
            "INSERT INTO categories (user_id, name, type, created_at)
             VALUES ({$this->anaId}, 'Legada', 'expense', 'x')"
        );

        $categories = $this->repo->allForUser($this->anaId);

        $this->assertSame(Category::DEFAULT_COLOR, $categories[0]->color);
    }

    public function testDeleteRejectsCategoryInUseByTransactions(): void
    {
        $categoryId = $this->createCategory($this->anaId, 'Mercado', CategoryType::Expense);
        $this->pdo->exec(
            "INSERT INTO accounts (user_id, name, type, created_at) VALUES ({$this->anaId}, 'Carteira', 'wallet', 'x')"
        );
        $accountId = (int) $this->pdo->lastInsertId();
        $this->pdo->exec(
            "INSERT INTO transactions
                (user_id, account_id, category_id, type, amount_cents, description, date, created_at, updated_at)
             VALUES ({$this->anaId}, {$accountId}, {$categoryId}, 'expense', 100, 'x', '2026-01-01', 'x', 'x')"
        );

        $this->expectException(PDOException::class);
        $this->repo->delete($this->anaId, $categoryId);
    }

    private function createCategory(
        int $userId,
        string $name,
        CategoryType $type,
        string $color = Category::DEFAULT_COLOR,
    ): int {
        return $this->repo->create($userId, $type, new CategoryInput($name, $color));
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
