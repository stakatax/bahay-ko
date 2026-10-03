document.addEventListener(
    'DOMContentLoaded',
    () => {
setupAcademicFlashAlert();
setupAcademicStructureTabs();
setupAcademicLevelGrouping();
setupAcademicDirectoryFilters();
setupAcademicSelectedRecord();
setupAcademicCreateModal();
setupAcademicStatusModal();
    }
);


/* ==========================================
   ACADEMIC FLASH ALERT
========================================== */

function setupAcademicFlashAlert() {
    const flashAlert =
        document.querySelector(
            '[data-academic-flash-alert]'
        );

    if (!flashAlert) {
        return;
    }

    const dismissDelay =
        8000;

    let remainingTime =
        dismissDelay;

    let startedAt =
        Date.now();

    let dismissTimer =
        null;

    startTimer();

    flashAlert.addEventListener(
        'mouseenter',
        pauseTimer
    );

    flashAlert.addEventListener(
        'mouseleave',
        resumeTimer
    );

    flashAlert.addEventListener(
        'focusin',
        pauseTimer
    );

    flashAlert.addEventListener(
        'focusout',
        resumeTimer
    );

    function startTimer() {
        startedAt =
            Date.now();

        dismissTimer =
            window.setTimeout(
                dismissAlert,
                remainingTime
            );
    }

    function pauseTimer() {
        if (dismissTimer === null) {
            return;
        }

        window.clearTimeout(
            dismissTimer
        );

        dismissTimer =
            null;

        remainingTime =
            Math.max(
                0,
                remainingTime -
                    (
                        Date.now() -
                        startedAt
                    )
            );
    }

    function resumeTimer() {
        if (
            dismissTimer !== null ||
            remainingTime <= 0
        ) {
            return;
        }

        startTimer();
    }

    function dismissAlert() {
        dismissTimer =
            null;

        flashAlert.classList.add(
            'is-dismissing'
        );

        flashAlert.addEventListener(
            'transitionend',
            () => {
                flashAlert.remove();
            },
            {
                once:
                    true
            }
        );

        window.setTimeout(
            () => {
                flashAlert.remove();
            },
            400
        );
    }
}



/* ==========================================
   ACADEMIC STRUCTURE TABS
========================================== */

function setupAcademicStructureTabs() {
    const tabList =
        document.querySelector(
            '.academic-structure-tabs'
        );

    if (!tabList) {
        return;
    }

    const tabs =
        Array.from(
            tabList.querySelectorAll(
                '[data-academic-tab]'
            )
        );

    const panels =
        Array.from(
            document.querySelectorAll(
                '[data-academic-panel]'
            )
        );

    if (
        tabs.length === 0 ||
        panels.length === 0
    ) {
        return;
    }

    const availableTabs =
        tabs.map(
            (tab) =>
                tab.dataset.academicTab
        );

    const url =
        new URL(
            window.location.href
        );

      const requestedTab =
        url.searchParams.get(
            'tab'
        ) ||
        url.searchParams.get(
            'type'
        );

    const initialTab =
        availableTabs.includes(
            requestedTab
        )
            ? requestedTab
            : (
                tabs.find(
                    (tab) =>
                        tab.classList.contains(
                            'active'
                        )
                )?.dataset.academicTab ||
                availableTabs[0]
            );

    const activateTab = (
        tabName,
        options = {}
    ) => {
        if (
            !availableTabs.includes(
                tabName
            )
        ) {
            return;
        }

        const {
            moveFocus = false,
            updateUrl = true
        } = options;

        tabs.forEach((tab) => {
            const isActive =
                tab.dataset.academicTab ===
                tabName;

            tab.classList.toggle(
                'active',
                isActive
            );

            tab.setAttribute(
                'aria-selected',
                isActive
                    ? 'true'
                    : 'false'
            );

            tab.tabIndex =
                isActive
                    ? 0
                    : -1;

            if (
                isActive &&
                moveFocus
            ) {
                tab.focus();
            }
        });

        panels.forEach((panel) => {
            const isActive =
                panel.dataset.academicPanel ===
                tabName;

            panel.hidden =
                !isActive;
        });

        if (updateUrl) {
            const nextUrl =
                new URL(
                    window.location.href
                );

            nextUrl.searchParams.set(
                'tab',
                tabName
            );

            window.history.replaceState(
                {},
                '',
                nextUrl
            );
        }
    };

    tabs.forEach(
        (tab, index) => {
            tab.addEventListener(
                'click',
                () => {
                    activateTab(
                        tab.dataset.academicTab
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
                            .academicTab,
                        {
                            moveFocus:
                                true
                        }
                    );
                }
            );
        }
    );

 activateTab(
        initialTab,
        {
            updateUrl:
                requestedTab !==
                initialTab
        }
    );
}


/* ==========================================
   SECTION DEPARTMENT CLUSTERS
========================================== */

