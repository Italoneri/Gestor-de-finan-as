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
            'ceilings',
            'income_goals',
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

    public function testRollsBackFailedMigrationWithoutRecordingIt(): void
    {
        $dir = sys_get_temp_dir() . '/migrator-test-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/001_ok.sql', 'CREATE TABLE ok_table (id INTEGER PRIMARY KEY);');
        file_put_contents($dir . '/002_broken.sql', 'CREATE TABLE broken (id INTEGER PRIMARY KEY); SYNTAX ERROR;');
        $pdo = $this->freshConnection();

        try {
            $this->expectException(\RuntimeException::class);
            Migrator::run($pdo, $dir);
        } finally {
            $applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
            $this->assertSame(['001_ok.sql'], $applied);

            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")
                ->fetchAll(PDO::FETCH_COLUMN);
            $this->assertNotContains('broken', $tables);

            @unlink($dir . '/001_ok.sql');
            @unlink($dir . '/002_broken.sql');
            @rmdir($dir);
        }
    }

    private function freshConnection(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }
}
