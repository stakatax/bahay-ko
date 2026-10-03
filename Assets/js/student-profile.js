document.addEventListener(
    'DOMContentLoaded',
    setupStudentProfile
);

function setupStudentProfile() {
    setupStudentProfileAlert();
    setupExpandedStudentSurvey();

    const form =
        document.getElementById(
            'studentProfileForm'
        );

    if (!form) {
        return;
    }

    const interestCards =
        Array.from(
            form.querySelectorAll(
                '[data-interest-card]'
            )
        );

    const interestCheckboxes =
        Array.from(
            form.querySelectorAll(
                '[data-interest-checkbox]'
            )
        );

    const selectionCount =
        form.querySelector(
            '[data-interest-selection-count]'
        );

    const selectionRequirement =
        form.querySelector(
            '[data-interest-selection-requirement]'
        );

    const progress =
        form.querySelector(
            '[data-interest-progress]'
        );

    const validation =
        document.getElementById(
            'studentProfileValidation'
        );

    const clearButton =
        form.querySelector(
            '[data-clear-interest-selection]'
        );

    const submitButton =
        form.querySelector(
            '[data-save-student-profile]'
        );

    let isSubmitting =
        false;

    interestCheckboxes.forEach(
        (checkbox) => {
            checkbox.addEventListener(
                'change',
                () => {
                    synchronizeInterestCard(
                        checkbox
                    );

                    updateSelectionState();
                }
            );
        }
    );

    clearButton?.addEventListener(
        'click',
        () => {
            interestCheckboxes.forEach(
                (checkbox) => {
                    checkbox.checked =
                        false;

                    synchronizeInterestCard(
                        checkbox
                    );
                }
            );

            updateSelectionState();

            interestCards[0]
                ?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
        }
    );

    form.addEventListener(
        'submit',
        (event) => {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            const selectedCount =
                getSelectedInterestCount();

            if (selectedCount < 3) {
                event.preventDefault();

                showValidation(
                    `Select at least three interests. ${
                        3 - selectedCount
                    } more ${
                        3 - selectedCount === 1
                            ? 'is'
                            : 'are'
                    } required.`
                );

                const firstUnchecked =
                    interestCheckboxes.find(
                        (checkbox) =>
                            !checkbox.checked
                    );

                firstUnchecked
                    ?.closest(
                        '[data-interest-card]'
                    )
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                firstUnchecked?.focus();

                return;
            }

            hideValidation();

            isSubmitting =
                true;

            if (submitButton) {
                submitButton.disabled =
                    true;

                submitButton.innerHTML = `
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Saving Interests
                `;
            }

            if (clearButton) {
                clearButton.disabled =
                    true;
            }
        }
    );

    interestCheckboxes.forEach(
        synchronizeInterestCard
    );

    updateSelectionState();
    function synchronizeInterestCard(
        checkbox
    ) {
        const card =
            checkbox.closest(
                '[data-interest-card]'
            );

        const weightSelect =
            card?.querySelector(
                '[data-interest-weight]'
            );

        card?.classList.toggle(
            'is-selected',
            checkbox.checked
        );

        if (weightSelect) {
            weightSelect.disabled =
                !checkbox.checked;
        }
    }

    function getSelectedInterestCount() {
        return interestCheckboxes.filter(
            (checkbox) =>
                checkbox.checked
        ).length;
    }

    function updateSelectionState() {
        const selectedCount =
            getSelectedInterestCount();

        const remaining =
            Math.max(
                0,
                3 - selectedCount
            );

        if (selectionCount) {
            selectionCount.textContent =
                String(selectedCount);
        }

        if (selectionRequirement) {
            selectionRequirement.textContent =
                remaining === 0
                    ? 'Minimum reached'
                    : `${remaining} more required`;
        }

        if (progress) {
            progress.style.width =
                `${Math.min(
                    100,
                    (
                        selectedCount /
                        3
                    ) *
                    100
                )}%`;
        }

        if (
            submitButton &&
            !isSubmitting
        ) {
            submitButton.disabled =
                selectedCount < 3;
        }

        if (
            clearButton &&
            !isSubmitting
        ) {
            clearButton.disabled =
                selectedCount === 0;
        }

        if (selectedCount >= 3) {
            hideValidation();
        }
    }

    function showValidation(
        message
    ) {
        if (!validation) {
            return;
        }

        const messageElement =
            validation.querySelector(
                'span'
            );

        if (messageElement) {
            messageElement.textContent =
                message;
        }

        validation.hidden =
            false;

        validation.classList.remove(
            'is-visible'
        );

        requestAnimationFrame(
            () => {
                validation.classList.add(
                    'is-visible'
                );
            }
        );
    }

    function hideValidation() {
        if (!validation) {
            return;
        }

        validation.hidden =
            true;

        validation.classList.remove(
            'is-visible'
        );
    }
}

