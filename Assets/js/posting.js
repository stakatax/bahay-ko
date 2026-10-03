document.addEventListener('DOMContentLoaded', () => {
    'use strict';

        /* ==========================================
       RESPONSIVE LIVE PREVIEW PLACEMENT
    ========================================== */

    const publisherPreviewCard =
        document.getElementById(
            'publisherPreviewCard'
        );

    const publisherMobilePreviewSlot =
        document.getElementById(
            'publisherMobilePreviewSlot'
        );

    const publisherDesktopPreviewSlot =
        document.getElementById(
            'publisherDesktopPreviewSlot'
        );

    const publisherMobilePreviewQuery =
        window.matchMedia(
            '(max-width: 850px)'
        );

    function synchronizePreviewPlacement() {
        if (
            !publisherPreviewCard ||
            !publisherMobilePreviewSlot ||
            !publisherDesktopPreviewSlot
        ) {
            return;
        }

        if (
            publisherMobilePreviewQuery
                .matches
        ) {
            if (
                publisherPreviewCard
                    .parentElement !==
                publisherMobilePreviewSlot
            ) {
                publisherMobilePreviewSlot
                    .appendChild(
                        publisherPreviewCard
                    );
            }

            return;
        }

        if (
            publisherPreviewCard
                .previousElementSibling !==
            publisherDesktopPreviewSlot
        ) {
            publisherDesktopPreviewSlot
                .insertAdjacentElement(
                    'afterend',
                    publisherPreviewCard
                );
        }
    }

    synchronizePreviewPlacement();

    if (
        typeof publisherMobilePreviewQuery
            .addEventListener ===
        'function'
    ) {
        publisherMobilePreviewQuery
            .addEventListener(
                'change',
                synchronizePreviewPlacement
            );
    } else {
        /*
         * Compatibility fallback for older
         * Safari versions.
         */
        publisherMobilePreviewQuery
            .addListener(
                synchronizePreviewPlacement
            );
    }

    /* ==========================================
       FORM AND GENERAL ELEMENTS
    ========================================== */

    const form =
        document.getElementById('postForm');

    if (!form) {
        return;
    }

    const currentUserRole =
    form.dataset.userRole ||
    'Guest';

const isFacultyUser =
    currentUserRole === 'Faculty';

const isAdminUser =
    currentUserRole === 'Admin';

    const typeInput =
        document.getElementById('post_type');

    const typeButtons = Array.from(
        document.querySelectorAll(
            '[data-post-type]:not(:disabled)'
        )
    );

    const contentPanels = Array.from(
        document.querySelectorAll(
            '[data-panel]'
        )
    );

    const publishButton =
        document.getElementById(
            'publishButton'
        );

    const publishButtonText =
        publishButton?.querySelector('span');


        const redundancyOverride =
    document.getElementById(
        'redundancyOverride'
    );

const redundancyOverrideReason =
    document.getElementById(
        'redundancyOverrideReason'
    );

 const validTypes = [
    'announcement',
    'event',
    'document',
    'survey'
];

const contentLabels = {
    announcement: 'Announcement',
    event: 'Event',
    document: 'Document',
    survey: 'Survey'
};

const publishingRequirements =
    document.getElementById(
        'publishingRequirements'
    );

const readinessTitle =
    document.getElementById(
        'readinessTitle'
    );

const readinessDescription =
    document.getElementById(
        'readinessDescription'
    );

const readinessCount =
    document.getElementById(
        'readinessCount'
    );


    /* ==========================================
   PUBLISHING INSPECTOR ELEMENTS
========================================== */

const livePreviewMedia =
    document.getElementById(
        'livePreviewMedia'
    );

const livePreviewImage =
    document.getElementById(
        'livePreviewImage'
    );

const livePreviewType =
    document.getElementById(
        'livePreviewType'
    );

const livePreviewPriority =
    document.getElementById(
        'livePreviewPriority'
    );

const livePreviewAudience =
    document.getElementById(
        'livePreviewAudience'
    );

const livePreviewRelease =
    document.getElementById(
        'livePreviewRelease'
    );

const livePreviewTitle =
    document.getElementById(
        'livePreviewTitle'
    );

const livePreviewDescription =
    document.getElementById(
        'livePreviewDescription'
    );

const inspectorAudience =
    document.getElementById(
        'inspectorAudience'
    );

const inspectorRelease =
    document.getElementById(
        'inspectorRelease'
    );

const inspectorReadiness =
    document.querySelector(
        '.publisher-inspector-placeholder'
    );

const publisherCommandBar =
    document.querySelector(
        '.publisher-command-bar'
    );

const commandReadinessText =
    document.getElementById(
        'commandReadinessText'
    );

    /* ==========================================
       ACADEMIC TARGET ELEMENTS
    ========================================== */
const academicAudienceScopes =
    document.getElementById(
        'academicAudienceScopes'
    );

const addAcademicAudienceScope =
    document.getElementById(
        'addAcademicAudienceScope'
    );

const academicAudienceConfigurationElement =
    document.getElementById(
        'academicAudienceConfiguration'
    );

let academicAudienceConfiguration = {
    current_role: currentUserRole,
    current_department_id: 0,
    initial_scopes: [],
    departments: [],
    education_levels: [],
    academic_programs: [],
    grade_levels: [],
    sections: []
};

if (
    academicAudienceConfigurationElement
) {
    try {
        academicAudienceConfiguration =
            JSON.parse(
                academicAudienceConfigurationElement
                    .textContent ||
                '{}'
            );
    } catch (error) {
        console.error(
            'Unable to read the academic audience configuration.',
            error
        );
    }
}

/*
 * Temporary null aliases keep the old singleton filtering
 * functions harmless until they are removed after the new
 * repeatable renderer is installed.
 */

const educationSelect = null;

const programSelect = null;

const gradeSelect = null;

const sectionSelect = null;

    const roleCheckboxes = Array.from(
        document.querySelectorAll(
            'input[name="target_roles[]"]'
        )
    );

    const audienceScopeInputs = Array.from(
    document.querySelectorAll(
        'input[name="audience_scope"]'
    )
);

const customAudienceFields =
    document.getElementById(
        'customAudienceFields'
    );

const audienceSummaryText =
    document.getElementById(
        'audienceSummaryText'
    );

    /* ==========================================
       ANNOUNCEMENT ELEMENTS
    ========================================== */

    const announcementCategory =
        document.getElementById(
            'announcementCategory'
        );

    const announcementPriority =
        document.getElementById(
            'announcementPriority'
        );

    const acknowledgmentCheckbox =
        document.querySelector(
            'input[name="require_acknowledgment"]'
        );

    const notificationCheckbox =
        document.querySelector(
            'input[name="send_notification"]'
        );

    /* ==========================================
       EVENT ELEMENTS
    ========================================== */

    const eventDateInput =
        document.getElementById(
            'eventDate'
        );

    const eventEndDateInput =
        document.getElementById(
            'eventEndDate'
        );

        /* ==========================================
   SURVEY ELEMENTS
========================================== */

const surveyTitleInput =
    document.getElementById(
        'surveyTitle'
    );

const surveyDescriptionInput =
    document.getElementById(
        'surveyDescription'
    );

const surveyOpensAtInput =
    document.getElementById(
        'surveyOpensAt'
    );

const surveyClosesAtInput =
    document.getElementById(
        'surveyClosesAt'
    );

const surveyQuestionList =
    document.getElementById(
        'surveyQuestionList'
    );

const addSurveyQuestionButton =
    document.getElementById(
        'addSurveyQuestion'
    );

    const surveyQuestionCount =
    document.getElementById(
        'surveyQuestionCount'
    );

let surveyQuestionCounter = 0;

const surveyEditQuestionsElement =
    document.getElementById(
        'surveyEditQuestionsData'
    );

let surveyEditQuestions = [];

if (surveyEditQuestionsElement) {
    try {
        const parsedQuestions =
            JSON.parse(
                surveyEditQuestionsElement
                    .textContent ||
                '[]'
            );

        if (Array.isArray(parsedQuestions)) {
            surveyEditQuestions =
                parsedQuestions;
        }
    } catch (error) {
        console.error(
            'Unable to parse Survey edit questions.',
            error
        );

        surveyEditQuestions = [];
    }
}

    /* ==========================================
       RELEASE ELEMENTS
    ========================================== */

    const releaseOptions = Array.from(
        document.querySelectorAll(
            'input[name="release_mode"]'
        )
    );

    const scheduledReleaseFields =
        document.getElementById(
            'scheduledReleaseFields'
        );

    const scheduledPublishInput =
        document.getElementById(
            'scheduledPublishAt'
        );

    const scheduledReleaseNotice =
    document.getElementById(
        'scheduledReleaseNotice'
    );

const scheduledReleaseNoticeDate =
    document.getElementById(
        'scheduledReleaseNoticeDate'
    );

    const calendarReference =
        document.getElementById(
            'calendarReference'
        );

        const scheduledReleaseCountdown =
    document.getElementById(
        'scheduledReleaseCountdown'
    );

        const calendarReleaseOption =
    document.querySelector(
        '[data-release-option="calendar"]'
    );

const calendarReleaseInput =
    calendarReleaseOption
        ?.querySelector(
            'input[name="release_mode"]'
        );


    const calendarReleaseNotice =
    document.getElementById(
        'calendarReleaseNotice'
    );

    const calendarReleaseNoticeTitle =
    document.getElementById(
        'calendarReleaseNoticeTitle'
    );

const calendarReleaseCountdown =
    document.getElementById(
        'calendarReleaseCountdown'
    );

    /* ==========================================
       WORKFLOW ELEMENTS
    ========================================== */

    const workflowInput =
        document.getElementById(
            'workflowAction'
        );

    const saveDraftButton =
    document.getElementById(
        'saveDraftButton'
    );

const primaryWorkflowAction =
    isFacultyUser
        ? 'submit_review'
        : 'publish';

    const workflowButtons = Array.from(
        document.querySelectorAll(
            '[data-workflow-action]'
        )
    );

    const workflowLabels = {
        draft: 'Save as Draft',
        submit_review: 'Submit for Review',
        publish: 'Publish'
    };

    /* ==========================================
       PREVIEW ELEMENTS
    ========================================== */

    const previewButton =
        document.getElementById(
            'previewPostButton'
        );

    const previewModal =
        document.getElementById(
            'postPreviewModal'
        );

    const closePreviewButton =
        document.getElementById(
            'closePostPreview'
        );

    const returnToEditorButton =
        document.getElementById(
            'returnToEditorButton'
        );

    const previewContentType =
        document.getElementById(
            'previewContentType'
        );

    const previewPriority =
        document.getElementById(
            'previewPriority'
        );

    const previewAudience =
        document.getElementById(
            'previewAudience'
        );

    const previewImageWrap =
        document.getElementById(
            'previewImageWrap'
        );

    const previewImage =
        document.getElementById(
            'previewImage'
        );

    const previewTitle =
        document.getElementById(
            'previewTitle'
        );

    const previewContent =
        document.getElementById(
            'previewContent'
        );

    const previewReleaseMode =
        document.getElementById(
            'previewReleaseMode'
        );

    const previewWorkflow =
        document.getElementById(
            'previewWorkflow'
        );

    const imagePreviewSources =
        new Map();

    /* ==========================================
       ALERT HELPER
    ========================================== */

    function showAlert(
        icon,
        title,
        text
    ) {
        if (
            typeof Swal !== 'undefined'
        ) {
           return Swal.fire({
    icon,
    title,
    text,

    confirmButtonColor:
        '#8B0000',

    returnFocus: false
});
        }

        window.alert(text);

        return Promise.resolve();
    }


    async function runRedundancyPreflight() {
    if (
        !redundancyOverride ||
        !redundancyOverrideReason
    ) {
        showAlert(
            'error',
            'Redundancy check unavailable',
            'The content redundancy controls could not be loaded.'
        );

        return false;
    }

    redundancyOverride.value =
        '0';

    redundancyOverrideReason.value =
        '';

    const type =
        getCurrentType();

    const comparableFields = {
        announcement: [
            'announcement_title',
            'announcement_content'
        ],

        event: [
            'event_title',
            'event_description',
            'event_date'
        ],

        document: [
            'document_title',
            'document_description'
        ],

        survey: [
            'survey_title',
            'survey_description'
        ]
    };

    const payload =
        new FormData();

    payload.append(
        'csrf_token',
        form.querySelector(
            '[name="csrf_token"]'
        )?.value || ''
    );

    payload.append(
        'post_type',
        type
    );

    const editId =
        form.querySelector(
            '[name="edit_id"]'
        )?.value || '';

    if (editId !== '') {
        payload.append(
            'edit_id',
            editId
        );
    }

    (
        comparableFields[type]
        || []
    ).forEach(
        (fieldName) => {
            payload.append(
                fieldName,
                form.querySelector(
                    `[name="${fieldName}"]`
                )?.value || ''
            );
        }
    );

    let response;
    let result;

    try {
        response =
            await fetch(
                'index.php?page=content_redundancy_check',
                {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin',
                    headers: {
                        Accept:
                            'application/json'
                    }
                }
            );

        result =
            await response.json();
    } catch (error) {
        await showAlert(
            'error',
            'Redundancy check failed',
            'The system could not check for similar content. Please try again.'
        );

        return false;
    }

    if (
        !response.ok ||
        !result?.success
    ) {
        await showAlert(
            'error',
            'Redundancy check failed',
            result?.message ||
                'The system could not check for similar content.'
        );

        return false;
    }

    const assessment =
        result.assessment || {};

    const matches =
        Array.isArray(
            assessment.matches
        )
            ? assessment.matches
            : [];

    if (
        assessment.blocked ||
        assessment.level ===
            'blocked'
    ) {
        const match =
            matches[0] || {};

        await showAlert(
            'error',
            'Identical content already exists',
            `${
                match.title ||
                'An existing content item'
            } is already stored as ${
                match.workflow_status ||
                'active content'
            }. Open the existing record instead of creating a duplicate.`
        );

        return false;
    }

    if (
        !assessment
            .requires_confirmation
    ) {
        return true;
    }

    const matchSummary =
        matches.map(
            (match) => {
                const similarity =
                    Number(
                        match.similarity || 0
                    );

                return `${
                    match.title ||
                    'Untitled content'
                } — ${
                    match.workflow_status ||
                    'unknown status'
                } — ${
                    similarity.toFixed(1)
                }% similar`;
            }
        ).join('\n');

    let confirmation;

    if (
        typeof Swal !==
        'undefined'
    ) {
        confirmation =
            await Swal.fire({
                icon: 'warning',
                title:
                    'Possible duplicate content',
                text:
                    'Review these similar records before continuing:\n\n'
                    + matchSummary,
...(isAdminUser
    ? {}
    : {
        input: 'textarea',

        inputLabel:
            'Why should this content still be saved?',

        inputPlaceholder:
            'Example: This is an updated notice for a different schedule.',

        inputAttributes: {
            maxlength: '500'
        }
    }),

                showCancelButton: true,
                confirmButtonText:
                    'Continue Anyway',

                cancelButtonText:
                    'Review Content',

                confirmButtonColor:
                    '#8B0000',

                cancelButtonColor:
                    '#6c757d',

                inputValidator: (
                    value
                ) => {

                    if (isAdminUser) {
    return undefined;
}
                    const reason =
                        String(
                            value || ''
                        ).trim();

                    const wordCount =
                        reason
                            .split(/\s+/)
                            .filter(Boolean)
                            .length;

                    if (
                        reason.length < 10 ||
                        wordCount < 2
                    ) {
                        return 'Enter at least two words and 10 characters.';
                    }

                    return undefined;
                }
            });
    } else {
        window.alert(
            'Possible duplicate content:\n\n'
                + matchSummary
        );

if (isAdminUser) {
    confirmation = {
        isConfirmed:
            window.confirm(
                'Continue despite the similar content warning?'
            ),

        value:
            ''
    };
} else {
    const reason =
        window.prompt(
            'Explain why this content should still be saved:'
        );

    confirmation = {
        isConfirmed:
            reason !== null,

        value:
            reason || ''
    };
}
    }

    if (!confirmation.isConfirmed) {
        return false;
    }

const overrideReason =
    isAdminUser
        ? 'Administrative override after reviewing similar content.'
        : String(
            confirmation.value || ''
        ).trim();

    const overrideWordCount =
        overrideReason
            .split(/\s+/)
            .filter(Boolean)
            .length;

    if (
        overrideReason.length < 10 ||
        overrideWordCount < 2
    ) {
        await showAlert(
            'warning',
            'Reason required',
            'Enter at least two words and 10 characters before continuing.'
        );

        return false;
    }

    redundancyOverride.value =
        '1';

    redundancyOverrideReason.value =
        overrideReason;

    return true;
}

    /* ==========================================
       CONTENT TYPE SWITCHING
    ========================================== */

    function getCurrentType() {
        return validTypes.includes(
            typeInput?.value
        )
            ? typeInput.value
            : 'announcement';
    }

    function activateType(type) {
        if (!validTypes.includes(type)) {
            type = 'announcement';
        }

      if (typeInput) {
    typeInput.value = type;

    if (type === 'survey') {
        form.action =
            'index.php?page=survey_store';
    } else {
        form.action =
            'index.php?page=post_store';
    }

    const isEventType =
        type === 'event';

    if (calendarReleaseOption) {
        calendarReleaseOption.hidden =
            isEventType;
    }

    if (
        isEventType &&
        calendarReleaseInput?.checked
    ) {
        const immediateOption =
            releaseOptions.find(
                (option) =>
                    option.value ===
                    'immediate'
            );

        if (immediateOption) {
            immediateOption.checked =
                true;
        }
    }

    console.log(
        'Current post_type:',
        typeInput.value
    );

    console.log(
        'Current form action:',
        form.action
    );
}

        typeButtons.forEach((button) => {
            const isActive =
                button.dataset.postType ===
                type;

            button.classList.toggle(
                'active',
                isActive
            );

            button.setAttribute(
                'aria-pressed',
                String(isActive)
            );
        });

        contentPanels.forEach((panel) => {
            const isActive =
                panel.dataset.panel ===
                type;

            panel.hidden =
                !isActive;

            panel.classList.toggle(
                'active',
                isActive
            );

            panel
                .querySelectorAll(
                    'input, select, textarea, button'
                )
                .forEach((field) => {
                    field.disabled =
                        !isActive;

                    const requiredType =
                        field.dataset.requiredFor;

                    if (requiredType) {
                        field.required =
                            isActive &&
                            requiredType ===
                                type;
                    }
                });
        });
if (
    type === 'survey' &&
    surveyQuestionList &&
    surveyQuestionList.children.length === 0
) {
    surveyQuestionList.appendChild(
        createSurveyQuestionCard()
    );
}

updateSubmitButton();
updateReleaseFields();
updatePublishingReadiness();
updatePublishingInspector();
    }

    typeButtons.forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                activateType(
                    button.dataset.postType
                );
            }
        );

    });

    /* ==========================================
       ACADEMIC TARGETING DATA
    ========================================== */

    function getRealOptions(select) {
        if (!select) {
            return [];
        }

        return Array.from(
            select.options
        ).filter(
            (option) =>
                option.value !== ''
        );
    }

    const programOptions =
        getRealOptions(
            programSelect
        );

    const gradeOptions =
        getRealOptions(
            gradeSelect
        );

    const sectionOptions =
        getRealOptions(
            sectionSelect
        );

    function getProgramEducation(
        programId
    ) {
        if (!programId) {
            return '';
        }

        const option =
            programOptions.find(
                (item) =>
                    item.value ===
                    programId
            );

        return option?.dataset
            .educationLevel || '';
    }

    function getGradeEducation(
        gradeId
    ) {
        if (!gradeId) {
            return '';
        }

        const option =
            gradeOptions.find(
                (item) =>
                    item.value ===
                    gradeId
            );

        return option?.dataset
            .educationLevel || '';
    }

    function hideOption(
        option,
        shouldHide
    ) {
        option.hidden =
            shouldHide;

        option.disabled =
            shouldHide;
    }

    function clearInvalidSelection(
        select
    ) {
        if (!select) {
            return;
        }

        const selected =
            select.selectedOptions[0];

        if (
            selected &&
            (
                selected.hidden ||
                selected.disabled
            )
        ) {
            select.value = '';
        }
    }

    /* ==========================================
       SMART ACADEMIC FILTERING
    ========================================== */

    function filterAcademicOptions() {
        if (
            !educationSelect ||
            !programSelect ||
            !gradeSelect ||
            !sectionSelect
        ) {
            return;
        }

        const educationId =
            educationSelect.value;

        const programId =
            programSelect.value;

        const gradeId =
            gradeSelect.value;

        programOptions.forEach((option) => {
            const optionEducation =
                option.dataset
                    .educationLevel || '';

            const invalid =
                Boolean(educationId) &&
                optionEducation !==
                    educationId;

            hideOption(
                option,
                invalid
            );
        });

        gradeOptions.forEach((option) => {
            const optionEducation =
                option.dataset
                    .educationLevel || '';

            const invalid =
                Boolean(educationId) &&
                optionEducation !==
                    educationId;

            hideOption(
                option,
                invalid
            );
        });

        sectionOptions.forEach((option) => {
            const sectionGrade =
                option.dataset
                    .gradeLevel || '';

            const sectionProgram =
                option.dataset
                    .program || '';

            const sectionEducation =
                getGradeEducation(
                    sectionGrade
                ) ||
                getProgramEducation(
                    sectionProgram
                );

            const invalidEducation =
                Boolean(educationId) &&
                sectionEducation !==
                    educationId;

            const invalidProgram =
                Boolean(programId) &&
                sectionProgram !==
                    programId;

            const invalidGrade =
                Boolean(gradeId) &&
                sectionGrade !==
                    gradeId;

            hideOption(
                option,
                invalidEducation ||
                    invalidProgram ||
                    invalidGrade
            );
        });

        clearInvalidSelection(
            programSelect
        );

        clearInvalidSelection(
            gradeSelect
        );

        clearInvalidSelection(
            sectionSelect
        );

        const visiblePrograms =
            programOptions.filter(
                (option) =>
                    !option.hidden
            );

        const visibleGrades =
            gradeOptions.filter(
                (option) =>
                    !option.hidden
            );

        const visibleSections =
            sectionOptions.filter(
                (option) =>
                    !option.hidden
            );

        programSelect.disabled =
            visiblePrograms.length === 0;

        gradeSelect.disabled =
            visibleGrades.length === 0;

        sectionSelect.disabled =
            visibleSections.length === 0;
    }

    function handleEducationChange() {
        if (!educationSelect?.value) {
            if (programSelect) {
                programSelect.value = '';
            }

            if (gradeSelect) {
                gradeSelect.value = '';
            }

            if (sectionSelect) {
                sectionSelect.value = '';
            }
        }

        filterAcademicOptions();
    }

    function handleProgramChange() {
        const programId =
            programSelect?.value || '';

        if (programId) {
            const programEducation =
                getProgramEducation(
                    programId
                );

            if (
                programEducation &&
                educationSelect
            ) {
                educationSelect.value =
                    programEducation;
            }

            const gradeEducation =
                getGradeEducation(
                    gradeSelect?.value || ''
                );

            if (
                gradeSelect?.value &&
                gradeEducation !==
                    programEducation
            ) {
                gradeSelect.value = '';
            }
        }

        if (sectionSelect) {
            sectionSelect.value = '';
        }

        filterAcademicOptions();
    }

    function handleGradeChange() {
        const gradeId =
            gradeSelect?.value || '';

        if (gradeId) {
            const gradeEducation =
                getGradeEducation(
                    gradeId
                );

            if (
                gradeEducation &&
                educationSelect
            ) {
                educationSelect.value =
                    gradeEducation;
            }

            const programEducation =
                getProgramEducation(
                    programSelect?.value || ''
                );

            if (
                programSelect?.value &&
                programEducation !==
                    gradeEducation
            ) {
                programSelect.value = '';
            }
        }

        if (sectionSelect) {
            sectionSelect.value = '';
        }

        filterAcademicOptions();
    }

    function handleSectionChange() {
        const selected =
            sectionSelect
                ?.selectedOptions[0];

        if (
            !selected ||
            !selected.value
        ) {
            filterAcademicOptions();
            return;
        }

        const gradeId =
            selected.dataset
                .gradeLevel || '';

        const programId =
            selected.dataset
                .program || '';

        if (
            gradeId &&
            gradeSelect
        ) {
            gradeSelect.value =
                gradeId;
        }

        if (
            programSelect &&
            programId &&
            programId !== '0'
        ) {
            programSelect.value =
                programId;
        } else if (programSelect) {
            programSelect.value = '';
        }

        const educationId =
            getProgramEducation(
                programSelect?.value || ''
            ) ||
            getGradeEducation(
                gradeSelect?.value || ''
            );

        if (
            educationId &&
            educationSelect
        ) {
            educationSelect.value =
                educationId;
        }

        filterAcademicOptions();
    }

    educationSelect?.addEventListener(
        'change',
        handleEducationChange
    );

    programSelect?.addEventListener(
        'change',
        handleProgramChange
    );

    gradeSelect?.addEventListener(
        'change',
        handleGradeChange
    );

    sectionSelect?.addEventListener(
        'change',
        handleSectionChange
    );

    filterAcademicOptions();


    /* ==========================================
   REPEATABLE ACADEMIC AUDIENCE SCOPES
========================================== */

