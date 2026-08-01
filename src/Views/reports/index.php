<?php

use App\Models\Money;

?>
<section class="page-head report-head">
    <h1>Relatório — <?= e(br_month($month)) ?></h1>
    <form method="get" action="/reports" class="month-picker">
        <input type="month" name="month" value="<?= e($month) ?>">
        <button type="submit" class="btn btn-primary">Ver</button>
    </form>
</section>

<section class="stats-grid">
    <div class="card stat-card">
        <span class="stat-label">Receitas</span>
        <strong class="stat-value amount-income">R$ <?= e(Money::fromCents($incomeCents)->formatBr()) ?></strong>
    </div>
    <div class="card stat-card">
        <span class="stat-label">Despesas</span>
        <strong class="stat-value amount-expense">R$ <?= e(Money::fromCents($expenseCents)->formatBr()) ?></strong>
    </div>
    <div class="card stat-card">
        <span class="stat-label">Resultado do mês</span>
        <?php $result = $incomeCents - $expenseCents; ?>
        <strong class="stat-value <?= $result >= 0 ? 'amount-income' : 'amount-expense' ?>">
            R$ <?= e(Money::fromCents($result)->formatBr()) ?>
        </strong>
    </div>
</section>

<div class="grid-2col">
    <section class="card">
        <h2>Receitas por categoria</h2>
        <?php if ($incomeByCategory === []) : ?>
            <p class="muted">Sem receitas neste mês.</p>
        <?php else : ?>
            <table class="table">
                <tbody>
                <?php foreach ($incomeByCategory as $total) : ?>
                    <tr>
                        <td><?= e($total->name) ?></td>
                        <td class="num amount-income">R$ <?= e($total->total()->formatBr()) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2>Despesas por categoria</h2>
        <?php if ($expensesByCategory === []) : ?>
            <p class="muted">Sem despesas neste mês.</p>
        <?php else : ?>
            <table class="table">
                <tbody>
                <?php foreach ($expensesByCategory as $total) : ?>
                    <tr>
                        <td><?= e($total->name) ?></td>
                        <td class="num amount-expense">R$ <?= e($total->total()->formatBr()) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<section class="card">
    <h2>Metas do mês</h2>
    <?php if ($budgets === []) : ?>
        <p class="muted">Nenhuma meta para este mês. <a href="/budgets?month=<?= e($month) ?>">Criar metas</a></p>
    <?php else : ?>
        <?php foreach ($budgets as $item) : ?>
            <div class="budget-row">
                <div class="budget-meta">
                    <span><?= e($item->categoryName) ?></span>
                    <span class="<?= $item->overLimit() ? 'amount-expense' : 'muted' ?>">
                        R$ <?= e($item->spent()->formatBr()) ?> / R$ <?= e($item->limit()->formatBr()) ?>
                        (<?= e((string) $item->percent()) ?>%)
                    </span>
                </div>
                <div class="progress">
                    <div class="progress-bar<?= $item->overLimit() ? ' over' : '' ?>"
                         style="width: <?= e((string) min($item->percent(), 100)) ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
