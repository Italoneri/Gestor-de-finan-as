/**
 * Palette shortcuts for the category colour field. The native colour input holds
 * the value that gets submitted — these buttons only write into it, so the field
 * degrades to a plain picker when this script does not run.
 */
(() => {
    'use strict';

    document.querySelectorAll('[data-color-field]').forEach((field) => {
        const input = field.querySelector('input[type="color"]');
        const swatches = field.querySelectorAll('[data-color]');

        const markSelected = () => swatches.forEach((swatch) => {
            const selected = swatch.dataset.color.toLowerCase() === input.value.toLowerCase();
            swatch.setAttribute('aria-pressed', String(selected));
        });

        swatches.forEach((swatch) => swatch.addEventListener('click', () => {
            input.value = swatch.dataset.color;
            markSelected();
        }));

        input.addEventListener('input', markSelected);
        markSelected();
    });
})();