const academicDepartments =
    Array.isArray(
        academicAudienceConfiguration
            .departments
    )
        ? academicAudienceConfiguration
            .departments
        : [];

const academicEducationLevels =
    Array.isArray(
        academicAudienceConfiguration
            .education_levels
    )
        ? academicAudienceConfiguration
            .education_levels
        : [];

const academicPrograms =
    Array.isArray(
        academicAudienceConfiguration
            .academic_programs
    )
        ? academicAudienceConfiguration
            .academic_programs
        : [];

const academicGradeLevels =
    Array.isArray(
        academicAudienceConfiguration
            .grade_levels
    )
        ? academicAudienceConfiguration
            .grade_levels
        : [];

const academicSections =
    Array.isArray(
        academicAudienceConfiguration
            .sections
    )
        ? academicAudienceConfiguration
            .sections
        : [];

const configuredDepartmentId =
    String(
        academicAudienceConfiguration
            .current_department_id ||
        ''
    );

const assignedFacultyScope = academicAudienceConfiguration.faculty_scope || {};

function appendAcademicOptions(
    select,
    records,
    valueKey,
    labelBuilder,
    datasetBuilder = null
) {
    records.forEach((record) => {
        const option =
            document.createElement(
                'option'
            );

        option.value =
            String(
                record[valueKey] ||
                ''
            );

        option.textContent =
            labelBuilder(
                record
            );

        if (datasetBuilder) {
            const dataset =
                datasetBuilder(
                    record
                );

            Object.entries(
                dataset
            ).forEach(
                ([
                    key,
                    value
                ]) => {
                    option.dataset[key] =
                        String(
                            value ||
                            ''
                        );
                }
            );
        }

        select.appendChild(
            option
        );
    });
}

function getAcademicScopeElements(
    row
) {
    return {
        department:
            row.querySelector(
                '[data-academic-field="department_id"]'
            ),

        departmentHidden:
            row.querySelector(
                '[data-academic-department-hidden]'
            ),

        education:
            row.querySelector(
                '[data-academic-field="education_level_id"]'
            ),

        program:
            row.querySelector(
                '[data-academic-field="academic_program_id"]'
            ),

        grade:
            row.querySelector(
                '[data-academic-field="grade_level_id"]'
            ),

        section:
            row.querySelector(
                '[data-academic-field="section_id"]'
            )
    };
}

function findAcademicRecord(
    records,
    key,
    value
) {
    const targetValue =
        String(
            value ||
            ''
        );

    return records.find(
        (record) =>
            String(
                record[key] ||
                ''
            ) ===
            targetValue
    ) || null;
}

function setAcademicOptionVisibility(
    option,
    isVisible
) {
    option.hidden =
        !isVisible;

    option.disabled =
        !isVisible;
}

function clearUnavailableAcademicValue(
    select
) {
    if (
        !select ||
        !select.value
    ) {
        return;
    }

    const selected =
        select.selectedOptions[0];

    if (
        !selected ||
        selected.hidden ||
        selected.disabled
    ) {
        select.value = '';
    }
}

function filterAcademicScopeRow(
    row
) {
    const fields =
        getAcademicScopeElements(
            row
        );

    if (isFacultyUser) {
        for (const [field, key] of [['department', 'department_id'], ['education', 'education_level_id'], ['program', 'academic_program_id']]) {
            if (assignedFacultyScope[key] && fields[field]) {
                fields[field].value = String(assignedFacultyScope[key]);
                fields[field].disabled = true;
            }
        }
    }

    const departmentId =
        fields.department?.value ||
        '';

    Array.from(
        fields.education?.options ||
        []
    ).forEach((option) => {
        if (!option.value) {
            return;
        }

        const matches =
            !departmentId ||
            option.dataset
                .departmentId ===
                departmentId;

        setAcademicOptionVisibility(
            option,
            matches
        );
    });

    clearUnavailableAcademicValue(
        fields.education
    );

    const educationId =
        fields.education?.value ||
        '';

    [
        fields.program,
        fields.grade
    ].forEach((select) => {
        Array.from(
            select?.options ||
            []
        ).forEach((option) => {
            if (!option.value) {
                return;
            }

            const matches =
                !educationId ||
                option.dataset
                    .educationLevelId ===
                    educationId;

            setAcademicOptionVisibility(
                option,
                matches
            );
        });

        clearUnavailableAcademicValue(
            select
        );
    });

    const programId =
        fields.program?.value ||
        '';

    const gradeId =
        fields.grade?.value ||
        '';

    Array.from(
        fields.section?.options ||
        []
    ).forEach((option) => {
        if (!option.value) {
            return;
        }

        const sectionGradeId =
            option.dataset
                .gradeLevelId ||
            '';

        const sectionProgramId =
            option.dataset
                .programId ||
            '';

        const grade =
            findAcademicRecord(
                academicGradeLevels,
                'grade_level_id',
                sectionGradeId
            );

        const sectionEducationId =
            String(
                grade?.education_level_id ||
                ''
            );

        const education =
            findAcademicRecord(
                academicEducationLevels,
                'education_level_id',
                sectionEducationId
            );

        const sectionDepartmentId =
            String(
                education?.department_id ||
                ''
            );

        const matchesDepartment =
            !departmentId ||
            sectionDepartmentId ===
                departmentId;

        const matchesEducation =
            !educationId ||
            sectionEducationId ===
                educationId;

        const matchesProgram =
            !programId ||
            sectionProgramId ===
                programId;

        const matchesGrade =
            !gradeId ||
            sectionGradeId ===
                gradeId;

        setAcademicOptionVisibility(
            option,
            matchesDepartment &&
                matchesEducation &&
                matchesProgram &&
                matchesGrade
        );
    });

    clearUnavailableAcademicValue(
        fields.section
    );
}

function reindexAcademicScopeRows() {
    const rows =
        Array.from(
            academicAudienceScopes
                ?.querySelectorAll(
                    '[data-academic-scope-row]'
                ) ||
            []
        );

    rows.forEach((
        row,
        index
    ) => {
        row.dataset.scopeIndex =
            String(
                index
            );

        const title =
            row.querySelector(
                '[data-academic-scope-title]'
            );

        if (title) {
            title.textContent =
                `Academic Target ${index + 1}`;
        }

        const fields =
            getAcademicScopeElements(
                row
            );

        const isFaculty =
            isFacultyUser &&
            configuredDepartmentId !== '';

        if (fields.department) {
            if (isFaculty) {
                fields.department.disabled =
                    true;

                fields.department.removeAttribute(
                    'name'
                );
            } else {
                fields.department.disabled =
                    false;

                fields.department.name =
                    `audience_scopes[${index}][department_id]`;
            }
        }

        if (fields.departmentHidden) {
            fields.departmentHidden.disabled =
                !isFaculty;

            fields.departmentHidden.name =
                `audience_scopes[${index}][department_id]`;

            fields.departmentHidden.value =
                isFaculty
                    ? configuredDepartmentId
                    : '';
        }

        [
            [
                fields.education,
                'education_level_id'
            ],
            [
                fields.program,
                'academic_program_id'
            ],
            [
                fields.grade,
                'grade_level_id'
            ],
            [
                fields.section,
                'section_id'
            ]
        ].forEach(([
            select,
            fieldName
        ]) => {
            if (select) {
                select.name =
                    `audience_scopes[${index}][${fieldName}]`;
            }
        });
    });

    rows.forEach((row) => {
        const removeButton =
            row.querySelector(
                '[data-remove-academic-scope]'
            );

        if (removeButton) {
            removeButton.disabled =
                rows.length === 1;
        }
    });
}

function createAcademicScopeRow(
    scope = {}
) {
    if (!academicAudienceScopes) {
        return null;
    }

    const row =
        document.createElement(
            'article'
        );

    row.className =
        'academic-audience-scope';

    row.dataset.academicScopeRow =
        '';

    row.innerHTML = `
        <header class="academic-audience-scope-header">
            <div>
                <strong data-academic-scope-title>
                    Academic Target
                </strong>
                <small>
                    Narrow the selected recipient groups.
                </small>
            </div>

            <button
                type="button"
                class="academic-audience-remove"
                data-remove-academic-scope
                aria-label="Remove academic target">
                <i class="fa-solid fa-trash"></i>
                <span>Remove</span>
            </button>
        </header>

        <div class="form-grid publisher-advanced-targeting">
            <div class="form-field">
                <label>Department</label>
                <select data-academic-field="department_id">
                    <option value="">All departments</option>
                </select>
                <input
                    type="hidden"
                    data-academic-department-hidden
                    disabled>
            </div>

            <div class="form-field">
                <label>Education level</label>
                <select data-academic-field="education_level_id">
                    <option value="">All education levels</option>
                </select>
            </div>

            <div class="form-field">
                <label>Program or strand</label>
                <select data-academic-field="academic_program_id">
                    <option value="">All programs or strands</option>
                </select>
            </div>

            <div class="form-field">
                <label>Grade or year level</label>
                <select data-academic-field="grade_level_id">
                    <option value="">All grade or year levels</option>
                </select>
            </div>

            <div class="form-field">
                <label>Section</label>
                <select data-academic-field="section_id">
                    <option value="">All sections</option>
                </select>
            </div>
        </div>
    `;

    const fields =
        getAcademicScopeElements(
            row
        );

    appendAcademicOptions(
        fields.department,
        academicDepartments,
        'department_id',
        (department) =>
            department.department_name ||
            'Department'
    );

    appendAcademicOptions(
        fields.education,
        academicEducationLevels,
        'education_level_id',
        (education) =>
            education.education_level_name ||
            'Education Level',
        (education) => ({
            departmentId:
                education.department_id
        })
    );

    appendAcademicOptions(
        fields.program,
        academicPrograms,
        'academic_program_id',
        (program) => {
            const code =
                String(
                    program.program_code ||
                    ''
                ).trim();

            const name =
                program.program_name ||
                'Program';

            return code
                ? `${code} — ${name}`
                : name;
        },
        (program) => ({
            educationLevelId:
                program.education_level_id
        })
    );

    appendAcademicOptions(
        fields.grade,
        academicGradeLevels,
        'grade_level_id',
        (grade) =>
            grade.grade_level_name ||
            'Grade Level',
        (grade) => ({
            educationLevelId:
                grade.education_level_id
        })
    );

    appendAcademicOptions(
        fields.section,
        academicSections,
        'section_id',
        (section) =>
            section.section_name ||
            'Section',
        (section) => ({
            gradeLevelId:
                section.grade_level_id,

            programId:
                section.academic_program_id
        })
    );

    fields.department.value =
        isFacultyUser
            ? configuredDepartmentId
            : String(
                scope.department_id ||
                ''
            );

    fields.education.value =
        String(
            scope.education_level_id ||
            ''
        );

    fields.program.value =
        String(
            scope.academic_program_id ||
            ''
        );

    fields.grade.value =
        String(
            scope.grade_level_id ||
            ''
        );

    fields.section.value =
        String(
            scope.section_id ||
            ''
        );

    row.addEventListener(
        'change',
        (event) => {
            const changedField =
                event.target?.dataset
                    ?.academicField ||
                '';

            if (changedField === 'department_id') {
                fields.education.value = '';
                fields.program.value = '';
                fields.grade.value = '';
                fields.section.value = '';
            }

            if (changedField === 'education_level_id') {
                fields.program.value = '';
                fields.grade.value = '';
                fields.section.value = '';
            }

            if (
                changedField ===
                    'academic_program_id' &&
                fields.program.value
            ) {
                const program =
                    findAcademicRecord(
                        academicPrograms,
                        'academic_program_id',
                        fields.program.value
                    );

                const education =
                    findAcademicRecord(
                        academicEducationLevels,
                        'education_level_id',
                        program?.education_level_id
                    );

                fields.education.value =
                    String(
                        program?.education_level_id ||
                        ''
                    );

                fields.department.value =
                    String(
                        education?.department_id ||
                        ''
                    );

                fields.section.value = '';
            }

            if (
                changedField ===
                    'grade_level_id' &&
                fields.grade.value
            ) {
                const grade =
                    findAcademicRecord(
                        academicGradeLevels,
                        'grade_level_id',
                        fields.grade.value
                    );

                const education =
                    findAcademicRecord(
                        academicEducationLevels,
                        'education_level_id',
                        grade?.education_level_id
                    );

                fields.education.value =
                    String(
                        grade?.education_level_id ||
                        ''
                    );

                fields.department.value =
                    String(
                        education?.department_id ||
                        ''
                    );

                fields.section.value = '';
            }

            if (
                changedField ===
                    'section_id' &&
                fields.section.value
            ) {
                const section =
                    findAcademicRecord(
                        academicSections,
                        'section_id',
                        fields.section.value
                    );

                const grade =
                    findAcademicRecord(
                        academicGradeLevels,
                        'grade_level_id',
                        section?.grade_level_id
                    );

                const education =
                    findAcademicRecord(
                        academicEducationLevels,
                        'education_level_id',
                        grade?.education_level_id
                    );

                fields.grade.value =
                    String(
                        section?.grade_level_id ||
                        ''
                    );

                fields.program.value =
                    String(
                        section?.academic_program_id ||
                        ''
                    );

                fields.education.value =
                    String(
                        grade?.education_level_id ||
                        ''
                    );

                fields.department.value =
                    String(
                        education?.department_id ||
                        ''
                    );
            }

            filterAcademicScopeRow(
                row
            );

            validateAcademicAudienceScopes(
    false
);

            updateAudienceSummary();
            updatePublishingReadiness();
        }
    );

    row.querySelector(
        '[data-remove-academic-scope]'
    )?.addEventListener(
        'click',
        () => {
            row.remove();

            reindexAcademicScopeRows();
            validateAcademicAudienceScopes(
    false
);
            updateAudienceSummary();
            updatePublishingReadiness();
        }
    );

    academicAudienceScopes.appendChild(
        row
    );

    filterAcademicScopeRow(
        row
    );

    reindexAcademicScopeRows();

    return row;
}

function getAcademicAudienceScopeLabels() {
    return Array.from(
        academicAudienceScopes
            ?.querySelectorAll(
                '[data-academic-scope-row]'
            ) ||
        []
    ).map((row) => {
        const fields =
            getAcademicScopeElements(
                row
            );

        const selectedLabels = [
            fields.department,
            fields.education,
            fields.program,
            fields.grade,
            fields.section
        ]
            .map((select) => {
                const selected =
                    select
                        ?.selectedOptions[0];

                return selected?.value
                    ? selected.text.trim()
                    : '';
            })
            .filter(Boolean);

        return selectedLabels.length > 0
            ? selectedLabels.join(' → ')
            : 'All academic classifications';
    });
}

function setAcademicAudienceValidationMessage(
    message = ''
) {
    const validationMessage =
        document.getElementById(
            'academicAudienceValidationMessage'
        );

    if (!validationMessage) {
        return;
    }

    validationMessage.textContent =
        message;

    validationMessage.hidden =
        message === '';
}

