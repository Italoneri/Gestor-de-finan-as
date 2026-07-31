<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Migrator;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private const MIGRATIONS_PATH = __DIR__ . '/../../database/migrations';

    public function testAppliesAllMigrationsOnFreshDatabase(): void
    {
        $pdo = $this->freshConnection();

        Migrator::run($pdo, self::MIGRATIONS_PATH);

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")
            ->fetchAll(PDO::FETCH_COLUMN);
        $expected = [
            'users',
            'login_attempts',
            'remember_tokens',
            'password_resets',
            'email_verifications',
            'accounts',
            'categories',
            'transactions',
            'budgets',
        ];
        foreach ($expected as $table) {
            $this->assertContains($table, $tables);
        }
    }

    public function testSecondRunAppliesNothing(): void
    {
        $pdo = $this->freshConnection();

        Migrator::run($pdo, self::MIGRATIONS_PATH);

        $this->assertSame([], Migrator::run($pdo, self::MIGRATIONS_PATH));
    }

    public function testEnforcesForeignKeysOnDataTables(): void
    {
        $pdo = $this->freshConnection();
        Migrator::run($pdo, self::MIGRATIONS_PATH);

        $this->expectException(PDOException::class);
        $pdo->exec(
            "INSERT INTO accounts (user_id, name, type, created_at) VALUES (999, 'x', 'wallet', '2026-01-01')"
        );
    }

    private function freshConnection(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }
}
