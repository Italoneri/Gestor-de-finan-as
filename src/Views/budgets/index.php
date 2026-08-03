<?php

use App\Models\Money;

?>
<section class="page-head report-head">
    <h1>Metas — <?= e(br_month($month)) ?></h1>
    <form method="get" action="/budgets" class="month-picker">
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

<section class="card form-card">
    <h2>Definir meta de gasto</h2>
    <?php if ($expenseCategories === []) : ?>
        <p class="alert alert-error">
            Cadastre ao menos uma <a href="/categories">categoria de despesa</a> antes de criar metas.
        </p>
    <?php else : ?>
        <form method="post" action="/budgets" class="form-row" novalidate>
            <?= csrf_field($csrfToken) ?>
            <input type="hidden" name="month" value="<?= e($month) ?>">
            <div class="field grow">
                <label for="category_id">Categoria de despesa</label>
                <select id="category_id" name="category_id" required>
                    <?php foreach ($expenseCategories as $category) : ?>
                        <option value="<?= e((string) $category->id) ?>"><?= e($category->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])) : ?>
                    <p class="field-error"><?= e($errors['category_id']) ?></p>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="amount">Limite mensal (R$)</label>
                <input type="text" id="amount" name="amount" inputmode="decimal" placeholder="0,00" required>
                <?php if (isset($errors['amount'])) : ?>
                    <p class="field-error"><?= e($errors['amount']) ?></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Salvar meta</button>
        </form>
        <p class="hint">Salvar uma meta já existente para a mesma categoria e mês substitui o limite.</p>
    <?php endif; ?>
</section>

<section class="card list-card">
    <h2>Metas definidas</h2>
    <?php if ($budgets === []) : ?>
        <div class="empty-state">
            <?= icon('target') ?>
            <p>Nenhuma meta definida para <?= e(mb_strtolower(br_month($month))) ?>.</p>
            <p>Use o formulário acima para definir um limite de gasto.</p>
        </div>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr><th>Categoria</th><th class="num">Limite</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($budgets as $budget) : ?>
                <tr>
                    <td><?= e($budget->categoryName) ?></td>
                    <td class="num">R$ <?= e(Money::fromCents($budget->limitCents)->formatBr()) ?></td>
                    <td class="row-actions">
                        <form method="post" action="/budgets/<?= e((string) $budget->id) ?>/delete">
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
    <p class="muted">O progresso das metas aparece no <a href="/">painel</a> e no <a href="/reports">relatório</a>.</p>
</section>