function validateAcademicAudienceScopes(
    showMessage = true
) {
    const rows =
        Array.from(
            academicAudienceScopes
                ?.querySelectorAll(
                    '[data-academic-scope-row]'
                ) ||
            []
        );

    rows.forEach((row) => {
        row.classList.remove(
            'is-duplicate'
        );
    });

    setAcademicAudienceValidationMessage();

    if (
        getAudienceScope() ===
        'schoolwide'
    ) {
        return true;
    }

    if (rows.length === 0) {
        setAcademicAudienceValidationMessage(
            'Add at least one academic target.'
        );

        if (showMessage) {
            showAlert(
                'warning',
                'Academic target required',
                'Add at least one academic target.'
            );
        }

        academicAudienceScopes
            ?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

        return false;
    }

    const scopes =
        rows.map((row) => {
            const fields =
                getAcademicScopeElements(
                    row
                );

            return {
                row,

                values: [
                    fields.department
                        ?.value ||
                        '',

                    fields.education
                        ?.value ||
                        '',

                    fields.program
                        ?.value ||
                        '',

                    fields.grade
                        ?.value ||
                        '',

                    fields.section
                        ?.value ||
                        ''
                ]
            };
        });

    const emptyScopes =
    scopes.filter(
        (scope) =>
            scope.values.every(
                (value) =>
                    value === ''
            )
    );

if (
    scopes.length > 1 &&
    emptyScopes.length > 0
) {
    /*
     * While editing, a newly added blank row remains
     * neutral instead of being reported as overlapping.
     */
    if (!showMessage) {
        return true;
    }

    emptyScopes.forEach((scope) => {
        scope.row.classList.add(
            'is-duplicate'
        );
    });

    setAcademicAudienceValidationMessage(
        'Complete or remove the empty academic target before posting.'
    );

    showAlert(
        'warning',
        'Incomplete academic target',
        'Choose an academic classification or remove the empty target.'
    );

    emptyScopes[0].row.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    return false;
}

    for (
        let firstIndex = 0;
        firstIndex < scopes.length;
        firstIndex += 1
    ) {
        for (
            let secondIndex =
                firstIndex + 1;
            secondIndex <
                scopes.length;
            secondIndex += 1
        ) {
            const first =
                scopes[firstIndex];

            const second =
                scopes[secondIndex];

            const firstContainsSecond =
                first.values.every(
                    (
                        value,
                        index
                    ) =>
                        value === '' ||
                        value ===
                            second.values[index]
                );

            const secondContainsFirst =
                second.values.every(
                    (
                        value,
                        index
                    ) =>
                        value === '' ||
                        value ===
                            first.values[index]
                );

            if (
                !firstContainsSecond &&
                !secondContainsFirst
            ) {
                continue;
            }

            first.row.classList.add(
                'is-duplicate'
            );

            second.row.classList.add(
                'is-duplicate'
            );

            setAcademicAudienceValidationMessage(
                'These academic targets overlap. Remove the duplicate or broader target.'
            );

            if (showMessage) {
                showAlert(
                    'warning',
                    'Overlapping academic targets',
                    'Remove the duplicate or broader target because it already includes the other selection.'
                );
            }

            second.row.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            return false;
        }
    }

    return true;
}

if (academicAudienceScopes) {
    const initialScopes =
        Array.isArray(
            academicAudienceConfiguration
                .initial_scopes
        ) &&
        academicAudienceConfiguration
            .initial_scopes.length > 0
            ? academicAudienceConfiguration
                .initial_scopes
            : [{}];

    initialScopes.forEach(
        (scope) => {
            createAcademicScopeRow(
                scope
            );
        }
    );

    validateAcademicAudienceScopes(
        false
    );
}

addAcademicAudienceScope
    ?.addEventListener(
        'click',
        () => {
            const row =
                createAcademicScopeRow({
                    department_id:
                        isFacultyUser
                            ? configuredDepartmentId
                            : ''
                });

            row?.querySelector(
                '[data-academic-field="department_id"]'
            )?.focus();

            updateAudienceSummary();
            updatePublishingReadiness();
        }
    );
    /* ==========================================
   AUDIENCE SCOPE
========================================== */

function getAudienceScope() {
    return (
        audienceScopeInputs.find(
            (input) => input.checked
        )?.value ||
        'schoolwide'
    );
}


function clearAcademicTargets() {
    [
        educationSelect,
        programSelect,
        gradeSelect,
        sectionSelect
    ].forEach((select) => {
        if (select) {
            select.value = '';
        }
    });
}


function applyAudienceScope() {
    const scope =
        getAudienceScope();

    const isSchoolwide =
        scope === 'schoolwide';

    if (customAudienceFields) {
        customAudienceFields.hidden =
            isSchoolwide;
    }

    if (isSchoolwide) {

        roleCheckboxes.forEach(
            (checkbox) => {
                checkbox.checked = true;
            }
        );

        clearAcademicTargets();

    } else {

        const hasSelectedRole =
            roleCheckboxes.some(
                (checkbox) =>
                    checkbox.checked
            );

        if (!hasSelectedRole) {
            const studentRole =
                roleCheckboxes.find(
                    (checkbox) =>
                        checkbox.value ===
                        'Student'
                );

            if (studentRole) {
                studentRole.checked = true;
            }
        }

        filterAcademicOptions();
    }

    updateAudienceSummary();
    updatePublishingReadiness();
}


function updateAudienceSummary() {
    if (!audienceSummaryText) {
        return;
    }

    audienceSummaryText.textContent =
        getAudienceLabel();
}


audienceScopeInputs.forEach(
    (input) => {
        input.addEventListener(
            'change',
            applyAudienceScope
        );
    }
);

    /* ==========================================
       REQUIRED RECIPIENT ROLE
    ========================================== */

function validateAudience(
    showMessage = true
) {
    if (
        getAudienceScope() ===
        'schoolwide'
    ) {
        return true;
    }

    const selectedRoles =
        roleCheckboxes.filter(
            (checkbox) =>
                checkbox.checked
        );

    if (
        selectedRoles.length === 0
    ) {
        if (showMessage) {
            showAlert(
                'warning',
                'Recipient required',
                'Select at least one recipient group.'
            );
        }

        const audienceSection =
    document.querySelector(
        '.audience-scope-section'
    );

audienceSection
    ?.classList.add(
        'validation-attention'
    );

audienceSection
    ?.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

window.setTimeout(
    () => {
        audienceSection
            ?.classList.remove(
                'validation-attention'
            );
    },
    2200
);

        return false;
    }

    if (
    !validateAcademicAudienceScopes(
        showMessage
    )
) {
    return false;
}

return true;
}

    roleCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener(
            'change',
            () => {
                const checked =
                    roleCheckboxes.filter(
                        (item) =>
                            item.checked
                    );

               if (
    getAudienceScope() ===
        'custom' &&
    checked.length === 0
) {
                    checkbox.checked =
                        true;

                    showAlert(
                        'warning',
                        'Recipient required',
                        'At least one recipient group must remain selected.'
                    );
                }

                updateAudienceSummary();
updatePublishingReadiness();
            }
        );
    });

    /* ==========================================
       DATE HELPERS
    ========================================== */

    function getLocalMinimumDateTime() {
        const now =
            new Date();

        now.setSeconds(0, 0);

        const timezoneOffset =
            now.getTimezoneOffset() *
            60000;

        return new Date(
            now.getTime() -
                timezoneOffset
        )
            .toISOString()
            .slice(0, 16);
    }

    function formatDateTime(value) {
        if (!value) {
            return '';
        }

        const date =
            new Date(value);

        if (
            Number.isNaN(
                date.getTime()
            )
        ) {
            return '';
        }

        return new Intl.DateTimeFormat(
            'en-US',
            {
                month: 'long',
                day: '2-digit',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            }
        ).format(date);
    }

    function updateDateMinimums() {
        const minimum =
            getLocalMinimumDateTime();

        if (eventDateInput) {
            eventDateInput.min =
                minimum;
        }

        if (scheduledPublishInput) {
            scheduledPublishInput.min =
                minimum;
        }
    }

    updateDateMinimums();

    eventDateInput?.addEventListener(
        'change',
        () => {
            if (!eventEndDateInput) {
                return;
            }

            eventEndDateInput.min =
                eventDateInput.value;

            if (
                eventEndDateInput.value &&
                eventEndDateInput.value <=
                    eventDateInput.value
            ) {
                eventEndDateInput.value =
                    '';
            }
        }
    );

    eventEndDateInput?.addEventListener(
        'change',
        () => {
            if (
                !eventDateInput?.value ||
                !eventEndDateInput.value
            ) {
                return;
            }

            if (
                eventEndDateInput.value <=
                eventDateInput.value
            ) {
                eventEndDateInput.value =
                    '';

                showAlert(
                    'error',
                    'Invalid event schedule',
                    'The event end date must be later than the start date.'
                );
            }
        }
    );

    /* ==========================================
       RELEASE MODE
    ========================================== */

    function getReleaseMode() {
        return (
            releaseOptions.find(
                (option) =>
                    option.checked
            )?.value ||
            'immediate'
        );
    }


    function formatReleaseCountdown(
    targetDate
) {
    const difference =
        targetDate.getTime() -
        Date.now();

    if (difference <= 0) {
        return isFacultyUser
            ? 'Requested release time has passed'
            : 'Release time reached';
    }

    const totalSeconds =
        Math.floor(
            difference / 1000
        );

    const days =
        Math.floor(
            totalSeconds / 86400
        );

    const hours =
        Math.floor(
            (
                totalSeconds % 86400
            ) / 3600
        );

    const minutes =
        Math.floor(
            (
                totalSeconds % 3600
            ) / 60
        );

    const seconds =
        totalSeconds % 60;

    const prefix =
        isFacultyUser
            ? 'Requested release in'
            : 'Releases in';

    if (days > 0) {
        return `${prefix} ${days} day${
            days === 1 ? '' : 's'
        } ${hours} hr`;
    }

    if (hours > 0) {
        return `${prefix} ${hours} hr ${minutes} min`;
    }

    if (minutes > 0) {
        return `${prefix} ${minutes} min ${seconds} sec`;
    }

    return `${prefix} ${seconds} sec`;
}

   function updateScheduledReleaseNotice() {
    if (
        !scheduledPublishInput ||
        !scheduledReleaseNoticeDate
    ) {
        return;
    }

    const rawValue =
        scheduledPublishInput.value;

    if (rawValue === '') {
        scheduledReleaseNoticeDate
            .innerHTML =
            'Select a release date and time';

        if (scheduledReleaseCountdown) {
            scheduledReleaseCountdown
                .textContent =
                'Waiting for schedule';
        }

        return;
    }

    const parsedDate =
        new Date(
            rawValue
        );

    if (
        Number.isNaN(
            parsedDate.getTime()
        )
    ) {
        scheduledReleaseNoticeDate
            .textContent =
            'Invalid release date and time';

        if (scheduledReleaseCountdown) {
            scheduledReleaseCountdown
                .textContent =
                'Invalid schedule';
        }

        return;
    }

    const dateLabel =
        parsedDate.toLocaleDateString(
            undefined,
            {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }
        );

    const timeLabel =
        parsedDate.toLocaleTimeString(
            undefined,
            {
                hour: 'numeric',
                minute: '2-digit'
            }
        );

    scheduledReleaseNoticeDate.innerHTML =
        `
            <span>${dateLabel}</span>
            <span>${timeLabel}</span>
        `;

    if (scheduledReleaseCountdown) {
        scheduledReleaseCountdown
            .textContent =
            formatReleaseCountdown(
                parsedDate
            );
    }
}

    function updateReleaseFields() {
        const mode =
            getReleaseMode();

        const isScheduled =
            mode === 'scheduled';

        const isCalendar =
            mode === 'calendar';


        if (scheduledReleaseNotice) {
    scheduledReleaseNotice.hidden =
        !isScheduled;
}

if (calendarReleaseNotice) {
    calendarReleaseNotice.hidden =
        !isCalendar;
}

        if (calendarReleaseNotice) {
    calendarReleaseNotice.hidden =
        !isCalendar;
}

        if (scheduledReleaseFields) {
            scheduledReleaseFields.hidden =
                !isScheduled &&
                !isCalendar;
        }

        if (scheduledPublishInput) {
            scheduledPublishInput.disabled =
                !isScheduled;

            scheduledPublishInput.required =
                isScheduled;

            if (!isScheduled) {
                scheduledPublishInput.value =
                    '';
            }
        }

        if (calendarReference) {
            calendarReference.disabled =
                !isCalendar;

            calendarReference.required =
                isCalendar;

            if (!isCalendar) {
                calendarReference.value =
                    '';
            }
        }

  updateScheduledReleaseNotice();

setInterval(
    () => {
        const releaseMode =
            getReleaseMode();

        if (
            releaseMode ===
            'scheduled'
        ) {
            updateScheduledReleaseNotice();
        }

        if (
            releaseMode ===
            'calendar'
        ) {
            updateCalendarReleaseNotice();
        }
    },
    1000
);
updateCalendarReleaseNotice();

        updateSubmitButton();
    }

releaseOptions.forEach((option) => {
    option.addEventListener(
        'change',
        updateReleaseFields
    );
});

calendarReference?.addEventListener(
    'change',
    updateCalendarReleaseNotice
);

scheduledPublishInput?.addEventListener(
    'input',
    updateScheduledReleaseNotice
);

scheduledPublishInput?.addEventListener(
    'change',
    updateScheduledReleaseNotice
);

updateReleaseFields();

function updateCalendarReleaseNotice() {
    if (
        !calendarReleaseNotice ||
        !calendarReference
    ) {
        return;
    }

    const selectedOption =
        calendarReference.options[
            calendarReference.selectedIndex
        ];

    const eventTitle =
        selectedOption?.dataset
            .eventTitle ||
        '';

    const eventDate =
        selectedOption?.dataset
            .eventDate ||
        '';

    if (calendarReleaseNoticeTitle) {
        calendarReleaseNoticeTitle
            .textContent =
            eventTitle !== ''
                ? eventTitle
                : 'Select an event';
    }

    if (eventDate === '') {
        if (calendarReleaseNoticeDate) {
            calendarReleaseNoticeDate
                .textContent =
                'Select an event to view its schedule.';
        }

        if (calendarReleaseCountdown) {
            calendarReleaseCountdown
                .textContent =
                'Waiting for event selection';
        }

        return;
    }

    const parsedDate =
        new Date(
            eventDate.replace(
                ' ',
                'T'
            )
        );

    if (
        Number.isNaN(
            parsedDate.getTime()
        )
    ) {
        if (calendarReleaseNoticeDate) {
            calendarReleaseNoticeDate
                .textContent =
                'Invalid event schedule';
        }

        if (calendarReleaseCountdown) {
            calendarReleaseCountdown
                .textContent =
                'Unable to calculate event time';
        }

        return;
    }

    const dateLabel =
        parsedDate.toLocaleDateString(
            undefined,
            {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }
        );

    const timeLabel =
        parsedDate.toLocaleTimeString(
            undefined,
            {
                hour: 'numeric',
                minute: '2-digit'
            }
        );

    if (calendarReleaseNoticeDate) {
        calendarReleaseNoticeDate
            .innerHTML =
            `
                <span>${dateLabel}</span>
                <span>${timeLabel}</span>
            `;
    }

    if (calendarReleaseCountdown) {
        calendarReleaseCountdown
            .textContent =
            formatCalendarCountdown(
                parsedDate
            );
    }
}



updateCalendarReleaseNotice();

function formatCalendarCountdown(
    targetDate
) {
    const difference =
        targetDate.getTime() -
        Date.now();

    if (difference <= 0) {
        return isFacultyUser
            ? 'Linked event has started'
            : 'Event has started';
    }

    const totalSeconds =
        Math.floor(
            difference / 1000
        );

    const days =
        Math.floor(
            totalSeconds / 86400
        );

    const hours =
        Math.floor(
            (
                totalSeconds % 86400
            ) / 3600
        );

    const minutes =
        Math.floor(
            (
                totalSeconds % 3600
            ) / 60
        );

    const seconds =
        totalSeconds % 60;

    const prefix =
        isFacultyUser
            ? 'Linked event starts in'
            : 'Event starts in';

    if (days > 0) {
        return `${prefix} ${days} day${
            days === 1
                ? ''
                : 's'
        } ${hours} hr`;
    }

    if (hours > 0) {
        return `${prefix} ${hours} hr ${minutes} min`;
    }

    if (minutes > 0) {
        return `${prefix} ${minutes} min ${seconds} sec`;
    }

    return `${prefix} ${seconds} sec`;
}

/* ==========================================
   SURVEY QUESTION BUILDER
========================================== */

function createSurveyQuestionCard() {
    surveyQuestionCounter += 1;

    const index =
        surveyQuestionCounter - 1;

    const card =
        document.createElement(
            'article'
        );

    card.className =
        'survey-question-card';

    card.dataset.questionIndex =
        String(index);

    card.innerHTML = `
   <div class="survey-question-card-header">

    <div class="survey-question-identity">

        <span class="survey-question-index">
            ${String(
                surveyQuestionCounter
            ).padStart(2, '0')}
        </span>

        <div>
            <small>
                Survey Question
            </small>

            <strong>
                Question ${surveyQuestionCounter}
            </strong>
        </div>

    </div>

            <button
                type="button"
                class="survey-question-remove"
                data-remove-survey-question
                aria-label="Remove question">

              <i class="fa-regular fa-trash-can"></i>

<span>Remove</span>
            </button>

        </div>

        <div class="form-field">

            <label>
                Question text

                <span class="required-mark">
                    *
                </span>
            </label>

            <input
                type="text"
                name="questions[${index}][question]"
                maxlength="2000"
                placeholder="Enter the survey question"
                required>

        </div>

        <div class="form-grid">

            <div class="form-field">

                <label>
                    Question type
                </label>

                <select
                    name="questions[${index}][question_type]"
                    data-survey-question-type>

                    <option value="Short Text">
                        Short Text
                    </option>

                    <option value="Long Text">
                        Long Text
                    </option>

                    <option value="Multiple Choice">
                        Multiple Choice
                    </option>

                    <option value="Checkbox">
                        Checkbox
                    </option>

                    <option value="Rating">
                        Rating
                    </option>

                    <option value="Yes/No">
                        Yes / No
                    </option>

                </select>

            </div>

            <div class="form-field">

                <label>
                    Required response
                </label>

                <label class="publisher-toggle-card">

                    <input
                        type="checkbox"
                        name="questions[${index}][is_required]"
                        value="1"
                        checked>

                    <span class="publisher-toggle-control"></span>

                    <div>
                        <strong>
                            Required
                        </strong>

                        <small>
                            Recipient must answer this question
                        </small>
                    </div>

                </label>

            </div>

        </div>

        <div
            class="survey-choice-builder"
            data-survey-choice-builder
            hidden>

            <div class="publisher-subsection-heading">

                <div>
                    <h4>
                        Answer choices
                    </h4>

                    <p>
                        Add the available options.
                    </p>
                </div>

            </div>

            <div
                class="survey-choice-list"
                data-survey-choice-list>
            </div>

            <button
                type="button"
                class="app-button secondary"
                data-add-survey-choice>

                <i class="fa-solid fa-plus"></i>

                Add Choice
            </button>

        </div>

       <div
    class="survey-rating-builder"
    data-survey-rating-builder
    hidden>

    <div class="form-field">

        <label>
            Rating scale
        </label>

        <div class="survey-fixed-rating-scale">

            <span>
                1
            </span>

            <i class="fa-solid fa-arrow-right"></i>

            <span>
                5
            </span>

            <small>
                Fixed 1–5 rating scale
            </small>

        </div>

        <input
            type="hidden"
            name="questions[${index}][rating_min]"
            value="1">

        <input
            type="hidden"
            name="questions[${index}][rating_max]"
            value="5">

    </div>

</div>

        </div>
    `;

    return card;
}

