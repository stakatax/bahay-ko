document.addEventListener('DOMContentLoaded', () => {
    const form =
        document.getElementById('registrationForm');

    if (!form) {
        return;
    }

    /* ==========================================
       ELEMENTS
    ========================================== */

    const roleInputs = Array.from(
        document.querySelectorAll(
            'input[name="role_type"]'
        )
    );

    const roleOptions = Array.from(
        document.querySelectorAll(
            '.role-option'
        )
    );

    const rolePanels = Array.from(
        document.querySelectorAll(
            '[data-role-panel]'
        )
    );

    const departmentSelect =
        document.getElementById('departmentId');

    const educationLevelSelect =
    document.getElementById(
        'educationLevelId'
    );

    const programField =
        document.getElementById(
            'academicProgramField'
        );

    const programSelect =
        document.getElementById(
            'academicProgramId'
        );

    const programHint =
        document.getElementById(
            'academicProgramHint'
        );

    const gradeSelect =
        document.getElementById(
            'gradeLevelId'
        );

    const sectionSelect =
        document.getElementById(
            'sectionId'
        );

    const birthdateInput =
        document.getElementById(
            'birthdate'
        );

    const calculatedAge =
        document.getElementById(
            'calculatedAge'
        );

    const passwordInput =
        document.getElementById(
            'registrationPassword'
        );

    const confirmationInput =
        document.getElementById(
            'passwordConfirmation'
        );

    const passwordStrength =
        document.getElementById(
            'passwordStrength'
        );

    const matchMessage =
        document.getElementById(
            'passwordMatchMessage'
        );

    const submitButton =
        document.getElementById(
            'registrationSubmitButton'
        );


            const legalDocumentButtons =
        Array.from(
            document.querySelectorAll(
                '[data-legal-document]'
            )
        );

    /* ==========================================
       LEGAL DOCUMENT MODALS
    ========================================== */

       const legalDocumentsPayload =
        document.getElementById(
            'registrationLegalDocuments'
        );

    let legalDocuments = {};

    try {
        const databaseDocuments =
            JSON.parse(
                legalDocumentsPayload
                    ?.textContent
                    || '{}'
            );

        legalDocuments = {
            terms: {
                title:
                    databaseDocuments
                        ?.Terms
                        ?.title
                    || 'Terms and Conditions',

                html:
                    databaseDocuments
                        ?.Terms
                        ?.content
                    || ''
            },

            privacy: {
                title:
                    databaseDocuments
                        ?.Privacy
                        ?.title
                    || 'Privacy Notice',

                html:
                    databaseDocuments
                        ?.Privacy
                        ?.content
                    || ''
            }
        };
    } catch (error) {
        legalDocuments = {};
    }

        legalDocumentButtons.forEach(
        (button) => {
            button.addEventListener(
                'click',
                () => {
                    const documentKey =
                        button.dataset
                            .legalDocument;

                    const legalDocument =
                        legalDocuments[
                            documentKey
                        ];

                    if (!legalDocument) {
                        return;
                    }

                    Swal.fire({
                        title:
                            legalDocument.title,

                        html:
                            legalDocument.html,

                        width:
                            720,

                        confirmButtonText:
                            'Close',

                        confirmButtonColor:
                            '#7f1d1d',

                        customClass: {
                            popup:
                                'register-legal-modal'
                        }
                    });
                }
            );
        }
    );

    /* ==========================================
       ROLE SWITCHING
    ========================================== */

    function getActiveRole() {
        return (
            roleInputs.find(
                (input) => input.checked
            )?.value || 'Student'
        );
    }

    function setPanelState(
        panel,
        active,
        role
    ) {
        panel.hidden = !active;

        panel
            .querySelectorAll(
                'input, select'
            )
            .forEach((field) => {
                field.disabled = !active;

                const requiredRole =
                    field.dataset.requiredFor;

                if (requiredRole) {
                    field.required =
                        active &&
                        requiredRole === role;
                }
            });
    }

    function activateRole(role) {
        roleOptions.forEach((option) => {
            const input =
                option.querySelector(
                    'input[name="role_type"]'
                );

            option.classList.toggle(
                'active',
                input?.value === role
            );
        });

        rolePanels.forEach((panel) => {
            const active =
                panel.dataset.rolePanel === role;

            setPanelState(
                panel,
                active,
                role
            );
        });

        if (role === 'Student') {
            updateAcademicHierarchy();
        }
        updateChildRegistration();
    }

    function updateChildRegistration() {
        const checkbox = document.getElementById('childNoAccount');
        const details = document.getElementById('childRegistrationDetails');
        const isParent = getActiveRole() === 'Parent';
        const withoutAccount = isParent && Boolean(checkbox?.checked);
        if (details) {
            details.hidden = !withoutAccount;
            details.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !withoutAccount;
                field.required = withoutAccount && field.hasAttribute('data-child-required');
            });
        }
        const id = document.getElementById('childStudentId');
        if (id) { id.required = isParent && !withoutAccount; }
        const label = document.getElementById('childStudentIdLabel');
        if (label) { label.textContent = withoutAccount ? 'Child Student ID (if available)' : 'Child Student ID *'; }
        const hint = document.getElementById('childStudentIdHint');
        if (hint) { hint.textContent = withoutAccount ? 'Enter the school-issued ID if you know it; a Digital Hub account is not required.' : 'Enter the Student ID of an active Student account.'; }
    }
    document.getElementById('childNoAccount')?.addEventListener('change', updateChildRegistration);

    roleInputs.forEach((input) => {
        input.addEventListener(
            'change',
            () => {
                activateRole(input.value);
            }
        );
    });

    /* ==========================================
       ACADEMIC HIERARCHY
    ========================================== */

