<section class="card auth-card">
    <h1>Redefinir senha</h1>
    <form method="post" action="/reset-password" novalidate>
        <?= csrf_field($csrfToken) ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <div class="field">
            <label for="password">Nova senha</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" autofocus>
            <p class="hint">Mínimo 8 caracteres, com maiúscula, minúscula, número e caractere especial.</p>
            <?php if (isset($errors['password'])) : ?>
                <p class="field-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirmar nova senha</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   required autocomplete="new-password">
            <?php if (isset($errors['password_confirmation'])) : ?>
                <p class="field-error"><?= e($errors['password_confirmation']) ?></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Redefinir senha</button>
    </form>
    <p class="muted"><a href="/login">Voltar ao login</a></p>
</section>
