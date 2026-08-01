<section class="card form-card">
    <h1>Editar lançamento</h1>
    <?php
    $formAction = '/transactions/' . $transaction->id;
    $submitLabel = 'Salvar';
    $values = [
        'date' => $old['date'] ?? $transaction->date,
        'description' => $old['description'] ?? $transaction->description,
        'amount' => $old['amount'] ?? $transaction->amount()->formatBr(),
        'category_id' => $old['category_id'] ?? (string) $transaction->categoryId,
        'account_id' => $old['account_id'] ?? (string) $transaction->accountId,
    ];
    require __DIR__ . '/_form.php';
    ?>
    <p class="muted"><a href="/transactions">Voltar</a></p>
</section>
