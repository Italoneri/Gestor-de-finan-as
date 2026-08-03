/**
 * Theme toggle. The server already stamped data-theme from the cookie, so this
 * script only handles the switch — there is no first-paint work and no flash.
 */
(() => {
    'use strict';

    const COOKIE_NAME = 'theme';
    const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

    const root = document.documentElement;
    const toggle = document.querySelector('[data-theme-toggle]');

    if (toggle === null) {
        return;
    }

    const labels = JSON.parse(toggle.dataset.themeLabels);
    const opposite = (theme) => (theme === 'dark' ? 'light' : 'dark');

    const persist = (theme) => {
        const secure = location.protocol === 'https:' ? '; secure' : '';
        document.cookie =
            `${COOKIE_NAME}=${theme}; path=/; max-age=${COOKIE_MAX_AGE}; samesite=lax${secure}`;
    };

    // browser chrome follows the page background token instead of a second copy of the hex
    const syncBrowserColor = () => {
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta !== null) {
            meta.content = getComputedStyle(root).getPropertyValue('--bg').trim();
        }
    };

    toggle.addEventListener('click', () => {
        const theme = opposite(root.dataset.theme);

        root.dataset.theme = theme;
        toggle.setAttribute('aria-label', labels[opposite(theme)]);
        toggle.setAttribute('title', labels[opposite(theme)]);
        syncBrowserColor();
        persist(theme);

        // canvas and other painted widgets cannot read CSS tokens on their own
        document.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    });
})();
