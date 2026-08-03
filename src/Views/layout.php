<?php

use App\Core\Theme;

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

$isAuthenticated = !empty($userName);

// the toggle always names the theme it switches to, so the copy lives here
$themeLabels = [
    Theme::Dark->value => Theme::Dark->switchLabel(),
    Theme::Light->value => Theme::Light->switchLabel(),
];

?>
<!doctype html>
<html lang="pt-BR" data-theme="<?= e($theme->value) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= e($theme->browserColor()) ?>">
    <title><?= e(isset($title) ? $title . ' · Fluxo' : 'Fluxo') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
    <script src="<?= e(asset('/assets/js/theme.js')) ?>" defer></script>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="/"><?= icon('waves') ?>Fluxo</a>
            <?php if ($isAuthenticated) : ?>
                <nav class="topbar-nav" aria-label="Principal">
                    <?php foreach ($navItems as $href => $label) : ?>
                        <a href="<?= e($href) ?>"
                           class="<?= $isCurrent($href) ? 'active' : '' ?>"
                            <?= $isCurrent($href) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <div class="topbar-actions">
                <button type="button"
                        class="theme-toggle"
                        data-theme-toggle
                        data-theme-labels="<?= e(json_encode($themeLabels)) ?>"
                        title="<?= e($theme->opposite()->switchLabel()) ?>"
                        aria-label="<?= e($theme->opposite()->switchLabel()) ?>">
                    <?= icon('sun', 'icon theme-icon-sun') ?>
                    <?= icon('moon', 'icon theme-icon-moon') ?>
                </button>
                <?php if ($isAuthenticated) : ?>
                    <div class="topbar-user">
                        <span class="topbar-username"><?= e($userName) ?></span>
                        <form method="post" action="/logout">
                            <?= csrf_field($csrfToken) ?>
                            <button type="submit" class="btn-ghost">Sair</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">
        <?= $content ?>
    </main>
</body>
</html>
