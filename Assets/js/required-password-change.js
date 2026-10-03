document.addEventListener(
    'DOMContentLoaded',
    () => {
        const form =
            document.getElementById(
                'requiredPasswordForm'
            );

        const newPassword =
            document.getElementById(
                'requiredNewPassword'
            );

        const confirmation =
            document.getElementById(
                'requiredPasswordConfirmation'
            );

        const matchMessage =
            document.getElementById(
                'requiredPasswordMatch'
            );

        const submitButton =
            document.getElementById(
                'requiredPasswordSubmit'
            );

        const rules = {
            length:
                (value) =>
                    value.length >= 8,

            uppercase:
                (value) =>
                    /[A-Z]/.test(value),

            lowercase:
                (value) =>
                    /[a-z]/.test(value),

            number:
                (value) =>
                    /[0-9]/.test(value),

            symbol:
                (value) =>
                    /[^A-Za-z0-9]/.test(value)
        };

        const updateRules = () => {
            const value =
                newPassword?.value ||
                '';

            Object.entries(
                rules
            ).forEach(
                ([name, validator]) => {
                    const element =
                        document.querySelector(
                            `[data-rule="${name}"]`
                        );

                    const passed =
                        validator(value);

                    element?.classList.toggle(
                        'valid',
                        passed
                    );

                    const icon =
                        element?.querySelector(
                            'i'
                        );

                    if (icon) {
                        icon.className =
                            passed
                                ? 'fa-solid fa-circle-check'
                                : 'fa-solid fa-circle';
                    }
                }
            );
        };

        const updateMatch = () => {
            if (
                !newPassword ||
                !confirmation
            ) {
                return;
            }

            const hasConfirmation =
                confirmation.value !== '';

            const matches =
                newPassword.value ===
                confirmation.value;

            confirmation.setCustomValidity(
                !hasConfirmation ||
                matches
                    ? ''
                    : 'Passwords do not match.'
            );

            matchMessage?.classList.toggle(
                'valid',
                hasConfirmation &&
                matches
            );

            if (matchMessage) {
                matchMessage.textContent =
                    hasConfirmation &&
                    matches
                        ? 'Passwords match.'
                        : 'Passwords must match.';
            }
        };

        newPassword?.addEventListener(
            'input',
            () => {
                updateRules();
                updateMatch();
            }
        );

        confirmation?.addEventListener(
            'input',
            updateMatch
        );

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

                        const hidden =
                            input.type ===
                            'password';

                        input.type =
                            hidden
                                ? 'text'
                                : 'password';

                        const icon =
                            button.querySelector(
                                'i'
                            );

                        if (icon) {
                            icon.className =
                                hidden
                                    ? 'fa-regular fa-eye-slash'
                                    : 'fa-regular fa-eye';
                        }

                        button.setAttribute(
                            'aria-label',
                            hidden
                                ? 'Hide password'
                                : 'Show password'
                        );
                    }
                );
            });

        form?.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                updateRules();
                updateMatch();

                if (!form.checkValidity()) {
                    form.reportValidity();

                    form.querySelector(
                        ':invalid'
                    )?.focus();

                    return;
                }

                let confirmed = true;

                if (
                    typeof Swal !==
                    'undefined'
                ) {
                    const result =
                        await Swal.fire({
                            icon:
                                'question',

                            title:
                                'Secure Your Account?',

                            text:
                                'Your temporary password will be replaced permanently.',

                            showCancelButton:
                                true,

                            confirmButtonText:
                                'Change Password',

                            cancelButtonText:
                                'Review Password',

                            confirmButtonColor:
                                '#7f1d1d',

                            reverseButtons:
                                true
                        });

                    confirmed =
                        result.isConfirmed;
                }

                if (!confirmed) {
                    return;
                }

                if (submitButton) {
                    submitButton.disabled =
                        true;

                    submitButton.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i><span>Securing Account...</span>';
                }

                form.submit();
            }
        );

        updateRules();
        updateMatch();

        document.querySelector(
            '.password-change-alert'
        )?.scrollIntoView({
            behavior:
                'smooth',

            block:
                'center'
        });
    }
);