function populateSurveyQuestionCard(
    card,
    questionData = {}
) {
    if (!card) {
        return;
    }

    const questionInput =
        card.querySelector(
            'input[name*="[question]"]'
        );

    const questionTypeSelect =
        card.querySelector(
            '[data-survey-question-type]'
        );

    const requiredInput =
        card.querySelector(
            'input[name*="[is_required]"]'
        );

    const ratingMinInput =
        card.querySelector(
            'input[name*="[rating_min]"]'
        );

    const ratingMaxInput =
        card.querySelector(
            'input[name*="[rating_max]"]'
        );

    const choiceList =
        card.querySelector(
            '[data-survey-choice-list]'
        );

    if (questionInput) {
        questionInput.value =
            String(
                questionData.question
                    ?? ''
            );
    }

    if (questionTypeSelect) {
        const savedType =
            String(
                questionData.question_type
                    ?? 'Short Text'
            );

        const supportedType =
            Array.from(
                questionTypeSelect.options
            ).some(
                (option) =>
                    option.value ===
                    savedType
            );

        questionTypeSelect.value =
            supportedType
                ? savedType
                : 'Short Text';
    }

    if (requiredInput) {
        requiredInput.checked =
            questionData.is_required === true ||
            String(
                questionData.is_required
                    ?? ''
            ) === '1';
    }

    if (ratingMinInput) {
        ratingMinInput.value =
            String(
                questionData.rating_min
                    ?? 1
            );
    }

    if (ratingMaxInput) {
        ratingMaxInput.value =
            String(
                questionData.rating_max
                    ?? 5
            );
    }

    if (choiceList) {
        choiceList.innerHTML = '';
    }

    const choices =
        Array.isArray(
            questionData.choices
        )
            ? questionData.choices
            : [];

    choices.forEach((choice) => {
        const choiceText =
            typeof choice === 'object' &&
            choice !== null
                ? (
                    choice.choice_text
                    ?? ''
                )
                : choice;

        if (
            String(choiceText)
                .trim() !== ''
        ) {
            addSurveyChoice(
                card,
                String(choiceText)
            );
        }
    });

    updateSurveyQuestionType(
        card
    );
}



function renumberSurveyQuestions() {
    if (!surveyQuestionList) {
        return;
    }

    const cards =
        surveyQuestionList.querySelectorAll(
            '.survey-question-card'
        );

    cards.forEach(
        (card, index) => {
            card.dataset.questionIndex =
                String(index);

            const numberLabel =
                card.querySelector(
                    '.survey-question-card-header strong'
                );

            if (numberLabel) {
                numberLabel.textContent =
                    `Question ${index + 1}`;
            }

            const indexBadge =
    card.querySelector(
        '.survey-question-index'
    );

if (indexBadge) {
    indexBadge.textContent =
        String(
            index + 1
        ).padStart(
            2,
            '0'
        );
}

            const fields =
                card.querySelectorAll(
                    '[name]'
                );

            fields.forEach(
                (field) => {
                    field.name =
                        field.name.replace(
                            /questions\[\d+\]/,
                            `questions[${index}]`
                        );
                }
            );
        }
    );

    surveyQuestionCounter =
        cards.length;

    updateSurveyQuestionCount();
updateSurveyRemoveButtons();
updatePublishingReadiness();
updatePublishingInspector();
}




function updateSurveyQuestionCount() {
    if (
        !surveyQuestionCount ||
        !surveyQuestionList
    ) {
        return;
    }

    const count =
        surveyQuestionList
            .querySelectorAll(
                '.survey-question-card'
            )
            .length;

    surveyQuestionCount.textContent =
        `${count} ${
            count === 1
                ? 'Question'
                : 'Questions'
        }`;
}


function updateSurveyRemoveButtons() {
    if (!surveyQuestionList) {
        return;
    }

    const cards =
        Array.from(
            surveyQuestionList
                .querySelectorAll(
                    '.survey-question-card'
                )
        );

    cards.forEach((card) => {
        const button =
            card.querySelector(
                '[data-remove-survey-question]'
            );

        if (!button) {
            return;
        }

        button.disabled =
            cards.length <= 1;

        button.title =
            cards.length <= 1
                ? 'A survey must contain at least one question.'
                : 'Remove this question';
    });
}


function addSurveyChoice(
    card,
    value = ''
) {
    if (!card) {
        return;
    }

    const choiceList =
        card.querySelector(
            '[data-survey-choice-list]'
        );

    if (!choiceList) {
        return;
    }

    const questionIndex =
        Number(
            card.dataset.questionIndex
            ?? 0
        );

    const row =
        document.createElement(
            'div'
        );

    row.className =
        'survey-choice-row';

   row.innerHTML = `
    <span class="survey-choice-number">
        ${choiceList.children.length + 1}
    </span>

    <span class="survey-choice-drag">
        <i class="fa-solid fa-grip-vertical"></i>
    </span>

    <input
        type="text"
        name="questions[${questionIndex}][choices][]"
        maxlength="255"
        placeholder="Enter answer choice"
        value="">

    <button
        type="button"
        class="survey-choice-remove"
        data-remove-survey-choice
        aria-label="Remove choice">

        <i class="fa-solid fa-xmark"></i>

    </button>
`;

    const input =
        row.querySelector(
            'input'
        );

    if (input) {
        input.value =
            String(value);
    }

    choiceList.appendChild(
        row
    );
}

function updateSurveyQuestionType(
    card
) {
    if (!card) {
        return;
    }

    const typeSelect =
        card.querySelector(
            '[data-survey-question-type]'
        );

    const choiceBuilder =
        card.querySelector(
            '[data-survey-choice-builder]'
        );

    const ratingBuilder =
        card.querySelector(
            '[data-survey-rating-builder]'
        );

    if (
        !typeSelect ||
        !choiceBuilder ||
        !ratingBuilder
    ) {
        return;
    }

    const type =
        typeSelect.value;

    const usesChoices =
        type === 'Multiple Choice' ||
        type === 'Checkbox';

    const usesRating =
        type === 'Rating';

    choiceBuilder.hidden =
        !usesChoices;

    ratingBuilder.hidden =
        !usesRating;

    const choiceInputs =
        choiceBuilder.querySelectorAll(
            'input'
        );

    choiceInputs.forEach(
        (input) => {
            input.disabled =
                !usesChoices;
        }
    );

    const ratingInputs =
        ratingBuilder.querySelectorAll(
            'input'
        );

    ratingInputs.forEach(
        (input) => {
            input.disabled =
                !usesRating;
        }
    );

    if (usesChoices) {
        const choiceList =
            card.querySelector(
                '[data-survey-choice-list]'
            );

        if (
            choiceList &&
            choiceList.children.length === 0
        ) {
            addSurveyChoice(card);
            addSurveyChoice(card);
        }
    }
}


    /* ==========================================
       EMERGENCY ANNOUNCEMENT RULES
    ========================================== */

    function applyEmergencyRules() {
        if (
            getCurrentType() !==
            'announcement'
        ) {
            return;
        }

        const category =
            announcementCategory?.value ||
            '';

        const priority =
            announcementPriority?.value ||
            '';

        const isEmergency =
            category === 'emergency' ||
            priority === 'Emergency';

        if (!isEmergency) {
            return;
        }

        if (announcementPriority) {
            announcementPriority.value =
                'Emergency';
        }

        if (acknowledgmentCheckbox) {
            acknowledgmentCheckbox.checked =
                true;
        }

        if (notificationCheckbox) {
            notificationCheckbox.checked =
                true;
        }
    }

    announcementCategory?.addEventListener(
        'change',
        applyEmergencyRules
    );

    announcementPriority?.addEventListener(
        'change',
        applyEmergencyRules
    );

    /* ==========================================
       WORKFLOW ACTION
    ========================================== */

    function getWorkflowAction() {
        return (
            workflowInput?.value ||
            'publish'
        );
    }

    function activateWorkflow(action) {
        const selectedButton =
            workflowButtons.find(
                (button) =>
                    button.dataset
                        .workflowAction ===
                    action
            );

        if (!selectedButton) {
            return;
        }

        workflowButtons.forEach((button) => {
            const isActive =
                button ===
                selectedButton;

            button.classList.toggle(
                'active',
                isActive
            );

            button.setAttribute(
                'aria-pressed',
                String(isActive)
            );
        });

        if (workflowInput) {
            workflowInput.value =
                action;
        }

        updateSubmitButton();
    }

    workflowButtons.forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                activateWorkflow(
                    button.dataset
                        .workflowAction
                );
            }
        );
    });

    function updateSubmitButton() {
        if (!publishButtonText) {
            return;
        }

        const type =
            getCurrentType();

        const typeLabel =
            contentLabels[type];

        const workflow =
            getWorkflowAction();

        if (workflow === 'draft') {
            publishButtonText.textContent =
                `Save ${typeLabel} as Draft`;

            return;
        }

        if (
            workflow ===
            'submit_review'
        ) {
            publishButtonText.textContent =
                `Submit ${typeLabel} for Review`;

            return;
        }

        const releaseMode =
            getReleaseMode();

      if (
    releaseMode === 'scheduled'
) {
            publishButtonText.textContent =
                `Schedule ${typeLabel}`;

            return;
        }

      if (
    releaseMode === 'calendar'
) {
            publishButtonText.textContent =
                `Connect ${typeLabel} to Calendar`;

            return;
        }

publishButtonText.textContent =
    `Publish ${typeLabel}`;
    }

    /* ==========================================
   COMMAND BAR ACTIONS
========================================== */

saveDraftButton?.addEventListener(
    'click',
    () => {
        if (workflowInput) {
            workflowInput.value =
                'draft';
        }

        form.requestSubmit(
            publishButton
        );
    }
);

publishButton?.addEventListener(
    'click',
    () => {
        if (workflowInput) {
            workflowInput.value =
                primaryWorkflowAction;
        }

        updateSubmitButton();
    }
);

    /* ==========================================
       CHARACTER COUNTERS
    ========================================== */

    document
        .querySelectorAll(
            'textarea[maxlength], input[type="text"][maxlength]'
        )
        .forEach((field) => {
            const counter =
                document.querySelector(
                    `[data-counter-for="${field.id}"]`
                );

            if (!counter) {
                return;
            }

            function updateCounter() {
                counter.textContent =
                    `${field.value.length} / ${field.maxLength}`;
            }

            field.addEventListener(
                'input',
                updateCounter
            );

            updateCounter();
        });

    /* ==========================================
       FILE LABELS
    ========================================== */

    function getEmptyFileLabel(input) {
        if (
            input.id ===
            'documentFile'
        ) {
            return 'No document selected';
        }

        if (
            input.id ===
            'documentCover'
        ) {
            return 'No cover selected';
        }

        return 'No image selected';
    }

    document
        .querySelectorAll(
            '[data-upload-zone]'
        )
        .forEach((zone) => {
            const input =
                zone.querySelector(
                    'input[type="file"]'
                );

            const fileLabel =
                zone.querySelector(
                    '.upload-file-name'
                );

            if (!input) {
                return;
            }

            input.addEventListener(
                'change',
                () => {
                    if (fileLabel) {
                        fileLabel.textContent =
                            input.files?.[0]
                                ?.name ||
                            getEmptyFileLabel(
                                input
                            );
                    }
                }
            );

            [
                'dragenter',
                'dragover'
            ].forEach((eventName) => {
                zone.addEventListener(
                    eventName,
                    (event) => {
                        event.preventDefault();

                        if (!input.disabled) {
                            zone.classList.add(
                                'drag-active'
                            );
                        }
                    }
                );
            });

            [
                'dragleave',
                'drop'
            ].forEach((eventName) => {
                zone.addEventListener(
                    eventName,
                    () => {
                        zone.classList.remove(
                            'drag-active'
                        );
                    }
                );
            });
        });

    /* ==========================================
       IMAGE PREVIEWS
    ========================================== */

    document
        .querySelectorAll(
            '[data-image-input]'
        )
        .forEach((input) => {
            const field =
                input.closest(
                    '.form-field'
                );

            const preview =
                field?.querySelector(
                    '[data-image-preview]'
                );

            const image =
                preview?.querySelector(
                    'img'
                );

            const removeButton =
                preview?.querySelector(
                    '[data-remove-image]'
                );

            const fileLabel =
                field?.querySelector(
                    '.upload-file-name'
                );

            function clearPreview() {
                input.value = '';

                const oldSource =
                    imagePreviewSources.get(
                        input.id
                    );

                if (oldSource) {
                    URL.revokeObjectURL(
                        oldSource
                    );

                    imagePreviewSources.delete(
                        input.id
                    );
                }

                if (image) {
                    image.removeAttribute(
                        'src'
                    );
                }

                if (preview) {
                    preview.hidden = true;
                }

                if (fileLabel) {
                    fileLabel.textContent =
                        getEmptyFileLabel(
                            input
                        );
                }

                updatePublishingInspector();
            }

            input.addEventListener(
                'change',
                () => {
                    const file =
                        input.files?.[0];

                    if (!file) {
                        clearPreview();
                        return;
                    }

                    const allowedTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/webp'
                    ];

                    if (
                        !allowedTypes.includes(
                            file.type
                        )
                    ) {
                        clearPreview();

                        showAlert(
                            'error',
                            'Invalid image',
                            'Only JPG, PNG, and WEBP images are allowed.'
                        );

                        return;
                    }

                    if (
                        file.size >
                        5 * 1024 * 1024
                    ) {
                        clearPreview();

                        showAlert(
                            'error',
                            'Image too large',
                            'The image must not exceed 5 MB.'
                        );

                        return;
                    }

                    const previousSource =
                        imagePreviewSources.get(
                            input.id
                        );

                    if (previousSource) {
                        URL.revokeObjectURL(
                            previousSource
                        );
                    }

                    const source =
                        URL.createObjectURL(
                            file
                        );

                    imagePreviewSources.set(
                        input.id,
                        source
                    );

                    if (
                        image &&
                        preview
                    ) {
                        image.src =
                            source;

                        preview.hidden =
                            false;
                    }

                    updatePublishingInspector();
                }
            );

            removeButton?.addEventListener(
                'click',
                clearPreview
            );
        });

    /* ==========================================
       DOCUMENT VALIDATION
    ========================================== */

 const documentFile =
    document.getElementById(
        'documentFile'
    );

const documentTitleInput =
    document.getElementById(
        'documentTitle'
    );

const documentDescriptionInput =
    document.getElementById(
        'documentDescription'
    );

    documentFile?.addEventListener(
        'change',
        () => {
            const file =
                documentFile.files?.[0];

            if (!file) {
                return;
            }

            if (
                file.size >
                20 * 1024 * 1024
            ) {
                documentFile.value = '';

                const label =
                    documentFile
                        .closest(
                            '[data-upload-zone]'
                        )
                        ?.querySelector(
                            '.upload-file-name'
                        );

                if (label) {
                    label.textContent =
                        'No document selected';
                }

                showAlert(
                    'error',
                    'Document too large',
                    'The document must not exceed 20 MB.'
                );
            }
        }
    );

    /* ==========================================
       AUDIENCE LABEL
    ========================================== */

    function getSelectedRecipientRoleLabels() {
    return roleCheckboxes
        .filter(
            (checkbox) =>
                checkbox.checked
        )
        .map(
            (checkbox) =>
                checkbox.value
        );
}

function getAudienceLabel() {
    if (
        getAudienceScope() ===
        'schoolwide'
    ) {
        return 'School-wide';
    }

    const selectedRoles =
        getSelectedRecipientRoleLabels();

    const academicScopes =
        getAcademicAudienceScopeLabels();

    const parts = [];

    if (selectedRoles.length > 0) {
        parts.push(
            selectedRoles.join(', ')
        );
    }

    if (academicScopes.length > 0) {
        parts.push(
            academicScopes.join('; ')
        );
    }

    return parts.length > 0
        ? parts.join(' • ')
        : 'Custom recipients';
}

function getCompactAudienceLabel() {
    if (
        getAudienceScope() ===
        'schoolwide'
    ) {
        return 'School-wide';
    }

    const selectedRoles =
        getSelectedRecipientRoleLabels();

    const academicScopes =
        getAcademicAudienceScopeLabels();

    const roleLabel =
        selectedRoles.length > 0
            ? selectedRoles.join(', ')
            : 'Selected recipients';

    if (academicScopes.length === 0) {
        return roleLabel;
    }

    if (academicScopes.length === 1) {
        return `${roleLabel} • ${academicScopes[0]}`;
    }

    return `${roleLabel} • ${academicScopes.length} academic targets`;
}

/* ==========================================
   RICH TEXT EDITOR
========================================== */

function sanitizeRichTextClient(html) {
    const template =
        document.createElement(
            'template'
        );

    template.innerHTML =
        String(html || '');

    /*
     * contenteditable commonly creates DIV
     * blocks when Enter is pressed.
     *
     * Convert those DIVs to paragraphs
     * BEFORE sanitizing so line structure
     * is preserved.
     */
    template.content
        .querySelectorAll('div')
        .forEach((div) => {
            const paragraph =
                document.createElement(
                    'p'
                );

            while (div.firstChild) {
                paragraph.appendChild(
                    div.firstChild
                );
            }

            div.replaceWith(
                paragraph
            );
        });

        /* ======================================
   NORMALIZE BROWSER FORMAT TAGS
====================================== */

template.content
    .querySelectorAll('b')
    .forEach((bold) => {
        const strong =
            document.createElement(
                'strong'
            );

        while (bold.firstChild) {
            strong.appendChild(
                bold.firstChild
            );
        }

        bold.replaceWith(
            strong
        );
    });

template.content
    .querySelectorAll('i')
    .forEach((italic) => {
        const emphasis =
            document.createElement(
                'em'
            );

        while (italic.firstChild) {
            emphasis.appendChild(
                italic.firstChild
            );
        }

        italic.replaceWith(
            emphasis
        );
    });

    const allowedTags =
        new Set([
            'P',
            'BR',
            'STRONG',
            'EM',
            'U',
            'UL',
            'OL',
            'LI',
            'A'
        ]);

    const cleanNode =
        (node) => {
            const children =
                Array.from(
                    node.childNodes
                );

            children.forEach(
                (child) => {
                    if (
                        child.nodeType ===
                        Node.ELEMENT_NODE
                    ) {
                        if (
                            !allowedTags.has(
                                child.tagName
                            )
                        ) {
                            cleanNode(child);

                            while (
                                child.firstChild
                            ) {
                                child.parentNode
                                    ?.insertBefore(
                                        child.firstChild,
                                        child
                                    );
                            }

                            child.remove();

                            return;
                        }

                        let safeHref = '';

                        if (
                            child.tagName ===
                            'A'
                        ) {
                            const href =
                                child
                                    .getAttribute(
                                        'href'
                                    )
                                    ?.trim() ||
                                '';

                            if (
                                /^(https?:\/\/|mailto:)/i
                                    .test(href)
                            ) {
                                safeHref =
                                    href;
                            }
                        }

                        Array
                            .from(
                                child.attributes
                            )
                            .forEach(
                                (attribute) => {
                                    child.removeAttribute(
                                        attribute.name
                                    );
                                }
                            );

                        if (
                            child.tagName === 'A' &&
                            safeHref
                        ) {
                            child.setAttribute(
                                'href',
                                safeHref
                            );

                            child.setAttribute(
                                'target',
                                '_blank'
                            );

                            child.setAttribute(
                                'rel',
                                'noopener noreferrer'
                            );
                        }
                    }

                    cleanNode(
                        child
                    );
                }
            );
        };

    cleanNode(
        template.content
    );

    return template.innerHTML.trim();
}


function getRichTextPlainText(html) {
    const element =
        document.createElement(
            'div'
        );

    element.innerHTML =
        String(html || '');

    return (
        element.textContent ||
        ''
    )
        .replace(
            /\u00a0/g,
            ' '
        )
        .trim();
}


