document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const modal =
            document.getElementById(
                'accountDecisionModal'
            );

        const modalDialog =
            modal?.querySelector(
                '.account-decision-dialog'
            );

        const modalIcon =
            document.getElementById(
                'accountDecisionIcon'
            );

        const modalTitle =
            document.getElementById(
                'accountDecisionTitle'
            );

        const modalMessage =
            document.getElementById(
                'accountDecisionMessage'
            );

        const modalReason =
            document.getElementById(
                'accountDecisionReason'
            );

        const modalReasonText =
            modalReason?.querySelector(
                'p'
            );

        const confirmButton =
            document.getElementById(
                'accountDecisionConfirm'
            );

        let activeForm = null;
        let previousFocus = null;

        function closeModal() {
            if (!modal) {
                return;
            }

            modal.hidden = true;

            document.body.classList.remove(
                'account-decision-open'
            );

            activeForm = null;

            previousFocus?.focus();
        }

        function openModal(
            form,
            decision,
            rejectionReason = ''
        ) {
            if (
                !modal ||
                !confirmButton
            ) {
                return;
            }

            activeForm = form;
            previousFocus =
                document.activeElement;

            const applicantName =
                form.dataset
                    .applicantName ||
                'this applicant';

            const isApproval =
                decision === 'approve';

            modal.classList.toggle(
                'is-rejection',
                !isApproval
            );

            if (modalIcon) {
                modalIcon.innerHTML =
                    isApproval
                        ? '<i class="fa-solid fa-user-check"></i>'
                        : '<i class="fa-solid fa-user-xmark"></i>';
            }

            if (modalTitle) {
                modalTitle.textContent =
                    isApproval
                        ? 'Approve and activate this account?'
                        : 'Reject this registration?';
            }

            if (modalMessage) {
                modalMessage.textContent =
                    isApproval
                        ? `${applicantName} will receive access to the OLSHCO Digital Hub.`
                        : `${applicantName} will be unable to sign in after rejection.`;
            }

            if (
                modalReason &&
                modalReasonText
            ) {
                modalReason.hidden =
                    isApproval;

                modalReasonText.textContent =
                    rejectionReason;
            }

            confirmButton.className =
                isApproval
                    ? 'app-button primary'
                    : 'app-button account-rejection-button';

            confirmButton.innerHTML =
                isApproval
                    ? '<i class="fa-solid fa-user-check"></i> Confirm Approval'
                    : '<i class="fa-solid fa-user-xmark"></i> Confirm Rejection';

            modal.hidden = false;

            document.body.classList.add(
                'account-decision-open'
            );

            window.requestAnimationFrame(
                () => {
                    confirmButton.focus();
                }
            );
        }

        document
            .querySelectorAll(
                '[data-open-review-confirmation]'
            )
            .forEach((button) => {
                button.addEventListener(
                    'click',
                    () => {
                        const form =
                            button.closest(
                                '[data-review-form]'
                            );

                        if (!form) {
                            return;
                        }

                        const decision =
                            form.dataset
                                .reviewForm;

                        if (
                            decision ===
                            'reject'
                        ) {
                            const textarea =
                                form.querySelector(
                                    '[name="review_notes"]'
                                );

                            const error =
                                form.querySelector(
                                    '[data-rejection-error]'
                                );

                            const reason =
                                textarea?.value
                                    .trim() ||
                                '';

                            if (reason === '') {
                                if (error) {
                                    error.hidden =
                                        false;
                                }

                                textarea?.classList.add(
                                    'has-error'
                                );

                                textarea?.focus();

                                textarea?.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });

                                return;
                            }

                            if (error) {
                                error.hidden =
                                    true;
                            }

                            textarea?.classList.remove(
                                'has-error'
                            );

                            openModal(
                                form,
                                decision,
                                reason
                            );

                            return;
                        }

                        if (!form.reportValidity()) return;
                        openModal(
                            form,
                            decision
                        );
                    }
                );
            });

        document
            .querySelector(
                '#accountRejectionNotes'
            )
            ?.addEventListener(
                'input',
                (event) => {
                    event.target.classList
                        .remove(
                            'has-error'
                        );

                    const error =
                        document.querySelector(
                            '[data-rejection-error]'
                        );

                    if (error) {
                        error.hidden = true;
                    }
                }
            );

        document
            .querySelectorAll(
                '[data-close-review-confirmation]'
            )
            .forEach((button) => {
                button.addEventListener(
                    'click',
                    closeModal
                );
            });

        confirmButton?.addEventListener(
            'click',
            () => {
                if (!activeForm) {
                    return;
                }

                const confirmationInput =
                    activeForm.querySelector(
                        '[data-confirmation-value]'
                    );

                if (confirmationInput) {
                    confirmationInput.value =
                        '1';
                }

                confirmButton.disabled = true;
                confirmButton.setAttribute(
                    'aria-busy',
                    'true'
                );

                confirmButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

                activeForm.submit();
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key === 'Escape' &&
                    modal &&
                    !modal.hidden
                ) {
                    closeModal();
                }

                if (
                    event.key === 'Tab' &&
                    modal &&
                    !modal.hidden &&
                    modalDialog
                ) {
                    const controls =
                        Array.from(
                            modalDialog
                                .querySelectorAll(
                                    'button:not([disabled])'
                                )
                        );

                    if (controls.length === 0) {
                        return;
                    }

                    const first =
                        controls[0];

                    const last =
                        controls[
                            controls.length - 1
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
            }
        );

        /*
         * Existing success and error messages
         * briefly behave like toast notifications.
         */
        document
            .querySelectorAll(
                '.account-approval-alert'
            )
            .forEach((alert) => {
                alert.setAttribute(
                    'role',
                    'status'
                );

                window.setTimeout(
                    () => {
                        alert.classList.add(
                            'is-hiding'
                        );

                        window.setTimeout(
                            () => {
                                alert.remove();
                            },
                            250
                        );
                    },
                    5000
                );
            });
    }
);
// Child verification shares the existing Admin review page.
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.child-review-form');
    if (!form) return;
    const action = form.elements.namedItem('child_action');
    const sync = () => {
        ['child_name','child_student_id','child_section_id'].forEach((name) => {
            const field = form.elements.namedItem(name);
            field.disabled = action.value !== 'update';
            field.required = action.value === 'update' && name !== 'child_student_id';
        });
        const link = form.elements.namedItem('link_student_id');
        link.disabled = action.value !== 'link';
        link.required = action.value === 'link';
    };
    action.addEventListener('change', sync);
    sync();
    form.addEventListener('submit', (event) => {
        const label = action.options[action.selectedIndex].textContent.trim();
        if (!window.confirm(label + '? This changes the Parent verification and may change content access.')) {
            event.preventDefault(); return;
        }
        form.querySelector('button[type="submit"]').disabled = true;
    });
});
