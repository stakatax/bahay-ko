document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const storageKey =
            'dashboardScrollPosition';

        const savedPosition =
            sessionStorage.getItem(
                storageKey
            );

        if (savedPosition === null) {
            return;
        }

        sessionStorage.removeItem(
            storageKey
        );

        const scrollPosition =
            Number(savedPosition);

        if (
            !Number.isFinite(
                scrollPosition
            ) ||
            scrollPosition < 0
        ) {
            return;
        }

        window.requestAnimationFrame(
            () => {
                window.scrollTo({
                    top: scrollPosition,
                    left: 0,
                    behavior: 'auto'
                });
            }
        );
    }
);