<section class="card auth-card">
    <h1>Editar categoria</h1>
    <p class="muted">Tipo: <?= e($category->type->label()) ?> (o tipo não pode ser alterado)</p>
    <form method="post" action="/categories/<?= e((string) $category->id) ?>" novalidate>
        <?= csrf_field($csrfToken) ?>
        <div class="field">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" value="<?= e($category->name) ?>" required autofocus>
            <?php if (isset($errors['name'])) : ?>
                <p class="field-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
    <p class="muted"><a href="/categories">Voltar</a></p>
</section>
