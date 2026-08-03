<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;

const TEST_EMAIL = 'teste@exemplo.com';
const TEST_PASSWORD = 'Senha@123';

$config = Config::load(dirname(__DIR__));
$pdo = Database::connect($config);

$existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$existing->execute([TEST_EMAIL]);
if ($existing->fetch() !== false) {
    echo "Seed already applied (test user exists).\n";
    exit(0);
}

$now = date('c');
$month = date('Y-m');

$pdo->beginTransaction();

$insertUser = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, email_verified_at, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$insertUser->execute([
    'Usuário Teste',
    TEST_EMAIL,
    password_hash(TEST_PASSWORD, PASSWORD_DEFAULT),
    $now,
    $now,
    $now,
]);
$userId = (int) $pdo->lastInsertId();

$insertAccount = $pdo->prepare('INSERT INTO accounts (user_id, name, type, created_at) VALUES (?, ?, ?, ?)');
$accountIds = [];
foreach ([['Carteira', 'wallet'], ['Conta Corrente', 'checking'], ['Poupança', 'savings']] as [$name, $type]) {
    $insertAccount->execute([$userId, $name, $type, $now]);
    $accountIds[$name] = (int) $pdo->lastInsertId();
}

$insertCategory = $pdo->prepare(
    'INSERT INTO categories (user_id, name, type, color, created_at) VALUES (?, ?, ?, ?, ?)'
);
$categoryIds = [];
$categories = [
    ['Salário', 'income', '#22c55e'],
    ['Freelance', 'income', '#14b8a6'],
    ['Moradia', 'expense', '#6366f1'],
    ['Mercado', 'expense', '#f97316'],
    ['Transporte', 'expense', '#0ea5e9'],
    ['Lazer', 'expense', '#f43f5e'],
];
foreach ($categories as [$name, $type, $color]) {
    $insertCategory->execute([$userId, $name, $type, $color, $now]);
    $categoryIds[$name] = (int) $pdo->lastInsertId();
}

$insertTx = $pdo->prepare(
    'INSERT INTO transactions
        (user_id, account_id, category_id, type, amount_cents, description, date, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$transactions = [
    ['income', 'Salário', 'Conta Corrente', 520000, 'Salário mensal', "{$month}-05"],
    ['income', 'Freelance', 'Conta Corrente', 80000, 'Projeto freelance', "{$month}-12"],
    ['expense', 'Moradia', 'Conta Corrente', 180000, 'Aluguel', "{$month}-10"],
    ['expense', 'Mercado', 'Conta Corrente', 62350, 'Compras do mês', "{$month}-08"],
    ['expense', 'Mercado', 'Carteira', 8990, 'Feira', "{$month}-15"],
    ['expense', 'Transporte', 'Carteira', 15000, 'Combustível', "{$month}-14"],
    ['expense', 'Lazer', 'Conta Corrente', 12000, 'Cinema e jantar', "{$month}-16"],
];
foreach ($transactions as [$type, $category, $account, $cents, $description, $date]) {
    $insertTx->execute([
        $userId,
        $accountIds[$account],
        $categoryIds[$category],
        $type,
        $cents,
        $description,
        $date,
        $now,
        $now,
    ]);
}

$insertBudget = $pdo->prepare(
    'INSERT INTO budgets (user_id, category_id, month, limit_cents) VALUES (?, ?, ?, ?)'
);
foreach ([['Mercado', 80000], ['Lazer', 30000], ['Transporte', 25000]] as [$category, $limit]) {
    $insertBudget->execute([$userId, $categoryIds[$category], $month, $limit]);
}

$pdo->commit();

echo 'Seed complete: test user ' . TEST_EMAIL . ' / password ' . TEST_PASSWORD . "\n";
