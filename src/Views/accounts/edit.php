<?php

use App\Models\AccountType;

?>
<section class="card auth-card">
    <h1>Editar conta</h1>
    <form method="post" action="/accounts/<?= e((string) $account->id) ?>" novalidate>
        <?= csrf_field($csrfToken) ?>
        <div class="field">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" value="<?= e($account->name) ?>" required autofocus>
            <?php if (isset($errors['name'])) : ?>
                <p class="field-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="type">Tipo</label>
            <select id="type" name="type" required>
                <?php foreach (AccountType::cases() as $case) : ?>
                    <option value="<?= e($case->value) ?>"
                        <?= $case === $account->type ? 'selected' : '' ?>><?= e($case->label()) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['type'])) : ?>
                <p class="field-error"><?= e($errors['type']) ?></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
    <p class="muted"><a href="/accounts">Voltar</a></p>
</section>
