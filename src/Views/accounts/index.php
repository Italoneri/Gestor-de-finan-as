<?php

use App\Models\AccountType;

?>
<section class="page-head">
    <h1>Contas</h1>
</section>

<?php if (!empty($status)) : ?>
    <p class="alert alert-success"><?= e($status) ?></p>
<?php endif; ?>
<?php if (!empty($error)) : ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endif; ?>

<section class="card form-card">
    <h2>Nova conta</h2>
    <form method="post" action="/accounts" class="form-row" novalidate>
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
                <?php foreach (AccountType::cases() as $case) : ?>
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

<section class="card list-card">
    <h2>Suas contas</h2>
    <?php if ($accounts === []) : ?>
        <div class="empty-state">
            <?= icon('wallet') ?>
            <p>Nenhuma conta cadastrada ainda.</p>
            <p>Cadastre a primeira no formulário acima.</p>
        </div>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr><th>Nome</th><th>Tipo</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $account) : ?>
                <tr>
                    <td><?= e($account->name) ?></td>
                    <td><?= e($account->type->label()) ?></td>
                    <td class="row-actions">
                        <a href="/accounts/<?= e((string) $account->id) ?>/edit">Editar</a>
                        <form method="post" action="/accounts/<?= e((string) $account->id) ?>/delete">
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