function initializeRichTextEditors() {
    document
        .querySelectorAll(
            '[data-rich-editor]'
        )
        .forEach(
            (editor) => {
                const targetId =
                    editor.dataset.target;

                const target =
                    document
                        .getElementById(
                            targetId
                        );

                const surface =
                    editor
                        .querySelector(
                            '[data-rich-surface]'
                        );

                const counter =
                    document
                        .querySelector(
                            `[data-counter-for="${targetId}"]`
                        );

                if (
                    !target ||
                    !surface
                ) {
                    return;
                }


                /* ==================================
                   INITIAL VALUE
                ================================== */

                if (
                    target.value.trim() !==
                    ''
                ) {
                    surface.innerHTML =
                        sanitizeRichTextClient(
                            target.value
                        );
                }


                /* ==================================
                   SYNC EDITOR → TEXTAREA
                ================================== */

                const syncEditor = () => {
                    let html =
                        sanitizeRichTextClient(
                            surface.innerHTML
                        );

                    const plainText =
                        getRichTextPlainText(
                            html
                        );

                    /*
                     * Prevent empty formatting tags
                     * from passing validation.
                     */
                    if (
                        plainText === ''
                    ) {
                        html = '';
                    }

                    /*
                     * 5000 visible-character limit.
                     */
                    if (
                        plainText.length >
                        5000
                    ) {
                        showAlert(
                            'warning',
                            'Content limit reached',
                            'Rich text content is limited to 5,000 characters.'
                        );

                        return;
                    }

                    target.value =
                        html;

                    if (counter) {
                        counter.textContent =
                            `${plainText.length} / 5000`;
                    }

                    target.dispatchEvent(
                        new Event(
                            'input',
                            {
                                bubbles: true
                            }
                        )
                    );
                };


                /* ==================================
                   TEXT INPUT
                ================================== */

                surface.addEventListener(
                    'input',
                    syncEditor
                );


                /* ==================================
                   KEEP SELECTION WHEN TOOLBAR CLICKED
                ================================== */

                editor
                    .querySelectorAll(
                        '.rich-text-toolbar button'
                    )
                    .forEach(
                        (button) => {
                            button
                                .addEventListener(
                                    'mousedown',
                                    (event) => {
                                        event
                                            .preventDefault();
                                    }
                                );
                        }
                    );


                /* ==================================
                   FORMAT COMMANDS
                ================================== */

                editor
                    .querySelectorAll(
                        '[data-rich-command]'
                    )
                    .forEach(
                        (button) => {
                            button
                                .addEventListener(
                                    'click',
                                    () => {
                                        surface.focus();

                                        document.execCommand(
                                            button.dataset
                                                .richCommand,
                                            false,
                                            null
                                        );

                                        syncEditor();
                                    }
                                );
                        }
                    );


                /* ==================================
                   LINK
                ================================== */

                const linkButton =
                    editor
                        .querySelector(
                            '[data-rich-link]'
                        );

                linkButton
                    ?.addEventListener(
                        'click',
                        () => {
                            surface.focus();

                            const selection =
                                window
                                    .getSelection();

                            if (
                                !selection ||
                                selection.isCollapsed
                            ) {
                                showAlert(
                                    'info',
                                    'Select text first',
                                    'Highlight the text you want to turn into a link.'
                                );

                                return;
                            }

                            const url =
                                window.prompt(
                                    'Enter an https://, http://, or mailto: link:'
                                );

                            if (!url) {
                                return;
                            }

                            const cleanUrl =
                                url.trim();

                            if (
                                !/^(https?:\/\/|mailto:)/i
                                    .test(
                                        cleanUrl
                                    )
                            ) {
                                showAlert(
                                    'error',
                                    'Invalid link',
                                    'Use an http://, https://, or mailto: address.'
                                );

                                return;
                            }

                            document.execCommand(
                                'createLink',
                                false,
                                cleanUrl
                            );

                            syncEditor();
                        }
                    );


                syncEditor();
            }
        );
}


    /* ==========================================
       PREVIEW CONTENT
    ========================================== */

function getCurrentContent() {
    const type =
        getCurrentType();

    if (type === 'event') {
        const content =
            document
                .getElementById(
                    'eventDescription'
                )
                ?.value.trim() ||
            '';

        return {
            title:
                document
                    .getElementById(
                        'eventTitle'
                    )
                    ?.value.trim() ||
                'Untitled Event',

            content,

            fallback:
                'No event description provided.',

            isRichText: true,

            priority:
                'Event',

            imageInput:
                document.getElementById(
                    'eventImage'
                )
        };
    }

    if (type === 'survey') {
        const questionCount =
            surveyQuestionList
                ?.querySelectorAll(
                    '.survey-question-card'
                )
                .length ||
            0;

        const content =
            surveyDescriptionInput
                ?.value.trim() ||
            '';

        return {
            title:
                surveyTitleInput
                    ?.value.trim() ||
                'Untitled Survey',

            content,

            fallback:
                'No survey description provided.',

            isRichText: true,

            priority:
                `${questionCount} ${
                    questionCount === 1
                        ? 'Question'
                        : 'Questions'
                }`,

            imageInput: null
        };
    }

        if (type === 'announcement') {
        const audioField =
            document.querySelector(
                '[data-announcement-audio-field]'
            );

        const audioInput =
            audioField?.querySelector(
                '[data-announcement-audio-input]'
            );

        const removeAudio =
            audioField?.querySelector(
                '[data-remove-announcement-audio]'
            );

        const transcript =
            audioField?.querySelector(
                '[data-announcement-audio-transcript]'
            );

        const hasNewAudio =
            Boolean(
                audioInput?.files?.[0]
            );

        const hasExistingAudio =
            audioField?.dataset
                .existingAudio ===
                'true' &&
            !removeAudio?.checked;

        const hasAudio =
            hasNewAudio ||
            hasExistingAudio;



        if (
            hasAudio &&
            (
                transcript?.value.length ||
                0
            ) > 10000
        ) {
            showAlert(
                'error',
                'Audio transcript too long',
                'The audio transcript cannot exceed 10,000 characters.'
            );

            focusInvalidField(
                transcript,
                audioField
            );

            return false;
        }
    }


   if (type === 'document') {
    const file =
        documentFile?.files?.[0];

    const existingFile =
        documentFile?.dataset
            .existingFile
            ?.trim() ||
        '';

    return {
        title:
            documentTitleInput
                ?.value.trim() ||
            'Untitled Document',

        content:
            documentDescriptionInput
                ?.value.trim() ||
            '',

        fallback:
            'No document description provided.',

        isRichText: false,

        priority:
            file
                ? file.name
                : (
                    existingFile !== ''
                        ? 'Existing File'
                        : 'Document'
                ),

        imageInput:
            document.getElementById(
                'documentCover'
            )
    };
}

    const content =
        document
            .getElementById(
                'announcementContent'
            )
            ?.value.trim() ||
        '';

    return {
        title:
            document
                .getElementById(
                    'announcementTitle'
                )
                ?.value.trim() ||
            'Untitled Announcement',

        content,

        fallback:
            'No announcement content provided.',

        isRichText: true,

        priority:
            announcementPriority
                ?.value ||
            'Normal',

        imageInput:
            document.getElementById(
                'announcementImage'
            )
    };
}



function getReleaseLabel() {
    const mode =
        getReleaseMode();

    if (mode === 'scheduled') {
        const formatted =
            formatDateTime(
                scheduledPublishInput
                    ?.value
            );

        return formatted
            ? `Scheduled for ${formatted}`
            : 'Scheduled release';
    }

    if (mode === 'calendar') {
        const selected =
            calendarReference
                ?.selectedOptions[0];

        return selected?.value
            ? `Calendar-based: ${selected.text.trim()}`
            : 'Calendar-based release';
    }

    return 'Immediate release';
}

function getCompactReleaseLabel() {
    const mode =
        getReleaseMode();

    if (mode === 'scheduled') {
        return 'Scheduled';
    }

    if (mode === 'calendar') {
        return 'Calendar-based';
    }

    return 'Immediate';
}

    function openPreview() {
        const content =
            getCurrentContent();

        const type =
            getCurrentType();

        if (previewContentType) {
            previewContentType.textContent =
                contentLabels[type];
        }

        if (previewPriority) {
            previewPriority.textContent =
                content.priority;
        }

        if (previewAudience) {
            previewAudience.textContent =
                getAudienceLabel();
        }

        if (previewTitle) {
            previewTitle.textContent =
                content.title;
        }
if (previewContent) {
    if (content.isRichText) {
        const safeHtml =
            sanitizeRichTextClient(
                content.content
            );

        if (
            getRichTextPlainText(
                safeHtml
            ) !== ''
        ) {
            previewContent.innerHTML =
                safeHtml;
        } else {
            previewContent.textContent =
                content.fallback;
        }
    } else {
        previewContent.textContent =
            content.content ||
            content.fallback;
    }
}

        if (previewReleaseMode) {
            previewReleaseMode.textContent =
                getReleaseLabel();
        }

        if (previewWorkflow) {
            previewWorkflow.textContent =
                workflowLabels[
                    getWorkflowAction()
                ] || 'Publish';
        }

        const source =
            content.imageInput
                ? imagePreviewSources.get(
                    content.imageInput.id
                )
                : null;

        if (
            previewImageWrap &&
            previewImage
        ) {
            if (source) {
                previewImage.src =
                    source;

                previewImageWrap.hidden =
                    false;
            } else {
                previewImage.removeAttribute(
                    'src'
                );

                previewImageWrap.hidden =
                    true;
            }
        }

        previewModal?.classList.add(
            'active'
        );

        previewModal?.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.style.overflow =
            'hidden';
    }

    function closePreview() {
        previewModal?.classList.remove(
            'active'
        );

        previewModal?.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.style.overflow =
            '';
    }

    previewButton?.addEventListener(
        'click',
        openPreview
    );

    closePreviewButton?.addEventListener(
        'click',
        closePreview
    );

    returnToEditorButton?.addEventListener(
        'click',
        closePreview
    );

    previewModal?.addEventListener(
        'click',
        (event) => {
            if (
                event.target ===
                previewModal
            ) {
                closePreview();
            }
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape' &&
                previewModal?.classList
                    .contains('active')
            ) {
                closePreview();
            }
        }
    );

/* ==========================================
   VALIDATION FOCUS HELPER
========================================== */

function focusInvalidField(
    field,
    container = null
) {
    if (!field) {
        return;
    }

    const target =
        container ||
        field.closest(
            '.form-field'
        ) ||
        field;

    target.classList.add(
        'validation-attention'
    );

    target.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });


    /* ======================================
       WAIT FOR SWEETALERT TO CLOSE
    ====================================== */

    const focusField = () => {
        const activeAlert =
            document.querySelector(
                '.swal2-container'
            );

        if (activeAlert) {
            window.setTimeout(
                focusField,
                100
            );

            return;
        }

        field.focus({
            preventScroll: true
        });
    };

    window.setTimeout(
        focusField,
        100
    );


    /* ======================================
       REMOVE ATTENTION ON CORRECTION
    ====================================== */

    const clearAttention = () => {
        target.classList.remove(
            'validation-attention'
        );

        field.removeEventListener(
            'input',
            clearAttention
        );

        field.removeEventListener(
            'change',
            clearAttention
        );
    };

    field.addEventListener(
        'input',
        clearAttention
    );

    field.addEventListener(
        'change',
        clearAttention
    );
}