function getSelectedEducationLevelId() {
    return (
        educationLevelSelect?.value ||
        ''
    );
}

    function filterOptions(
        select,
        predicate
    ) {
        if (!select) {
            return 0;
        }

        let visibleCount = 0;

        Array.from(
            select.options
        ).forEach((option, index) => {
            if (index === 0) {
                return;
            }

            const visible =
                predicate(option);

            option.hidden = !visible;
            option.disabled = !visible;

            if (visible) {
                visibleCount += 1;
            }
        });

        return visibleCount;
    }

    function updateEducationLevels() {
    if (!educationLevelSelect) {
        return;
    }

    const departmentId =
        departmentSelect?.value ||
        '';

    const visibleCount =
        filterOptions(
            educationLevelSelect,
            (option) =>
                option.dataset
                    .departmentId ===
                departmentId
        );

    educationLevelSelect.value =
        '';

    educationLevelSelect.disabled =
        departmentId === '' ||
        visibleCount === 0;

    educationLevelSelect.options[0]
        .textContent =
        departmentId === ''
            ? 'Select School Division first'
            : visibleCount > 0
                ? 'Select Education Level'
                : 'No Education Level available';

    updateAcademicHierarchy();
}

    function updatePrograms(
        educationLevelId
    ) {
        if (
            !programField ||
            !programSelect
        ) {
            return false;
        }

        const visibleCount =
            filterOptions(
                programSelect,
                (option) =>
                    option.dataset
                        .educationLevelId ===
                    educationLevelId
            );

        const requiresProgram =
            educationLevelId !== '' &&
            visibleCount > 0;

        programField.hidden =
            !requiresProgram;

        programSelect.disabled =
            !requiresProgram;

        programSelect.required =
            requiresProgram;

        programSelect.value = '';

        programSelect.options[0]
            .textContent =
            requiresProgram
                ? 'Select Program or Strand'
                : 'No Program or Strand required';

        if (programHint) {
            const firstVisibleOption =
                Array.from(
                    programSelect.options
                ).find(
                    (option, index) =>
                        index > 0 &&
                        !option.hidden
                );

            const programType =
                firstVisibleOption?.dataset
                    .programType ||
                'Program or Strand';

            programHint.textContent =
                requiresProgram
                    ? `Select the applicable ${programType}.`
                    : 'This Education Level does not require a Program or Strand.';
        }

        return requiresProgram;
    }

    function updateGrades(
        educationLevelId
    ) {
        if (!gradeSelect) {
            return;
        }

        const visibleCount =
            filterOptions(
                gradeSelect,
                (option) =>
                    option.dataset
                        .educationLevelId ===
                    educationLevelId
            );

        gradeSelect.value = '';

        gradeSelect.disabled =
            educationLevelId === '' ||
            visibleCount === 0;

        gradeSelect.options[0]
            .textContent =
            educationLevelId === ''
                ?  'Select Education Level first'
                : visibleCount > 0
                    ? 'Select Grade or Year Level'
                    : 'No Grade or Year Level available';
    }

    function updateSections() {
        if (!sectionSelect) {
            return;
        }

        const gradeLevelId =
            gradeSelect?.value || '';

        const programRequired =
            programSelect?.required === true;

        const selectedProgramId =
            programRequired
                ? programSelect?.value || ''
                : '';

        const visibleCount =
            filterOptions(
                sectionSelect,
                (option) => {
                    const sameGrade =
                        option.dataset
                            .gradeLevelId ===
                        gradeLevelId;

                    const optionProgramId =
                        option.dataset
                            .academicProgramId ||
                        '';

                    const sameProgram =
                        programRequired
                            ? optionProgramId ===
                              selectedProgramId
                            : optionProgramId === '';

                    return (
                        sameGrade &&
                        sameProgram
                    );
                }
            );

        sectionSelect.value = '';

        const hierarchyIncomplete =
            gradeLevelId === '' ||
            (
                programRequired &&
                selectedProgramId === ''
            );

        sectionSelect.disabled =
            hierarchyIncomplete ||
            visibleCount === 0;

        sectionSelect.options[0]
            .textContent =
            hierarchyIncomplete
                ? 'Complete the academic fields first'
                : visibleCount > 0
                    ? 'Select Section'
                    : 'No matching Section available';
    }

    function updateAcademicHierarchy() {
        const educationLevelId =
            getSelectedEducationLevelId();

        updatePrograms(
            educationLevelId
        );

        updateGrades(
            educationLevelId
        );

        updateSections();
    }

   departmentSelect?.addEventListener(
    'change',
    updateEducationLevels
);