function setupAcademicLevelGrouping() {
    const groupedPanelTypes = [
    'academic_program',
    'grade_level',
    'section'
];

    groupedPanelTypes.forEach(
        (entityType) => {
            const panel =
                document.querySelector(
                    `[data-academic-panel="${entityType}"]`
                );

            const list =
                panel?.querySelector(
                    '.academic-structure-list'
                );

            if (
                !panel ||
                !list
            ) {
                return;
            }

            const records =
                Array.from(
                    list.querySelectorAll(
                        '[data-academic-record]'
                    )
                );

            if (records.length === 0) {
                return;
            }

            const educationGroups =
                new Map();

            records.forEach((record) => {
                const educationLevelId =
                    record.dataset
                        .recordEducationLevelId ||
                    '0';

                const educationLevelName =
                    record.dataset
                        .recordEducationLevelName ||
                    'Unassigned Education Level';

                if (
                    !educationGroups.has(
                        educationLevelId
                    )
                ) {
                    educationGroups.set(
                        educationLevelId,
                        {
                            name:
                                educationLevelName,

                            records:
                                [],

                            grades:
                                new Map()
                        }
                    );
                }

                const educationGroup =
                    educationGroups.get(
                        educationLevelId
                    );

                educationGroup.records.push(
                    record
                );

                if (
                    entityType !==
                    'section'
                ) {
                    return;
                }

                const gradeLevelId =
                    record.dataset
                        .recordGradeLevelId ||
                    '0';

                const gradeLevelName =
                    record.dataset
                        .recordGradeLevelName ||
                    'Unassigned Grade or Year';

                if (
                    !educationGroup.grades.has(
                        gradeLevelId
                    )
                ) {
                    educationGroup.grades.set(
                        gradeLevelId,
                        {
                            name:
                                gradeLevelName,

                            records:
                                []
                        }
                    );
                }

                educationGroup.grades
                    .get(
                        gradeLevelId
                    )
                    .records
                    .push(
                        record
                    );
            });

            const fragment =
                document.createDocumentFragment();

            educationGroups.forEach(
                (
                    educationGroup,
                    educationLevelId
                ) => {
                    fragment.appendChild(
                        createEducationHeading(
                            educationLevelId,
                            educationGroup.name,
                            educationGroup.records
                                .length,
                            entityType
                        )
                    );

                    if (
                        entityType ===
                        'section'
                    ) {
                        educationGroup.grades
                            .forEach(
                                (
                                    gradeGroup,
                                    gradeLevelId
                                ) => {
                                    fragment.appendChild(
                                        createGradeHeading(
                                            educationLevelId,
                                            gradeLevelId,
                                            gradeGroup.name,
                                            gradeGroup.records
                                                .length
                                        )
                                    );

                                    gradeGroup.records
                                        .forEach(
                                            (record) => {
                                                fragment.appendChild(
                                                    record
                                                );
                                            }
                                        );
                                }
                            );

                        return;
                    }

                    educationGroup.records
                        .forEach(
                            (record) => {
                                fragment.appendChild(
                                    record
                                );
                            }
                        );
                }
            );

            list.replaceChildren(
                fragment
            );
        }
    );

    function createEducationHeading(
        educationLevelId,
        educationLevelName,
        recordCount,
        entityType
    ) {
        const heading =
            document.createElement(
                'div'
            );

        heading.className =
            'academic-education-group-heading';

        heading.dataset
            .academicEducationHeading =
            educationLevelId;

        const icon =
            document.createElement(
                'span'
            );

        icon.className =
            'academic-education-group-icon';

        icon.innerHTML =
            '<i class="fa-solid fa-layer-group"></i>';

        const copy =
            document.createElement(
                'span'
            );

        copy.className =
            'academic-education-group-copy';

        const title =
            document.createElement(
                'strong'
            );

        title.textContent =
            educationLevelName;

        const description =
            document.createElement(
                'small'
            );

const groupDescriptions = {
    academic_program:
        'Available programs and strands',

    grade_level:
        'Available grade and year levels',

    section:
        'Sections organized by grade or year'
};

description.textContent =
    groupDescriptions[
        entityType
    ] ||
    'Academic records';

        copy.append(
            title,
            description
        );

        const count =
            document.createElement(
                'span'
            );

        count.className =
            'academic-education-group-count';

        count.dataset
            .academicHeadingCount =
            '';

        count.textContent =
            `${recordCount} records`;

        heading.append(
            icon,
            copy,
            count
        );

        return heading;
    }

    function createGradeHeading(
        educationLevelId,
        gradeLevelId,
        gradeLevelName,
        recordCount
    ) {
        const heading =
            document.createElement(
                'div'
            );

        heading.className =
            'academic-grade-group-heading';

        heading.dataset
            .academicGradeHeading =
            gradeLevelId;

        heading.dataset
            .educationLevelId =
            educationLevelId;

        const title =
            document.createElement(
                'strong'
            );

        title.textContent =
            gradeLevelName;

        const count =
            document.createElement(
                'span'
            );

        count.dataset
            .academicHeadingCount =
            '';

        count.textContent =
            `${recordCount} section${
                recordCount === 1
                    ? ''
                    : 's'
            }`;

        heading.append(
            title,
            count
        );

        return heading;
    }
}

/* ==========================================
   ACADEMIC DIRECTORY FILTERS
========================================== */

