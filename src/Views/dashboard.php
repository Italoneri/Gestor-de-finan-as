<?php

use App\Models\CategoryType;
use App\Models\Money;

$amountClass = static fn (int $cents): string => match (true) {
    $cents > 0 => 'amount-income',
    $cents < 0 => 'amount-expense',
    default => '',
};

?>
<section class="page-head">
    <h1>Painel — <?= e(br_month($month)) ?></h1>
</section>

<section class="stats-grid">
    <div class="card stat-card">
        <span class="stat-head">
            <span class="stat-icon"><?= icon('wallet') ?></span>
            <span class="stat-label">Saldo atual</span>
        </span>
        <strong class="stat-value <?= e($amountClass($balanceCents)) ?>">
            R$ <?= e(Money::fromCents($balanceCents)->formatBr()) ?>
        </strong>
        <?php if ($balanceCents === 0) : ?>
            <span class="stat-empty">Sem movimentações registradas até agora</span>
        <?php endif; ?>
    </div>
    <div class="card stat-card">
        <span class="stat-head">
            <span class="stat-icon"><?= icon('trending-up') ?></span>
            <span class="stat-label">Receitas do mês</span>
        </span>
        <strong class="stat-value <?= $incomeCents > 0 ? 'amount-income' : '' ?>">
            R$ <?= e(Money::fromCents($incomeCents)->formatBr()) ?>
        </strong>
        <?php if ($incomeCents === 0) : ?>
            <span class="stat-empty">Nenhuma receita registrada este mês</span>
        <?php endif; ?>
    </div>
    <div class="card stat-card">
        <span class="stat-head">
            <span class="stat-icon"><?= icon('trending-down') ?></span>
            <span class="stat-label">Despesas do mês</span>
        </span>
        <strong class="stat-value <?= $expenseCents > 0 ? 'amount-expense' : '' ?>">
            R$ <?= e(Money::fromCents($expenseCents)->formatBr()) ?>
        </strong>
        <?php if ($expenseCents === 0) : ?>
            <span class="stat-empty">Nenhuma despesa registrada este mês</span>
        <?php endif; ?>
    </div>
</section>

<div class="grid-2col">
    <section class="card">
        <h2>Despesas por categoria</h2>
        <?php if ($expensesByCategory === []) : ?>
            <div class="empty-state">
                <?= icon('inbox') ?>
                <p>Nenhuma despesa registrada este mês.</p>
                <p><a href="/transactions">Adicionar lançamento</a></p>
            </div>
        <?php else : ?>
            <div class="chart-wrap">
                <canvas id="expensesChart" role="img" aria-label="Despesas por categoria no mês"></canvas>
            </div>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2>Metas do mês</h2>
        <?php if ($budgets === []) : ?>
            <div class="empty-state">
                <?= icon('target') ?>
                <p>Nenhuma meta definida para este mês.</p>
                <p><a href="/budgets">Criar metas</a></p>
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
</div>

<section class="card list-card">
    <h2>Últimos lançamentos</h2>
    <?php if ($recent === []) : ?>
        <div class="empty-state">
            <?= icon('receipt') ?>
            <p>Nenhum lançamento ainda.</p>
            <p><a href="/transactions">Adicionar o primeiro</a></p>
        </div>
    <?php else : ?>
        <div class="table-wrap">
            <table class="table">
                <tbody>
                <?php foreach ($recent as $tx) : ?>
                    <tr>
                        <td><?= e(br_date($tx->date)) ?></td>
                        <td><?= e($tx->description) ?></td>
                        <td><?= e($tx->categoryName) ?></td>
                        <td class="num <?= $tx->type === CategoryType::Income ? 'amount-income' : 'amount-expense' ?>">
                            <?= $tx->type === CategoryType::Income ? '+' : '−' ?>
                            R$ <?= e($tx->amount()->formatBr()) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted"><a href="/transactions">Ver todas as transações</a></p>
    <?php endif; ?>
</section>

<?php if ($expensesByCategory !== []) : ?>
    <?php
    $chartData = [
        'labels' => array_map(fn ($t) => $t->name, $expensesByCategory),
        'values' => array_map(fn ($t) => round($t->totalCents / 100, 2), $expensesByCategory),
    ];
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"
            integrity="sha384-b0GXujLkk9eYYSmcSfoyZbfyElGAQnDyY0skCHSG6w3JgTMFnz11ggrTAr7seu9f"
            crossorigin="anonymous"></script>
    <script>
        const chartData = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const palette = [
            '#0f766e', '#0891b2', '#6366f1', '#a855f7',
            '#ec4899', '#f59e0b', '#84cc16', '#14b8a6'
        ];
        const brl = (value) => value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const total = chartData.values.reduce((sum, value) => sum + value, 0);

        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

        new Chart(document.getElementById('expensesChart'), {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.values,
                    backgroundColor: chartData.values.map((_, i) => palette[i % palette.length]),
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                cutout: '62%',
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#64748b',
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (c) => {
                                const share = total > 0 ? Math.round((c.parsed / total) * 100) : 0;
                                return ` ${brl(c.parsed)} (${share}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
<?php endif; ?>
