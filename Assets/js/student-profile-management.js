document.addEventListener('DOMContentLoaded', () => {
    setupProfileFlash();
    setupProfileModals();
    setupQuestionDirectory();
    setupQuestionEditor();
    setupCycleTargeting();
    setupConfirmationForms();
});

function setupProfileFlash() {
    const flash = document.querySelector('[data-profile-flash]');

    if (!flash) {
        return;
    }

    const dismiss = () => {
        flash.classList.add('leaving');

        window.setTimeout(() => {
            flash.remove();
        }, 220);
    };

    flash
        .querySelector('[data-dismiss-profile-flash]')
        ?.addEventListener('click', dismiss);

    if (!flash.classList.contains('error')) {
        window.setTimeout(dismiss, 8000);
    }
}

function setupProfileModals() {
    const modals = Array.from(
        document.querySelectorAll('[data-profile-modal]')
    );

    let activeModal = null;
    let returnFocus = null;

    const openModal = (name, trigger = null) => {
        const modal = modals.find(
            (item) => item.dataset.profileModal === name
        );

        if (!modal) {
            return;
        }

        returnFocus = trigger || document.activeElement;
        activeModal = modal;
        modal.hidden = false;
        document.body.classList.add('student-profile-modal-open');

        window.requestAnimationFrame(() => {
            modal.classList.add('visible');
            modal
                .querySelector(
                    'input:not([type="hidden"]), select, textarea, button'
                )
                ?.focus();
        });
    };

    const closeModal = (modal = activeModal) => {
        if (!modal) {
            return;
        }

        modal.classList.remove('visible');

        window.setTimeout(() => {
            if (modal.classList.contains('visible')) {
                return;
            }
            modal.hidden = true;

            if (activeModal === modal) {
                activeModal = null;
                document.body.classList.remove(
                    'student-profile-modal-open'
                );
                returnFocus?.focus?.();
            }
        }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 400);
    };

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            openModal(button.dataset.openModal, button);
        });
    });

    document.querySelectorAll('[data-close-profile-modal]').forEach(
        (button) => {
            button.addEventListener('click', () => {
                closeModal(button.closest('[data-profile-modal]'));
            });
        }
    );

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeModal) {
            closeModal();
        }
    });

    window.studentProfileModal = {
        open: openModal,
        close: closeModal
    };
}

