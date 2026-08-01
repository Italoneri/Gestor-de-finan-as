<?php

use App\Models\CategoryType;

$byType = ['income' => [], 'expense' => []];
foreach ($categories as $category) {
    $byType[$category->type->value][] = $category;
}
?>
<section class="page-head">
    <h1>Categorias</h1>
</section>

<?php if (!empty($status)) : ?>
    <p class="alert alert-success"><?= e($status) ?></p>
<?php endif; ?>
<?php if (!empty($error)) : ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endif; ?>

<section class="card form-card">
    <h2>Nova categoria</h2>
    <form method="post" action="/categories" class="form-row" novalidate>
        <?= csrf_field($csrfToken) ?>
        <div class="field grow">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required>
            <?php if (isset($errors['name'])) : ?>
                <p class="field-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="type">Tipo</label>
            <select id="type" name="type" required>
                <?php foreach (CategoryType::cases() as $case) : ?>
                    <option value="<?= e($case->value) ?>"><?= e($case->label()) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['type'])) : ?>
                <p class="field-error"><?= e($errors['type']) ?></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Adicionar</button>
    </form>
</section>

<?php foreach ([CategoryType::Income, CategoryType::Expense] as $type) : ?>
    <section class="card list-card">
        <h2><?= e($type === CategoryType::Income ? 'Receitas' : 'Despesas') ?></h2>
        <?php if ($byType[$type->value] === []) : ?>
            <div class="empty-state">
                <?= icon($type === CategoryType::Income ? 'trending-up' : 'trending-down') ?>
                <p>Nenhuma categoria de <?= e(mb_strtolower($type->label())) ?> ainda.</p>
            </div>
        <?php else : ?>
            <table class="table">
                <tbody>
                <?php foreach ($byType[$type->value] as $category) : ?>
                    <tr>
                        <td><?= e($category->name) ?></td>
                        <td class="row-actions">
                            <a href="/categories/<?= e((string) $category->id) ?>/edit">Editar</a>
                            <form method="post" action="/categories/<?= e((string) $category->id) ?>/delete">
                                <?= csrf_field($csrfToken) ?>
                                <button type="submit" class="btn-link danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
