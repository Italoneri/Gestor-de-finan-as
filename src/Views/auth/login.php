<section class="card auth-card">
    <h1>Entrar</h1>
    <?php if (!empty($status)) : ?>
        <p class="alert alert-success"><?= e($status) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <p class="alert alert-error"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if (isset($errors['credentials'])) : ?>
        <p class="alert alert-error"><?= e($errors['credentials']) ?></p>
    <?php endif; ?>
    <form method="post" action="/login" novalidate>
        <?= csrf_field($csrfToken) ?>
        <div class="field">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>"
                   required autocomplete="email" autofocus>
        </div>
        <div class="field">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="field checkbox-row">
            <label class="checkbox-label">
                <input type="checkbox" name="remember" value="1"> Lembrar de mim
            </label>
            <a href="/forgot-password">Esqueci minha senha</a>
        </div>
        <button type="submit" class="btn btn-primary">Entrar</button>
    </form>
    <p class="muted">Não tem conta? <a href="/register">Criar conta</a></p>
</section>
