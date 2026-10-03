document.addEventListener(
    'DOMContentLoaded',
    () => {
        setupUserTabs();
       setupStatusForm();
setupUnlockForm();
setupRoleForm();
setupFacultyAssignments();
setupFacultyProvisioning();
setupLiveDirectorySearch();

document.addEventListener(
    'input',
    (event) => {
        const field =
            event.target.closest(
                'input, select, textarea'
            );

        const form =
            field?.closest(
                'form'
            );

        if (!field || !form) {
            return;
        }

        field.removeAttribute(
            'aria-invalid'
        );

        field.closest(
            'label'
        )?.classList.remove(
            'field-invalid'
        );

        if (
            form.querySelectorAll(
                '.field-invalid'
            ).length === 0
        ) {
            form.querySelector(
                '.manage-form-error-summary'
            )?.remove();
        }
    }
);


function setupLiveDirectorySearch() {
    const searchInput =
        document.getElementById(
            'manageUsersLiveSearch'
        );

    const directory =
        document.querySelector(
            '.manage-users-list'
        );

    const countHeading =
        document.querySelector(
            '[data-manage-user-count]'
        );

    const countValue =
        countHeading?.querySelector(
            'span'
        );

    const emptyState =
        document.querySelector(
            '[data-manage-user-live-empty]'
        );

    if (
        !searchInput ||
        !directory
    ) {
        return;
    }

    const rows =
        Array.from(
            directory.querySelectorAll(
                '.manage-user-row'
            )
        );

    const normalizeValue = (
        value
    ) =>
        value
            .toLocaleLowerCase()
            .trim()
            .replace(
                /\s+/g,
                ' '
            );

    const updateResults = () => {
        const searchTerm =
            normalizeValue(
                searchInput.value
            );

        let visibleCount = 0;

        rows.forEach((row) => {
            const searchableText =
                normalizeValue(
                    row.textContent || ''
                );

            const visible =
                searchTerm === '' ||
                searchableText.includes(
                    searchTerm
                );

            row.hidden =
                !visible;

            if (visible) {
                visibleCount++;
            }
        });

        if (countValue) {
            countValue.textContent =
                visibleCount.toLocaleString();
        }

        if (countHeading) {
            const label =
                visibleCount === 1
                    ? 'account'
                    : 'accounts';

            countHeading.lastChild.textContent =
                ` matching ${label}`;
        }

        if (emptyState) {
            emptyState.hidden =
                visibleCount !== 0;
        }
    };

    searchInput.addEventListener(
        'input',
        updateResults
    );

    updateResults();
}

function setupUserTabs() {
    const tabList =
        document.querySelector(
            '.manage-user-tabs'
        );

    if (!tabList) {
        return;
    }

    const tabs =
        Array.from(
            tabList.querySelectorAll(
                '[data-user-tab]'
            )
        );

    const panels =
        Array.from(
            document.querySelectorAll(
                '[data-user-tab-panel]'
            )
        );

    const availableTabs =
        tabs.map(
            (tab) =>
                tab.dataset.userTab
        );

    const updateDirectoryLinks = (
        tabName
    ) => {
        document
            .querySelectorAll(
                '.manage-user-row'
            )
            .forEach((link) => {
                const linkUrl =
                    new URL(
                        link.href,
                        window.location.href
                    );

                linkUrl.searchParams.set(
                    'tab',
                    tabName
                );

                link.href =
                    linkUrl.toString();
            });
    };

    const updatePageUrl = (
        tabName
    ) => {
        const currentUrl =
            new URL(
                window.location.href
            );

        currentUrl.searchParams.set(
            'tab',
            tabName
        );

        window.history.replaceState(
            {},
            '',
            currentUrl.toString()
        );
    };

    const activateTab = (
        requestedTab,
        moveFocus = false,
        updateUrl = true
    ) => {
        const tabName =
            availableTabs.includes(
                requestedTab
            )
                ? requestedTab
                : 'overview';

        tabs.forEach((tab) => {
            const active =
                tab.dataset.userTab ===
                tabName;

            tab.classList.toggle(
                'active',
                active
            );

            tab.setAttribute(
                'aria-selected',
                active
                    ? 'true'
                    : 'false'
            );

            tab.tabIndex =
                active
                    ? 0
                    : -1;

            if (
                active &&
                moveFocus
            ) {
                tab.focus();
            }
        });

        panels.forEach((panel) => {
            const active =
                panel.dataset
                    .userTabPanel ===
                tabName;

            panel.hidden =
                !active;

            panel.classList.toggle(
                'active',
                active
            );
        });

        updateDirectoryLinks(
            tabName
        );

        if (updateUrl) {
            updatePageUrl(
                tabName
            );
        }
    };

    tabs.forEach(
        (tab, index) => {
            tab.addEventListener(
                'click',
                () => {
                    activateTab(
                        tab.dataset.userTab
                    );
                }
            );

            tab.addEventListener(
                'keydown',
                (event) => {
                    if (
                        ![
                            'ArrowLeft',
                            'ArrowRight',
                            'Home',
                            'End'
                        ].includes(
                            event.key
                        )
                    ) {
                        return;
                    }

                    event.preventDefault();

                    let nextIndex =
                        index;

                    if (
                        event.key ===
                        'ArrowRight'
                    ) {
                        nextIndex =
                            (
                                index + 1
                            ) %
                            tabs.length;
                    }

                    if (
                        event.key ===
                        'ArrowLeft'
                    ) {
                        nextIndex =
                            (
                                index - 1 +
                                tabs.length
                            ) %
                            tabs.length;
                    }

                    if (
                        event.key ===
                        'Home'
                    ) {
                        nextIndex = 0;
                    }

                    if (
                        event.key ===
                        'End'
                    ) {
                        nextIndex =
                            tabs.length - 1;
                    }

                    activateTab(
                        tabs[nextIndex]
                            .dataset
                            .userTab,
                        true
                    );
                }
            );
        }
    );

    const requestedTab =
        new URLSearchParams(
            window.location.search
        ).get('tab');

    activateTab(
        availableTabs.includes(
            requestedTab
        )
            ? requestedTab
            : 'overview',
        false,
        true
    );
}

        function setupStatusForm() {
            const form =
                document.getElementById(
                    'manageStatusForm'
                );

            if (!form) {
                return;
            }

            const confirmationInput =
                document.getElementById(
                    'confirmStatusChange'
                );

            form.addEventListener(
                'submit',
                async (event) => {
                    event.preventDefault();

                if (!validateForm(form)) {
    return;
}

                    const userName =
                        form.dataset.userName ||
                        'this user';

                    const newStatus =
                        form.dataset.newStatus ||
                        '';

                    const isActivation =
                        newStatus === 'Active';






                    const confirmed =
                        await requestConfirmation({
                            icon:
                                isActivation
                                    ? 'question'
                                    : 'warning',

                            title:
                                isActivation
                                    ? 'Activate Account?'
                                    : 'Deactivate Account?',

                            message:
                                `${userName} will be marked as ${newStatus}.`,

                            confirmText:
                                isActivation
                                    ? 'Activate Account'
                                    : 'Deactivate Account',

                            confirmColor:
                                isActivation
                                    ? '#15803d'
                                    : '#b91c1c'
                        });

                    if (!confirmed) {
                        return;
                    }

                    confirmationInput.value =
                        '1';

                    submitForm(
                        form,
                        'Processing...'
                    );
                }
            );
        }

        function setupUnlockForm() {
            const form =
                document.getElementById(
                    'manageUnlockForm'
                );

            if (!form) {
                return;
            }

            const confirmationInput =
                document.getElementById(
                    'confirmAccountUnlock'
                );

            form.addEventListener(
                'submit',
                async (event) => {
                    event.preventDefault();

                    const userName =
                        form.dataset.userName ||
                        'this user';

                    const confirmed =
                        await requestConfirmation({
                            icon:
                                'warning',

                            title:
                                'Unlock Account?',

                            message:
                                `Failed login attempts and the temporary lock for ${userName} will be cleared.`,

                            confirmText:
                                'Unlock Account',

                            confirmColor:
                                '#b45309'
                        });

                    if (!confirmed) {
                        return;
                    }

                    confirmationInput.value =
                        '1';

                    submitForm(
                        form,
                        'Unlocking...'
                    );
                }
            );
        }

        function setupRoleForm() {
    const form =
        document.getElementById(
            'manageRoleForm'
        );

    if (!form) {
        return;
    }

    const roleSelect =
        document.getElementById(
            'managedNewRole'
        );

    const confirmationInput =
        document.getElementById(
            'confirmRoleChange'
        );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();

                form.querySelector(
                    ':invalid'
                )?.focus();

                return;
            }

            const userName =
                form.dataset.userName ||
                'this staff member';

            const currentRole =
                form.dataset.currentRole ||
                'Unknown';

            const selectedOption =
                roleSelect
                    ?.selectedOptions[0];

            const newRole =
                selectedOption
                    ?.dataset
                    .rolePrefix ||
                selectedOption
                    ?.textContent
                    .trim() ||
                'Unknown';

            const confirmed =
                await requestConfirmation({
                    icon:
                        newRole === 'Admin'
                            ? 'warning'
                            : 'question',

                    title:
                        'Change Staff Role?',

                    message:
                        `${userName} will change from ${currentRole} to ${newRole}.`,

                    confirmText:
                        'Change Staff Role',

                    confirmColor:
                        newRole === 'Admin'
                            ? '#7f1d1d'
                            : '#334155'
                });

            if (!confirmed) {
                return;
            }

            confirmationInput.value =
                '1';

            submitForm(
                form,
                'Updating Role...'
            );
        }
    );
}