function setupAcademicDirectoryFilters() {
    const panels =
        Array.from(
            document.querySelectorAll(
                '[data-academic-panel]'
            )
        );

    panels.forEach((panel) => {
        const toolbar =
            panel.querySelector(
                '[data-academic-directory-toolbar]'
            );

        const searchInput =
            panel.querySelector(
                '[data-academic-directory-search]'
            );

        const filterButtons =
            Array.from(
                panel.querySelectorAll(
                    '[data-academic-status-filter]'
                )
            );

        const recordList =
            panel.querySelector(
                '.academic-structure-list'
            );

        const records =
            Array.from(
                panel.querySelectorAll(
                    '[data-academic-record]'
                )
            );

        const educationHeadings =
            Array.from(
                panel.querySelectorAll(
                    '[data-academic-education-heading]'
                )
            );

        const gradeHeadings =
            Array.from(
                panel.querySelectorAll(
                    '[data-academic-grade-heading]'
                )
            );

        const resultCount =
            panel.querySelector(
                '[data-academic-result-count]'
            );

        const noResults =
            panel.querySelector(
                '[data-academic-no-results]'
            );

        if (
            !toolbar ||
            !searchInput ||
            !recordList ||
            records.length === 0
        ) {
            return;
        }

        let selectedStatus =
            'all';

        searchInput.disabled =
            false;

        filterButtons.forEach(
            (button) => {
                button.disabled =
                    false;

                button.addEventListener(
                    'click',
                    () => {
                        selectedStatus =
                            button.dataset
                                .academicStatusFilter ||
                            'all';

                        filterButtons.forEach(
                            (filterButton) => {
                                const isSelected =
                                    filterButton ===
                                    button;

                                filterButton.classList
                                    .toggle(
                                        'active',
                                        isSelected
                                    );

                                filterButton.setAttribute(
                                    'aria-pressed',
                                    String(
                                        isSelected
                                    )
                                );
                            }
                        );

                        applyFilters();
                    }
                );
            }
        );

        searchInput.addEventListener(
            'input',
            applyFilters
        );

        searchInput.addEventListener(
            'search',
            applyFilters
        );

        applyFilters();

        function applyFilters() {
            const searchTerm =
                normalizeSearchValue(
                    searchInput.value
                );

            const hasActiveFilter =
                searchTerm !== '' ||
                selectedStatus !==
                    'all';

            let visibleCount =
                0;

            records.forEach((record) => {
                const recordText =
                    normalizeSearchValue(
                        record.textContent
                    );

                const recordStatus =
                    String(
                        record.dataset
                            .recordStatus ||
                        ''
                    ).toLowerCase();

                const matchesSearch =
                    searchTerm === '' ||
                    recordText.includes(
                        searchTerm
                    );

                const matchesStatus =
                    selectedStatus ===
                        'all' ||
                    recordStatus ===
                        selectedStatus;

                const isVisible =
                    matchesSearch &&
                    matchesStatus;

                record.hidden =
                    !isVisible;

                if (isVisible) {
                    visibleCount +=
                        1;
                }
            });

            educationHeadings.forEach(
                (heading) => {
                    const educationLevelId =
                        heading.dataset
                            .academicEducationHeading ||
                        '0';

                    const groupRecords =
                        records.filter(
                            (record) =>
                                record.dataset
                                    .recordEducationLevelId ===
                                educationLevelId
                        );

                    const groupVisibleCount =
                        groupRecords.filter(
                            (record) =>
                                !record.hidden
                        ).length;

                    heading.hidden =
                        groupVisibleCount ===
                        0;

                    updateHeadingCount(
                        heading,
                        groupVisibleCount,
                        groupRecords.length,
                        hasActiveFilter,
                        'records'
                    );
                }
            );

            gradeHeadings.forEach(
                (heading) => {
                    const educationLevelId =
                        heading.dataset
                            .educationLevelId ||
                        '0';

                    const gradeLevelId =
                        heading.dataset
                            .academicGradeHeading ||
                        '0';

                    const groupRecords =
                        records.filter(
                            (record) =>
                                record.dataset
                                    .recordEducationLevelId ===
                                    educationLevelId &&
                                record.dataset
                                    .recordGradeLevelId ===
                                    gradeLevelId
                        );

                    const groupVisibleCount =
                        groupRecords.filter(
                            (record) =>
                                !record.hidden
                        ).length;

                    heading.hidden =
                        groupVisibleCount ===
                        0;

                    updateHeadingCount(
                        heading,
                        groupVisibleCount,
                        groupRecords.length,
                        hasActiveFilter,
                        'sections'
                    );
                }
            );

            recordList.hidden =
                visibleCount === 0;

            if (noResults) {
                noResults.hidden =
                    visibleCount !== 0;
            }

            if (resultCount) {
                resultCount.textContent =
                    `${visibleCount} of ${records.length} records`;
            }
        }

        function updateHeadingCount(
            heading,
            visibleCount,
            totalCount,
            hasActiveFilter,
            noun
        ) {
            const countElement =
                heading.querySelector(
                    '[data-academic-heading-count]'
                );

            if (!countElement) {
                return;
            }

            const count =
                hasActiveFilter
                    ? visibleCount
                    : totalCount;

            const singularNoun =
                noun === 'sections'
                    ? 'section'
                    : 'record';

            countElement.textContent =
                `${count} ${
                    count === 1
                        ? singularNoun
                        : noun
                }`;
        }
    });

    function normalizeSearchValue(
        value
    ) {
        return String(value || '')
            .toLocaleLowerCase()
            .trim()
            .replace(
                /\s+/g,
                ' '
            );
    }
}

/* ==========================================
   SELECTED ACADEMIC RECORD
========================================== */

function setupAcademicSelectedRecord() {
    const url =
        new URL(
            window.location.href
        );

    const entityType =
        url.searchParams.get(
            'type'
        ) || '';

    const entityId =
        Number.parseInt(
            url.searchParams.get(
                'entity_id'
            ) || '0',
            10
        );

    if (
        entityType === '' ||
        !Number.isInteger(entityId) ||
        entityId <= 0
    ) {
        return;
    }

    const selectedRecord =
        Array.from(
            document.querySelectorAll(
                '[data-academic-record]'
            )
        ).find(
            (record) =>
                record.dataset.entityType ===
                    entityType &&
                Number.parseInt(
                    record.dataset.entityId ||
                        '0',
                    10
                ) === entityId
        );

    if (!selectedRecord) {
        return;
    }

    selectedRecord.classList.add(
        'academic-record-selected'
    );

    selectedRecord.setAttribute(
        'aria-label',
        'Recently updated academic record'
    );

    window.requestAnimationFrame(
        () => {
            selectedRecord.scrollIntoView({
                behavior:
                    window.matchMedia(
                        '(prefers-reduced-motion: reduce)'
                    ).matches
                        ? 'auto'
                        : 'smooth',

                block:
                    'center',

                inline:
                    'nearest'
            });
        }
    );
}


/* ==========================================
   CREATE ACADEMIC RECORD
========================================== */

