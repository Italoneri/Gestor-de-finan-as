<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    public static function connect(Config $config): PDO
    {
        $driver = $config->get('DB_DRIVER', 'sqlite');

        $pdo = match ($driver) {
            'sqlite' => self::sqlite($config),
            'mysql' => self::mysql($config),
            default => throw new RuntimeException("unsupported DB_DRIVER \"{$driver}\""),
        };

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    private static function sqlite(Config $config): PDO
    {
        $path = $config->get('DB_SQLITE_PATH', 'database/app.sqlite');
        $dsn = $path === ':memory:' ? 'sqlite::memory:' : 'sqlite:' . $config->basePath($path);

        $pdo = new PDO($dsn);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    private static function mysql(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config->get('DB_HOST', '127.0.0.1'),
            $config->get('DB_PORT', '3306'),
            $config->get('DB_NAME', 'finance'),
        );

        return new PDO($dsn, $config->get('DB_USER'), $config->get('DB_PASS'));
    }
}