educationLevelSelect?.addEventListener(
    'change',
    updateAcademicHierarchy
);

    programSelect?.addEventListener(
        'change',
        updateSections
    );

    gradeSelect?.addEventListener(
        'change',
        updateSections
    );

    /* ==========================================
       BIRTHDATE AND AGE
    ========================================== */

    function updateAge() {
        if (
            !birthdateInput ||
            !calculatedAge
        ) {
            return;
        }

        if (!birthdateInput.value) {
            calculatedAge.textContent =
                'Age will be calculated automatically.';

            calculatedAge.classList.remove(
                'valid',
                'invalid'
            );

            return;
        }

        const birthdate = new Date(
            `${birthdateInput.value}T00:00:00`
        );

        if (
            Number.isNaN(
                birthdate.getTime()
            )
        ) {
            calculatedAge.textContent =
                'Invalid birthdate.';

            calculatedAge.classList.add(
                'invalid'
            );

            calculatedAge.classList.remove(
                'valid'
            );

            return;
        }

        const today = new Date();

        let age =
            today.getFullYear() -
            birthdate.getFullYear();

        const monthDifference =
            today.getMonth() -
            birthdate.getMonth();

        if (
            monthDifference < 0 ||
            (
                monthDifference === 0 &&
                today.getDate() <
                birthdate.getDate()
            )
        ) {
            age -= 1;
        }

        const validAge =
            age >= 1 &&
            age <= 120;

        calculatedAge.textContent =
            validAge
                ? `Calculated age: ${age}`
                : 'Invalid birthdate.';

        calculatedAge.classList.toggle(
            'valid',
            validAge
        );

        calculatedAge.classList.toggle(
            'invalid',
            !validAge
        );
    }

    if (birthdateInput) {
        const today = new Date();

        birthdateInput.max =
            new Date(
                today.getTime() -
                today.getTimezoneOffset() *
                60000
            )
                .toISOString()
                .slice(0, 10);
    }

    birthdateInput?.addEventListener(
        'change',
        updateAge
    );

    /* ==========================================
       PASSWORD STRENGTH
    ========================================== */

    function calculatePasswordScore(
        password
    ) {
        let score = 0;

        if (password.length >= 8) {
            score += 1;
        }

        if (/[A-Z]/.test(password)) {
            score += 1;
        }

        if (/[a-z]/.test(password)) {
            score += 1;
        }

        if (/\d/.test(password)) {
            score += 1;
        }

        return score;
    }

    function updatePasswordStrength() {
        if (
            !passwordInput ||
            !passwordStrength
        ) {
            return;
        }

        passwordStrength.dataset.score =
            String(
                calculatePasswordScore(
                    passwordInput.value
                )
            );
    }

    function updatePasswordMatch() {
        if (
            !confirmationInput ||
            !passwordInput ||
            !matchMessage
        ) {
            return;
        }

        if (!confirmationInput.value) {
            matchMessage.textContent =
                'Passwords must match.';

            matchMessage.classList.remove(
                'valid',
                'invalid'
            );

            confirmationInput
                .setCustomValidity('');

            return;
        }

        const passwordsMatch =
            confirmationInput.value ===
            passwordInput.value;

        matchMessage.textContent =
            passwordsMatch
                ? 'Passwords match.'
                : 'Passwords do not match.';

        matchMessage.classList.toggle(
            'valid',
            passwordsMatch
        );

        matchMessage.classList.toggle(
            'invalid',
            !passwordsMatch
        );

        confirmationInput.setCustomValidity(
            passwordsMatch
                ? ''
                : 'Passwords do not match.'
        );
    }

    passwordInput?.addEventListener(
        'input',
        () => {
            updatePasswordStrength();
            updatePasswordMatch();
        }
    );

    confirmationInput?.addEventListener(
        'input',
        updatePasswordMatch
    );

    /* ==========================================
       PASSWORD VISIBILITY
    ========================================== */

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

                    const currentlyHidden =
                        input.type ===
                        'password';

                    input.type =
                        currentlyHidden
                            ? 'text'
                            : 'password';

                    const icon =
                        button.querySelector(
                            'i'
                        );

                    if (icon) {
                        icon.className =
                            currentlyHidden
                                ? 'fa-regular fa-eye-slash'
                                : 'fa-regular fa-eye';
                    }

                    button.setAttribute(
                        'aria-label',
                        currentlyHidden
                            ? 'Hide password'
                            : 'Show password'
                    );
                }
            );
        });

    /* ==========================================
       FORM SUBMISSION
    ========================================== */

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            updatePasswordMatch();

           if (!form.checkValidity()) {
    const invalidFields =
        Array.from(
            form.querySelectorAll(
                ':invalid'
            )
        ).filter(
            (field) =>
                !field.disabled &&
                !field.closest('[hidden]')
        );

    invalidFields.forEach((field) => {
        field.setAttribute(
            'aria-invalid',
            'true'
        );

        field.classList.add(
            'registration-invalid-control'
        );

        field
            .closest(
                '.auth-field, .register-field, label'
            )
            ?.classList
            .add(
                'registration-error-field'
            );
    });

    const firstInvalid =
        invalidFields[0];

    if (firstInvalid) {
        const fieldLabel =
            form.querySelector(
                `label[for="${firstInvalid.id}"]`
            )
            ?.textContent
            ?.replace('*', '')
            ?.trim() ||
            firstInvalid.name ||
            'required field';

        Swal.fire({
            icon: 'warning',
            title:
                'Complete Required Fields',
            html:
                `Please complete <strong>${fieldLabel}</strong> and the other highlighted fields before submitting.`,
            confirmButtonText:
                'Review Form',
            confirmButtonColor:
                '#7f1d1d'
        }).then(() => {
            firstInvalid.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            window.setTimeout(
                () => {
                    firstInvalid.focus({
                        preventScroll: true
                    });
                },
                350
            );
        });
    }

    return;
}

            const role =
                getActiveRole();

            const result =
                await Swal.fire({
                    icon: 'question',
                    title:
                        'Submit Registration?',
                    text:
                        `Your ${role} account will be sent for Administrator approval.`,
                    showCancelButton: true,
                    confirmButtonText:
                        'Submit Registration',
                    cancelButtonText:
                        'Review Details',
                    confirmButtonColor:
                        '#7f1d1d'
                });

            if (!result.isConfirmed) {
                return;
            }

            registrationSubmitting = true;

            if (submitButton) {
                submitButton.disabled =
                    true;
            }

            const buttonLabel =
                submitButton
                    ?.querySelector(
                        'span'
                    );

            if (buttonLabel) {
                buttonLabel.textContent =
                    'Submitting...';
            }

            form.submit();
        }
    );

    /* ==========================================
   RESTORE SAFE SERVER INPUT
========================================== */

