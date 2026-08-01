<?php

use App\Models\CategoryType;

/**
 * Shared form. Expects: $formAction, $submitLabel, $values (date, description,
 * amount, category_id, account_id), $categories, $accounts, $errors, $csrfToken.
 */
$incomeCategories = array_filter($categories, fn ($c) => $c->type === CategoryType::Income);
$expenseCategories = array_filter($categories, fn ($c) => $c->type === CategoryType::Expense);
?>
<?php if ($categories === [] || $accounts === []) : ?>
    <p class="alert alert-error">
        Antes de lançar, cadastre ao menos uma <a href="/categories">categoria</a>
        e uma <a href="/accounts">conta</a>.
    </p>
<?php endif; ?>
<form method="post" action="<?= e($formAction) ?>" novalidate>
    <?= csrf_field($csrfToken) ?>
    <div class="form-row">
        <div class="field">
            <label for="date">Data</label>
            <input type="date" id="date" name="date" value="<?= e($values['date']) ?>" required>
            <?php if (isset($errors['date'])) : ?>
                <p class="field-error"><?= e($errors['date']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field grow">
            <label for="description">Descrição</label>
            <input type="text" id="description" name="description"
                   value="<?= e($values['description']) ?>" required>
            <?php if (isset($errors['description'])) : ?>
                <p class="field-error"><?= e($errors['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="amount">Valor (R$)</label>
            <input type="text" id="amount" name="amount" inputmode="decimal" placeholder="0,00"
                   value="<?= e($values['amount']) ?>" required>
            <?php if (isset($errors['amount'])) : ?>
                <p class="field-error"><?= e($errors['amount']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="form-row">
        <div class="field grow">
            <label for="category_id">Categoria</label>
            <select id="category_id" name="category_id" required>
                <option value="">Selecione…</option>
                <?php if ($incomeCategories !== []) : ?>
                    <optgroup label="Receitas">
                        <?php foreach ($incomeCategories as $category) : ?>
                            <option value="<?= e((string) $category->id) ?>"
                                <?= (string) $category->id === $values['category_id'] ? 'selected' : '' ?>>
                                <?= e($category->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if ($expenseCategories !== []) : ?>
                    <optgroup label="Despesas">
                        <?php foreach ($expenseCategories as $category) : ?>
                            <option value="<?= e((string) $category->id) ?>"
                                <?= (string) $category->id === $values['category_id'] ? 'selected' : '' ?>>
                                <?= e($category->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
            <p class="hint">O tipo (receita/despesa) segue a categoria escolhida.</p>
            <?php if (isset($errors['category_id'])) : ?>
                <p class="field-error"><?= e($errors['category_id']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field grow">
            <label for="account_id">Conta</label>
            <select id="account_id" name="account_id" required>
                <option value="">Selecione…</option>
                <?php foreach ($accounts as $account) : ?>
                    <option value="<?= e((string) $account->id) ?>"
                        <?= (string) $account->id === $values['account_id'] ? 'selected' : '' ?>>
                        <?= e($account->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['account_id'])) : ?>
                <p class="field-error"><?= e($errors['account_id']) ?></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
    </div>
</form>
