<?php

use App\Models\CategoryType;
use App\Models\Money;

?>
<section class="page-head">
    <h1>Painel — <?= e(br_month($month)) ?></h1>
</section>

<section class="stats-grid">
    <div class="card stat-card">
        <span class="stat-label">Saldo atual</span>
        <strong class="stat-value <?= $balanceCents >= 0 ? 'amount-income' : 'amount-expense' ?>">
            R$ <?= e(Money::fromCents($balanceCents)->formatBr()) ?>
        </strong>
    </div>
    <div class="card stat-card">
        <span class="stat-label">Receitas do mês</span>
        <strong class="stat-value amount-income">R$ <?= e(Money::fromCents($incomeCents)->formatBr()) ?></strong>
    </div>
    <div class="card stat-card">
        <span class="stat-label">Despesas do mês</span>
        <strong class="stat-value amount-expense">R$ <?= e(Money::fromCents($expenseCents)->formatBr()) ?></strong>
    </div>
</section>

<div class="grid-2col">
    <section class="card">
        <h2>Despesas por categoria</h2>
        <?php if ($expensesByCategory === []) : ?>
            <p class="muted">Sem despesas neste mês.</p>
        <?php else : ?>
            <div class="chart-wrap">
                <canvas id="expensesChart" role="img" aria-label="Despesas por categoria no mês"></canvas>
            </div>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2>Metas do mês</h2>
        <?php if ($budgets === []) : ?>
            <p class="muted">Nenhuma meta definida. <a href="/budgets">Criar metas</a></p>
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
        <p class="muted">Nenhum lançamento ainda. <a href="/transactions">Adicionar</a></p>
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
        new Chart(document.getElementById('expensesChart'), {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.values,
                    backgroundColor: '#2563eb',
                    borderRadius: 4,
                    barThickness: 18
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (c) => c.parsed.x.toLocaleString('pt-BR', {
                                style: 'currency',
                                currency: 'BRL'
                            })
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0, 0, 0, 0.06)' },
                        ticks: { color: '#6b7280', callback: (v) => v.toLocaleString('pt-BR') }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#1f2933' }
                    }
                }
            }
        });
    </script>
<?php endif; ?>
