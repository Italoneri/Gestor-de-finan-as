<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Migrator;

$config = Config::load(dirname(__DIR__));
$pdo = Database::connect($config);

$applied = Migrator::run($pdo, __DIR__ . '/migrations');

echo $applied === []
    ? "Nothing to migrate.\n"
    : 'Applied: ' . implode(', ', $applied) . "\n";
