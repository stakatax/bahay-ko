document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const resetForm =
            document.querySelector(
                '[data-password-reset-form]'
            );

        const password =
            document.querySelector(
                '[data-password-input]'
            );

        const confirmation =
            document.querySelector(
                '[data-password-confirmation]'
            );

        const toggleButtons =
            Array.from(
                document.querySelectorAll(
                    '[data-password-toggle]'
                )
            );

        const requirementItems =
            new Map(
                Array.from(
                    document.querySelectorAll(
                        '[data-password-rule]'
                    )
                ).map(
                    (item) => [
                        item.dataset
                            .passwordRule,
                        item
                    ]
                )
            );

        /* ======================================
           PASSWORD VISIBILITY
        ====================================== */

        toggleButtons.forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        const control =
                            button.closest(
                                '.auth-control'
                            );

                        const input =
                            control?.querySelector(
                                'input'
                            );

                        if (!input) {
                            return;
                        }

                        const showing =
                            input.type ===
                            'text';

                        input.type =
                            showing
                                ? 'password'
                                : 'text';

                        button.setAttribute(
                            'aria-pressed',
                            String(
                                !showing
                            )
                        );

                        button.setAttribute(
                            'aria-label',
                            showing
                                ? 'Show password'
                                : 'Hide password'
                        );

                        const icon =
                            button.querySelector(
                                'i'
                            );

                        if (icon) {
                            icon.className =
                                showing
                                    ? 'fa-regular fa-eye'
                                    : 'fa-regular fa-eye-slash';
                        }

                        input.focus();
                    }
                );
            }
        );

        /* ======================================
           LIVE PASSWORD REQUIREMENTS
        ====================================== */

        function setRuleState(
            rule,
            valid
        ) {
            requirementItems
                .get(rule)
                ?.classList.toggle(
                    'is-valid',
                    valid
                );
        }

        function updateRequirements() {
            if (
                !password ||
                !confirmation
            ) {
                return;
            }

            const value =
                password.value;

            const confirmationValue =
                confirmation.value;

            setRuleState(
                'length',
                value.length >= 8 &&
                    value.length <= 72
            );

            setRuleState(
                'uppercase',
                /[A-Z]/.test(
                    value
                )
            );

            setRuleState(
                'lowercase',
                /[a-z]/.test(
                    value
                )
            );

            setRuleState(
                'number',
                /\d/.test(
                    value
                )
            );

            setRuleState(
                'match',
                confirmationValue !==
                    '' &&
                value ===
                    confirmationValue
            );

            password.setCustomValidity(
                ''
            );

            confirmation
                .setCustomValidity(
                    ''
                );
        }

        password?.addEventListener(
            'input',
            updateRequirements
        );

        confirmation?.addEventListener(
            'input',
            updateRequirements
        );

        updateRequirements();

        /* ======================================
           RESET VALIDATION
        ====================================== */

        resetForm?.addEventListener(
            'submit',
            (event) => {
                if (
                    !password ||
                    !confirmation
                ) {
                    return;
                }

                const value =
                    password.value;

                if (
                    value.length < 8 ||
                    value.length > 72 ||
                    !/[A-Z]/.test(
                        value
                    ) ||
                    !/[a-z]/.test(
                        value
                    ) ||
                    !/\d/.test(
                        value
                    )
                ) {
                    event.preventDefault();

                    password
                        .setCustomValidity(
                            'Use 8 to 72 characters with uppercase, lowercase, and a number.'
                        );

                    password
                        .reportValidity();

                    password.focus();

                    return;
                }

                if (
                    value !==
                    confirmation.value
                ) {
                    event.preventDefault();

                    confirmation
                        .setCustomValidity(
                            'Password confirmation does not match.'
                        );

                    confirmation
                        .reportValidity();

                    confirmation.focus();

                    return;
                }
            }
        );

        /* ======================================
           DOUBLE-SUBMIT PROTECTION
        ====================================== */

        document
            .querySelectorAll(
                '.password-recovery-form'
            )
            .forEach(
                (form) => {
                    form.addEventListener(
                        'submit',
                        () => {
                            if (
                                !form
                                    .checkValidity()
                            ) {
                                return;
                            }

                            const submitButton =
                                form.querySelector(
                                    '[type="submit"]'
                                );

                            if (!submitButton) {
                                return;
                            }

                            submitButton.disabled =
                                true;

                            submitButton.innerHTML = `
                                <i
                                    class="fa-solid fa-spinner fa-spin"
                                    aria-hidden="true"></i>

                                Processing...
                            `;
                        }
                    );
                }
            );
    }
);