<?php

$navItems = [
    '/' => 'Painel',
    '/transactions' => 'Transações',
    '/categories' => 'Categorias',
    '/accounts' => 'Contas',
    '/budgets' => 'Metas',
    '/reports' => 'Relatório',
];
$isCurrent = fn (string $href): bool => $href === '/'
    ? ($currentPath ?? '/') === '/'
    : str_starts_with($currentPath ?? '/', $href);

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(isset($title) ? $title . ' · Fluxo' : 'Fluxo') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="/"><?= icon('waves') ?>Fluxo</a>
            <?php if (!empty($userName)) : ?>
                <nav class="topbar-nav" aria-label="Principal">
                    <?php foreach ($navItems as $href => $label) : ?>
                        <a href="<?= e($href) ?>"
                           class="<?= $isCurrent($href) ? 'active' : '' ?>"
                            <?= $isCurrent($href) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                    <?php endforeach; ?>
                </nav>
                <div class="topbar-user">
                    <span class="topbar-username"><?= e($userName) ?></span>
                    <form method="post" action="/logout">
                        <?= csrf_field($csrfToken) ?>
                        <button type="submit" class="btn-ghost">Sair</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="container">
        <?= $content ?>
    </main>
</body>
</html>
