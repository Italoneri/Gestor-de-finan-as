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

<?php $result = $incomeCents - $expenseCents; ?>
<section class="stats-grid">
    <div class="card stat-card">
        <span class="stat-head">
            <span class="stat-icon"><?= icon('trending-up') ?></span>
            <span class="stat-label">Receitas</span>
        </span>
        <strong class="stat-value <?= $incomeCents > 0 ? 'amount-income' : '' ?>">
            R$ <?= e(Money::fromCents($incomeCents)->formatBr()) ?>
        </strong>
        <?php if ($incomeCents === 0) : ?>
            <span class="stat-empty">Nenhuma receita registrada neste mês</span>
        <?php endif; ?>
    </div>
    <div class="card stat-card">
        <span class="stat-head">
            <span class="stat-icon"><?= icon('trending-down') ?></span>
            <span class="stat-label">Despesas</span>
        </span>
        <strong class="stat-value <?= $expenseCents > 0 ? 'amount-expense' : '' ?>">
            R$ <?= e(Money::fromCents($expenseCents)->formatBr()) ?>
        </strong>
        <?php if ($expenseCents === 0) : ?>
            <span class="stat-empty">Nenhuma despesa registrada neste mês</span>
        <?php endif; ?>
    </div>
    <div class="card stat-card">
        <span class="stat-head">
            <span class="stat-icon"><?= icon('wallet') ?></span>
            <span class="stat-label">Resultado do mês</span>
        </span>
        <?php $resultClass = match (true) {
            $result > 0 => 'amount-income',
            $result < 0 => 'amount-expense',
            default => '',
        }; ?>
        <strong class="stat-value <?= e($resultClass) ?>">
            R$ <?= e(Money::fromCents($result)->formatBr()) ?>
        </strong>
        <?php if ($incomeCents === 0 && $expenseCents === 0) : ?>
            <span class="stat-empty">Sem movimentações neste mês</span>
        <?php endif; ?>
    </div>
</section>

<div class="grid-2col">
    <section class="card">
        <h2>Receitas por categoria</h2>
        <?php if ($incomeByCategory === []) : ?>
            <div class="empty-state">
                <?= icon('trending-up') ?>
                <p>Nenhuma receita registrada neste mês.</p>
            </div>
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
            <div class="empty-state">
                <?= icon('trending-down') ?>
                <p>Nenhuma despesa registrada neste mês.</p>
            </div>
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
        <div class="empty-state">
            <?= icon('target') ?>
            <p>Nenhuma meta definida para este mês.</p>
            <p><a href="/budgets?month=<?= e($month) ?>">Criar metas</a></p>
        </div>
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