function setupFacultyAssignments() {
    document.querySelectorAll('[data-faculty-assignment-fields]').forEach((container) => {
        const department = container.querySelector('[name="department_id"]');
        const education = container.querySelector('[name="education_level_id"]');
        const program = container.querySelector('[name="academic_program_id"]');
        const refresh = () => {
            const college = department.selectedOptions[0]?.dataset.division === 'COLLEGE';
            Array.from(education.options).forEach((option) => {
                option.hidden = option.disabled = !!option.value && option.dataset.departmentId !== department.value;
            });
            if (education.selectedOptions[0]?.disabled) education.value = '';
            Array.from(program.options).forEach((option) => {
                option.hidden = option.disabled = !!option.value && option.dataset.educationLevelId !== education.value;
            });
            if (!college || program.selectedOptions[0]?.disabled) program.value = '';
            program.disabled = !college;
            program.required = college;
            container.querySelector('[data-faculty-program-field]').hidden = !college;
        };
        department.addEventListener('change', refresh);
        education.addEventListener('change', refresh);
        refresh();
    });
    const form = document.getElementById('facultyAssignmentForm');
    let pending = false;
    let submitted = false;
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (pending || submitted || !form.reportValidity()) return;
        pending = true;
        try {
            const confirmed = await requestConfirmation({
                icon: 'question', title: 'Update Faculty Assignment?',
                message: 'The Faculty member will be restricted to this assignment on their next content submission.',
                confirmText: 'Update Assignment', confirmColor: '#7f1d1d'
            });
            if (!confirmed) return;
            form.querySelector('[name="confirm_faculty_assignment"]').value = '1';
            submitForm(form, 'Updating Assignment...');
            submitted = true;
        } finally {
            pending = false;
        }
    });
}

