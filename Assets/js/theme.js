(() => {
    'use strict';
    const key = 'olshco-theme';
    const root = document.documentElement;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let transition = null;
    let animationTimer = null;
    const valid = value => value === 'dark' || value === 'light';
    let preference = null;
    try { const saved = localStorage.getItem(key); preference = valid(saved) ? saved : null; } catch (_) {}

    function apply() {
        const dark = preference === 'dark';
        root.dataset.theme = dark ? 'dark' : 'light';
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.setAttribute('aria-pressed', String(dark));
            button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
            button.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
            const label = button.querySelector('[data-theme-label]');
            if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
            const icon = button.querySelector('i');
            if (icon) icon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        });
    }
    apply(); // Head script sets the theme before page styles paint.
    document.addEventListener('DOMContentLoaded', apply);
    function animateChange() {
        if (transition) transition.skipTransition();
        if (animationTimer !== null) window.clearTimeout(animationTimer);
        if (reducedMotion.matches) {
            delete root.dataset.themeAnimating;
            delete root.dataset.themeTransition;
            apply();
            return;
        }
        root.dataset.themeAnimating = 'true';
        animationTimer = window.setTimeout(() => {
            delete root.dataset.themeAnimating;
            delete root.dataset.themeTransition;
            animationTimer = null;
        }, 600);
        if (typeof document.startViewTransition === 'function') {
            try {
                const current = document.startViewTransition(apply);
                transition = current;
                current.finished.catch(() => {}).then(() => {
                    if (transition === current) transition = null;
                });
                return;
            } catch (_) { /* Older/unsupported browser: use color transitions. */ }
        }
        // Resolve the transition styles before changing color variables.
        root.dataset.themeTransition = 'colors';
        void root.offsetWidth;
        apply();
    }
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-theme-toggle]')) return;
        preference = (preference || root.dataset.theme) === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(key, preference); } catch (_) {}
        animateChange();
    });
    window.addEventListener('storage', event => {
        if (event.key !== key && event.key !== null) return;
        preference = valid(event.newValue) ? event.newValue : null;
        apply();
    });
})();
