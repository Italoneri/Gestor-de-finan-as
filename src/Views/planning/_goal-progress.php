<?php

/**
 * Expects $goals (list<IncomeGoalProgress>) and $planLink (href to /planning).
 * Mirror of _ceiling-progress.php: a goal reached is good news, so it reads in
 * the income colour instead of the over-limit red.
 */

?>
<section class="card">
    <h2>Metas do mês</h2>
    <?php if ($goals === []) : ?>
        <div class="empty-state">
            <?= icon('target') ?>
            <p>Nenhuma meta definida para este mês.</p>
            <p><a href="<?= e($planLink) ?>#metas">Criar metas</a></p>
        </div>
    <?php else : ?>
        <?php foreach ($goals as $item) : ?>
            <div class="plan-row">
                <div class="plan-meta">
                    <span><?= e($item->categoryName) ?></span>
                    <span class="<?= $item->reached() ? 'amount-income' : 'muted' ?>">
                        R$ <?= e($item->received()->formatBr()) ?> / R$ <?= e($item->target()->formatBr()) ?>
                        (<?= e((string) $item->percent()) ?>%)
                        <?php if ($item->reached()) : ?>
                            · Meta atingida
                        <?php else : ?>
                            · Faltam R$ <?= e($item->remaining()->formatBr()) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="progress">
                    <div class="progress-bar<?= $item->reached() ? ' reached' : '' ?>"
                         style="width: <?= e((string) min($item->percent(), 100)) ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
