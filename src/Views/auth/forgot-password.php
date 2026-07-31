<section class="card auth-card">
    <h1>Recuperar senha</h1>
    <?php if (!empty($status)) : ?>
        <p class="alert alert-success"><?= e($status) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <p class="alert alert-error"><?= e($error) ?></p>
    <?php endif; ?>
    <p class="muted">Informe seu e-mail e enviaremos um link para redefinir a senha.</p>
    <form method="post" action="/forgot-password" novalidate>
        <?= csrf_field($csrfToken) ?>
        <div class="field">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required autocomplete="email" autofocus>
            <?php if (isset($errors['email'])) : ?>
                <p class="field-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Enviar link</button>
    </form>
    <p class="muted"><a href="/login">Voltar ao login</a></p>
</section>
