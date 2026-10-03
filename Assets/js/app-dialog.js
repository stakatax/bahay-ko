(() => {
    'use strict';

    let activeDialog = null;

    const iconClasses = {
        success:
            'fa-solid fa-circle-check',

        error:
            'fa-solid fa-circle-xmark',

        warning:
            'fa-solid fa-triangle-exclamation',

        danger:
            'fa-solid fa-triangle-exclamation',

        question:
            'fa-solid fa-circle-question',

        info:
            'fa-solid fa-circle-info'
    };

    function createElement(
        tag,
        className = '',
        text = ''
    ) {
        const element =
            document.createElement(
                tag
            );

        if (className !== '') {
            element.className =
                className;
        }

        if (text !== '') {
            element.textContent =
                text;
        }

        return element;
    }

    function closeActiveDialog(
        result = false
    ) {
        if (!activeDialog) {
            return;
        }

        const {
            overlay,
            resolve,
            previousFocus
        } = activeDialog;

        activeDialog = null;

        overlay.classList.remove(
            'visible'
        );

        document.body.classList.remove(
            'app-dialog-open'
        );

        window.setTimeout(
            () => {
                overlay.remove();

                if (
                    previousFocus &&
                    typeof previousFocus.focus ===
                    'function'
                ) {
                    previousFocus.focus();
                }

                resolve(result);
            },
            160
        );
    }

    function show(options = {}) {
        if (activeDialog) {
            closeActiveDialog(
                false
            );
        }

        const type =
            [
                'success',
                'error',
                'warning',
                'danger',
                'question',
                'info'
            ].includes(options.type)
                ? options.type
                : 'info';

        const title =
            String(
                options.title ||
                'Confirmation'
            );

        const message =
            String(
                options.message ||
                ''
            );

        const confirmText =
            String(
                options.confirmText ||
                'Continue'
            );

        const cancelText =
            String(
                options.cancelText ||
                'Cancel'
            );

        const showCancel =
            options.showCancel !==
            false;

        const dismissible =
            options.dismissible !==
            false;

        const previousFocus =
            document.activeElement;

        return new Promise(
            (resolve) => {
                const overlay =
                    createElement(
                        'div',
                        `app-dialog-overlay ${type}`
                    );

                overlay.setAttribute(
                    'role',
                    'presentation'
                );

                const dialog =
                    createElement(
                        'section',
                        'app-dialog'
                    );

                dialog.setAttribute(
                    'role',
                    'dialog'
                );

                dialog.setAttribute(
                    'aria-modal',
                    'true'
                );

                dialog.setAttribute(
                    'aria-labelledby',
                    'appDialogTitle'
                );

                const header =
                    createElement(
                        'header',
                        'app-dialog-header'
                    );

                const icon =
                    createElement(
                        'span',
                        'app-dialog-icon'
                    );

                const iconElement =
                    createElement(
                        'i',
                        iconClasses[type]
                    );

                icon.appendChild(
                    iconElement
                );

                const copy =
                    createElement(
                        'div',
                        'app-dialog-copy'
                    );

                const heading =
                    createElement(
                        'h2',
                        '',
                        title
                    );

                heading.id =
                    'appDialogTitle';

                const paragraph =
                    createElement(
                        'p',
                        '',
                        message
                    );

                copy.append(
                    heading,
                    paragraph
                );

                header.append(
                    icon,
                    copy
                );

                dialog.appendChild(
                    header
                );

                if (
                    Array.isArray(
                        options.fields
                    ) &&
                    options.fields.length > 0
                ) {
                    const details =
                        createElement(
                            'dl',
                            'app-dialog-details'
                        );

                    options.fields.forEach(
                        (field) => {
                            const row =
                                createElement(
                                    'div'
                                );

                            const term =
                                createElement(
                                    'dt',
                                    '',
                                    String(
                                        field.label ||
                                        ''
                                    )
                                );

                            const value =
                                createElement(
                                    'dd',
                                    field.secret
                                        ? 'secret'
                                        : '',
                                    String(
                                        field.value ||
                                        ''
                                    )
                                );

                            row.append(
                                term,
                                value
                            );

                            details.appendChild(
                                row
                            );
                        }
                    );

                    dialog.appendChild(
                        details
                    );
                }

                const footer =
                    createElement(
                        'footer',
                        'app-dialog-actions'
                    );

                let cancelButton =
                    null;

                if (showCancel) {
                    cancelButton =
                        createElement(
                            'button',
                            'app-dialog-button cancel',
                            cancelText
                        );

                    cancelButton.type =
                        'button';

                    footer.appendChild(
                        cancelButton
                    );
                }

                const confirmButton =
                    createElement(
                        'button',
                        'app-dialog-button confirm',
                        confirmText
                    );

                confirmButton.type =
                    'button';

                footer.appendChild(
                    confirmButton
                );

                dialog.appendChild(
                    footer
                );

                overlay.appendChild(
                    dialog
                );

                document.body.appendChild(
                    overlay
                );

                activeDialog = {
                    overlay,
                    resolve,
                    previousFocus
                };

                document.body.classList.add(
                    'app-dialog-open'
                );

                window.requestAnimationFrame(
                    () => {
                        overlay.classList.add(
                            'visible'
                        );

                        (
                            cancelButton ||
                            confirmButton
                        ).focus();
                    }
                );

                confirmButton.addEventListener(
                    'click',
                    () => {
                        closeActiveDialog(
                            true
                        );
                    }
                );

                cancelButton?.addEventListener(
                    'click',
                    () => {
                        closeActiveDialog(
                            false
                        );
                    }
                );

                overlay.addEventListener(
                    'mousedown',
                    (event) => {
                        if (
                            dismissible &&
                            event.target ===
                            overlay
                        ) {
                            closeActiveDialog(
                                false
                            );
                        }
                    }
                );

                dialog.addEventListener(
                    'keydown',
                    (event) => {
                        if (
                            event.key !==
                            'Tab'
                        ) {
                            return;
                        }

                        const focusable =
                            Array.from(
                                dialog.querySelectorAll(
                                    'button:not([disabled])'
                                )
                            );

                        if (
                            focusable.length ===
                            0
                        ) {
                            return;
                        }

                        const first =
                            focusable[0];

                        const last =
                            focusable[
                                focusable.length - 1
                            ];

                        if (
                            event.shiftKey &&
                            document.activeElement ===
                            first
                        ) {
                            event.preventDefault();
                            last.focus();
                        } else if (
                            !event.shiftKey &&
                            document.activeElement ===
                            last
                        ) {
                            event.preventDefault();
                            first.focus();
                        }
                    }
                );

                document.addEventListener(
                    'keydown',
                    function escapeHandler(
                        event
                    ) {
                        if (
                            !activeDialog ||
                            activeDialog.overlay !==
                            overlay
                        ) {
                            document.removeEventListener(
                                'keydown',
                                escapeHandler
                            );

                            return;
                        }

                        if (
                            event.key ===
                            'Escape' &&
                            dismissible
                        ) {
                            document.removeEventListener(
                                'keydown',
                                escapeHandler
                            );

                            closeActiveDialog(
                                false
                            );
                        }
                    }
                );
            }
        );
    }

    window.AppDialog = {
        confirm(options = {}) {
            return show({
                ...options,
                showCancel:
                    true
            });
        },

        alert(options = {}) {
            return show({
                ...options,
                showCancel:
                    false
            });
        },

        credentials(options = {}) {
            return show({
                ...options,
                type:
                    'success',

                showCancel:
                    true,

                dismissible:
                    false,

                confirmText:
                    options.confirmText ||
                    'Copy Credentials',

                cancelText:
                    options.cancelText ||
                    'I Saved Them'
            });
        },

        close() {
            closeActiveDialog(
                false
            );
        }
    };
})();