function setupQuestionDirectory() {
    const rows = Array.from(
        document.querySelectorAll('[data-question-row]')
    );

    if (rows.length === 0) {
        return;
    }

    const search = document.querySelector('[data-question-search]');
    const section = document.querySelector(
        '[data-question-section-filter]'
    );
    const type = document.querySelector('[data-question-type-filter]');
    const status = document.querySelector(
        '[data-question-status-filter]'
    );
    const count = document.querySelector('[data-question-result-count]');
    const empty = document.querySelector('[data-question-empty]');
    const groups = Array.from(
        document.querySelectorAll('[data-question-group]')
    );

    const applyFilters = () => {
        const query = (search?.value || '').trim().toLowerCase();
        const sectionValue = section?.value || '';
        const typeValue = type?.value || '';
        const statusValue = status?.value || '';
        let visibleCount = 0;

        rows.forEach((row) => {
            const visible =
                (!query || (row.dataset.search || '').includes(query)) &&
                (!sectionValue || row.dataset.section === sectionValue) &&
                (!typeValue || row.dataset.type === typeValue) &&
                (!statusValue || row.dataset.status === statusValue);

            row.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        groups.forEach((group) => {
            const groupRows = Array.from(
                group.querySelectorAll('[data-question-row]')
            );
            const groupVisible = groupRows.filter((row) => !row.hidden);
            group.hidden = groupVisible.length === 0;

            const groupCount = group.querySelector('[data-group-count]');

            if (groupCount) {
                groupCount.textContent = String(groupVisible.length);
            }
        });

        if (count) {
            count.textContent = `${visibleCount} question${
                visibleCount === 1 ? '' : 's'
            }`;
        }

        if (empty) {
            empty.hidden = visibleCount !== 0;
        }
    };

    [search, section, type, status].forEach((control) => {
        control?.addEventListener('input', applyFilters);
        control?.addEventListener('change', applyFilters);
    });
}

function setupQuestionEditor() {
    const form = document.querySelector('[data-question-form]');

    if (!form) {
        return;
    }

    const title = document.querySelector('[data-question-modal-title]');
    const questionId = form.querySelector('[data-question-id]');
    const responseType = form.elements.response_type;
    const optionEditor = form.querySelector('[data-option-editor]');
    const optionList = form.querySelector('[data-option-list]');
    const addOption = form.querySelector('[data-add-option]');
    const sensitive = form.querySelector('[data-sensitive-toggle]');
    const consentField = form.querySelector('[data-consent-field]');
    const consent = form.elements.consent_key;

    const createOption = (value = '') => {
        const row = document.createElement('div');
        row.className = 'student-profile-option-row';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'options[]';
        input.maxLength = 150;
        input.value = value;
        input.placeholder = 'Enter a response option';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'icon-button danger';
        remove.setAttribute('aria-label', 'Remove option');
        remove.innerHTML = '<i class="fa-solid fa-trash"></i>';
        remove.addEventListener('click', () => row.remove());

        row.append(input, remove);
        optionList.append(row);
    };

    const setOptions = (options = []) => {
        optionList.replaceChildren();

        const values = options.length > 0 ? options : ['', ''];
        values.forEach((value) => createOption(String(value)));
    };

    const syncQuestionFields = () => {
        const usesOptions = [
            'SingleChoice',
            'MultipleChoice'
        ].includes(responseType.value);

        optionEditor.hidden = !usesOptions;

        optionList.querySelectorAll('input').forEach((input) => {
            input.disabled = !usesOptions;
            input.required = usesOptions;
        });

        consentField.hidden = !sensitive.checked;
        consent.disabled = !sensitive.checked;
        consent.required = sensitive.checked;
    };

    const resetForCreate = () => {
        form.reset();
        form.action = 'index.php?page=student_profile_question_create';
        questionId.value = '';
        title.textContent = 'Add Question';
        form.dataset.confirmTitle = 'Add this question?';
        setOptions();
        syncQuestionFields();
    };

    document.querySelector('[data-open-question-create]')?.addEventListener(
        'click',
        (event) => {
            resetForCreate();
            window.studentProfileModal?.open('question', event.currentTarget);
        }
    );

    document.querySelectorAll('[data-edit-question]').forEach((button) => {
        button.addEventListener('click', () => {
            let question = {};

            try {
                question = JSON.parse(button.dataset.question || '{}');
            } catch (error) {
                return;
            }

            form.reset();
            form.action = 'index.php?page=student_profile_question_update';
            questionId.value = question.question_id || '';
            form.elements.section_key.value = question.section_key || '';
            form.elements.question_text.value = question.question_text || '';
            form.elements.help_text.value = question.help_text || '';
            responseType.value = question.response_type || 'SingleChoice';
            form.elements.sort_order.value = question.sort_order ?? 0;
            form.elements.is_required.checked = Boolean(
                question.is_required
            );
            form.elements.analytics_enabled.checked = Boolean(
                question.analytics_enabled
            );
            sensitive.checked = Boolean(question.is_sensitive);
            consent.value = question.consent_key || '';
            title.textContent = 'Edit Question';
            form.dataset.confirmTitle = 'Save question changes?';
            setOptions(Array.isArray(question.options) ? question.options : []);
            syncQuestionFields();
            window.studentProfileModal?.open('question', button);
        });
    });

    addOption?.addEventListener('click', () => {
        createOption();
        syncQuestionFields();
        optionList.lastElementChild?.querySelector('input')?.focus();
    });

    responseType?.addEventListener('change', syncQuestionFields);
    sensitive?.addEventListener('change', syncQuestionFields);
    setOptions();
    syncQuestionFields();
}

function setupCycleTargeting() {
    const form =
        document.querySelector(
            '[data-cycle-form]'
        );

    if (!form) {
        return;
    }

    // Reveal a collapsed date field before native validation focuses it.
    form.addEventListener('invalid', (event) => {
        const details = event.target.closest('details');
        if (details) details.open = true;
    }, true);

    const audienceInputs =
        Array.from(
            form.querySelectorAll(
                'input[name="profile_audience"]'
            )
        );

    const scopeTypeInput =
        form.querySelector(
            '[data-cycle-scope-type]'
        );

    const scopeIdInput =
        form.querySelector(
            '[data-cycle-scope-id]'
        );

    if (
        audienceInputs.length === 0 ||
        !scopeTypeInput ||
        !scopeIdInput
    ) {
        return;
    }

    const synchronizeAudience = () => {
        const selectedAudience =
            audienceInputs.find(
                (input) =>
                    input.checked
            );

        if (!selectedAudience) {
            scopeTypeInput.value =
                'AllStudents';

            scopeIdInput.value =
                '0';

            return;
        }

        scopeTypeInput.value =
            selectedAudience.dataset
                .scopeType
            || 'AllStudents';

        scopeIdInput.value =
            selectedAudience.dataset
                .scopeId
            || '0';
    };

    audienceInputs.forEach(
        (input) => {
            input.addEventListener(
                'change',
                synchronizeAudience
            );
        }
    );

    synchronizeAudience();
}

function setupConfirmationForms() {
    document.querySelectorAll('[data-confirm-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const title = form.dataset.confirmTitle || 'Continue?';
            const text = form.dataset.confirmText ||
                'Please confirm this action.';
            let confirmed = false;

            if (window.Swal?.fire) {
                const result = await window.Swal.fire({
                    icon: 'question',
                    title,
                    text,
                    showCancelButton: true,
                    confirmButtonText: 'Confirm',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#8b0000',
                    reverseButtons: true,
                    focusCancel: true
                });

                confirmed = result.isConfirmed;
            } else {
                confirmed = window.confirm(`${title}\n\n${text}`);
            }

            if (!confirmed) {
                return;
            }

            form.querySelectorAll('button[type="submit"]').forEach(
                (button) => {
                    button.disabled = true;
                    button.classList.add('loading');
                }
            );

            HTMLFormElement.prototype.submit.call(form);
        });
    });
}