function initializeAnnouncementAudio() {
    const field =
        document.querySelector(
            '[data-announcement-audio-field]'
        );

    if (!field) {
        return;
    }

    const audioInput =
        field.querySelector(
            '[data-announcement-audio-input]'
        );

    const fileName =
        field.querySelector(
            '[data-announcement-audio-name]'
        );

    const transcriptField =
        field.querySelector(
            '[data-audio-transcript-field]'
        );

    const transcript =
        field.querySelector(
            '[data-announcement-audio-transcript]'
        );

    const transcriptCounter =
        field.querySelector(
            '[data-audio-transcript-counter]'
        );

    const removeAudio =
        field.querySelector(
            '[data-remove-announcement-audio]'
        );

    const existingPreview =
        field.querySelector(
            '[data-existing-audio-preview]'
        );

    const existingPlayer =
        field.querySelector(
            '[data-existing-audio-player]'
        );

    const existingAudio =
        field.querySelector(
            '[data-existing-audio-element]'
        );

    const existingPlayButton =
        field.querySelector(
            '[data-existing-audio-play]'
        );

    const existingPlayIcon =
        existingPlayButton
            ?.querySelector('i');

    const existingProgress =
        field.querySelector(
            '[data-existing-audio-progress]'
        );

    const existingCurrentTime =
        field.querySelector(
            '[data-existing-audio-current]'
        );

    const existingDuration =
        field.querySelector(
            '[data-existing-audio-duration]'
        );

            const recorderPanel =
        field.querySelector(
            '[data-announcement-audio-recorder]'
        );

    const recordingStatusText =
        field.querySelector(
            '[data-recording-status-text]'
        );

    const recordingTimer =
        field.querySelector(
            '[data-recording-timer]'
        );

    const startRecordingButton =
        field.querySelector(
            '[data-start-audio-recording]'
        );

    const stopRecordingButton =
        field.querySelector(
            '[data-stop-audio-recording]'
        );

    const discardRecordingButton =
        field.querySelector(
            '[data-discard-audio-recording]'
        );

            const postingForm =
        field.closest('form');

    postingForm?.addEventListener(
        'submit',
        (event) => {
            if (
                mediaRecorder?.state !==
                    'recording'
            ) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            showAlert(
                'warning',
                'Stop the recording first',
                'Finish or discard the active audio recording before saving or publishing the announcement.'
            );

            stopRecordingButton?.focus();
        },
        true
    );

    window.addEventListener(
        'beforeunload',
        () => {
            stopRecordingTimer();
            stopMicrophoneStream();
            revokeRecordedAudioUrl();
        }
    );

    const recordedAudioPlayer =
        field.querySelector(
            '[data-recorded-audio-player]'
        );

    const recordedAudioPreview =
        field.querySelector(
            '[data-recorded-audio-preview]'
        );

    const recordedAudioPlayButton =
        field.querySelector(
            '[data-recorded-audio-play]'
        );

    const recordedAudioPlayIcon =
        recordedAudioPlayButton
            ?.querySelector(
                'i'
            );

    const recordedAudioProgress =
        field.querySelector(
            '[data-recorded-audio-progress]'
        );

    const recordedAudioCurrentTime =
        field.querySelector(
            '[data-recorded-audio-current]'
        );

    const recordedAudioDuration =
        field.querySelector(
            '[data-recorded-audio-duration]'
        );

    const recordingSupportMessage =
        field.querySelector(
            '[data-recording-support-message]'
        );

    let microphoneStream =
        null;

    let mediaRecorder =
        null;

    let recordingChunks =
        [];

    let recordingStartedAt =
        0;

    let recordingTimerId =
        null;

    let recordedAudioUrl =
        '';

    let recordedAudioFile =
        null;

    let recordedAudioDurationSeconds =
        0;

    const hasStoredAudio =
        field.dataset.existingAudio ===
        'true';

    const initialFileName =
        fileName?.textContent.trim() ||
        'No audio selected';

    const maximumSize =
        15 * 1024 * 1024;

const allowedExtensions = [
    'mp3',
    'm4a',
    'wav',
    'webm'
];

    const recordingMimeCandidates = [
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/mp4'
    ];

    const recordingSupported =
        Boolean(
            navigator.mediaDevices
                ?.getUserMedia
        ) &&
        typeof window.MediaRecorder !==
            'undefined' &&
        typeof window.DataTransfer !==
            'undefined';

    function getSupportedRecordingMimeType() {
        if (
            typeof window.MediaRecorder ===
                'undefined'
        ) {
            return '';
        }

        return (
            recordingMimeCandidates.find(
                (mimeType) =>
                    MediaRecorder
                        .isTypeSupported(
                            mimeType
                        )
            ) ??
            ''
        );
    }

    function formatRecordingTime(
        totalSeconds
    ) {
        const safeSeconds =
            Math.max(
                0,
                Math.floor(
                    Number(totalSeconds) ||
                    0
                )
            );

        const minutes =
            Math.floor(
                safeSeconds / 60
            );

        const seconds =
            safeSeconds % 60;

        return `${minutes}:${
            String(seconds)
                .padStart(2, '0')
        }`;
    }

    function stopRecordingTimer() {
        if (recordingTimerId !== null) {
            window.clearInterval(
                recordingTimerId
            );

            recordingTimerId =
                null;
        }
    }

    function stopMicrophoneStream() {
        microphoneStream
            ?.getTracks()
            .forEach(
                (track) => {
                    track.stop();
                }
            );

        microphoneStream =
            null;
    }

    function revokeRecordedAudioUrl() {
        if (recordedAudioUrl !== '') {
            URL.revokeObjectURL(
                recordedAudioUrl
            );

            recordedAudioUrl =
                '';
        }
    }

    function setRecordingStatus(
        message,
        seconds = null
    ) {
        if (recordingStatusText) {
            recordingStatusText.textContent =
                message;
        }

        if (
            recordingTimer &&
            seconds !== null
        ) {
            recordingTimer.textContent =
                formatRecordingTime(
                    seconds
                );
        }
    }

    function resetRecordedAudio(
        clearFileInput = true
    ) {
        stopRecordingTimer();
        stopMicrophoneStream();
        revokeRecordedAudioUrl();

        recordingChunks = [];
        recordedAudioFile = null;
        mediaRecorder = null;
        recordingStartedAt = 0;

        recorderPanel
            ?.classList.remove(
                'is-recording',
                'has-recording'
            );

        if (recordedAudioPreview) {
            recordedAudioPreview.pause();

            recordedAudioPreview
                .removeAttribute(
                    'src'
                );

            recordedAudioPreview.load();
        }

        recordedAudioDurationSeconds =
            0;

        if (recordedAudioPlayer) {
            recordedAudioPlayer.hidden =
                true;

            recordedAudioPlayer
                .classList.remove(
                    'is-playing'
                );
        }

        if (recordedAudioPlayButton) {
            recordedAudioPlayButton.disabled =
                false;

            recordedAudioPlayButton
                .setAttribute(
                    'aria-label',
                    'Play recorded audio preview'
                );
        }

        if (recordedAudioPlayIcon) {
            recordedAudioPlayIcon.className =
                'fa-solid fa-play';
        }

        if (recordedAudioProgress) {
            recordedAudioProgress.value =
                '0';

            recordedAudioProgress.disabled =
                true;

            recordedAudioProgress.style
                .setProperty(
                    '--audio-progress',
                    '0%'
                );
        }

        if (recordedAudioCurrentTime) {
            recordedAudioCurrentTime.textContent =
                '0:00';
        }

        if (recordedAudioDuration) {
            recordedAudioDuration.textContent =
                '0:00';
        }

        if (startRecordingButton) {
            startRecordingButton.hidden =
                false;

            startRecordingButton.disabled =
                !recordingSupported;
        }

        if (stopRecordingButton) {
            stopRecordingButton.hidden =
                true;

            stopRecordingButton.disabled =
                false;
        }

        if (discardRecordingButton) {
            discardRecordingButton.hidden =
                true;
        }

        if (
            clearFileInput &&
            audioInput
        ) {
            audioInput.value =
                '';
        }

        setRecordingStatus(
            recordingSupported
                ? 'Ready to record'
                : 'Recording is unavailable',
            0
        );
    }

    if (!recordingSupported) {
        if (startRecordingButton) {
            startRecordingButton.disabled =
                true;
        }

        if (recordingSupportMessage) {
            recordingSupportMessage.hidden =
                false;

            recordingSupportMessage.textContent =
                'Live recording is not supported by this browser or device. You can still upload an MP3, M4A, WAV, or WebM file.';
        }

        setRecordingStatus(
            'Recording is unavailable',
            0
        );
    }

    function synchronizeAudioState() {
        const selectedFile =
            audioInput?.files?.[0] ||
            null;

        const removingStoredAudio =
            Boolean(
                removeAudio?.checked
            );

        const hasExistingAudio =
            hasStoredAudio &&
            !removingStoredAudio &&
            !selectedFile;

        const hasAudio =
            Boolean(
                selectedFile ||
                hasExistingAudio
            );

        if (fileName) {
            fileName.textContent =
                selectedFile
                    ? selectedFile.name
                    : (
                        hasExistingAudio
                            ? initialFileName
                            : 'No audio selected'
                    );
        }

        if (transcriptField) {
            transcriptField.hidden =
                !hasAudio;
        }

                if (transcript) {
            transcript.disabled =
                !hasAudio;

            transcript.required =
                false;
        }

        if (existingPreview) {
            existingPreview.hidden =
                !hasStoredAudio ||
                removingStoredAudio ||
                Boolean(selectedFile);
        }

        if (transcriptCounter) {
            transcriptCounter.textContent =
                `${
                    transcript?.value.length ||
                    0
                } / 10000`;
        }
    }

        const maximumRecordingSeconds =
        10 * 60;

    startRecordingButton
        ?.addEventListener(
            'click',
            async () => {
                if (
                    !recordingSupported ||
                    mediaRecorder?.state ===
                        'recording'
                ) {
                    return;
                }

                resetRecordedAudio(
                    true
                );

                try {
                    microphoneStream =
                        await navigator
                            .mediaDevices
                            .getUserMedia({
                                audio: {
                                    echoCancellation:
                                        true,

                                    noiseSuppression:
                                        true,

                                    autoGainControl:
                                        true
                                }
                            });

                    const mimeType =
                        getSupportedRecordingMimeType();

                    const recorderOptions = {
                        audioBitsPerSecond:
                            128000
                    };

                    if (mimeType !== '') {
                        recorderOptions.mimeType =
                            mimeType;
                    }

                    mediaRecorder =
                        new MediaRecorder(
                            microphoneStream,
                            recorderOptions
                        );

                    recordingChunks = [];

                    mediaRecorder
                        .addEventListener(
                            'dataavailable',
                            (event) => {
                                if (
                                    event.data &&
                                    event.data.size >
                                        0
                                ) {
                                    recordingChunks
                                        .push(
                                            event.data
                                        );
                                }
                            }
                        );

                    mediaRecorder
                        .addEventListener(
                            'error',
                            (event) => {
                                console.error(
                                    'Audio recording error.',
                                    event.error ??
                                        event
                                );

                                stopRecordingTimer();
                                stopMicrophoneStream();

                                resetRecordedAudio(
                                    true
                                );

                                synchronizeAudioState();

                                showAlert(
                                    'error',
                                    'Recording failed',
                                    'The audio recording could not be completed. You may try again or upload an audio file.'
                                );
                            }
                        );

                    mediaRecorder
                        .addEventListener(
                            'stop',
                            () => {
                                stopRecordingTimer();
                                stopMicrophoneStream();

                                if (audioInput) {
                                    audioInput.disabled =
                                        false;
                                }

                                const finalMimeType =
                                    (
                                        mediaRecorder
                                            ?.mimeType ||
                                        recordingChunks[0]
                                            ?.type ||
                                        mimeType ||
                                        'audio/webm'
                                    )
                                        .split(';')[0]
                                        .toLowerCase();

                                const recordedBlob =
                                    new Blob(
                                        recordingChunks,
                                        {
                                            type:
                                                finalMimeType
                                        }
                                    );

                                recordingChunks = [];

                                if (
                                    recordedBlob.size <=
                                        0
                                ) {
                                    resetRecordedAudio(
                                        true
                                    );

                                    synchronizeAudioState();

                                    showAlert(
                                        'error',
                                        'Empty recording',
                                        'No audio was captured. Check your microphone and try again.'
                                    );

                                    return;
                                }

                                if (
                                    recordedBlob.size >
                                        maximumSize
                                ) {
                                    resetRecordedAudio(
                                        true
                                    );

                                    synchronizeAudioState();

                                    showAlert(
                                        'error',
                                        'Recording is too large',
                                        'The recording exceeds the 15 MB limit. Record a shorter audio message.'
                                    );

                                    return;
                                }

                                const extension =
                                    finalMimeType ===
                                        'audio/mp4'
                                    ? 'm4a'
                                    : 'webm';

                                const timestamp =
                                    new Date()
                                        .toISOString()
                                        .replace(
                                            /[:.]/g,
                                            '-'
                                        );

                                recordedAudioFile =
                                    new File(
                                        [
                                            recordedBlob
                                        ],
                                        `announcement-recording-${timestamp}.${extension}`,
                                        {
                                            type:
                                                finalMimeType,

                                            lastModified:
                                                Date.now()
                                        }
                                    );

                                const transfer =
                                    new DataTransfer();

                                transfer.items.add(
                                    recordedAudioFile
                                );

                                if (audioInput) {
                                    audioInput.files =
                                        transfer.files;
                                }

                                if (removeAudio) {
                                    removeAudio.checked =
                                        false;
                                }

                                revokeRecordedAudioUrl();

                                recordedAudioUrl =
                                    URL.createObjectURL(
                                        recordedBlob
                                    );

                                if (
                                    recordedAudioPreview
                                ) {
                                    recordedAudioPreview.src =
                                        recordedAudioUrl;

                                    recordedAudioPreview.load();
                                }

                                if (
                                    recordedAudioPlayer
                                ) {
                                    recordedAudioPlayer.hidden =
                                        false;
                                }

                                recorderPanel
                                    ?.classList.remove(
                                        'is-recording'
                                    );

                                recorderPanel
                                    ?.classList.add(
                                        'has-recording'
                                    );

                                if (
                                    startRecordingButton
                                ) {
                                    startRecordingButton.hidden =
                                        false;

                                    startRecordingButton.disabled =
                                        false;

                                    startRecordingButton.innerHTML =
                                        '<i class="fa-solid fa-rotate-right"></i> Record Again';
                                }

                                if (
                                    stopRecordingButton
                                ) {
                                    stopRecordingButton.hidden =
                                        true;
                                }

                                if (
                                    discardRecordingButton
                                ) {
                                    discardRecordingButton.hidden =
                                        false;
                                }

                                const recordedSeconds =
                                    recordingStartedAt > 0
                                    ? (
                                        Date.now() -
                                        recordingStartedAt
                                    ) / 1000
                                    : 0;

                                recordedAudioDurationSeconds =
                                    Math.max(
                                        0,
                                        recordedSeconds
                                    );

                                                                    synchronizeRecordedAudioTimeline();

                                setRecordingStatus(
                                    'Recording ready to attach',
                                    recordedSeconds
                                );

                                synchronizeAudioState();

                                /*
                                 * Refresh only the publishing UI.
                                 * Do not dispatch a change event,
                                 * because the audio input's manual
                                 * upload handler would remove the
                                 * generated recording preview.
                                 */
                                updatePublishingReadiness();
                                updatePublishingInspector();
                            }
                        );

                    mediaRecorder.start(
                        1000
                    );

                    recordingStartedAt =
                        Date.now();

                    recorderPanel
                        ?.classList.add(
                            'is-recording'
                        );

                    recorderPanel
                        ?.classList.remove(
                            'has-recording'
                        );

                    if (audioInput) {
                        audioInput.disabled =
                            true;
                    }

                    if (
                        startRecordingButton
                    ) {
                        startRecordingButton.hidden =
                            true;
                    }

                    if (
                        stopRecordingButton
                    ) {
                        stopRecordingButton.hidden =
                            false;
                    }

                    if (
                        discardRecordingButton
                    ) {
                        discardRecordingButton.hidden =
                            true;
                    }

                    setRecordingStatus(
                        'Recording in progress',
                        0
                    );

                    recordingTimerId =
    window.setInterval(
        () => {
            /*
             * A queued interval callback may run
             * immediately after Stop or Discard.
             * Ignore it once recording has ended.
             */
            if (
                recordingStartedAt <= 0 ||
                mediaRecorder?.state !==
                    'recording'
            ) {
                stopRecordingTimer();

                return;
            }

            const elapsedSeconds =
                (
                    Date.now() -
                    recordingStartedAt
                ) / 1000;

            setRecordingStatus(
                'Recording in progress',
                elapsedSeconds
            );

            if (
                elapsedSeconds >=
                    maximumRecordingSeconds
            ) {
                mediaRecorder.stop();
            }
        },
        250
    );
                } catch (error) {
                    console.error(
                        'Unable to access the microphone.',
                        error
                    );

                    stopMicrophoneStream();

                    resetRecordedAudio(
                        true
                    );

                    synchronizeAudioState();

                    const permissionDenied =
                        error?.name ===
                            'NotAllowedError' ||
                        error?.name ===
                            'PermissionDeniedError';

                    showAlert(
                        'error',
                        permissionDenied
                            ? 'Microphone permission denied'
                            : 'Microphone unavailable',

                        permissionDenied
                            ? 'Allow microphone access in your browser settings, then try recording again.'
                            : 'No usable microphone was found. You can still upload an audio file.'
                    );
                }
            }
        );

    stopRecordingButton
        ?.addEventListener(
            'click',
            () => {
                if (
                    mediaRecorder?.state ===
                    'recording'
                ) {
                    setRecordingStatus(
                        'Processing recording'
                    );

                    stopRecordingButton.disabled =
                        true;

                    mediaRecorder.stop();
                }
            }
        );

    discardRecordingButton
        ?.addEventListener(
            'click',
            () => {
                resetRecordedAudio(
                    true
                );

                if (
                    startRecordingButton
                ) {
                    startRecordingButton.innerHTML =
                        '<i class="fa-solid fa-microphone"></i> Start Recording';
                }

                synchronizeAudioState();
            }
        );

    audioInput?.addEventListener(
        'change',
        () => {

            if (recordedAudioFile) {
    resetRecordedAudio(
        false
    );

    if (startRecordingButton) {
        startRecordingButton.innerHTML =
            '<i class="fa-solid fa-microphone"></i> Start Recording';
    }
}

            const selectedFile =
                audioInput.files?.[0];

            if (!selectedFile) {
                synchronizeAudioState();

                return;
            }

            const extension =
                selectedFile.name
                    .split('.')
                    .pop()
                    ?.toLowerCase() ||
                '';

            if (
                !allowedExtensions.includes(
                    extension
                )
            ) {
                audioInput.value =
                    '';

                showAlert(
                    'error',
                    'Unsupported audio file',
                    'Select an MP3, M4A, WAV, or WebM audio file.'
                );

                synchronizeAudioState();

                return;
            }

            if (
                selectedFile.size <= 0 ||
                selectedFile.size >
                    maximumSize
            ) {
                audioInput.value =
                    '';

                showAlert(
                    'error',
                    'Invalid audio size',
                    'Select a non-empty audio file not exceeding 15 MB.'
                );

                synchronizeAudioState();

                return;
            }

            if (removeAudio) {
                removeAudio.checked =
                    false;
            }

            synchronizeAudioState();
        }
    );

    removeAudio?.addEventListener(
        'change',
        () => {
            if (
                removeAudio.checked &&
                audioInput
            ) {
                audioInput.value =
                    '';
            }

            synchronizeAudioState();
        }
    );

    /* ==========================================
       AUDIO TIME FORMATTER
    ========================================== */

    const formatAudioTime =
        (seconds) => {
            const numericSeconds =
                Number(
                    seconds
                );

            if (
                !Number.isFinite(
                    numericSeconds
                ) ||
                numericSeconds < 0
            ) {
                return '0:00';
            }

            const minutes =
                Math.floor(
                    numericSeconds /
                    60
                );

            const remainingSeconds =
                Math.floor(
                    numericSeconds %
                    60
                );

            return `${minutes}:${
                String(
                    remainingSeconds
                ).padStart(
                    2,
                    '0'
                )
            }`;
        };

    /* ==========================================
       RECORDED AUDIO CUSTOM PLAYER
    ========================================== */

    const getRecordedAudioDuration =
        () => {
            if (!recordedAudioPreview) {
                return 0;
            }

            const nativeDuration =
                Number(
                    recordedAudioPreview.duration
                );

            if (
                Number.isFinite(
                    nativeDuration
                ) &&
                nativeDuration > 0
            ) {
                return nativeDuration;
            }

            if (
                Number.isFinite(
                    recordedAudioDurationSeconds
                ) &&
                recordedAudioDurationSeconds > 0
            ) {
                return recordedAudioDurationSeconds;
            }

            return 0;
        };

    const synchronizeRecordedAudioTimeline =
        () => {
            if (!recordedAudioPreview) {
                return;
            }

            const duration =
                getRecordedAudioDuration();

            const rawCurrentTime =
                Number(
                    recordedAudioPreview.currentTime
                );

            const currentTime =
                Number.isFinite(
                    rawCurrentTime
                )
                    ? Math.max(
                        0,
                        rawCurrentTime
                    )
                    : 0;

            const percentage =
                duration > 0
                    ? Math.min(
                        100,
                        Math.max(
                            0,
                            (
                                currentTime /
                                duration
                            ) * 100
                        )
                    )
                    : 0;

            if (recordedAudioCurrentTime) {
                recordedAudioCurrentTime
                    .textContent =
                    formatAudioTime(
                        currentTime
                    );
            }

            if (recordedAudioDuration) {
                recordedAudioDuration
                    .textContent =
                    duration > 0
                        ? formatAudioTime(
                            duration
                        )
                        : '0:00';
            }

            if (recordedAudioProgress) {
                recordedAudioProgress.value =
                    String(
                        percentage
                    );

                recordedAudioProgress.disabled =
                    duration <= 0;

                recordedAudioProgress.style
                    .setProperty(
                        '--audio-progress',
                        `${percentage}%`
                    );
            }
        };

    recordedAudioPlayButton
        ?.addEventListener(
            'click',
            async () => {
                if (!recordedAudioPreview) {
                    return;
                }

                if (
                    recordedAudioPreview.paused
                ) {
                    /*
                     * Prevent the saved-audio player
                     * and recorded preview from playing
                     * simultaneously.
                     */
                    existingAudio?.pause();

                    try {
                        await recordedAudioPreview
                            .play();
                    } catch (error) {
                        console.error(
                            error
                        );

                        showAlert(
                            'error',
                            'Unable to play recording',
                            'The recorded audio preview could not be played.'
                        );
                    }

                    return;
                }

                recordedAudioPreview.pause();
            }
        );

    recordedAudioPreview
        ?.addEventListener(
            'play',
            () => {
                recordedAudioPlayer
                    ?.classList.add(
                        'is-playing'
                    );

                if (recordedAudioPlayIcon) {
                    recordedAudioPlayIcon.className =
                        'fa-solid fa-pause';
                }

                recordedAudioPlayButton
                    ?.setAttribute(
                        'aria-label',
                        'Pause recorded audio preview'
                    );
            }
        );

    recordedAudioPreview
        ?.addEventListener(
            'pause',
            () => {
                recordedAudioPlayer
                    ?.classList.remove(
                        'is-playing'
                    );

                if (recordedAudioPlayIcon) {
                    recordedAudioPlayIcon.className =
                        'fa-solid fa-play';
                }

                recordedAudioPlayButton
                    ?.setAttribute(
                        'aria-label',
                        'Play recorded audio preview'
                    );
            }
        );

    [
        'loadedmetadata',
        'durationchange',
        'loadeddata',
        'canplay',
        'timeupdate'
    ].forEach(
        (eventName) => {
            recordedAudioPreview
                ?.addEventListener(
                    eventName,
                    synchronizeRecordedAudioTimeline
                );
        }
    );

    recordedAudioProgress
        ?.addEventListener(
            'input',
            () => {
                if (
                    !recordedAudioPreview ||
                    !recordedAudioProgress
                ) {
                    return;
                }

                const duration =
                    getRecordedAudioDuration();

                const percentage =
                    Number(
                        recordedAudioProgress.value
                    );

                if (
                    duration <= 0 ||
                    !Number.isFinite(
                        percentage
                    )
                ) {
                    return;
                }

                const boundedPercentage =
                    Math.min(
                        100,
                        Math.max(
                            0,
                            percentage
                        )
                    );

                const requestedTime =
                    (
                        boundedPercentage /
                        100
                    ) *
                    duration;

                if (
                    Number.isFinite(
                        requestedTime
                    )
                ) {
                    recordedAudioPreview.currentTime =
                        requestedTime;

                    synchronizeRecordedAudioTimeline();
                }
            }
        );

    recordedAudioPreview
        ?.addEventListener(
            'ended',
            () => {
                recordedAudioPreview.currentTime =
                    0;

                synchronizeRecordedAudioTimeline();
            }
        );

    existingPlayButton?.addEventListener(
        'click',
        async () => {
            if (!existingAudio) {
                return;
            }

            if (existingAudio.paused) {
                try {
                    await existingAudio.play();
                } catch (error) {
                    console.error(
                        'Unable to play the existing announcement audio.',
                        error
                    );
                }

                return;
            }

            existingAudio.pause();
        }
    );

    existingAudio?.addEventListener(
        'play',
        () => {
            if (existingPlayIcon) {
                existingPlayIcon.className =
                    'fa-solid fa-pause';
            }

            existingPlayButton
                ?.setAttribute(
                    'aria-label',
                    'Pause existing audio broadcast'
                );

            existingPlayer
                ?.classList.add(
                    'is-playing'
                );
        }
    );

    existingAudio?.addEventListener(
        'pause',
        () => {
            if (existingPlayIcon) {
                existingPlayIcon.className =
                    'fa-solid fa-play';
            }

            existingPlayButton
                ?.setAttribute(
                    'aria-label',
                    'Play existing audio broadcast'
                );

            existingPlayer
                ?.classList.remove(
                    'is-playing'
                );
        }
    );

            let resolvingExistingAudioDuration =
        false;

    /* ==========================================
       EXISTING AUDIO DURATION
    ========================================== */

    const getExistingAudioDuration =
        () => {
            if (!existingAudio) {
                return 0;
            }

            const nativeDuration =
                Number(
                    existingAudio.duration
                );

            if (
                Number.isFinite(
                    nativeDuration
                ) &&
                nativeDuration > 0
            ) {
                return nativeDuration;
            }

            const seekable =
                existingAudio.seekable;

            if (
                seekable &&
                seekable.length > 0
            ) {
                const lastRangeIndex =
                    seekable.length - 1;

                const seekableEnd =
                    Number(
                        seekable.end(
                            lastRangeIndex
                        )
                    );

                if (
                    Number.isFinite(
                        seekableEnd
                    ) &&
                    seekableEnd > 0
                ) {
                    return seekableEnd;
                }
            }

            return 0;
        };

    /* ==========================================
       EXISTING AUDIO TIMELINE
    ========================================== */

    const synchronizeExistingAudioTimeline =
        () => {
            if (
                !existingAudio ||
                resolvingExistingAudioDuration
            ) {
                return;
            }

            const duration =
                getExistingAudioDuration();

            const rawCurrentTime =
                Number(
                    existingAudio.currentTime
                );

            const currentTime =
                Number.isFinite(
                    rawCurrentTime
                )
                    ? Math.max(
                        0,
                        rawCurrentTime
                    )
                    : 0;

            const percentage =
                duration > 0
                    ? Math.min(
                        100,
                        Math.max(
                            0,
                            (
                                currentTime /
                                duration
                            ) * 100
                        )
                    )
                    : 0;

            if (existingCurrentTime) {
                existingCurrentTime.textContent =
                    formatAudioTime(
                        currentTime
                    );
            }

            if (existingDuration) {
                existingDuration.textContent =
                    duration > 0
                        ? formatAudioTime(
                            duration
                        )
                        : '0:00';
            }

            if (existingProgress) {
                existingProgress.value =
                    String(
                        percentage
                    );

                existingProgress.disabled =
                    duration <= 0;

                existingProgress.style
                    .setProperty(
                        '--audio-progress',
                        `${percentage}%`
                    );
            }
        };

    /* ==========================================
       WEBM DURATION RESOLUTION
    ========================================== */

    const resolveExistingWebmDuration =
        () => {
            if (
                !existingAudio ||
                resolvingExistingAudioDuration
            ) {
                return;
            }

            const knownDuration =
                getExistingAudioDuration();

            if (knownDuration > 0) {
                synchronizeExistingAudioTimeline();

                return;
            }

            const nativeDuration =
                Number(
                    existingAudio.duration
                );

            /*
             * Only WebM recordings with an
             * unresolved duration need the
             * large-seek workaround.
             */
            if (nativeDuration !== Infinity) {
                synchronizeExistingAudioTimeline();

                return;
            }

            resolvingExistingAudioDuration =
                true;

            const previousTime =
                Number.isFinite(
                    existingAudio.currentTime
                )
                    ? Math.max(
                        0,
                        existingAudio.currentTime
                    )
                    : 0;

            let resolutionCompleted =
                false;

            let resolutionTimeoutId =
                null;

            const finishDurationResolution =
                () => {
                    if (resolutionCompleted) {
                        return;
                    }

                    resolutionCompleted =
                        true;

                    existingAudio.removeEventListener(
                        'timeupdate',
                        finishDurationResolution
                    );

                    if (
                        resolutionTimeoutId !==
                        null
                    ) {
                        window.clearTimeout(
                            resolutionTimeoutId
                        );
                    }

                    const resolvedDuration =
                        getExistingAudioDuration();

                    resolvingExistingAudioDuration =
                        false;

                    const restoredTime =
                        resolvedDuration > 0
                            ? Math.min(
                                previousTime,
                                resolvedDuration
                            )
                            : 0;

                    try {
                        existingAudio.currentTime =
                            restoredTime;
                    } catch (error) {
                        existingAudio.currentTime =
                            0;
                    }

                    synchronizeExistingAudioTimeline();
                };

            existingAudio.addEventListener(
                'timeupdate',
                finishDurationResolution
            );

            resolutionTimeoutId =
                window.setTimeout(
                    finishDurationResolution,
                    1000
                );

            try {
                /*
                 * This large but finite seek makes
                 * Chromium calculate the actual
                 * duration of MediaRecorder WebM.
                 */
                existingAudio.currentTime =
                    1e101;
            } catch (error) {
                finishDurationResolution();
            }
        };

    /* ==========================================
       EXISTING AUDIO EVENTS
    ========================================== */

    existingAudio?.addEventListener(
        'loadedmetadata',
        resolveExistingWebmDuration
    );

    [
        'loadedmetadata',
        'durationchange',
        'loadeddata',
        'canplay',
        'progress',
        'timeupdate'
    ].forEach(
        (eventName) => {
            existingAudio?.addEventListener(
                eventName,
                synchronizeExistingAudioTimeline
            );
        }
    );

    existingProgress?.addEventListener(
        'input',
        () => {
            if (
                !existingAudio ||
                !existingProgress
            ) {
                return;
            }

            const duration =
                getExistingAudioDuration();

            const percentage =
                Number(
                    existingProgress.value
                );

            if (
                duration <= 0 ||
                !Number.isFinite(
                    percentage
                )
            ) {
                return;
            }

            const boundedPercentage =
                Math.min(
                    100,
                    Math.max(
                        0,
                        percentage
                    )
                );

            const requestedTime =
                (
                    boundedPercentage /
                    100
                ) *
                duration;

            if (
                Number.isFinite(
                    requestedTime
                )
            ) {
                existingAudio.currentTime =
                    requestedTime;

                synchronizeExistingAudioTimeline();
            }
        }
    );

    /*
     * Cached audio may already have metadata
     * before event listeners are registered.
     */
    if (
        existingAudio &&
        existingAudio.readyState >= 1
    ) {
        resolveExistingWebmDuration();
    } else {
        synchronizeExistingAudioTimeline();
    }

    removeAudio?.addEventListener(
        'change',
        () => {
            if (
                removeAudio.checked &&
                existingAudio
            ) {
                existingAudio.pause();
                existingAudio.currentTime =
                    0;
            }
        }
    );

    transcript?.addEventListener(
        'input',
        synchronizeAudioState
    );

    synchronizeAudioState();
}

