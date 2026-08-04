<?php

use App\Models\Money;

?>
<section class="page-head report-head">
    <h1>Planejamento — <?= e(br_month($month)) ?></h1>
    <form method="get" action="/planning" class="month-picker">
        <input type="month" name="month" value="<?= e($month) ?>">
        <button type="submit" class="btn btn-primary">Ver</button>
    </form>
</section>

<?php if (!empty($status)) : ?>
    <p class="alert alert-success"><?= e($status) ?></p>
<?php endif; ?>
<?php if (!empty($error)) : ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endif; ?>

<section id="tetos" class="card form-card">
    <h2>Definir teto de gasto</h2>
    <?php if ($expenseCategories === []) : ?>
        <p class="alert alert-error">
            Cadastre ao menos uma <a href="/categories">categoria de despesa</a> antes de criar tetos.
        </p>
    <?php else : ?>
        <form method="post" action="/ceilings" class="form-row" novalidate>
            <?= csrf_field($csrfToken) ?>
            <input type="hidden" name="month" value="<?= e($month) ?>">
            <div class="field grow">
                <label for="ceiling_category_id">Categoria de despesa</label>
                <select id="ceiling_category_id" name="category_id" required>
                    <?php foreach ($expenseCategories as $category) : ?>
                        <option value="<?= e((string) $category->id) ?>"><?= e($category->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($ceilingErrors['category_id'])) : ?>
                    <p class="field-error"><?= e($ceilingErrors['category_id']) ?></p>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="ceiling_amount">Limite mensal (R$)</label>
                <input type="text" id="ceiling_amount" name="amount" inputmode="decimal" placeholder="0,00" required>
                <?php if (isset($ceilingErrors['amount'])) : ?>
                    <p class="field-error"><?= e($ceilingErrors['amount']) ?></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Salvar teto</button>
        </form>
        <p class="hint">Salvar um teto já existente para a mesma categoria e mês substitui o limite.</p>
    <?php endif; ?>
</section>

<section class="card list-card">
    <h2>Tetos definidos</h2>
    <?php if ($ceilings === []) : ?>
        <div class="empty-state">
            <?= icon('target') ?>
            <p>Nenhum teto definido para <?= e(mb_strtolower(br_month($month))) ?>.</p>
            <p>Use o formulário acima para definir um limite de gasto.</p>
        </div>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr><th>Categoria</th><th class="num">Limite</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($ceilings as $ceiling) : ?>
                <tr>
                    <td><?= e($ceiling->categoryName) ?></td>
                    <td class="num">R$ <?= e(Money::fromCents($ceiling->limitCents)->formatBr()) ?></td>
                    <td class="row-actions">
                        <form method="post" action="/ceilings/<?= e((string) $ceiling->id) ?>/delete">
                            <?= csrf_field($csrfToken) ?>
                            <input type="hidden" name="month" value="<?= e($month) ?>">
                            <button type="submit" class="btn-link danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section id="metas" class="card form-card">
    <h2>Definir meta de receita</h2>
    <?php if ($incomeCategories === []) : ?>
        <p class="alert alert-error">
            Cadastre ao menos uma <a href="/categories">categoria de receita</a> antes de criar metas.
        </p>
    <?php else : ?>
        <form method="post" action="/goals" class="form-row" novalidate>
            <?= csrf_field($csrfToken) ?>
            <input type="hidden" name="month" value="<?= e($month) ?>">
            <div class="field grow">
                <label for="goal_category_id">Categoria de receita</label>
                <select id="goal_category_id" name="category_id" required>
                    <?php foreach ($incomeCategories as $category) : ?>
                        <option value="<?= e((string) $category->id) ?>"><?= e($category->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($goalErrors['category_id'])) : ?>
                    <p class="field-error"><?= e($goalErrors['category_id']) ?></p>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="goal_amount">Alvo mensal (R$)</label>
                <input type="text" id="goal_amount" name="amount" inputmode="decimal" placeholder="0,00" required>
                <?php if (isset($goalErrors['amount'])) : ?>
                    <p class="field-error"><?= e($goalErrors['amount']) ?></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Salvar meta</button>
        </form>
        <p class="hint">Salvar uma meta já existente para a mesma categoria e mês substitui o alvo.</p>
    <?php endif; ?>
</section>

<section class="card list-card">
    <h2>Metas definidas</h2>
    <?php if ($goals === []) : ?>
        <div class="empty-state">
            <?= icon('target') ?>
            <p>Nenhuma meta definida para <?= e(mb_strtolower(br_month($month))) ?>.</p>
            <p>Use o formulário acima para definir um alvo de receita.</p>
        </div>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr><th>Categoria</th><th class="num">Alvo</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($goals as $goal) : ?>
                <tr>
                    <td><?= e($goal->categoryName) ?></td>
                    <td class="num">R$ <?= e(Money::fromCents($goal->targetCents)->formatBr()) ?></td>
                    <td class="row-actions">
                        <form method="post" action="/goals/<?= e((string) $goal->id) ?>/delete">
                            <?= csrf_field($csrfToken) ?>
                            <input type="hidden" name="month" value="<?= e($month) ?>">
                            <button type="submit" class="btn-link danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <p class="muted">O progresso aparece no <a href="/">painel</a> e no <a href="/reports">relatório</a>.</p>
</section>