function setupFacultyProvisioning() {
    const modal =
        document.getElementById(
            'facultyProvisionModal'
        );

    const openButton =
        document.getElementById(
            'openFacultyProvisionModal'
        );

    const form =
        document.getElementById(
            'facultyProvisionForm'
        );

    const confirmationInput =
        document.getElementById(
            'confirmFacultyProvisioning'
        );

    const state =
        document.getElementById(
            'facultyProvisioningState'
        );

    const result =
        document.getElementById(
            'facultyProvisioningResult'
        );

    if (!modal || !form) {
        return;
    }

    const openModal = () => {
        modal.hidden =
            false;

        document.body.classList.add(
            'manage-modal-open'
        );

        window.setTimeout(
            () => {
                form.querySelector(
                    'input:not([type="hidden"]), select'
                )?.focus();
            },
            50
        );
    };

    const closeModal = () => {
        modal.hidden =
            true;

        document.body.classList.remove(
            'manage-modal-open'
        );

        openButton?.focus();
    };

    openButton?.addEventListener(
        'click',
        openModal
    );

    modal
        .querySelectorAll(
            '[data-close-faculty-modal]'
        )
        .forEach((element) => {
            element.addEventListener(
                'click',
                closeModal
            );
        });

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape' &&
                !modal.hidden
            ) {
                closeModal();
            }
        }
    );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();

                form.querySelector(
                    ':invalid'
                )?.focus();

                return;
            }

            const email =
                form.querySelector(
                    '[name="email"]'
                )?.value.trim() ||
                'the Faculty email';

            const confirmed =
                await requestConfirmation({
                    icon:
                        'question',

                    title:
                        'Create Faculty Account?',

                    message:
                        `An active Faculty account will be created for ${email}.`,

                    confirmText:
                        'Create Faculty',

                    confirmColor:
                        '#7f1d1d'
                });

            if (!confirmed) {
                return;
            }

            confirmationInput.value =
                '1';

            submitForm(
                form,
                'Creating Faculty...'
            );
        }
    );

    if (
        state?.dataset.openForm === '1'
    ) {
        openModal();
    }

    if (result) {
        const facultyName =
            result.dataset.name ||
            'Faculty member';

        const email =
            result.dataset.email ||
            '';

        const temporaryPassword =
            result.dataset
                .temporaryPassword ||
            '';

        /*
         * Remove credentials from the live DOM as
         * soon as JavaScript has read the values.
         */
        result.remove();

        showProvisionedCredentials(
            facultyName,
            email,
            temporaryPassword
        );
    }

    async function showProvisionedCredentials(
    facultyName,
    email,
    temporaryPassword
) {
    const credentialText =
        `OLSHCO Digital Hub Faculty Account\n`
        + `Name: ${facultyName}\n`
        + `Email: ${email}\n`
        + `Temporary Password: ${temporaryPassword}\n`
        + `The password must be changed during first login.`;

    if (
        !window.AppDialog
    ) {
        console.error(
            'Unable to display the one-time Faculty credentials.'
        );

        return;
    }

    const shouldCopy =
        await window.AppDialog
            .credentials({
                title:
                    'Faculty Account Created',

                message:
                    'Save these credentials now. The temporary password will not be displayed again.',

                fields: [
                    {
                        label:
                            'Faculty Name',

                        value:
                            facultyName
                    },
                    {
                        label:
                            'Email Address',

                        value:
                            email
                    },
                    {
                        label:
                            'Temporary Password',

                        value:
                            temporaryPassword,

                        secret:
                            true
                    }
                ],

                confirmText:
                    'Copy Credentials',

                cancelText:
                    'I Saved Them'
            });

    if (!shouldCopy) {
        return;
    }

    try {
        await navigator.clipboard
            .writeText(
                credentialText
            );

        await window.AppDialog
            .alert({
                type:
                    'success',

                title:
                    'Credentials Copied',

                message:
                    'Share them with the Faculty member through a secure channel.',

                confirmText:
                    'Done'
            });
    } catch (error) {
        /*
         * Redisplay the credentials if clipboard access
         * fails so the one-time password is not lost.
         */
        await window.AppDialog
            .alert({
                type:
                    'warning',

                title:
                    'Copy Failed',

                message:
                    'Copy these credentials manually before leaving this page.',

                confirmText:
                    'I Saved Them',

                dismissible:
                    false,

                fields: [
                    {
                        label:
                            'Faculty Name',

                        value:
                            facultyName
                    },
                    {
                        label:
                            'Email Address',

                        value:
                            email
                    },
                    {
                        label:
                            'Temporary Password',

                        value:
                            temporaryPassword,

                        secret:
                            true
                    }
                ]
            });
    }
}
}