const restoreState =
    document.getElementById(
        'registrationRestoreState'
    );

let oldInput = {};

if (restoreState) {
    try {
        oldInput =
            JSON.parse(
                restoreState.dataset
                    .oldInput ||
                '{}'
            );
    } catch (error) {
        console.error(
            'Unable to restore registration values:',
            error
        );

        oldInput = {};
    }
}

function setFieldValue(
    fieldName,
    value
) {
    const field =
        form.elements.namedItem(
            fieldName
        );

    if (
        !field ||
        value === null ||
        value === undefined
    ) {
        return;
    }

    field.value =
        String(value);
}

function restoreRegistrationInput() {
    const savedRole =
        oldInput.role_type === 'Parent'
            ? 'Parent'
            : 'Student';

    roleInputs.forEach((input) => {
        input.checked =
            input.value === savedRole;
    });

    activateRole(
        savedRole
    );

   [
    'first_name',
    'middle_name',
    'last_name',
    'name_suffix',
    'email',
    ].forEach((fieldName) => {
        setFieldValue(
            fieldName,
            oldInput[fieldName]
        );
    });

    if (savedRole === 'Parent') {
        const checkbox = document.getElementById('childNoAccount');
        if (checkbox) { checkbox.checked = String(oldInput.child_no_account || '') === '1'; }
        ['child_name', 'child_section_id', 'child_reason', 'child_reason_details'].forEach((name) => setFieldValue(name, oldInput[name]));
        updateChildRegistration();
        setFieldValue(
            'child_student_id',
            oldInput.child_student_id
        );

        setFieldValue(
            'relationship',
            oldInput.relationship
        );
    } else {
        setFieldValue(
            'student_id',
            oldInput.student_id
        );

        /*
         * Restore dependent academic fields
         * in hierarchy order.
         */
        setFieldValue(
    'department_id',
    oldInput.department_id
);

updateEducationLevels();

setFieldValue(
    'education_level_id',
    oldInput.education_level_id
);

updateAcademicHierarchy();

setFieldValue(
    'academic_program_id',
    oldInput.academic_program_id
);

setFieldValue(
    'grade_level_id',
    oldInput.grade_level_id
);

updateSections();

setFieldValue(
    'section_id',
    oldInput.section_id
);
    }

    updateAge();
}

