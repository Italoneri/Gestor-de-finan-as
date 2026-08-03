<?php

use App\Models\CategoryType;

?>
<section class="page-head">
    <h1>Transações</h1>
</section>

<?php if (!empty($status)) : ?>
    <p class="alert alert-success"><?= e($status) ?></p>
<?php endif; ?>
<?php if (!empty($error)) : ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endif; ?>

<section class="card form-card">
    <h2>Novo lançamento</h2>
    <?php
    $formAction = '/transactions';
    $submitLabel = 'Adicionar';
    $values = [
        'date' => $old['date'] ?? date('Y-m-d'),
        'description' => $old['description'] ?? '',
        'amount' => $old['amount'] ?? '',
        'category_id' => $old['category_id'] ?? '',
        'account_id' => $old['account_id'] ?? '',
    ];
    require __DIR__ . '/_form.php';
    ?>
</section>

<?php
$filterQuery = $filter->toQuery();
$exportUrl = '/transactions/export' . ($filterQuery !== [] ? '?' . http_build_query($filterQuery) : '');
$pageUrl = fn (int $n): string => '/transactions?' . http_build_query([...$filterQuery, 'page' => $n]);
?>
<section class="card form-card">
    <h2>Filtrar</h2>
    <form method="get" action="/transactions" class="form-row" novalidate>
        <div class="field">
            <label for="from">De</label>
            <input type="date" id="from" name="from" value="<?= e($filter->dateFrom ?? '') ?>">
        </div>
        <div class="field">
            <label for="to">Até</label>
            <input type="date" id="to" name="to" value="<?= e($filter->dateTo ?? '') ?>">
        </div>
        <div class="field">
            <label for="filter-category">Categoria</label>
            <select id="filter-category" name="category_id">
                <option value="">Todas</option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?= e((string) $category->id) ?>"
                        <?= $category->id === $filter->categoryId ? 'selected' : '' ?>>
                        <?= e($category->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="filter-type">Tipo</label>
            <select id="filter-type" name="type">
                <option value="">Todos</option>
                <option value="income" <?= $filter->type === CategoryType::Income ? 'selected' : '' ?>>
                    Receitas
                </option>
                <option value="expense" <?= $filter->type === CategoryType::Expense ? 'selected' : '' ?>>
                    Despesas
                </option>
            </select>
        </div>
        <div class="field grow">
            <label for="q">Busca</label>
            <input type="text" id="q" name="q" placeholder="Descrição…"
                   value="<?= e($filter->search ?? '') ?>">
        </div>
        <div class="field">
            <label for="sort">Ordenar por</label>
            <select id="sort" name="sort">
                <option value="date" <?= $filter->sort === 'date' ? 'selected' : '' ?>>Data</option>
                <option value="amount" <?= $filter->sort === 'amount' ? 'selected' : '' ?>>Valor</option>
            </select>
        </div>
        <div class="field">
            <label for="dir">Direção</label>
            <select id="dir" name="dir">
                <option value="desc" <?= $filter->direction === 'desc' ? 'selected' : '' ?>>Decrescente</option>
                <option value="asc" <?= $filter->direction === 'asc' ? 'selected' : '' ?>>Crescente</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a class="btn-link filter-clear" href="/transactions">Limpar</a>
    </form>
</section>

<section class="card list-card">
    <div class="list-head">
        <h2>Lançamentos (<?= e((string) $total) ?>)</h2>
        <a href="<?= e($exportUrl) ?>">Exportar CSV</a>
    </div>
    <?php if ($transactions === []) : ?>
        <div class="empty-state">
            <?= icon('receipt') ?>
            <p>Nenhum lançamento encontrado.</p>
            <p>Ajuste os filtros ou registre um novo lançamento acima.</p>
        </div>
    <?php else : ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th><th>Descrição</th><th>Categoria</th>
                        <th>Conta</th><th class="num">Valor</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $tx) : ?>
                    <tr>
                        <td><?= e(br_date($tx->date)) ?></td>
                        <td><?= e($tx->description) ?></td>
                        <td><?= e($tx->categoryName) ?></td>
                        <td><?= e($tx->accountName) ?></td>
                        <td class="num <?= $tx->type === CategoryType::Income ? 'amount-income' : 'amount-expense' ?>">
                            <?= $tx->type === CategoryType::Income ? '+' : '−' ?>
                            R$ <?= e($tx->amount()->formatBr()) ?>
                        </td>
                        <td class="row-actions">
                            <a href="/transactions/<?= e((string) $tx->id) ?>/edit">Editar</a>
                            <form method="post" action="/transactions/<?= e((string) $tx->id) ?>/delete">
                                <?= csrf_field($csrfToken) ?>
                                <button type="submit" class="btn-link danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1) : ?>
            <nav class="pagination" aria-label="Paginação">
                <?php if ($page > 1) : ?>
                    <a href="<?= e($pageUrl($page - 1)) ?>">« Anterior</a>
                <?php endif; ?>
                <span class="muted">Página <?= e((string) $page) ?> de <?= e((string) $totalPages) ?></span>
                <?php if ($page < $totalPages) : ?>
                    <a href="<?= e($pageUrl($page + 1)) ?>">Próxima »</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