function validateForm(form) {
    clearFormErrors(
        form
    );

    const fields =
        Array.from(
            form.querySelectorAll(
                'input:not([type="hidden"]), select, textarea'
            )
        );

    const invalidFields =
        fields.filter(
            (field) =>
                !field.checkValidity()
        );

    if (
        invalidFields.length ===
        0
    ) {
        return true;
    }

    const summary =
        document.createElement(
            'div'
        );

    summary.className =
        'manage-form-error-summary';

    summary.setAttribute(
        'role',
        'alert'
    );

    const icon =
        document.createElement(
            'i'
        );

    icon.className =
        'fa-solid fa-circle-exclamation';

    const content =
        document.createElement(
            'div'
        );

    const heading =
        document.createElement(
            'strong'
        );

    heading.textContent =
        invalidFields.length === 1
            ? 'Complete the highlighted field.'
            : `Complete the ${invalidFields.length} highlighted fields.`;

    const list =
        document.createElement(
            'ul'
        );

    invalidFields.forEach(
        (field) => {
            const fieldContainer =
                field.closest(
                    'label'
                );

            fieldContainer?.classList.add(
                'field-invalid'
            );

            field.setAttribute(
                'aria-invalid',
                'true'
            );

            const item =
                document.createElement(
                    'li'
                );

            item.textContent =
                getFieldErrorMessage(
                    field
                );

            list.appendChild(
                item
            );
        }
    );

    content.append(
        heading,
        list
    );

    summary.append(
        icon,
        content
    );

    const firstVisibleContent =
        Array.from(
            form.children
        ).find(
            (element) =>
                element.type !==
                'hidden'
        );

    if (firstVisibleContent) {
        form.insertBefore(
            summary,
            firstVisibleContent
        );
    } else {
        form.prepend(
            summary
        );
    }

    const firstInvalid =
        invalidFields[0];

    firstInvalid.scrollIntoView({
        behavior:
            'smooth',

        block:
            'center'
    });

    window.setTimeout(
        () => {
            firstInvalid.focus();
        },
        250
    );

    return false;
}

