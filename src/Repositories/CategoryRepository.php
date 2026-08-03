<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Models\CategoryInput;
use App\Models\CategoryType;
use PDO;

/**
 * Every query is scoped by user_id — cross-user access is impossible at the
 * data layer, not just in controllers.
 */
final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<Category>
     */
    public function allForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, name, type, color FROM categories WHERE user_id = ? ORDER BY type, name'
        );
        $stmt->execute([$userId]);

        return array_map(Category::fromRow(...), $stmt->fetchAll());
    }

    public function find(int $userId, int $id): ?Category
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, name, type, color FROM categories WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Category::fromRow($row);
    }

    /**
     * Duplicate (user, name, type) surfaces as PDOException SQLSTATE 23000.
     */
    public function create(int $userId, CategoryType $type, CategoryInput $input): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (user_id, name, type, color, created_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $input->name, $type->value, $input->color, date('c')]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Type is absent from CategoryInput on purpose: transactions must always
     * match their category's type, and retyping a category would break that.
     */
    public function update(int $userId, int $id, CategoryInput $input): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories SET name = ?, color = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$input->name, $input->color, $id, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Categories referenced by transactions are protected by ON DELETE
     * RESTRICT and surface as PDOException SQLSTATE 23000.
     */
    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
