<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Controle Financeiro') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="/">Finanças</a>
            <?php if (!empty($userName)) : ?>
                <nav class="topbar-nav" aria-label="Principal">
                    <a href="/">Painel</a>
                    <a href="/categories">Categorias</a>
                    <a href="/accounts">Contas</a>
                    <span class="muted"><?= e($userName) ?></span>
                    <form method="post" action="/logout">
                        <?= csrf_field($csrfToken) ?>
                        <button type="submit" class="btn-link">Sair</button>
                    </form>
                </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="container">
        <?= $content ?>
    </main>
</body>
</html>
