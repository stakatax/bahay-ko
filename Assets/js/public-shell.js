document.addEventListener(
    'DOMContentLoaded',
    () => {
        const toggle =
            document.querySelector(
                '[data-public-menu-toggle]'
            );

        const navigation =
            document.querySelector(
                '[data-public-navigation]'
            );

        if (
            !toggle ||
            !navigation
        ) {
            return;
        }

        function setMenuState(
            isOpen
        ) {
            navigation.classList.toggle(
                'is-open',
                isOpen
            );

            toggle.setAttribute(
                'aria-expanded',
                String(isOpen)
            );

            toggle.setAttribute(
                'aria-label',
                isOpen
                    ? 'Close navigation menu'
                    : 'Open navigation menu'
            );

            const icon =
                toggle.querySelector('i');

            if (icon) {
                icon.className =
                    isOpen
                        ? 'fa-solid fa-xmark'
                        : 'fa-solid fa-bars';
            }
        }

        toggle.addEventListener(
            'click',
            () => {
                setMenuState(
                    !navigation.classList.contains(
                        'is-open'
                    )
                );
            }
        );

        document.addEventListener(
            'click',
            (event) => {
                if (
                    !navigation.classList.contains(
                        'is-open'
                    ) ||
                    navigation.contains(
                        event.target
                    ) ||
                    toggle.contains(
                        event.target
                    )
                ) {
                    return;
                }

                setMenuState(false);
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key === 'Escape'
                ) {
                    setMenuState(false);

                    toggle.focus();
                }
            }
        );

        window
            .matchMedia(
                '(min-width: 901px)'
            )
            .addEventListener(
                'change',
                (event) => {
                    if (event.matches) {
                        setMenuState(false);
                    }
                }
            );
    }
);