function setupAcademicCreateModal() {
    const modal =
        document.getElementById(
            'academicCreateModal'
        );

    const form =
        document.getElementById(
            'academicCreateForm'
        );

    const entityInput =
        document.getElementById(
            'academicCreateEntityType'
        );


        const editEntityInput =
        document.getElementById(
            'academicEditEntityId'
        );

    const submitButton =
        document.getElementById(
            'academicModalSubmit'
        );

    const modalTitle =
        document.getElementById(
            'academicCreateModalTitle'
        );

    const modalDescription =
        document.getElementById(
            'academicCreateModalDescription'
        );

    const errorSummary =
        document.getElementById(
            'academicCreateErrorSummary'
        );

    const state =
        document.getElementById(
            'academicCreateState'
        );

     if (
        !modal ||
        !form ||
        !entityInput ||
        !editEntityInput ||
        !submitButton
    ) {
        return;
    }

    const openButtons =
        Array.from(
            document.querySelectorAll(
                '[data-create-academic]'
            )
        );

        const editButtons =
        Array.from(
            document.querySelectorAll(
                '[data-edit-academic]'
            )
        );

    const closeButtons =
        Array.from(
            modal.querySelectorAll(
                '[data-close-academic-modal]'
            )
        );

    const fieldsets =
        Array.from(
            form.querySelectorAll(
                '[data-academic-create-fields]'
            )
        );

     const lockedFields =
        Array.from(
            form.querySelectorAll(
                '[data-locked-on-edit]'
            )
        );

    const gradeSelect =
        document.getElementById(
            'academicSectionGrade'
        );

    const programSelect =
        document.getElementById(
            'academicSectionProgram'
        );

    const entityConfig = {
        department: {
            title:
                'Add School Division',

            description:
                'Create a top-level division for broad academic targeting.'
        },

        education_level: {
            title:
                'Add Education Level',

            description:
                'Create a curriculum level under an active School Division.'
        },

        academic_program: {
            title:
                'Add Program or Strand',

            description:
                'Create a Senior High Strand or College Program.'
        },

        grade_level: {
            title:
                'Add Grade or Year Level',

            description:
                'Create a Grade or Year Level under an active Education Level.'
        },

        section: {
            title:
                'Add Section',

            description:
                'Create the most specific academic targeting group.'
        }
    };

    let lastFocusedElement =
        null;

    let isSubmitting =
        false;

        let currentMode =
        'create';

    openButtons.forEach((button) => {
        button.disabled =
            false;

        button.removeAttribute(
            'title'
        );

        button.addEventListener(
            'click',
            () => {
                openModal(
                    button.dataset
                        .createAcademic
                );
            }
        );
    });

        editButtons.forEach((button) => {
        button.disabled =
            false;

        button.removeAttribute(
            'title'
        );

        button.addEventListener(
            'click',
            () => {
                const recordElement =
                    button.closest(
                        '[data-academic-record]'
                    );

                if (!recordElement) {
                    return;
                }

                const entityType =
                    recordElement.dataset
                        .entityType ||
                    '';

                const entityId =
                    Number.parseInt(
                        recordElement.dataset
                            .entityId ||
                        '0',
                        10
                    );

                let record = {};

                try {
                    record =
                        JSON.parse(
                            recordElement.dataset
                                .academicRecord ||
                            '{}'
                        );
                } catch (error) {
                    console.error(
                        'Unable to read the selected academic record.',
                        error
                    );

                    return;
                }

                openModal(
                    entityType,
                    record,
                    'edit',
                    entityId
                );
            }
        );
    });

    closeButtons.forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                if (!isSubmitting) {
                    closeModal();
                }
            }
        );
    });

    gradeSelect?.addEventListener(
        'change',
        () => {
            filterSectionPrograms();
        }
    );

        lockedFields.forEach((field) => {
        field.addEventListener(
            'mousedown',
            (event) => {
                if (
                    field.getAttribute(
                        'aria-disabled'
                    ) === 'true'
                ) {
                    event.preventDefault();
                }
            }
        );

        field.addEventListener(
            'keydown',
            (event) => {
                if (
                    field.getAttribute(
                        'aria-disabled'
                    ) === 'true' &&
                    event.key !== 'Tab'
                ) {
                    event.preventDefault();
                }
            }
        );

        field.addEventListener(
            'change',
            () => {
                if (
                    field.getAttribute(
                        'aria-disabled'
                    ) === 'true'
                ) {
                    field.value =
                        field.dataset
                            .lockedValue ||
                        '';
                }
            }
        );
    });

    form.querySelectorAll(
        '[data-uppercase]'
    ).forEach((field) => {
        field.addEventListener(
            'input',
            () => {
                const selectionStart =
                    field.selectionStart;

                const selectionEnd =
                    field.selectionEnd;

                field.value =
                    field.value
                        .toUpperCase();

                if (
                    selectionStart !==
                        null &&
                    selectionEnd !==
                        null
                ) {
                    field.setSelectionRange(
                        selectionStart,
                        selectionEnd
                    );
                }
            }
        );
    });

    form.addEventListener(
        'input',
        (event) => {
            const field =
                event.target.closest(
                    'input, select, textarea'
                );

            if (!field) {
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
                    '[aria-invalid="true"]'
                ).length === 0
            ) {
                hideErrorSummary();
            }
        }
    );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            if (
                isSubmitting ||
                !validateForm()
            ) {
                return;
            }

                        const confirmed =
                await requestSubmissionConfirmation();
if (!confirmed) {
    confirmationInput.value =
        '0';

    return;
}

confirmationInput.value =
    '1';