function validateContentDetails() {
    const type =
        getCurrentType();

    const fieldMap = {
        announcement: {
            title:
                document.getElementById(
                    'announcementTitle'
                ),

            description:
                document.getElementById(
                    'announcementContent'
                ),

            titleLabel:
                'Announcement title',

            descriptionLabel:
                'Announcement content'
        },

        event: {
            title:
                document.getElementById(
                    'eventTitle'
                ),

            description:
                document.getElementById(
                    'eventDescription'
                ),

            titleLabel:
                'Event title',

            descriptionLabel:
                'Event description'
        },

        document: {
            title:
                documentTitleInput,

            description:
                documentDescriptionInput,

            titleLabel:
                'Document title',

            descriptionLabel:
                'Document description'
        },

        survey: {
            title:
                surveyTitleInput,

            description:
                surveyDescriptionInput,

            titleLabel:
                'Survey title',

            descriptionLabel:
                'Survey description'
        }
    };

    const fields =
        fieldMap[type];

    if (!fields) {
        return true;
    }

    if (
        fields.title?.value
            .trim() === ''
    ) {
        showAlert(
            'error',
            `${fields.titleLabel} required`,
            `Enter the ${fields.titleLabel.toLowerCase()}.`
        );

        focusInvalidField(
            fields.title
        );

        return false;
    }

    if (
        fields.description?.value
            .trim() === ''
    ) {
        showAlert(
            'error',
            `${fields.descriptionLabel} required`,
            `Enter the ${fields.descriptionLabel.toLowerCase()}.`
        );

        focusInvalidField(
            fields.description
        );

        return false;
    }

    if (type === 'document') {
        const hasNewDocument =
            Boolean(
                documentFile
                    ?.files?.[0]
            );

        const hasExistingDocument =
            (
                documentFile?.dataset
                    .existingFile
                ?? ''
            ).trim() !== '';

        if (
            !hasNewDocument &&
            !hasExistingDocument
        ) {
            showAlert(
                'error',
                'Document required',
                'Select the document file you want to publish.'
            );

            focusInvalidField(
                documentFile,
                documentFile?.closest(
                    '.form-field'
                )
            );

            return false;
        }
    }

    return true;
}

function validateSurveyFields() {
    if (
        getCurrentType() !==
        'survey'
    ) {
        return true;
    }

    const title =
        surveyTitleInput?.value
            .trim() ||
        '';

    const description =
        surveyDescriptionInput?.value
            .trim() ||
        '';

    if (title === '') {
        showAlert(
            'error',
            'Survey title required',
            'Enter a title for the survey.'
        );

        focusInvalidField(
            surveyTitleInput
        );

        return false;
    }

    if (description === '') {
        showAlert(
            'error',
            'Survey description required',
            'Enter the purpose or description of the survey.'
        );

        focusInvalidField(
            surveyDescriptionInput
        );

        return false;
    }

    if (
        surveyOpensAtInput?.value &&
        surveyClosesAtInput?.value
    ) {
        const opensAt =
            new Date(
                surveyOpensAtInput.value
            );

        const closesAt =
            new Date(
                surveyClosesAtInput.value
            );

        if (
            Number.isNaN(
                opensAt.getTime()
            ) ||
            Number.isNaN(
                closesAt.getTime()
            )
        ) {
            showAlert(
                'error',
                'Invalid survey schedule',
                'Enter valid survey opening and closing dates.'
            );

            return false;
        }

        if (
            closesAt.getTime() <=
            opensAt.getTime()
        ) {
            showAlert(
                'error',
                'Invalid survey schedule',
                'The survey closing time must be later than its opening time.'
            );

            focusInvalidField(
                surveyClosesAtInput
            );

            return false;
        }
    }

    return true;
}

function getContentReadiness() {
    const type =
        getCurrentType();

    let titleReady = false;
    let descriptionReady = false;

    if (type === 'announcement') {
        titleReady =
            document
                .getElementById(
                    'announcementTitle'
                )
                ?.value.trim() !== '';

        descriptionReady =
            document
                .getElementById(
                    'announcementContent'
                )
                ?.value.trim() !== '';
    }

    if (type === 'event') {
        titleReady =
            document
                .getElementById(
                    'eventTitle'
                )
                ?.value.trim() !== '';

        descriptionReady =
            document
                .getElementById(
                    'eventDescription'
                )
                ?.value.trim() !== '';
    }

    if (type === 'survey') {
        titleReady =
            surveyTitleInput
                ?.value.trim() !== '';

        descriptionReady =
            surveyDescriptionInput
                ?.value.trim() !== '';
    }

    if (type === 'document') {
    const hasDocumentFile =
        Boolean(
            documentFile
                ?.files?.[0]
        ) ||
        (
            documentFile?.dataset
                .existingFile
            ?? ''
        ).trim() !== '';

    titleReady =
        documentTitleInput
            ?.value.trim() !== '' &&
        hasDocumentFile;

    descriptionReady =
        documentDescriptionInput
            ?.value.trim() !== '' &&
        hasDocumentFile;
}

    const audienceReady =
        getAudienceScope() ===
            'schoolwide' ||
        roleCheckboxes.some(
            (checkbox) =>
                checkbox.checked
        );

    let questionsReady = true;

    if (type === 'survey') {
        const cards =
            Array.from(
                surveyQuestionList
                    ?.querySelectorAll(
                        '.survey-question-card'
                    ) ||
                []
            );

        questionsReady =
            cards.length > 0 &&
            cards.every((card) => {
                const question =
                    card.querySelector(
                        'input[name*="[question]"]'
                    );

                return (
                    question?.value
                        .trim() !== ''
                );
            });
    }

    const releaseMode =
        getReleaseMode();

    let scheduleReady = true;

    if (
        releaseMode ===
        'scheduled'
    ) {
        scheduleReady =
            Boolean(
                scheduledPublishInput
                    ?.value
            );
    }

    if (
        releaseMode ===
        'calendar'
    ) {
        scheduleReady =
            Boolean(
                calendarReference
                    ?.value
            );
    }

    return {
        title: titleReady,
        description:
            descriptionReady,
        audience:
            audienceReady,
        questions:
            questionsReady,
        schedule:
            scheduleReady
    };
}


function updatePublishingReadiness() {
    if (!publishingRequirements) {
        return;
    }

    const state =
        getContentReadiness();

    const type =
        getCurrentType();

    const questionRequirement =
        publishingRequirements
            .querySelector(
                '[data-requirement="questions"]'
            );

    if (questionRequirement) {
        questionRequirement.hidden =
            type !== 'survey';
    }

    let completed = 0;
    let total = 0;

    Object.entries(state)
        .forEach(
            ([key, ready]) => {

                if (
                    key === 'questions' &&
                    type !== 'survey'
                ) {
                    return;
                }

                const item =
                    publishingRequirements
                        .querySelector(
                            `[data-requirement="${key}"]`
                        );

                if (!item) {
                    return;
                }

                total += 1;

                item.classList.toggle(
                    'complete',
                    ready
                );

                item.classList.toggle(
                    'incomplete',
                    !ready
                );

                const icon =
                    item.querySelector('i');

                if (icon) {
                    icon.className =
                        ready
                            ? 'fa-solid fa-circle-check'
                            : 'fa-regular fa-circle';
                }

                if (ready) {
                    completed += 1;
                }
            }
        );

    if (readinessCount) {
        readinessCount.textContent =
            `${completed} / ${total}`;
    }

    const complete =
        completed === total;



    if (readinessTitle) {
        readinessTitle.textContent =
            complete
                ? 'Ready to publish'
                : 'Content needs attention';
    }

    if (readinessDescription) {
        readinessDescription.textContent =
            complete
                ? 'All publishing requirements are complete.'
                : 'Complete the remaining requirements before publishing.';
    }

    publishingRequirements
        .closest(
            '.publishing-readiness-card'
        )
        ?.classList.toggle(
            'ready',
            complete
        );
}

function updatePublishingInspector() {
    const type =
        getCurrentType();

    const content =
        getCurrentContent();

    const audience =
        getAudienceLabel();

    const release =
        getReleaseLabel();

    const readiness =
        getContentReadiness();

    const announcementAudioField =
        document.querySelector(
            '[data-announcement-audio-field]'
        );

    const announcementAudioInput =
        announcementAudioField
            ?.querySelector(
                '[data-announcement-audio-input]'
            );

    const removeAnnouncementAudio =
        announcementAudioField
            ?.querySelector(
                '[data-remove-announcement-audio]'
            );

    const hasAnnouncementAudio =
        type === 'announcement' &&
        (
            Boolean(
                announcementAudioInput
                    ?.files?.[0]
            ) ||
            (
                announcementAudioField
                    ?.dataset
                    .existingAudio ===
                    'true' &&
                !removeAnnouncementAudio
                    ?.checked
            )
        );

            const livePreviewAudio =
        document.getElementById(
            'livePreviewAudio'
        );

    const livePreviewAudioLabel =
        document.getElementById(
            'livePreviewAudioLabel'
        );

    const selectedAnnouncementAudio =
        announcementAudioInput
            ?.files?.[0]
        ?? null;

    if (livePreviewAudio) {
        livePreviewAudio.hidden =
            !hasAnnouncementAudio;
    }

    if (
        livePreviewAudioLabel &&
        hasAnnouncementAudio
    ) {
        const selectedFileName =
            String(
                selectedAnnouncementAudio
                    ?.name
                ?? ''
            ).toLowerCase();

        const isRecordedAudio =
            selectedFileName.startsWith(
                'announcement-recording-'
            );

        if (isRecordedAudio) {
            livePreviewAudioLabel.textContent =
                'Recorded audio attached';
        } else if (
            selectedAnnouncementAudio
        ) {
            livePreviewAudioLabel.textContent =
                'Audio file attached';
        } else {
            livePreviewAudioLabel.textContent =
                'Existing audio broadcast';
        }
    }

    /* ======================================
       LIVE PREVIEW
    ====================================== */

const existingPreviewImage =
    content.imageInput
        ? content.imageInput
            .closest('.form-field')
            ?.querySelector(
                '[data-image-preview] img'
            )
        : null;

const existingImageSource =
    existingPreviewImage
        ?.getAttribute('src')
        ?.trim()
    || '';

const selectedImageSource =
    content.imageInput
        ? (
            imagePreviewSources.get(
                content.imageInput.id
            )
            || ''
        )
        : '';

const imageSource =
    selectedImageSource ||
    existingImageSource;


    /* ======================================
   LIVE PREVIEW METADATA
====================================== */

if (livePreviewAudience) {
    const audienceText =
        livePreviewAudience.querySelector(
            'span'
        );

    if (audienceText) {
        audienceText.textContent =
    getCompactAudienceLabel();
    }
}

if (livePreviewRelease) {
    const releaseText =
        livePreviewRelease.querySelector(
            'span'
        );

    if (releaseText) {
releaseText.textContent =
    getCompactReleaseLabel();
    }
}

if (
    livePreviewMedia &&
    livePreviewImage
) {
    if (imageSource) {
        livePreviewImage.src =
            imageSource;

        livePreviewMedia.hidden =
            false;
    } else {
        livePreviewImage.removeAttribute(
            'src'
        );

        livePreviewMedia.hidden =
            true;
    }
}

    if (livePreviewType) {
        livePreviewType.textContent =
            contentLabels[type] ||
            'Content';
    }

 if (livePreviewPriority) {
    if (type === 'survey') {
        const questionCount =
            surveyQuestionList
                ?.querySelectorAll(
                    '.survey-question-card'
                )
                .length || 0;

        livePreviewPriority.textContent =
            `${questionCount} ${
                questionCount === 1
                    ? 'Question'
                    : 'Questions'
            }`;
    } else {
        livePreviewPriority.textContent =
            content.priority;
    }
}

    if (livePreviewTitle) {
        livePreviewTitle.textContent =
            content.title;
    }
if (livePreviewDescription) {
    if (content.isRichText) {
        const safeHtml =
            sanitizeRichTextClient(
                content.content
            );

        const hasContent =
            getRichTextPlainText(
                safeHtml
            ) !== '';

        if (hasContent) {
            livePreviewDescription.innerHTML =
                safeHtml;
        } else {
            livePreviewDescription.textContent =
                content.fallback;
        }
    } else {
        livePreviewDescription.textContent =
            content.content ||
            content.fallback;
    }
}

    /* ======================================
       AUDIENCE
    ====================================== */

    if (inspectorAudience) {
        inspectorAudience.textContent =
            audience;
    }

    /* ======================================
       RELEASE
    ====================================== */

    if (inspectorRelease) {
        inspectorRelease.textContent =
            release;
    }

    /* ======================================
       READINESS SUMMARY
    ====================================== */

    if (!inspectorReadiness) {
        return;
    }

    const requirements = [
        {
            key: 'title',
            label: 'Title'
        },
        {
            key: 'description',
            label: 'Description'
        },
        {
            key: 'audience',
            label: 'Recipients'
        },
        {
            key: 'questions',
            label: 'Questions',
            surveyOnly: true
        },
        {
            key: 'schedule',
            label: 'Release'
        }
    ];

    const visibleRequirements =
        requirements.filter(
            (requirement) =>
                !requirement.surveyOnly ||
                type === 'survey'
        );

    const completed =
        visibleRequirements.filter(
            (requirement) =>
                readiness[
                    requirement.key
                ]
        ).length;

    const total =
        visibleRequirements.length;

    const complete =
        completed === total;


    if (publisherCommandBar) {
    publisherCommandBar
        .classList.toggle(
            'ready',
            complete
        );
}

if (commandReadinessText) {
    commandReadinessText.textContent =
        complete
            ? (
                isFacultyUser
                    ? 'Ready to submit for review'
                    : 'Ready for publishing'
            )
            : `${completed} of ${total} requirements complete`;
}

    inspectorReadiness.innerHTML = `
        <div class="inspector-readiness-summary">
            <strong>
                ${
                    complete
                        ? 'Ready to publish'
                        : `${completed} of ${total} complete`
                }
            </strong>

            <div class="inspector-readiness-list">

                ${visibleRequirements
                    .map(
                        (requirement) => {
                            const ready =
                                readiness[
                                    requirement.key
                                ];

                            return `
                                <div
                                    class="inspector-readiness-item ${
                                        ready
                                            ? 'complete'
                                            : 'incomplete'
                                    }">

                                    <i class="${
                                        ready
                                            ? 'fa-solid fa-circle-check'
                                            : 'fa-regular fa-circle'
                                    }"></i>

                                    <span>
                                        ${requirement.label}
                                    </span>

                                </div>
                            `;
                        }
                    )
                    .join('')}

                ${
                    hasAnnouncementAudio
                        ? `
                            <div
                                class="inspector-readiness-item complete">

                                <i class="fa-solid fa-volume-high"></i>

                                <span>
                                    Audio attached
                                </span>

                            </div>
                        `
                        : ''
                }

            </div>
        </div>
    `;

    inspectorReadiness
        .classList.toggle(
            'ready',
            complete
        );
}

form.addEventListener(
    'input',
    () => {
        updateAudienceSummary();
        updatePublishingReadiness();
        updatePublishingInspector();
    }
);

form.addEventListener(
    'change',
    () => {
        updateAudienceSummary();
        updatePublishingReadiness();
        updatePublishingInspector();
    }
);

