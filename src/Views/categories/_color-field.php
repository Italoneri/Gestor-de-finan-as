<?php

use App\Models\Category;

/**
 * Colour picker for a category. The native input is the source of truth, so the
 * field still works with scripting off; the swatches are shortcuts that write
 * into it. Expects $color — the currently selected hex.
 */

?>
<fieldset class="field color-field" data-color-field>
    <legend>Cor</legend>
    <div class="swatches">
        <?php foreach (Category::PALETTE as $swatch) : ?>
            <button type="button"
                    class="swatch"
                    style="--swatch: <?= e($swatch) ?>"
                    data-color="<?= e($swatch) ?>"
                    aria-pressed="<?= $swatch === $color ? 'true' : 'false' ?>"
                    aria-label="Cor <?= e($swatch) ?>"></button>
        <?php endforeach; ?>
    </div>
    <label class="color-custom">
        <input type="color" name="color" value="<?= e($color) ?>">
        <span>Outra cor</span>
    </label>
    <?php if (isset($errors['color'])) : ?>
        <p class="field-error"><?= e($errors['color']) ?></p>
    <?php endif; ?>
</fieldset>
<script src="<?= e(asset('/assets/js/color-field.js')) ?>" defer></script>