isSubmitting =
    true;

            submitButton.disabled =
                true;

            submitButton.innerHTML =
                currentMode === 'edit'
                    ? `
                      <i class="fa-solid fa-spinner fa-spin"></i>
                      Saving...
                      `
                    : `
                      <i class="fa-solid fa-spinner fa-spin"></i>
                      Creating...
                      `;

            form.submit();
        }
    );

    modal.addEventListener(
        'keydown',
        handleModalKeyboard
    );

    restoreFailedSubmission();

      function openModal(
        entityType,
        oldInput = {},
        mode = 'create',
        entityId = 0
    ) {
        const config =
            entityConfig[entityType];

        if (!config) {
            return;
        }

        lastFocusedElement =
            document.activeElement;

        form.reset();

        clearValidation();

                currentMode =
            mode === 'edit'
                ? 'edit'
                : 'create';

        form.action =
            currentMode === 'edit'
                ? 'index.php?page=academic_update'
                : 'index.php?page=academic_create';

        entityInput.value =
            entityType;

                editEntityInput.value =
            currentMode === 'edit'
                ? Math.max(
                    0,
                    Number.parseInt(
                        entityId,
                        10
                    ) || 0
                )
                : 0;

                modalTitle.textContent =
            currentMode === 'edit'
                ? config.title.replace(
                    'Add',
                    'Edit'
                )
                : config.title;

        modalDescription.textContent =
            currentMode === 'edit'
                ? 'Update this record carefully. Hierarchy changes are validated and permanently audited.'
                : config.description;

        submitButton.disabled =
            false;

        submitButton.innerHTML =
            currentMode === 'edit'
                ? `
                  <i class="fa-solid fa-floppy-disk"></i>
                  Review Update
                  `
                : `
                  <i class="fa-solid fa-floppy-disk"></i>
                  Review Creation
                  `;

        fieldsets.forEach((fieldset) => {
            const isActive =
                fieldset.dataset
                    .academicCreateFields ===
                entityType;

            fieldset.hidden =
                !isActive;

            fieldset.disabled =
                !isActive;

            fieldset.querySelectorAll(
                '[data-required-for]'
            ).forEach((field) => {
                field.required =
                    isActive &&
                    field.dataset
                        .requiredFor ===
                    entityType;
            });
        });

        restoreInputValues(
            oldInput
        );

        if (
            entityType ===
            'section'
        ) {
            filterSectionPrograms(
                oldInput
                    .academic_program_id ||
                ''
            );
        }

                setLockedFields(
            currentMode ===
            'edit'
        );

        modal.hidden =
            false;

        document.body.classList.add(
            'academic-modal-open'
        );

        window.requestAnimationFrame(
            () => {
                const firstField =
                    form.querySelector(
                        'fieldset:not([hidden]) input:not([type="hidden"]), fieldset:not([hidden]) select, fieldset:not([hidden]) textarea'
                    );

                firstField?.focus();
            }
        );
    }

    function closeModal() {
        modal.hidden =
            true;

        document.body.classList.remove(
            'academic-modal-open'
        );

        form.reset();

        clearValidation();

        lastFocusedElement?.focus();

        lastFocusedElement =
            null;
    }

    function restoreInputValues(
        oldInput
    ) {
        if (
            !oldInput ||
            typeof oldInput !==
                'object'
        ) {
            return;
        }

        Object.entries(
            oldInput
        ).forEach(
            ([fieldName, value]) => {
                if (
                    value === null ||
                    value === undefined
                ) {
                    return;
                }

                const field =
                    Array.from(
                        form.elements
                    ).find(
                        (candidate) =>
                            candidate.name ===
                                fieldName &&
                            !candidate.disabled
                    );

                if (field) {
                    field.value =
                        String(value);
                }
            }
        );
    }

        function restoreFailedSubmission() {
        if (!state) {
            return;
        }

        const openForm =
            state.dataset.openForm ||
            '';

        if (
            ![
                'create',
                'edit'
            ].includes(
                openForm
            )
        ) {
            return;
        }

        const entityType =
            state.dataset.entityType ||
            '';

        const entityId =
            Number.parseInt(
                state.dataset.entityId ||
                '0',
                10
            ) || 0;

        if (!entityConfig[entityType]) {
            return;
        }

        let oldInput = {};

        try {
            oldInput =
                JSON.parse(
                    state.dataset.oldInput ||
                    '{}'
                );
        } catch (error) {
            console.error(
                'Unable to restore Academic Management input.',
                error
            );
        }

        openModal(
            entityType,
            oldInput,
            openForm,
            entityId
        );
    }

        function setLockedFields(
        shouldLock
    ) {
        lockedFields.forEach((field) => {
            const isRelevant =
                !field.disabled;

            const isLocked =
                shouldLock &&
                isRelevant;

            field.classList.toggle(
                'academic-field-locked',
                isLocked
            );

            if (isLocked) {
                field.dataset.lockedValue =
                    field.value;

                field.setAttribute(
                    'aria-disabled',
                    'true'
                );

                field.setAttribute(
                    'title',
                    'Hierarchy placement cannot be changed after creation.'
                );
            } else {
                field.removeAttribute(
                    'aria-disabled'
                );

                field.removeAttribute(
                    'title'
                );

                delete field.dataset
                    .lockedValue;
            }

            const label =
                field.closest(
                    'label'
                );

            if (!label) {
                return;
            }

            let note =
                label.querySelector(
                    '.academic-locked-note'
                );

            if (
                isLocked &&
                !note
            ) {
                note =
                    document.createElement(
                        'small'
                    );

                note.className =
                    'academic-locked-note';

                note.innerHTML =
                    `
                    <i class="fa-solid fa-lock"></i>
                    Hierarchy placement is locked after creation.
                    `;

                label.appendChild(
                    note
                );
            }

            if (note) {
                note.hidden =
                    !isLocked;
            }
        });
    }

    function filterSectionPrograms(
        preferredValue = ''
    ) {
        if (
            !gradeSelect ||
            !programSelect
        ) {
            return;
        }

        const selectedGrade =
            gradeSelect
                .selectedOptions[0];

        const educationLevelId =
            selectedGrade?.dataset
                .educationLevelId ||
            '';

        const previousValue =
            preferredValue ||
            programSelect.value;

        Array.from(
            programSelect.options
        ).forEach((option) => {
            if (option.value === '') {
                option.hidden =
                    false;

                option.disabled =
                    false;

                return;
            }

            const isAvailable =
                educationLevelId !== '' &&
                option.dataset
                    .educationLevelId ===
                educationLevelId;

            option.hidden =
                !isAvailable;

            option.disabled =
                !isAvailable;
        });

        const preferredOption =
            Array.from(
                programSelect.options
            ).find(
                (option) =>
                    option.value ===
                        String(
                            previousValue
                        ) &&
                    !option.disabled
            );

        programSelect.value =
            preferredOption
                ? preferredOption.value
                : '';
    }

    function validateForm() {
        clearValidation();

        const activeFields =
            Array.from(
                form.querySelectorAll(
                    'input:not(:disabled), select:not(:disabled), textarea:not(:disabled)'
                )
            );

        const invalidFields =
            activeFields.filter(
                (field) =>
                    !field.checkValidity()
            );

        if (
            invalidFields.length === 0
        ) {
            return true;
        }

        invalidFields.forEach(
            (field) => {
                field.setAttribute(
                    'aria-invalid',
                    'true'
                );

                field.closest(
                    'label'
                )?.classList.add(
                    'field-invalid'
                );
            }
        );

        const firstInvalid =
            invalidFields[0];

        const label =
            firstInvalid
                .closest('label')
                ?.querySelector(
                    ':scope > span'
                )
                ?.textContent
                .replace('*', '')
                .trim() ||
            'Required field';

        if (errorSummary) {
            errorSummary.textContent =
                `${label}: ${firstInvalid.validationMessage}`;

            errorSummary.hidden =
                false;
        }

        firstInvalid.focus();

        return false;
    }

    function clearValidation() {
        form.querySelectorAll(
            '[aria-invalid="true"]'
        ).forEach((field) => {
            field.removeAttribute(
                'aria-invalid'
            );
        });

        form.querySelectorAll(
            '.field-invalid'
        ).forEach((label) => {
            label.classList.remove(
                'field-invalid'
            );
        });

        hideErrorSummary();
    }

    function hideErrorSummary() {
        if (!errorSummary) {
            return;
        }

        errorSummary.hidden =
            true;

        errorSummary.textContent =
            '';
    }

    async function requestSubmissionConfirmation() {
        if (!window.AppDialog) {
            if (errorSummary) {
                errorSummary.textContent =
                    'The confirmation dialog could not be loaded. Refresh the page and try again.';

                errorSummary.hidden =
                    false;
            }

            return false;
        }

        const config =
            entityConfig[
                entityInput.value
            ];

        const entityLabel =
            (
                config?.title ||
                'Academic Record'
            ).replace(
                'Add ',
                ''
            );

        const isEdit =
            currentMode ===
            'edit';

        return window.AppDialog
            .confirm({
                type:
                    isEdit
                        ? 'warning'
                        : 'question',

                title:
                    isEdit
                        ? `Update ${entityLabel}?`
                        : `Create ${entityLabel}?`,

                message:
                    isEdit
                        ? 'This update may affect registration and CMS targeting. The previous and updated values will be permanently audited.'
                        : 'This record will become immediately available to registration and CMS audience targeting.',

                confirmText:
                    isEdit
                        ? 'Save Update'
                        : 'Create Record',

                cancelText:
                    'Review Details'
            });
    }

    function handleModalKeyboard(
        event
    ) {
        if (
            modal.hidden
        ) {
            return;
        }

        if (
            event.key ===
            'Escape' &&
            !isSubmitting
        ) {
            event.preventDefault();

            closeModal();

            return;
        }

        if (
            event.key !==
            'Tab'
        ) {
            return;
        }

        const focusable =
            Array.from(
                modal.querySelectorAll(
                    'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            ).filter(
                (element) =>
                    !element.closest(
                        '[hidden]'
                    )
            );

        if (
            focusable.length === 0
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
}

/* ==========================================
   ACADEMIC RECORD STATUS
========================================== */

function setupAcademicStatusModal() {
    const modal =
        document.getElementById(
            'academicStatusModal'
        );

    const form =
        document.getElementById(
            'academicStatusForm'
        );

    const entityTypeInput =
        document.getElementById(
            'academicStatusEntityType'
        );

    const entityIdInput =
        document.getElementById(
            'academicStatusEntityId'
        );

    const newStatusInput =
        document.getElementById(
            'academicStatusNewStatus'
        );

    const confirmationInput =
    document.getElementById(
        'academicStatusConfirmation'
    );

    const modalTitle =
        document.getElementById(
            'academicStatusModalTitle'
        );

    const modalDescription =
        document.getElementById(
            'academicStatusModalDescription'
        );

    const summaryIcon =
        document.getElementById(
            'academicStatusSummaryIcon'
        );

    const recordNameElement =
        document.getElementById(
            'academicStatusRecordName'
        );

    const transitionElement =
        document.getElementById(
            'academicStatusTransition'
        );

    const impactElement =
        document.getElementById(
            'academicStatusImpact'
        );

    const impactMessage =
        document.getElementById(
            'academicStatusImpactMessage'
        );

    const activeChildrenElement =
        document.getElementById(
            'academicStatusActiveChildren'
        );

    const assignedUsersElement =
        document.getElementById(
            'academicStatusAssignedUsers'
        );

    const reasonField =
        document.getElementById(
            'academicStatusReason'
        );

    const reasonLabel =
        document.getElementById(
            'academicStatusReasonLabel'
        );

    const errorSummary =
        document.getElementById(
            'academicStatusErrorSummary'
        );

    const submitButton =
        document.getElementById(
            'academicStatusSubmit'
        );

    const state =
        document.getElementById(
            'academicCreateState'
        );

    if (
        !modal ||
        !form ||
        !entityTypeInput ||
        !entityIdInput ||
        !newStatusInput ||
!confirmationInput ||
!reasonField ||
        !submitButton
    ) {
        return;
    }

    const statusButtons =
        Array.from(
            document.querySelectorAll(
                '[data-change-academic-status]'
            )
        );

    const closeButtons =
        Array.from(
            modal.querySelectorAll(
                '[data-close-academic-status]'
            )
        );

    const entityLabels = {
        department:
            'School Division',

        education_level:
            'Education Level',

        academic_program:
            'Program or Strand',

        grade_level:
            'Grade or Year Level',

        section:
            'Section'
    };

    const recordNameFields = {
        department:
            'department_name',

        education_level:
            'education_level_name',

        academic_program:
            'academic_program_name',

        grade_level:
            'grade_level_name',

        section:
            'section_name'
    };

    let lastFocusedElement =
        null;

    let activeStatusButton =
        null;

    let isSubmitting =
        false;

    initializeStatusButtons();

    statusButtons.forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                if (button.disabled) {
                    return;
                }

                openStatusModal(
                    button
                );
            }
        );
    });

    closeButtons.forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                if (!isSubmitting) {
                    closeStatusModal();
                }
            }
        );
    });

    reasonField.addEventListener(
        'input',
        () => {
            reasonField.setCustomValidity(
                ''
            );

            clearError();
        }
    );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            if (isSubmitting) {
                return;
            }

            clearError();

            const reason =
                normalizeReason(
                    reasonField.value
                );

            reasonField.value =
                reason;

          if (reason.length < 5) {
    reasonField.setCustomValidity(
        'Provide a reason using at least 5 characters.'
    );

    showError(
        'Provide a reason using at least 5 characters.'
    );

    reasonField.focus();

    return;
}

            reasonField.setCustomValidity(
                ''
            );

            if (!form.checkValidity()) {
                form.reportValidity();

                showError(
                    'Review the required information before continuing.'
                );

                return;
            }

            const confirmed =
                await requestStatusConfirmation();

         if (!confirmed) {
    confirmationInput.value =
        '0';

    return;
}

