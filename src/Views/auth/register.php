<section class="card auth-card">
    <h1>Criar conta</h1>
    <form method="post" action="/register" novalidate>
        <?= csrf_field($csrfToken) ?>
        <div class="field">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>"
                   required autocomplete="name" autofocus>
            <?php if (isset($errors['name'])) : ?>
                <p class="field-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>"
                   required autocomplete="email">
            <?php if (isset($errors['email'])) : ?>
                <p class="field-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <p class="hint">Mínimo 8 caracteres, com maiúscula, minúscula, número e caractere especial.</p>
            <?php if (isset($errors['password'])) : ?>
                <p class="field-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirmar senha</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   required autocomplete="new-password">
            <?php if (isset($errors['password_confirmation'])) : ?>
                <p class="field-error"><?= e($errors['password_confirmation']) ?></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Criar conta</button>
    </form>
    <p class="muted">Já tem conta? <a href="/login">Entrar</a></p>
</section>
