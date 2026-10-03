document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const form =
            document.getElementById(
                'loginForm'
            );

        const identifierInput =
            document.getElementById(
                'loginIdentifier'
            );

        const passwordInput =
            document.getElementById(
                'loginPassword'
            );

        const submitButton =
            document.getElementById(
                'loginSubmitButton'
            );

        const errorCard =
            document.getElementById(
                'loginErrorCard'
            );

        const capsLockWarning =
            document.getElementById(
                'loginCapsLockWarning'
            );

        /* ======================================
           PASSWORD VISIBILITY
        ======================================= */

        document
            .querySelectorAll(
                '[data-password-toggle]'
            )
            .forEach((button) => {
                button.addEventListener(
                    'click',
                    () => {
                        const input =
                            document.getElementById(
                                button.dataset
                                    .passwordToggle
                            );

                        if (!input) {
                            return;
                        }

                        const isPassword =
                            input.type ===
                            'password';

                        input.type =
                            isPassword
                                ? 'text'
                                : 'password';

                        const icon =
                            button.querySelector(
                                'i'
                            );

                        if (icon) {
                            icon.className =
                                isPassword
                                    ? 'fa-regular fa-eye-slash'
                                    : 'fa-regular fa-eye';
                        }

                        button.setAttribute(
                            'aria-label',
                            isPassword
                                ? 'Hide password'
                                : 'Show password'
                        );

                        input.focus();
                    }
                );
            });

        /* ======================================
           SERVER ERROR FOCUS
        ======================================= */

        if (errorCard) {
            const errorField =
                errorCard.dataset
                    .errorField ||
                '';

            const target =
                errorField === 'password'
                    ? passwordInput
                    : (
                        errorField ===
                        'identifier'
                            ? identifierInput
                            : errorCard
                    );

            window.requestAnimationFrame(
                () => {
                    errorCard.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    window.setTimeout(
                        () => {
                            target?.focus({
                                preventScroll: true
                            });
                        },
                        350
                    );
                }
            );
        }

        /* ======================================
           DISMISS ERROR
        ======================================= */

        document
            .querySelector(
                '[data-dismiss-login-error]'
            )
            ?.addEventListener(
                'click',
                () => {
                    errorCard?.remove();

                    if (
                        identifierInput &&
                        identifierInput.value
                            .trim() === ''
                    ) {
                        identifierInput.focus();
                    } else {
                        passwordInput?.focus();
                    }
                }
            );

        /* ======================================
           CLEAR FIELD ERROR WHILE EDITING
        ======================================= */

        [
            identifierInput,
            passwordInput
        ].forEach((input) => {
            input?.addEventListener(
                'input',
                () => {
                    input
                        .closest(
                            '.auth-field'
                        )
                        ?.classList
                        .remove(
                            'has-error'
                        );

                    input.removeAttribute(
                        'aria-invalid'
                    );
                }
            );
        });

        /* ======================================
           CAPS LOCK WARNING
        ======================================= */

        function updateCapsLockWarning(
            event
        ) {
            if (!capsLockWarning) {
                return;
            }

            capsLockWarning.hidden =
                !event.getModifierState(
                    'CapsLock'
                );
        }

        passwordInput?.addEventListener(
            'keydown',
            updateCapsLockWarning
        );

        passwordInput?.addEventListener(
            'keyup',
            updateCapsLockWarning
        );

        passwordInput?.addEventListener(
            'blur',
            () => {
                if (capsLockWarning) {
                    capsLockWarning.hidden =
                        true;
                }
            }
        );

        /* ======================================
           SUBMISSION STATE
        ======================================= */

        form?.addEventListener(
            'submit',
            () => {
                if (!form.checkValidity()) {
                    return;
                }

                if (submitButton) {
                    submitButton.disabled =
                        true;

                    submitButton.setAttribute(
                        'aria-busy',
                        'true'
                    );
                }

                const label =
                    submitButton
                    ?.querySelector(
                        'span'
                    );

                const icon =
                    submitButton
                    ?.querySelector(
                        'i'
                    );

                if (label) {
                    label.textContent =
                        'Signing In...';
                }

                if (icon) {
                    icon.className =
                        'fa-solid fa-spinner fa-spin';
                }
            }
        );
    }
);