confirmationInput.value =
    '1';

isSubmitting =
    true;

            submitButton.disabled =
                true;

            submitButton.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Saving Status Change
            `;

            closeButtons.forEach(
                (button) => {
                    button.disabled =
                        true;
                }
            );

            form.submit();
        }
    );

    modal.addEventListener(
        'keydown',
        handleStatusModalKeyboard
    );

    restoreFailedStatusSubmission();

    function initializeStatusButtons() {
        statusButtons.forEach((button) => {
            const newStatus =
                button.dataset.newStatus ||
                '';

            const activeChildren =
                parseCount(
                    button.dataset
                        .activeChildren
                );

            const assignedUsers =
                parseCount(
                    button.dataset
                        .assignedUsers
                );

            const isBlocked =
                newStatus === 'Inactive' &&
                (
                    activeChildren > 0 ||
                    assignedUsers > 0
                );

            button.disabled =
                isBlocked;

            if (isBlocked) {
                const blockers = [];

                if (activeChildren > 0) {
                    blockers.push(
                        `${activeChildren} active child record${
                            activeChildren === 1
                                ? ''
                                : 's'
                        }`
                    );
                }

                if (assignedUsers > 0) {
                    blockers.push(
                        `${assignedUsers} assigned user${
                            assignedUsers === 1
                                ? ''
                                : 's'
                        }`
                    );
                }

                button.title =
                    `Deactivation blocked by ${blockers.join(
                        ' and '
                    )}.`;
            } else {
                button.title =
                    newStatus === 'Inactive'
                        ? 'Deactivate this academic record.'
                        : 'Reactivate this academic record.';
            }
        });
    }

    function openStatusModal(
        button,
        restoredReason = '',
        restoredStatus = ''
    ) {
        const recordElement =
            button.closest(
                '[data-academic-record]'
            );

        if (!recordElement) {
            return;
        }

        const entityType =
            recordElement.dataset
                .entityType ||
            '';

        const entityId =
            Number.parseInt(
                recordElement.dataset
                    .entityId ||
                '0',
                10
            );

        if (
            !entityLabels[entityType] ||
            !Number.isInteger(entityId) ||
            entityId <= 0
        ) {
            return;
        }

        let record = {};

        try {
            record =
                JSON.parse(
                    recordElement.dataset
                        .academicRecord ||
                    '{}'
                );
        } catch (error) {
            console.error(
                'Unable to read the selected academic record.',
                error
            );

            return;
        }

        const currentStatus =
            button.dataset.currentStatus ||
            String(
                record.status ||
                ''
            );

        const requestedStatus =
            restoredStatus ||
            button.dataset.newStatus ||
            '';

        if (
            ![
                'Active',
                'Inactive'
            ].includes(
                requestedStatus
            ) ||
            requestedStatus ===
                currentStatus
        ) {
            return;
        }

        const activeChildren =
            parseCount(
                button.dataset
                    .activeChildren
            );

        const assignedUsers =
            parseCount(
                button.dataset
                    .assignedUsers
            );

        const isBlocked =
            requestedStatus ===
                'Inactive' &&
            (
                activeChildren > 0 ||
                assignedUsers > 0
            );

        if (isBlocked) {
            initializeStatusButtons();

            return;
        }

        const recordNameField =
            recordNameFields[
                entityType
            ];

        const recordName =
            String(
                record[recordNameField] ||
                entityLabels[entityType]
            );

        activeStatusButton =
            button;

        lastFocusedElement =
            document.activeElement;

        entityTypeInput.value =
            entityType;

        entityIdInput.value =
            String(entityId);

        newStatusInput.value =
    requestedStatus;

confirmationInput.value =
    '0';

reasonField.value =
    restoredReason;

        if (recordNameElement) {
            recordNameElement.textContent =
                recordName;
        }

        if (transitionElement) {
            transitionElement.textContent =
                `${currentStatus} → ${requestedStatus}`;
        }

        if (activeChildrenElement) {
            activeChildrenElement.textContent =
                String(activeChildren);
        }

        if (assignedUsersElement) {
            assignedUsersElement.textContent =
                String(assignedUsers);
        }

        const isDeactivation =
            requestedStatus ===
            'Inactive';

        if (modalTitle) {
            modalTitle.textContent =
                isDeactivation
                    ? `Deactivate ${entityLabels[entityType]}`
                    : `Activate ${entityLabels[entityType]}`;
        }

        if (modalDescription) {
            modalDescription.textContent =
                isDeactivation
                    ? 'Confirm that this record should no longer be available for new academic selections.'
                    : 'Confirm that this record should become available for academic selections again.';
        }

        if (reasonLabel) {
            reasonLabel.textContent =
                isDeactivation
                    ? 'Reason for deactivation *'
                    : 'Reason for activation *';
        }

        reasonField.placeholder =
            isDeactivation
                ? 'Explain why this academic record is being deactivated.'
                : 'Explain why this academic record is being reactivated.';

        if (summaryIcon) {
            summaryIcon.innerHTML =
                isDeactivation
                    ? '<i class="fa-solid fa-circle-pause"></i>'
                    : '<i class="fa-solid fa-circle-play"></i>';

            summaryIcon.classList.toggle(
                'success',
                !isDeactivation
            );

            summaryIcon.classList.toggle(
                'danger',
                isDeactivation
            );
        }

        if (impactElement) {
            impactElement.classList.remove(
                'warning'
            );
        }

        if (impactMessage) {
            impactMessage.textContent =
                isDeactivation
                    ? 'No active children or assigned users are blocking this change.'
                    : 'Activation does not remove or relocate existing academic records.';
        }

        submitButton.disabled =
            false;

        submitButton.innerHTML =
            isDeactivation
                ? `
                    <i class="fa-solid fa-circle-pause"></i>
                    Review Deactivation
                `
                : `
                    <i class="fa-solid fa-circle-play"></i>
                    Review Activation
                `;

        submitButton.classList.toggle(
            'danger',
            isDeactivation
        );

        submitButton.classList.toggle(
            'success',
            !isDeactivation
        );

        clearError();

        modal.hidden =
            false;

        document.body.classList.add(
            'academic-modal-open'
        );

        window.requestAnimationFrame(
            () => {
                reasonField.focus();
            }
        );
    }

    function closeStatusModal() {
        modal.hidden =
            true;

        document.body.classList.remove(
            'academic-modal-open'
        );

        form.reset();

        entityTypeInput.value =
            '';

newStatusInput.value =
    '';

confirmationInput.value =
    '0';

reasonField.setCustomValidity(
            ''
        );

        clearError();

        activeStatusButton =
            null;

        lastFocusedElement?.focus();

        lastFocusedElement =
            null;
    }

    function restoreFailedStatusSubmission() {
        if (
            !state ||
            state.dataset.openForm !==
                'status'
        ) {
            return;
        }

        const entityType =
            state.dataset.entityType ||
            '';

        const entityId =
            Number.parseInt(
                state.dataset.entityId ||
                '0',
                10
            );

        if (
            !entityLabels[entityType] ||
            !Number.isInteger(entityId) ||
            entityId <= 0
        ) {
            return;
        }

        let oldInput = {};

        try {
            oldInput =
                JSON.parse(
                    state.dataset.oldInput ||
                    '{}'
                );
        } catch (error) {
            console.error(
                'Unable to restore the Academic Status form.',
                error
            );
        }

        const requestedStatus =
            String(
                oldInput.new_status ||
                ''
            );

        const matchingButton =
            statusButtons.find(
                (button) => {
                    const recordElement =
                        button.closest(
                            '[data-academic-record]'
                        );

                    return (
                        recordElement?.dataset
                            .entityType ===
                            entityType &&
                        Number.parseInt(
                            recordElement.dataset
                                .entityId ||
                            '0',
                            10
                        ) === entityId &&
                        button.dataset
                            .newStatus ===
                            requestedStatus
                    );
                }
            );

        if (!matchingButton) {
            return;
        }

        openStatusModal(
            matchingButton,
            String(
                oldInput.reason ||
                ''
            ),
            requestedStatus
        );
    }

    async function requestStatusConfirmation() {
        if (!window.AppDialog) {
            showError(
                'The confirmation dialog could not be loaded. Refresh the page and try again.'
            );

            return false;
        }

        const isDeactivation =
            newStatusInput.value ===
            'Inactive';

        const entityLabel =
            entityLabels[
                entityTypeInput.value
            ] ||
            'Academic Record';

        const recordName =
            recordNameElement?.textContent
                ?.trim() ||
            entityLabel;

        return window.AppDialog
            .confirm({
                type:
                    isDeactivation
                        ? 'warning'
                        : 'question',

                title:
                    isDeactivation
                        ? `Deactivate ${recordName}?`
                        : `Activate ${recordName}?`,

                message:
                    isDeactivation
                        ? 'This record will become unavailable for new registration, approval placement, and CMS audience targeting. Historical references will remain preserved.'
                        : 'This record will become available again for registration, approval placement, and CMS audience targeting.',

                confirmText:
                    isDeactivation
                        ? 'Deactivate Record'
                        : 'Activate Record',

                cancelText:
                    'Review Details'
            });
    }

    function showError(
        message
    ) {
        if (!errorSummary) {
            return;
        }

        errorSummary.textContent =
            message;

        errorSummary.hidden =
            false;
    }

    function clearError() {
        if (!errorSummary) {
            return;
        }

        errorSummary.textContent =
            '';

        errorSummary.hidden =
            true;
    }

    function normalizeReason(
        value
    ) {
        return String(value || '')
            .trim()
            .replace(
                /\s+/g,
                ' '
            );
    }

    function parseCount(
        value
    ) {
        const parsed =
            Number.parseInt(
                String(value || '0'),
                10
            );

        return Number.isInteger(parsed) &&
            parsed > 0
            ? parsed
            : 0;
    }

    function handleStatusModalKeyboard(
        event
    ) {
        if (modal.hidden) {
            return;
        }

        if (
            event.key === 'Escape' &&
            !isSubmitting
        ) {
            event.preventDefault();

            closeStatusModal();

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable =
            Array.from(
                modal.querySelectorAll(
                    'button:not([disabled]), input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            ).filter(
                (element) =>
                    !element.closest(
                        '[hidden]'
                    )
            );

        if (focusable.length === 0) {
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
}
