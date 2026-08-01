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

<section class="card list-card">
    <h2>Últimos lançamentos</h2>
    <?php if ($transactions === []) : ?>
        <p class="muted">Nenhum lançamento ainda.</p>
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
    <?php endif; ?>
</section>
