document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const sidebar =
            document.getElementById(
                'appSidebar'
            );

        const toggleButton =
            document.getElementById(
                'sidebarMobileToggle'
            );

        const closeButton =
            document.getElementById(
                'sidebarMobileClose'
            );

        const overlay =
            document.getElementById(
                'sidebarMobileOverlay'
            );

                    const collapseButton =
            document.getElementById(
                'sidebarCollapseToggle'
            );

        if (
            !sidebar ||
            !toggleButton ||
            !closeButton ||
            !overlay ||
            !collapseButton
        ) {
            return;
        }

        const mobileQuery =
            window.matchMedia(
                '(max-width: 850px)'
            );


                    const tabletQuery =
            window.matchMedia(
                '(min-width: 851px) and (max-width: 1100px)'
            );

        const sidebarPreferenceKey =
            'olshco-sidebar-collapsed';

        let overlayTimer =
            null;

                    function setCollapsed(
            collapsed,
            rememberPreference = false
        ) {
            sidebar.classList.toggle(
                'is-collapsed',
                collapsed
            );

            collapseButton.setAttribute(
                'aria-expanded',
                collapsed
                    ? 'false'
                    : 'true'
            );

            collapseButton.setAttribute(
                'aria-label',
                collapsed
                    ? 'Expand navigation sidebar'
                    : 'Collapse navigation sidebar'
            );

            collapseButton.title =
                collapsed
                    ? 'Expand sidebar'
                    : 'Collapse sidebar';

            const icon =
                collapseButton.querySelector(
                    'i'
                );

            if (icon) {
                icon.className =
                    collapsed
                        ? 'fa-solid fa-angles-right'
                        : 'fa-solid fa-angles-left';
            }

            if (rememberPreference) {
                try {
                    window.localStorage.setItem(
                        sidebarPreferenceKey,
                        collapsed
                            ? '1'
                            : '0'
                    );
                } catch (storageError) {
                    /*
                     * Sidebar operation must continue
                     * when browser storage is unavailable.
                     */
                }
            }
        }

        const main = document.querySelector('.app-main');
        const mobileHeader = document.querySelector('.app-mobile-header');
        const mobileNavigation = document.querySelector('.app-mobile-nav');
        let openingFrame = null;

        function updateDrawerAccess(open) {
            sidebar.inert = mobileQuery.matches && !open;
            if (main) {
                main.inert = mobileQuery.matches && open;
            }
            toggleButton.inert = mobileQuery.matches && open;
            if (mobileHeader) mobileHeader.inert = mobileQuery.matches && open;
            if (mobileNavigation) mobileNavigation.inert = mobileQuery.matches && open;
            if (mobileQuery.matches && open) {
                sidebar.setAttribute('role', 'dialog');
                sidebar.setAttribute('aria-modal', 'true');
            } else {
                sidebar.removeAttribute('role');
                sidebar.removeAttribute('aria-modal');
            }
        }

        function cancelOpening() {
            if (openingFrame !== null) {
                window.cancelAnimationFrame(openingFrame);
                openingFrame = null;
            }
        }

        function openSidebar() {
            if (!mobileQuery.matches) {
                return;
            }

            if (overlayTimer !== null) {
                window.clearTimeout(
                    overlayTimer
                );

                overlayTimer =
                    null;
            }

            overlay.hidden =
                false;

            cancelOpening();
            openingFrame = window.requestAnimationFrame(
                () => {
                    openingFrame = null;
                    updateDrawerAccess(true);
                    sidebar.classList.add(
                        'is-open'
                    );

                    overlay.classList.add(
                        'is-visible'
                    );

                    document.body.classList.add(
                        'sidebar-mobile-open'
                    );

                    toggleButton.setAttribute(
                        'aria-expanded',
                        'true'
                    );

                    closeButton.focus();
                }
            );
        }

        function closeSidebar(
            restoreFocus = true
        ) {
            cancelOpening();
            updateDrawerAccess(false);
            sidebar.classList.remove(
                'is-open'
            );

            overlay.classList.remove(
                'is-visible'
            );

            document.body.classList.remove(
                'sidebar-mobile-open'
            );

            toggleButton.setAttribute(
                'aria-expanded',
                'false'
            );

            if (overlayTimer !== null) {
                window.clearTimeout(
                    overlayTimer
                );
            }

            overlayTimer =
                window.setTimeout(
                    () => {
                        if (
                            !overlay.classList
                                .contains(
                                    'is-visible'
                                )
                        ) {
                            overlay.hidden =
                                true;
                        }

                        overlayTimer =
                            null;
                    },
                    220
                );

            if (
                restoreFocus &&
                mobileQuery.matches
            ) {
                toggleButton.focus();
            }
        }

        function synchronizeLayout() {
            cancelOpening();
            updateDrawerAccess(false);
            if (mobileQuery.matches && sidebar.contains(document.activeElement)) {
                toggleButton.focus();
            }
            if (mobileQuery.matches) {
                setCollapsed(
                    false
                );

                closeSidebar(
                    false
                );

                return;
            }

            sidebar.classList.remove(
                'is-open'
            );

            overlay.classList.remove(
                'is-visible'
            );

            overlay.hidden =
                true;

            document.body.classList.remove(
                'sidebar-mobile-open'
            );

            toggleButton.setAttribute(
                'aria-expanded',
                'false'
            );

            if (tabletQuery.matches) {
                setCollapsed(
                    true
                );

                return;
            }

            let savedPreference =
                '0';

            try {
                savedPreference =
                    window.localStorage.getItem(
                        sidebarPreferenceKey
                    ) ||
                    '0';
            } catch (storageError) {
                savedPreference =
                    '0';
            }

            setCollapsed(
                savedPreference ===
                    '1'
            );
        }

                collapseButton.addEventListener(
            'click',
            () => {
                if (
                    mobileQuery.matches ||
                    tabletQuery.matches
                ) {
                    return;
                }

                setCollapsed(
                    !sidebar.classList.contains(
                        'is-collapsed'
                    ),
                    true
                );
            }
        );

        toggleButton.addEventListener(
            'click',
            openSidebar
        );

        closeButton.addEventListener(
            'click',
            () => {
                closeSidebar();
            }
        );

        overlay.addEventListener(
            'click',
            () => {
                closeSidebar();
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {
                if (event.key === 'Tab' && mobileQuery.matches && sidebar.classList.contains('is-open')) {
                    const controls = Array.from(sidebar.querySelectorAll(
                        'a[href], button:not([disabled]), [tabindex="0"]'
                    )).filter((element) => element.getClientRects().length > 0);
                    const first = controls[0];
                    const last = controls[controls.length - 1];
                    if (first && event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (last && !event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
                if (
                    event.key ===
                        'Escape' &&
                    sidebar.classList
                        .contains(
                            'is-open'
                        )
                ) {
                    closeSidebar();
                }
            }
        );

        sidebar
            .querySelectorAll(
                '.sidebar-link'
            )
            .forEach(
                (link) => {
                    link.addEventListener(
                        'click',
                        () => {
                            if (
                                mobileQuery.matches
                            ) {
                                closeSidebar(
                                    false
                                );
                            }
                        }
                    );
                }
            );

        if (
            typeof mobileQuery
                .addEventListener ===
            'function'
        ) {
            mobileQuery.addEventListener(
                'change',
                synchronizeLayout
            );

            tabletQuery.addEventListener(
                'change',
                synchronizeLayout
            );
        } else {
            mobileQuery.addListener(
                synchronizeLayout
            );

            tabletQuery.addListener(
                synchronizeLayout
            );
        }

        synchronizeLayout();
    }
);
