<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use Throwable;

final class Migrator
{
    /**
     * Applies pending .sql migrations in filename order. Repeatable: already
     * applied files (tracked in the migrations table) are skipped.
     *
     * @return list<string> applied migration filenames
     */
    public static function run(PDO $pdo, string $migrationsPath): array
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                filename   TEXT PRIMARY KEY,
                applied_at TEXT NOT NULL
            )'
        );

        $done = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob($migrationsPath . '/*.sql') ?: [];
        sort($files);

        $applied = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            self::apply($pdo, $file, $name);
            $applied[] = $name;
        }

        return $applied;
    }

    private static function apply(PDO $pdo, string $file, string $name): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("migration {$name}: unreadable file");
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            $stmt = $pdo->prepare('INSERT INTO migrations (filename, applied_at) VALUES (?, ?)');
            $stmt->execute([$name, date('c')]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw new RuntimeException("migration {$name}: {$e->getMessage()}", 0, $e);
        }
    }
}
