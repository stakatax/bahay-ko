document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const intakeForm =
            document.getElementById(
                'governmentAdvisoryIntakeForm'
            );

        if (!intakeForm) {
            return;
        }

        const previewButton =
            document.getElementById(
                'governmentAdvisoryPreviewButton'
            );

        const submitButton =
            document.getElementById(
                'governmentAdvisorySubmitButton'
            );

        const previewPanel =
            document.getElementById(
                'governmentAdvisoryPreview'
            );

        const previewLoading = document.getElementById('governmentAdvisoryLoading');

        const previewError =
            document.getElementById(
                'governmentAdvisoryPreviewError'
            );

        const previewSource =
            document.getElementById(
                'governmentPreviewSource'
            );

        const previewTitle =
            document.getElementById(
                'governmentPreviewTitle'
            );

        const previewScore =
            document.getElementById(
                'governmentPreviewScore'
            );

        const previewType =
            document.getElementById(
                'governmentPreviewType'
            );

        const previewScope =
            document.getElementById(
                'governmentPreviewScope'
            );

        const previewLevel =
            document.getElementById(
                'governmentPreviewLevel'
            );

        const previewRecommendation =
            document.getElementById(
                'governmentPreviewRecommendation'
            );

        const previewReasons =
            document.getElementById(
                'governmentPreviewReasons'
            );

        const previewExcerpt =
            document.getElementById(
                'governmentPreviewExcerpt'
            );

        const sourcePageModeInputs =
            Array.from(
                intakeForm.querySelectorAll(
                    'input[name="source_page_mode"]'
                )
            );

        const advisoryTitleInput =
            document.getElementById(
                'governmentAdvisoryTitle'
            );

        const externalReferenceInput =
            document.getElementById(
                'governmentExternalReference'
            );

        const advisorySummaryInput =
            document.getElementById(
                'governmentAdvisorySummary'
            );

        let previewApproved =
            false;

        /* ======================================
           MESSAGE HELPERS
        ======================================= */

        function showAlert(
            icon,
            title,
            text
        ) {
            if (!window.AppDialog) {
                console.error(
                    `${title}: ${text}`
                );

                return Promise.resolve(
                    false
                );
            }

            return window.AppDialog
                .alert({
                    type:
                        icon,

                    title,

                    message:
                        text,

                    confirmText:
                        'OK',

                    dismissible:
                        true
                });
        }

        async function confirmAction(
            title,
            text,
            confirmText
        ) {
            if (!window.AppDialog) {
                console.error(
                    `${title}: ${text}`
                );

                return false;
            }

            return window.AppDialog
                .confirm({
                    type:
                        'question',

                    title,

                    message:
                        text,

                    confirmText,

                    cancelText:
                        'Cancel',

                    dismissible:
                        true
                });
        }

                /* ======================================
           SOURCE PAGE MODE
        ======================================= */

        function setupSourcePageMode() {
            if (
                sourcePageModeInputs.length ===
                0
            ) {
                return;
            }

            const controlledFields = [
                advisoryTitleInput,
                externalReferenceInput,
                advisorySummaryInput
            ];

            const updateRequirements =
                () => {
                    const selectedMode =
                        sourcePageModeInputs
                            .find(
                                (input) =>
                                    input.checked
                            )
                            ?.value ||
                        'specific';

                    const isReusable =
                        selectedMode ===
                        'reusable';

                    controlledFields.forEach(
                        (field) => {
                            if (!field) {
                                return;
                            }

                            field.required =
                                isReusable;

                            field.setAttribute(
                                'aria-required',
                                isReusable
                                    ? 'true'
                                    : 'false'
                            );

                            const marker =
                                field
                                    .closest(
                                        '.government-advisory-field'
                                    )
                                    ?.querySelector(
                                        '.is-optional'
                                    );

                            if (marker) {
                                marker.textContent =
                                    isReusable
                                        ? 'Required'
                                        : 'Optional';

                                marker.classList.toggle(
                                    'is-required',
                                    isReusable
                                );
                            }
                        }
                    );

                    if (advisorySummaryInput) {
                        if (isReusable) {
                            advisorySummaryInput
                                .setAttribute(
                                    'minlength',
                                    '20'
                                );
                        } else {
                            advisorySummaryInput
                                .removeAttribute(
                                    'minlength'
                                );
                        }
                    }
                };

            sourcePageModeInputs.forEach(
                (input) => {
                    input.addEventListener(
                        'change',
                        () => {
                            updateRequirements();
                            clearPreview();
                        }
                    );
                }
            );

            updateRequirements();
        }

        setupSourcePageMode();



        /* ======================================
           PREVIEW STATE
        ======================================= */

        function clearPreview() {
            previewApproved =
                false;

            if (submitButton) {
                submitButton.disabled =
                    true;
            }

            if (previewPanel) {
                previewPanel.hidden =
                    true;
            }

            if (previewError) {
                previewError.hidden =
                    true;

                previewError.textContent =
                    '';
            }

            if (previewReasons) {
                previewReasons.replaceChildren();
            }
        }

        function setPreviewLoading(
            loading
        ) {
            if (previewLoading) previewLoading.hidden = !loading;
            previewPanel?.setAttribute('aria-busy', String(loading));

            if (previewButton) {
                previewButton.disabled =
                    loading;

                const label =
                    previewButton.querySelector(
                        'span'
                    );

                if (label) {
                    label.textContent =
                        loading
                            ? 'Checking Source...'
                            : 'Check link';
                }
            }

            intakeForm
                .querySelectorAll(
                    'input, textarea, select'
                )
                .forEach(
                    (field) => {
                        field.disabled =
                            loading;
                    }
                );
        }

        function createReasonTag(
            reason
        ) {
            const tag =
                document.createElement(
                    'span'
                );

            const label =
                document.createElement(
                    'span'
                );

            label.textContent =
                reason.rule_name ||
                'Matched rule';

            const adjustment =
                Number(
                    reason.score_adjustment ||
                    0
                );

            const score =
                document.createElement(
                    'strong'
                );

            score.textContent =
                adjustment >= 0
                    ? `+${adjustment}`
                    : String(
                        adjustment
                    );

            tag.append(
                label,
                score
            );

            return tag;
        }

        function renderPreview(
            preview
        ) {
            if (
                !previewPanel ||
                !preview
            ) {
                return;
            }

            previewSource.textContent =
                [
                    preview.agency_code,
                    preview.source_name
                ]
                .filter(Boolean)
                .join(' · ');

            previewTitle.textContent =
                preview.title ||
                'Untitled advisory';

            const score =
                Number(
                    preview.relevance_score ||
                    0
                );

            previewScore.textContent =
                `${score}/100`;

            previewScore.className =
                score >= 70
                    ? 'is-high'
                    : (
                        score >= 40
                            ? 'is-medium'
                            : 'is-low'
                    );

            previewType.textContent =
                preview.advisory_type ||
                'Other';

            previewScope.textContent =
                preview.scope_value ||
                preview.geographic_scope ||
                'Nationwide';

            previewLevel.textContent =
                `${preview.relevance_level || 'Low'} relevance`;

            previewRecommendation.textContent =
                preview.recommendation ||
                '';

            previewReasons.replaceChildren();

            (
                preview.relevance_reasons ||
                []
            ).forEach(
                (reason) => {
                    previewReasons.appendChild(
                        createReasonTag(
                            reason
                        )
                    );
                }
            );

                 const selectedSourcePageMode =
                sourcePageModeInputs
                    .find(
                        (input) =>
                            input.checked
                    )
                    ?.value ||
                'specific';

            const emptyExcerptMessage =
                selectedSourcePageMode ===
                    'reusable'
                ? 'Live source page selected. Review the official page together with the administrator-provided title, reference, and school-context summary.'
                : 'No readable article excerpt was available. Review the official source directly.';

            previewExcerpt.textContent =
                preview.excerpt ||
                emptyExcerptMessage;
            previewPanel.hidden =
                false;

            previewError.hidden =
                true;

            previewApproved =
                true;

            submitButton.disabled =
                false;
        }

                /* ======================================
           INTAKE FORM VALIDATION
        ======================================= */

        async function validateIntakeForm() {
            const sourceUrlInput =
                document.getElementById(
                    'governmentSourceUrl'
                );

            const sourceUrl =
                sourceUrlInput?.value
                    .trim() ||
                '';

            const showValidationError =
                async (
                    field,
                    title,
                    message
                ) => {
                    await showAlert(
                        'warning',
                        title,
                        message
                    );

                    const options = field?.closest('details');
                    if (options) { options.open = true; }
                    field?.focus();

                    return false;
                };

            if (sourceUrl === '') {
                return showValidationError(
                    sourceUrlInput,
                    'Official source required',
                    'Enter the HTTPS address of the official government advisory source.'
                );
            }

            let parsedSourceUrl;

            try {
                parsedSourceUrl =
                    new URL(
                        sourceUrl
                    );
            } catch (error) {
                return showValidationError(
                    sourceUrlInput,
                    'Invalid source address',
                    'Enter a complete official government URL, including https://.'
                );
            }

            if (
                parsedSourceUrl.protocol !==
                'https:'
            ) {
                return showValidationError(
                    sourceUrlInput,
                    'Secure source required',
                    'Government advisory sources must use an HTTPS address.'
                );
            }

            const selectedMode =
                sourcePageModeInputs
                    .find(
                        (input) =>
                            input.checked
                    )
                    ?.value ||
                'specific';

            if (
                selectedMode ===
                'reusable'
            ) {
                if (
                    (
                        advisoryTitleInput
                            ?.value
                            .trim() ||
                        ''
                    ) === ''
                ) {
                    return showValidationError(
                        advisoryTitleInput,
                        'Official title required',
                        'Enter the official title of the advisory shown on this reusable live page.'
                    );
                }

                if (
                    (
                        externalReferenceInput
                            ?.value
                            .trim() ||
                        ''
                    ) === ''
                ) {
                    return showValidationError(
                        externalReferenceInput,
                        'Official reference required',
                        'Enter a unique bulletin, memorandum, proclamation, or advisory reference.'
                    );
                }

                const summary =
                    advisorySummaryInput
                        ?.value
                        .trim() ||
                    '';

                if (summary.length < 20) {
                    return showValidationError(
                        advisorySummaryInput,
                        'School-context summary required',
                        'Explain the possible effect on school operations using at least 20 characters.'
                    );
                }
            }

            return true;
        }


        /* ======================================
           CHECK RELEVANCE
        ======================================= */

        previewButton?.addEventListener(
            'click',
            async () => {
                clearPreview();

                const formIsValid =
                    await validateIntakeForm();

                if (!formIsValid) {
                    return;
                }
                const previewPayload =
                    new FormData(
                        intakeForm
                    );

                setPreviewLoading(
                    true
                );

                try {
                    const response =
                        await fetch(
                            intakeForm.dataset
                                .previewUrl,
                            {
                                method:
                                    'POST',

                                headers: {
                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                body:
                                    previewPayload
                            }
                        );

                    let data;

                    try {
                        data =
                            await response.json();
                    } catch (jsonError) {
                        throw new Error(
                            'The server returned an invalid preview response.'
                        );
                    }

                    if (
                        !response.ok ||
                        data.status !==
                            'success'
                    ) {
                        throw new Error(
                            data.message ||
                            'The advisory could not be checked.'
                        );
                    }

                    renderPreview(
                        data.preview
                    );
                } catch (error) {
                    const options = document.getElementById('governmentAdvisoryOptions');
                    if (options) { options.open = true; }
                    previewError.textContent =
                        error.message ||
                        'Unable to check the government source.';

                    previewError.hidden =
                        false;

                    showAlert(
                        'error',
                        'Source check failed',
                        previewError.textContent
                    );
                } finally {
                    setPreviewLoading(
                        false
                    );
                }
            }
        );

        intakeForm.addEventListener(
            'input',
            () => {
                if (previewApproved) {
                    clearPreview();
                }
            }
        );

        intakeForm.addEventListener(
            'submit',
            async (event) => {
                if (!previewApproved) {
                    event.preventDefault();

                    showAlert(
                        'warning',
                        'Check the source first',
                        'Run the relevance check before adding this advisory to the review queue.'
                    );

                    return;
                }

                event.preventDefault();

                const confirmed =
                    await confirmAction(
                        'Add to review queue?',
                        'The source will be fetched again and stored for administrative review.',
                        'Add Advisory'
                    );

                if (confirmed) {
                    intakeForm.submit();
                }
            }
        );

        /* ======================================
           REVIEW DECISIONS
        ======================================= */

        document
            .querySelectorAll(
                '.government-advisory-review-form'
            )
            .forEach(
                (reviewForm) => {
                    reviewForm.addEventListener(
                        'submit',
                        async (event) => {
                            const submitter =
                                event.submitter;

                            const decision =
                                submitter?.value ||
                                '';

                            const notesInput =
                                reviewForm.querySelector(
                                    '[name="review_notes"]'
                                );

                            const notes =
                                notesInput?.value
                                    .trim() ||
                                '';

                            if (
                                (
                                    decision ===
                                        'Irrelevant' ||
                                    decision ===
                                        'Archived'
                                ) &&
                                (
                                    notes.length <
                                        10 ||
                                    notes
                                        .split(
                                            /\s+/
                                        )
                                        .filter(Boolean)
                                        .length <
                                        2
                                )
                            ) {
                                event.preventDefault();

                                showAlert(
                                    'warning',
                                    'Decision notes required',
                                    'Explain this decision using at least two words and 10 characters.'
                                );

                                notesInput?.focus();

                                return;
                            }

                            event.preventDefault();

                            const confirmed =
                                await confirmAction(
                                    `${decision} advisory?`,
                                    `This advisory will be marked as ${decision.toLowerCase()}.`,
                                    decision
                                );

                            if (!confirmed) {
                                return;
                            }

                            const decisionInput =
                                document.createElement(
                                    'input'
                                );

                            decisionInput.type =
                                'hidden';

                            decisionInput.name =
                                'review_status';

                            decisionInput.value =
                                decision;

                            reviewForm.appendChild(
                                decisionInput
                            );

                            reviewForm.submit();
                        }
                    );
                }
            );
    }
);