function setupExpandedStudentSurvey() {
    const survey =
        document.querySelector(
            '[data-student-survey]'
        );

    const form =
        survey?.querySelector(
            '[data-student-survey-form]'
        );

    if (!survey || !form) {
        return;
    }

    const sections =
        Array.from(
            form.querySelectorAll(
                '[data-survey-section]'
            )
        );

    const navigationButtons =
        Array.from(
            form.querySelectorAll(
                '[data-survey-section-button]'
            )
        );

    const currentStepInput =
        form.querySelector(
            '[data-survey-current-step]'
        );

    const previousButton =
        form.querySelector(
            '[data-survey-previous]'
        );

    const nextButton =
        form.querySelector(
            '[data-survey-next]'
        );

    const completeButton =
        form.querySelector(
            '[data-survey-complete]'
        );

    const validation =
        form.querySelector(
            '[data-survey-validation]'
        );

    const consentInputs =
        Array.from(
            form.querySelectorAll(
                '[data-survey-consent]'
            )
        );

    let activeIndex =
        Math.max(
            0,
            sections.findIndex(
                (section) =>
                    section.classList.contains(
                        'is-active'
                    )
            )
        );

    let isSubmitting =
        false;

    let hasUnsavedChanges = false;
    const position = form.querySelector('[data-survey-position]');
    const liveProgress = form.querySelector('[data-survey-live-progress]');
    const saveState = form.querySelector('[data-survey-save-state]');
    const progressBar = survey.querySelector('[role="progressbar"]');
    function updateSurveyFeedback() {
        const required = [...form.querySelectorAll('[data-survey-question][data-required="1"]')]
            .filter((question) => !question.disabled);
        const answered = required.filter(questionHasResponse).length;
        const percent = required.length ? Math.round(answered / required.length * 100) : 100;
        if (liveProgress) liveProgress.textContent = `${answered} of ${required.length} required answers filled`;
        if (progressBar) {
            progressBar.setAttribute('aria-valuenow', String(percent));
            progressBar.setAttribute('aria-label', 'Required answers filled');
            const fill = progressBar.querySelector('span');
            if (fill) fill.style.width = `${percent}%`;
        }
        const statusLabel = survey.querySelector('.student-survey-status strong');
        if (hasUnsavedChanges && statusLabel) statusLabel.textContent = `${percent}% filled (unsaved)`;
        if (hasUnsavedChanges && saveState) {
            saveState.textContent = 'Unsaved changes. Choose Save and continue later or complete the survey to save your answers.';
        }
    }
    const markSurveyChanged = () => {
        hasUnsavedChanges = true;
        updateSurveyFeedback();
    };
    form.addEventListener('input', markSurveyChanged);
    form.addEventListener('change', markSurveyChanged);
    window.addEventListener('beforeunload', (event) => {
        if (!hasUnsavedChanges || isSubmitting) return;
        event.preventDefault();
        event.returnValue = '';
    });

    function getSectionKey(
        section
    ) {
        return (
            section?.dataset
                .surveySection ||
            ''
        );
    }

    function showSection(
        nextIndex,
        scroll = false
    ) {
        activeIndex =
            Math.max(
                0,
                Math.min(
                    sections.length - 1,
                    nextIndex
                )
            );

        sections.forEach(
            (section, index) => {
                const active =
                    index === activeIndex;

                section.hidden =
                    !active;

                section.classList.toggle(
                    'is-active',
                    active
                );
            }
        );

        navigationButtons.forEach(
            (button) => {
                const active =
                    button.dataset
                        .surveySectionButton ===
                    getSectionKey(
                        sections[activeIndex]
                    );

                button.classList.toggle(
                    'is-active',
                    active
                );

                button.setAttribute(
                    'aria-current',
                    active
                        ? 'step'
                        : 'false'
                );
            }
        );

        if (currentStepInput) {
            currentStepInput.value =
                getSectionKey(
                    sections[activeIndex]
                );
        }

        if (previousButton) {
            previousButton.disabled =
                activeIndex === 0;
        }

        if (nextButton) {
            nextButton.hidden =
                activeIndex ===
                sections.length - 1;
        }

        if (completeButton) {
            completeButton.hidden =
                activeIndex !==
                sections.length - 1;
        }

        if (position) position.textContent = `Section ${activeIndex + 1} of ${sections.length}`;
        updateSurveyFeedback();
        hideValidation();

        if (scroll) {
            if (window.matchMedia('(max-width: 850px)').matches) {
                navigationButtons.find((button) => button.classList.contains('is-active'))
                    ?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'instant' });
            }
            survey.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    function questionHasResponse(
        question
    ) {
        if (question.disabled) {
            return true;
        }

        const controls =
            Array.from(
                question.querySelectorAll(
                    'input, textarea, select'
                )
            ).filter(
                (control) =>
                    !control.disabled
            );

        if (controls.length === 0) {
            return false;
        }

        const checkableControls =
            controls.filter(
                (control) =>
                    control.type ===
                        'radio' ||
                    control.type ===
                        'checkbox'
            );

        if (checkableControls.length > 0) {
            return checkableControls.some(
                (control) =>
                    control.checked
            );
        }

        return controls.some(
            (control) =>
                control.value.trim() !== ''
        );
    }

    function validateSection(
        section
    ) {
        const requiredQuestions =
            Array.from(
                section.querySelectorAll(
                    '[data-survey-question][data-required="1"]'
                )
            );

        let firstInvalid =
            null;

        requiredQuestions.forEach(
            (question) => {
                const valid =
                    questionHasResponse(
                        question
                    );

                question.classList.toggle(
                    'has-error',
                    !valid
                );

                if (
                    !valid &&
                    !firstInvalid
                ) {
                    firstInvalid =
                        question;
                }
            }
        );

        if (firstInvalid) {
            showValidation(
                'Complete the required questions in this section before continuing.'
            );

            firstInvalid.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            firstInvalid
                .querySelector(
                    'input, textarea, select'
                )
                ?.focus();

            return false;
        }

        hideValidation();

        return true;
    }

    function validateCompleteSurvey() {
        for (
            let index = 0;
            index < sections.length;
            index++
        ) {
            if (
                !validateSection(
                    sections[index]
                )
            ) {
                showSection(
                    index,
                    true
                );

                showValidation(
                    'Complete the required questions in this section before submitting the survey.'
                );

                return false;
            }
        }

        return true;
    }

    function showValidation(
        message
    ) {
        if (!validation) {
            return;
        }

        const messageElement =
            validation.querySelector(
                'span'
            );

        if (messageElement) {
            messageElement.textContent =
                message;
        }

        validation.hidden =
            false;
    }

    function hideValidation() {
        if (!validation) {
            return;
        }

        validation.hidden =
            true;
    }

    function synchronizeConsent(
        consentInput
    ) {
        const consentKey =
            consentInput.dataset
                .surveyConsent;

        form
            .querySelectorAll(
                '[data-consent-required]'
            )
            .forEach(
                (question) => {
                    if (
                        question.dataset
                            .consentRequired !==
                        consentKey
                    ) {
                        return;
                    }

                    question.disabled =
                        !consentInput.checked;

                    question.classList.toggle(
                        'is-consent-disabled',
                        !consentInput.checked
                    );

                    question.classList.remove(
                        'has-error'
                    );
                }
            );
    }

    function setupExclusiveChoices() {
        form
            .querySelectorAll(
                '[data-survey-question]'
            )
            .forEach(
                (question) => {
                    const checkboxes =
                        Array.from(
                            question
                                .querySelectorAll(
                                    'input[type="checkbox"]'
                                )
                        );

                    if (
                        checkboxes.length <
                        2
                    ) {
                        return;
                    }

                    checkboxes.forEach(
                        (checkbox) => {
                            checkbox.addEventListener(
                                'change',
                                () => {
                                    if (
                                        !checkbox.checked
                                    ) {
                                        return;
                                    }

                                    const exclusive =
                                        checkbox.value ===
                                            'None' ||
                                        checkbox.value ===
                                            'Prefer not to say';

                                    checkboxes.forEach(
                                        (otherCheckbox) => {
                                            if (
                                                otherCheckbox ===
                                                checkbox
                                            ) {
                                                return;
                                            }

                                            const otherExclusive =
                                                otherCheckbox.value ===
                                                    'None' ||
                                                otherCheckbox.value ===
                                                    'Prefer not to say';

                                            if (
                                                exclusive ||
                                                otherExclusive
                                            ) {
                                                otherCheckbox.checked =
                                                    false;
                                            }
                                        }
                                    );
                                }
                            );
                        }
                    );
                }
            );
    }

    navigationButtons.forEach(
        (button) => {
            button.addEventListener(
                'click',
                () => {
                    const targetIndex =
                        sections.findIndex(
                            (section) =>
                                getSectionKey(
                                    section
                                ) ===
                                button.dataset
                                    .surveySectionButton
                        );

                    if (targetIndex < 0) {
                        return;
                    }

                    showSection(
                        targetIndex,
                        true
                    );
                }
            );
        }
    );

    previousButton?.addEventListener(
        'click',
        () => {
            showSection(
                activeIndex - 1,
                true
            );
        }
    );

    nextButton?.addEventListener(
        'click',
        () => {
            if (
                !validateSection(
                    sections[activeIndex]
                )
            ) {
                return;
            }

            showSection(
                activeIndex + 1,
                true
            );
        }
    );

    consentInputs.forEach(
        (consentInput) => {
            synchronizeConsent(
                consentInput
            );

            consentInput.addEventListener(
                'change',
                () => {
                    synchronizeConsent(
                        consentInput
                    );
                }
            );
        }
    );

    form.addEventListener(
        'input',
        (event) => {
            event.target
                .closest(
                    '[data-survey-question]'
                )
                ?.classList.remove(
                    'has-error'
                );
        }
    );

    form.addEventListener(
        'submit',
        (event) => {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            const submitter =
                event.submitter;

            const completing =
                submitter?.value ===
                'complete';

            if (
                completing &&
                !validateCompleteSurvey()
            ) {
                event.preventDefault();
                return;
            }


                        const actionInput =
                document.createElement(
                    'input'
                );

            actionInput.type =
                'hidden';

            actionInput.name =
                'survey_action';

            actionInput.value =
                completing
                    ? 'complete'
                    : 'save';

            form.appendChild(
                actionInput
            );

            isSubmitting =
                true;

            form
                .querySelectorAll(
                    'button'
                )




                .forEach(
                    (button) => {
                        button.disabled =
                            true;
                    }
                );

            if (submitter) {
                submitter.innerHTML = `
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    ${completing
                        ? 'Completing Survey'
                        : 'Saving Progress'
                    }
                `;
            }
        }
    );

    setupExclusiveChoices();

    showSection(
        activeIndex
    );
}

function setupStudentProfileAlert() {
    const alert =
        document.querySelector(
            '[data-student-profile-alert]'
        );

    if (!alert) {
        return;
    }

    const dismissButton =
        alert.querySelector(
            '[data-dismiss-student-profile-alert]'
        );

    let dismissTimer =
        window.setTimeout(
            dismissAlert,
            8000
        );

    dismissButton?.addEventListener(
        'click',
        dismissAlert
    );

    alert.addEventListener(
        'mouseenter',
        () => {
            window.clearTimeout(
                dismissTimer
            );
        }
    );

    alert.addEventListener(
        'mouseleave',
        () => {
            dismissTimer =
                window.setTimeout(
                    dismissAlert,
                    8000
                );
        }
    );

    function dismissAlert() {
        window.clearTimeout(
            dismissTimer
        );

        alert.classList.add(
            'is-dismissing'
        );

        window.setTimeout(
            () => {
                alert.remove();
            },
            260
        );
    }
}