function clearFormErrors(form) {
    form.querySelector(
        '.manage-form-error-summary'
    )?.remove();

    form.querySelectorAll(
        '.field-invalid'
    ).forEach((element) => {
        element.classList.remove(
            'field-invalid'
        );
    });

    form.querySelectorAll(
        '[aria-invalid="true"]'
    ).forEach((field) => {
        field.removeAttribute(
            'aria-invalid'
        );
    });
}

function getFieldErrorMessage(field) {
    const label =
        field.closest(
            'label'
        )
        ?.querySelector(
            ':scope > span'
        )
        ?.textContent
        .replace(
            '*',
            ''
        )
        .trim() ||
        'Required field';

    if (
        field.validity.valueMissing
    ) {
        return `${label} is required.`;
    }

    if (
        field.validity.typeMismatch &&
        field.type === 'email'
    ) {
        return `${label} must contain a valid email address.`;
    }

    if (
        field.validity.tooLong
    ) {
        return `${label} must not exceed ${field.maxLength} characters.`;
    }

    if (
        field.validity.rangeUnderflow
    ) {
        return `${label} is below the allowed value.`;
    }

    if (
        field.validity.rangeOverflow
    ) {
        return `${label} exceeds the allowed value.`;
    }

    return field.validationMessage ||
        `${label} is invalid.`;
}

async function requestConfirmation(
    options
) {
    if (
        !window.AppDialog
    ) {
        console.error(
            'The application dialog component is unavailable.'
        );

        return false;
    }

    return window.AppDialog
        .confirm({
            type:
                options.icon ||
                'question',

            title:
                options.title ||
                'Confirm Action',

            message:
                options.message ||
                '',

            confirmText:
                options.confirmText ||
                'Continue',

            cancelText:
                'Cancel',

            dismissible:
                true
        });
}
        function submitForm(
            form,
            loadingText
        ) {
            const button =
                form.querySelector(
                    'button[type="submit"]'
                );

            if (button) {
                button.disabled =
                    true;

                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> '
                    + loadingText;
            }

            form.submit();
        }
    },

    function escapeHtml(value) {
    const element =
        document.createElement(
            'div'
        );

    element.textContent =
        String(value);

    return element.innerHTML;
}
);