restoreRegistrationInput();
updatePasswordStrength();

/* ==========================================
   SERVER ERROR FOCUS
========================================== */

const registrationErrorCard =
    document.getElementById(
        'registrationErrorCard'
    );

if (registrationErrorCard) {
    const errorFieldName =
        restoreState?.dataset
            .errorField ||
        '';

    const errorField =
        errorFieldName !== ''
            ? form.elements.namedItem(
                errorFieldName
            )
            : null;

    if (
        errorField &&
        typeof errorField.focus ===
        'function'
    ) {
        errorField.setAttribute(
            'aria-invalid',
            'true'
        );

        errorField.classList.add(
            'registration-invalid-control'
        );

        errorField
            .closest(
                '.auth-field, .register-field, label'
            )
            ?.classList
            .add(
                'registration-error-field'
            );
    }

    window.requestAnimationFrame(
        () => {
            const target =
                errorField ||
                registrationErrorCard;

            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            window.setTimeout(
                () => {
                    target.focus({
                        preventScroll: true
                    });
                },
                350
            );
        }
    );
}

document
    .querySelector(
        '[data-dismiss-registration-error]'
    )
    ?.addEventListener(
        'click',
        () => {
            registrationErrorCard?.remove();
        }
    );

form
    .querySelectorAll(
        'input, select, textarea'
    )
    .forEach((field) => {
        field.addEventListener(
            'input',
            () => {
                field.removeAttribute(
                    'aria-invalid'
                );

                field.classList.remove(
                    'registration-invalid-control'
                );

                field
                    .closest(
                        '.auth-field, .register-field, label'
                    )
                    ?.classList
                    .remove(
                        'registration-error-field'
                    );
            }
        );

        field.addEventListener(
            'change',
            () => {
                field.removeAttribute(
                    'aria-invalid'
                );

                field.classList.remove(
                    'registration-invalid-control'
                );
            }
        );
    });

/* ==========================================
   UNSAVED FORM PROTECTION
========================================== */

let registrationDirty = false;
let registrationSubmitting = false;

form.addEventListener(
    'input',
    () => {
        registrationDirty = true;
    }
);

form.addEventListener(
    'change',
    () => {
        registrationDirty = true;
    }
);

window.addEventListener(
    'beforeunload',
    (event) => {
        if (
            !registrationDirty ||
            registrationSubmitting
        ) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    }
);

/*
 * The existing submit handler uses form.submit()
 * after SweetAlert confirmation. Capture the
 * confirmed submission before navigation.
 */
form.addEventListener(
    'submit',
    () => {
        window.setTimeout(
            () => {
                const submitting =
                    submitButton?.disabled ===
                    true;

                if (submitting) {
                    registrationSubmitting =
                        true;
                }
            },
            0
        );
    }
);
});