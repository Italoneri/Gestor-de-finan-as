<?php

/**
 * Expects $ceilings (list<CeilingProgress>) and $planLink (href to /planning).
 * Shared by the dashboard and the monthly report.
 */

?>
<section class="card">
    <h2>Tetos do mês</h2>
    <?php if ($ceilings === []) : ?>
        <div class="empty-state">
            <?= icon('target') ?>
            <p>Nenhum teto definido para este mês.</p>
            <p><a href="<?= e($planLink) ?>#tetos">Criar tetos</a></p>
        </div>
    <?php else : ?>
        <?php foreach ($ceilings as $item) : ?>
            <div class="plan-row">
                <div class="plan-meta">
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