function validateSurveyQuestions() {
    if (
        getCurrentType() !==
        'survey'
    ) {
        return true;
    }

    if (!surveyQuestionList) {
        showAlert(
            'error',
            'Survey unavailable',
            'The survey question builder could not be loaded.'
        );

        return false;
    }

    const cards =
        Array.from(
            surveyQuestionList
                .querySelectorAll(
                    '.survey-question-card'
                )
        );

    if (cards.length === 0) {
        showAlert(
            'error',
            'Question required',
            'Add at least one question to the survey.'
        );

        return false;
    }

    for (const card of cards) {
        const questionInput =
            card.querySelector(
                'input[name*="[question]"]'
            );

        const typeSelect =
            card.querySelector(
                '[data-survey-question-type]'
            );

        const questionType =
            typeSelect?.value ||
            '';

        if (
            !questionInput ||
            questionInput.value
                .trim() === ''
        ) {
            showAlert(
                'error',
                'Question text required',
                'Every survey question must contain question text.'
            );

focusInvalidField(
    questionInput,
    card
);

return false;
        }

        if (
            questionType ===
                'Multiple Choice' ||
            questionType ===
                'Checkbox'
        ) {
            const choices =
                Array.from(
                    card.querySelectorAll(
                        '[data-survey-choice-list] input'
                    )
                );

            const validChoices =
                choices.filter(
                    (input) =>
                        input.value
                            .trim() !== ''
                );

            if (
                validChoices.length < 2
            ) {
                showAlert(
                    'error',
                    'Answer choices required',
                    'Multiple Choice and Checkbox questions need at least two answer choices.'
                );

            focusInvalidField(
    choices[0],
    card
);

return false;
            }

            const normalizedChoices =
                validChoices.map(
                    (input) =>
                        input.value
                            .trim()
                            .toLowerCase()
                );

            const uniqueChoices =
                new Set(
                    normalizedChoices
                );

            if (
                uniqueChoices.size !==
                normalizedChoices.length
            ) {
               showAlert(
    'error',
    'Duplicate answer choices',
    'Answer choices within the same question must be unique.'
);

focusInvalidField(
    validChoices[0],
    card
);

                return false;
            }
        }

    }

    return true;
}

    /* ==========================================
       VALIDATION
    ========================================== */

    function validateEventSchedule() {
        if (
            getCurrentType() !==
            'event'
        ) {
            return true;
        }

        if (!eventDateInput?.value) {
            return true;
        }

        const start =
            new Date(
                eventDateInput.value
            );

        if (
            start.getTime() <
            Date.now()
        ) {
            showAlert(
                'error',
                'Invalid event date',
                'The event start date cannot be in the past.'
            );

            return false;
        }

        if (eventEndDateInput?.value) {
            const end =
                new Date(
                    eventEndDateInput.value
                );

            if (
                end.getTime() <=
                start.getTime()
            ) {
                showAlert(
                    'error',
                    'Invalid event schedule',
                    'The event end date must be later than the start date.'
                );

                return false;
            }
        }

        return true;
    }

    function validateRelease() {
        const mode =
            getReleaseMode();

        if (
            mode === 'scheduled'
        ) {
            if (
                !scheduledPublishInput
                    ?.value
            ) {
                showAlert(
                    'error',
                    'Schedule required',
                    'Select a scheduled release date and time.'
                );
focusInvalidField(
    scheduledPublishInput
);
            }

            const selected =
                new Date(
                    scheduledPublishInput
                        .value
                );

            if (
                selected.getTime() <=
                Date.now()
            ) {
                showAlert(
                    'error',
                    'Invalid schedule',
                    'The scheduled release must be in the future.'
                );

                focusInvalidField(
    scheduledPublishInput
);

                return false;
            }
        }

        if (
            mode === 'calendar' &&
            !calendarReference?.value
        ) {
            showAlert(
                'error',
                'Calendar event required',
                'Select the calendar event connected to this announcement.'
            );

            focusInvalidField(
    calendarReference
);

            calendarReference?.focus();

            return false;
        }

        return true;
    }

    function validateContentTopics(
    showMessage = true
) {
    const section =
        document.querySelector(
            '[data-content-topic-section]'
        );

    if (!section) {
        return true;
    }

    const selectedTopics =
        section.querySelectorAll(
            '[data-content-topic-checkbox]:checked'
        );

    const validation =
        document.getElementById(
            'contentTopicValidation'
        );

    if (selectedTopics.length > 0) {
        if (validation) {
            validation.hidden =
                true;
        }

        return true;
    }

    if (validation) {
        validation.hidden =
            false;
    }

    const chooser =
        document.getElementById(
            'contentTopicChooser'
        );

    const toggleButton =
        document.getElementById(
            'toggleContentTopicChooser'
        );

    if (chooser) {
        chooser.hidden =
            false;
    }

    toggleButton?.setAttribute(
        'aria-expanded',
        'true'
    );

    if (showMessage) {
        showAlert(
            'warning',
            'Content topic required',
            'Select at least one topic that describes this content.'
        );
    }

    section.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    section.querySelector(
        '[data-content-topic-checkbox]'
    )?.focus();

    return false;
}

    /* ==========================================
       FORM SUBMISSION
    ========================================== */

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            applyEmergencyRules();
            updateReleaseFields();

            if (!validateContentDetails()) {
    return;
}

if (!validateContentTopics()) {
    return;
}

            if (!validateAudience()) {
    return;
}

if (!validateSurveyFields()) {
    return;
}

if (!validateSurveyQuestions()) {
    return;
}

if (!validateEventSchedule()) {
    return;
}

if (!validateRelease()) {
    return;
}

if (!form.checkValidity()) {
    form.reportValidity();
    return;
}

if (
    !await runRedundancyPreflight()
) {
    return;
}

            const type =
                getCurrentType();

            const typeLabel =
                contentLabels[type];

            const workflow =
                getWorkflowAction();

            const actionLabel =
                workflowLabels[workflow] ||
                'Publish';

            const audience =
                getAudienceLabel();

            const result =
                typeof Swal !==
                'undefined'
                    ? await Swal.fire({
                        icon: 'question',
                        title: actionLabel,
                        html: `
                            <p style="margin:0;line-height:1.6;">
                                <strong>${typeLabel}</strong>
                                will be processed for
                                <strong>${audience}</strong>.
                            </p>
                        `,
                        showCancelButton: true,
                        confirmButtonText:
                            actionLabel,
                        cancelButtonText:
                            'Review Form',
                        confirmButtonColor:
                            '#8B0000',
                        cancelButtonColor:
                            '#6c757d'
                    })
                    : {
                        isConfirmed:
                            window.confirm(
                                `${actionLabel} this ${typeLabel}?`
                            )
                    };

            if (!result.isConfirmed) {
                return;
            }

            if (publishButton) {
                publishButton.disabled =
                    true;
            }

            if (publishButtonText) {
                publishButtonText.textContent =
                    workflow === 'draft'
                        ? 'Saving Draft...'
                        : workflow ===
                          'submit_review'
                            ? 'Submitting...'
                            : 'Publishing...';
            }

            if (form.dataset.advisoryBlocked === 'true') {
                const advisory = document.getElementById('postingAdvisory');
                if (advisory) { advisory.open = true; advisory.scrollIntoView({block: 'center'}); }
                return;
            }
            form.submit();
        }
    );


    /* ==========================================
   SURVEY ADD QUESTION
========================================== */

    if (
    addSurveyQuestionButton &&
    surveyQuestionList
) {
    addSurveyQuestionButton
        .addEventListener(
            'click',
            () => {
                const card =
                    createSurveyQuestionCard();

                surveyQuestionList
                    .appendChild(
                        card
                    );

                renumberSurveyQuestions();

                updateSurveyQuestionType(
                    card
                );

                const questionInput =
                    card.querySelector(
                        'input[name*="[question]"]'
                    );

                if (questionInput) {
                    questionInput.focus();
                }
            }
        );
}

if (surveyQuestionList) {
    surveyQuestionList
        .addEventListener(
            'click',
            (event) => {
                const removeQuestionButton =
                    event.target.closest(
                        '[data-remove-survey-question]'
                    );

                if (removeQuestionButton) {
                    const card =
                        removeQuestionButton.closest(
                            '.survey-question-card'
                        );

                    const cards =
                        surveyQuestionList.querySelectorAll(
                            '.survey-question-card'
                        );

                    if (
                        card &&
                        cards.length > 1
                    ) {
                        card.remove();

                        renumberSurveyQuestions();
                    }

                    return;
                }

                const addChoiceButton =
                    event.target.closest(
                        '[data-add-survey-choice]'
                    );

                if (addChoiceButton) {
                    const card =
                        addChoiceButton.closest(
                            '.survey-question-card'
                        );

                    addSurveyChoice(
                        card
                    );

                    return;
                }

                const removeChoiceButton =
                    event.target.closest(
                        '[data-remove-survey-choice]'
                    );

                if (removeChoiceButton) {
                    const row =
                        removeChoiceButton.closest(
                            '.survey-choice-row'
                        );

                    const card =
                        removeChoiceButton.closest(
                            '.survey-question-card'
                        );

                    if (
                        row &&
                        card
                    ) {
                        const choiceList =
                            card.querySelector(
                                '[data-survey-choice-list]'
                            );

                        if (
                            choiceList &&
                            choiceList.children.length > 2
                        ) {
                            row.remove();
                        }
                    }
                }
            }
        );

    surveyQuestionList
        .addEventListener(
            'change',
            (event) => {
                if (
                    !event.target.matches(
                        '[data-survey-question-type]'
                    )
                ) {
                    return;
                }

                const card =
                    event.target.closest(
                        '.survey-question-card'
                    );

                updateSurveyQuestionType(
                    card
                );
            }
        );
}

    /* ==========================================
       INITIAL STATE
    ========================================== */

    const initialWorkflow =
        workflowInput?.value ||
        workflowButtons.find(
            (button) =>
                button.classList
                    .contains('active')
        )?.dataset.workflowAction ||
        'publish';

    activateWorkflow(
    initialWorkflow
);

if (
    getCurrentType() === 'survey' &&
    surveyQuestionList &&
    surveyEditQuestions.length > 0
) {
    surveyQuestionList.innerHTML = '';

    surveyEditQuestions.forEach(
        (questionData) => {
            const card =
                createSurveyQuestionCard();

            surveyQuestionList
                .appendChild(
                    card
                );

            populateSurveyQuestionCard(
                card,
                questionData
            );
        }
    );

    renumberSurveyQuestions();
}

activateType(
    typeInput?.value ||
    'announcement'
);
updateReleaseFields();
updateSubmitButton();
applyAudienceScope();
updateSurveyQuestionCount();
updateSurveyRemoveButtons();

/* ==========================================
   INITIALIZE RICH TEXT EDITORS
========================================== */

initializeRichTextEditors();
initializeAnnouncementAudio();

updatePublishingReadiness();
updatePublishingInspector();

});


/* ==========================================
   COMPACT CONTENT TOPICS
========================================== */

function setupCompactContentTopics() {
    const section =
        document.querySelector(
            '[data-content-topic-section]'
        );

    if (!section) {
        return;
    }

    const chooser =
        document.getElementById(
            'contentTopicChooser'
        );

    const toggleButton =
        document.getElementById(
            'toggleContentTopicChooser'
        );

    const closeButton =
        document.getElementById(
            'closeContentTopicChooser'
        );

    const selectedContainer =
        section.querySelector(
            '[data-content-topic-selected]'
        );

    const countElement =
        section.querySelector(
            '[data-content-topic-count]'
        );

    const validation =
        document.getElementById(
            'contentTopicValidation'
        );

    const livePreviewTopics =
        document.getElementById(
            'livePreviewTopics'
        );

    const previewTopics =
        document.getElementById(
            'previewTopics'
        );

    const checkboxes =
        Array.from(
            section.querySelectorAll(
                '[data-content-topic-checkbox]'
            )
        );

    toggleButton?.addEventListener(
        'click',
        () => {
            setChooserOpen(
                chooser?.hidden !== false
            );
        }
    );

    closeButton?.addEventListener(
        'click',
        () => {
            setChooserOpen(
                false
            );

            toggleButton?.focus();
        }
    );

    checkboxes.forEach(
        (checkbox) => {
            checkbox.addEventListener(
                'change',
                updateTopics
            );
        }
    );

    updateTopics();

    function setChooserOpen(
        isOpen
    ) {
        if (chooser) {
            chooser.hidden =
                !isOpen;
        }

        toggleButton?.setAttribute(
            'aria-expanded',
            String(isOpen)
        );
    }

    function getSelectedTopics() {
        return checkboxes
            .filter(
                (checkbox) =>
                    checkbox.checked
            )
            .map(
                (checkbox) => ({
                    label:
                        checkbox.dataset
                            .topicLabel ||
                        'Topic',

                    option:
                        checkbox.closest(
                            '[data-content-topic-option]'
                        )
                })
            );
    }

        function updateTopics() {
        const selectedTopics =
            getSelectedTopics();

        checkboxes.forEach(
            (checkbox) => {
                checkbox.closest(
                    '[data-content-topic-option]'
                )?.classList.toggle(
                    'is-selected',
                    checkbox.checked
                );
            }
        );

        renderChips(
            selectedContainer,
            selectedTopics,
            false
        );

        renderChips(
            livePreviewTopics,
            selectedTopics,
            true
        );

        renderChips(
            previewTopics,
            selectedTopics,
            false
        );

        if (countElement) {
            countElement.textContent =
                `${selectedTopics.length} selected`;
        }

        if (
            validation &&
            selectedTopics.length > 0
        ) {
            validation.hidden =
                true;
        }
    }

    function renderChips(
        container,
        topics,
        compact
    ) {
        if (!container) {
            return;
        }

        container.innerHTML =
            '';

        const displayedTopics =
            compact
                ? topics.slice(
                    0,
                    2
                )
                : topics;

        displayedTopics.forEach(
            (topic) => {
                const chip =
                    document.createElement(
                        'span'
                    );

                chip.textContent =
                    topic.label;

                container.appendChild(
                    chip
                );
            }
        );

        if (
            compact &&
            topics.length > 2
        ) {
            const remaining =
                document.createElement(
                    'span'
                );

            remaining.textContent =
                `+${topics.length - 2}`;

            container.appendChild(
                remaining
            );
        }

        if (
            topics.length === 0 &&
            container ===
                selectedContainer
        ) {
            const placeholder =
                document.createElement(
                    'small'
                );

            placeholder.textContent =
                'No topics selected';

            container.appendChild(
                placeholder
            );
        }

        container.hidden =
            topics.length === 0 &&
            container !==
                selectedContainer;
    }
}

   if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        setupCompactContentTopics
    );
} else {
    setupCompactContentTopics();
}

// Government sources enter the normal announcement editor, with explicit Admin review.
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('postingAdvisory');
    if (!panel) return;
    const byId = (name) => document.getElementById('postingAdvisory' + name);
    const form = panel.closest('form');
    const id = byId('Id');
    const submittedUrl = byId('SubmittedUrl');
    const urlInput = byId('Url');
    const fields = [...panel.querySelectorAll('[data-advisory-field]')];
    const check = byId('Check');
    const use = byId('Use');
    const remove = byId('Remove');
    const preview = byId('Preview');
    const status = byId('Status');
    const type = document.getElementById('post_type');
    let busy = false;
    let checking = false;
    let checkTimer = null;
    let checkSequence = 0;
    let activeCheck = null;
    let checkedPayload = null;
    const sourcePreviews = ['livePreviewDescription', 'previewContent'].map((name) => {
        const description = document.getElementById(name);
        if (!description) return null;
        const source = document.createElement('div');
        source.className = 'publisher-preview-government';
        source.hidden = true;
        const label = document.createElement('span');
        label.textContent = 'Government advisory';
        const link = document.createElement('a');
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        source.append(label, link);
        description.after(source);
        return {source, link};
    }).filter(Boolean);
    const showState = () => {
        const linked = Number(id.value) > 0;
        let sourceUrl;
        try { sourceUrl = new URL(submittedUrl.value); } catch (_) { /* No attached source. */ }
        const showSource = linked && type.value === 'announcement' && sourceUrl
            && sourceUrl.protocol === 'https:' && !sourceUrl.username && !sourceUrl.password;
        sourcePreviews.forEach(({source, link}) => {
            source.hidden = !showSource;
            if (showSource) {
                link.href = sourceUrl.href;
                link.textContent = sourceUrl.href;
                link.setAttribute('aria-label', 'Official source: ' + sourceUrl.href + ' (new tab)');
            } else {
                link.removeAttribute('href');
                link.removeAttribute('aria-label');
                link.textContent = '';
            }
        });
        fields.forEach((field) => { field.disabled = busy || linked || type.value !== 'announcement'; });
        check.disabled = busy || checking || linked;
        use.disabled = busy || checking || !checkedPayload;
        remove.hidden = !linked && urlInput.value.trim() === '';
        remove.disabled = busy;
        panel.setAttribute('aria-busy', String(busy || checking));
        const blocked = type.value === 'announcement' && (busy || checking || checkTimer !== null
            || (submittedUrl.value.trim() !== '' && !linked));
        form.dataset.advisoryBlocked = String(blocked);
        ['publishButton', 'saveDraftButton'].forEach((name) => {
            const button = document.getElementById(name);
            if (button) button.disabled = blocked;
        });
    };
    const payload = () => {
        const data = new FormData();
        fields.forEach((field) => data.set(field.dataset.advisoryField, field.value.trim()));
        data.set('csrf_token', form.querySelector('[name="csrf_token"]').value);
        data.set('announcement_link', '1');
        return data;
    };
    const request = async (route, data, signal) => {
        const response = await fetch('index.php?page=' + route, {
            method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: data, signal
        });
        const result = await response.json();
        if (!response.ok || result.status !== 'success') throw new Error(result.message || 'The advisory could not be checked.');
        return result;
    };
    const invalidateCheck = () => {
        clearTimeout(checkTimer); checkTimer = null;
        checkSequence++;
        activeCheck?.abort(); activeCheck = null;
        checking = false; checkedPayload = null; preview.hidden = true;
    };
    const checkLink = async () => {
        if (busy || Number(id.value) > 0 || type.value !== 'announcement') return;
        invalidateCheck();
        submittedUrl.value = urlInput.value.trim();
        let url;
        try { url = new URL(submittedUrl.value); } catch (_) { /* shown below */ }
        if (!url || url.protocol !== 'https:') {
            status.textContent = submittedUrl.value === '' ? '' : 'Enter a complete HTTPS link from an official government source.';
            showState();
            return;
        }
        const sequence = checkSequence;
        const data = payload();
        activeCheck = new AbortController();
        checking = true; showState();
        status.textContent = 'Checking the official source...';
        try {
            const result = await request('government_advisory_preview', data, activeCheck.signal);
            if (sequence !== checkSequence) return;
            checkedPayload = data;
            byId('PreviewTitle').textContent = result.preview.title || '';
            byId('PreviewText').textContent = result.preview.summary || result.preview.excerpt || '';
            preview.hidden = false;
            status.textContent = 'Valid official source. Review it and choose Attach advisory before posting.';
        } catch (error) {
            if (sequence !== checkSequence) return;
            status.textContent = error.message || 'Unable to verify this source. Try again or remove the link.';
        } finally {
            if (sequence === checkSequence) { checking = false; activeCheck = null; showState(); }
        }
    };
    urlInput.addEventListener('input', () => {
        invalidateCheck();
        submittedUrl.value = urlInput.value.trim();
        if (submittedUrl.value !== '') {
            status.textContent = 'Waiting to check the official link...';
            checkTimer = setTimeout(() => { checkTimer = null; checkLink(); }, 600);
        } else { status.textContent = ''; }
        showState();
    });
    check.addEventListener('click', () => { if (!checking) checkLink(); });
    use.addEventListener('click', async () => {
        if (busy || !checkedPayload || type.value !== 'announcement') return;
        busy = true; showState();
        try {
            if (!window.AppDialog) throw new Error('Confirmation is unavailable. Reload the page and try again.');
            const confirmed = await window.AppDialog.confirm({
                type: 'question', title: 'Attach this advisory?',
                message: 'Confirm this official source is relevant to your announcement. Your title and caption will stay unchanged.',
                confirmText: 'Attach advisory', cancelText: 'Cancel', dismissible: true
            });
            if (!confirmed) return;
            checkedPayload.set('review_confirmed', '1');
            status.textContent = 'Verifying and attaching the source...';
            const result = await request('government_advisory_prepare', checkedPayload);
            const advisory = result.advisory;
            id.value = advisory.government_advisory_id;
            submittedUrl.value = advisory.source_url;
            urlInput.value = advisory.source_url;
            preview.hidden = true;
            status.textContent = 'Government advisory attached. Your title and caption are unchanged.';
            document.getElementById('announcementTitle').focus();
        } catch (error) {
            status.textContent = error.message || 'Unable to prepare the announcement. Try again.';
        } finally { busy = false; showState(); }
    });
    remove.addEventListener('click', async () => {
        if (busy || !window.AppDialog) return;
        busy = true; showState();
        try {
            if (await window.AppDialog.confirm({type: 'question', title: 'Remove advisory link?',
                message: 'Your title and caption will stay unchanged. The advisory remains in history.',
                confirmText: 'Remove link', cancelText: 'Cancel', dismissible: true})) {
                invalidateCheck();
                id.value = '0'; submittedUrl.value = ''; urlInput.value = '';
                status.textContent = 'Advisory link removed. Your title and caption are unchanged.';
            }
        } finally { busy = false; showState(); }
    });
    // Keep existing post-type controls from re-enabling fields during a pending check.
    document.querySelectorAll('[data-post-type]').forEach((button) => button.addEventListener('click', (event) => {
        if (busy) { event.preventDefault(); event.stopImmediatePropagation(); }
        else { invalidateCheck(); queueMicrotask(showState); }
    }, true));
    form.addEventListener('submit', (event) => {
        if (form.dataset.advisoryBlocked === 'true') {
            event.preventDefault(); event.stopImmediatePropagation();
            panel.open = true;
            status.textContent = 'Check and attach the official link before posting, or remove it.';
        }
    }, true);
    if (Number(id.value) > 0) status.textContent = 'An approved advisory is linked to this announcement.';
    showState();
});
