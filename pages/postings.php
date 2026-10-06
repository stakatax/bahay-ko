<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$editMode =
    (bool) (
        $viewData['edit_mode']
        ?? false
    );

$editType =
    strtolower(
        trim(
            (string) (
                $viewData['edit_type']
                ?? ''
            )
        )
    );

$requestedPostType =
    isset($_GET['type']) &&
    !is_array($_GET['type'])
    ? strtolower(
        trim(
            (string) $_GET['type']
        )
    )
    : '';

$allowedInitialPostTypes = [
    'announcement',
    'event',
    'document',
    'survey'
];

$initialPostType =
    $editMode
    ? $editType
    : (
        in_array(
            $requestedPostType,
            $allowedInitialPostTypes,
            true
        )
        ? $requestedPostType
        : 'announcement'
    );

$calendarEventStartValue = '';

if (
    !$editMode &&
    $initialPostType === 'event' &&
    isset($_GET['calendar_date']) &&
    !is_array($_GET['calendar_date'])
) {
    $requestedCalendarDate =
        trim(
            (string) $_GET['calendar_date']
        );

    $calendarDateObject =
        DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $requestedCalendarDate
        );

    $currentManilaDate =
        new DateTimeImmutable(
            'today',
            new DateTimeZone(
                'Asia/Manila'
            )
        );

    if (
        $calendarDateObject &&
        $calendarDateObject->format(
            'Y-m-d'
        ) === $requestedCalendarDate &&
        $calendarDateObject >=
        $currentManilaDate
    ) {
        $currentManilaTime =
            new DateTimeImmutable(
                'now',
                new DateTimeZone(
                    'Asia/Manila'
                )
            );

        $calendarEventStartValue =
            $requestedCalendarDate
            . 'T'
            . $currentManilaTime->format(
                'H:i'
            );
    }
}

$editId =
    (int) (
        $viewData['edit_id']
        ?? 0
    );

$editItem =
    $viewData['edit_item']
    ?? null;

$governmentAdvisoryPrefillValue =
    $viewData['government_advisory_prefill']
    ?? [];

/** @var array<string, mixed> $governmentAdvisoryPrefill */
$governmentAdvisoryPrefill =
    is_array(
        $governmentAdvisoryPrefillValue
    )
    ? $governmentAdvisoryPrefillValue
    : [];

$isGovernmentAdvisoryPrefill =
    !$editMode &&
    (int) (
        $governmentAdvisoryPrefill['government_advisory_id']
        ?? 0
    ) > 0;

$governmentAdvisoryId =
    $isGovernmentAdvisoryPrefill
    ? (int) (
        $governmentAdvisoryPrefill['government_advisory_id']
        ?? 0
    )
    : 0;

$contentInterests =
    is_array(
        $viewData['content_interests']
            ?? null
    )
    ? $viewData['content_interests']
    : [];

$editContentInterestIds =
    is_array($editItem) &&
    is_array(
        $editItem['interest_ids']
            ?? null
    )
    ? array_values(
        array_unique(
            array_map(
                'intval',
                $editItem['interest_ids']
            )
        )
    )
    : [];

$contentInterestIcons = [
    'academic-updates' =>
    'fa-solid fa-graduation-cap',

    'scholarships-financial-aid' =>
    'fa-solid fa-hand-holding-dollar',

    'school-events' =>
    'fa-regular fa-calendar',

    'clubs-organizations' =>
    'fa-solid fa-people-group',

    'sports-recreation' =>
    'fa-solid fa-medal',

    'career-college-opportunities' =>
    'fa-solid fa-briefcase',

    'health-wellness' =>
    'fa-solid fa-heart-pulse',

    'safety-emergency' =>
    'fa-solid fa-shield-halved',

    'policies-memoranda' =>
    'fa-solid fa-file-signature',

    'community-outreach' =>
    'fa-solid fa-handshake-angle'
];

$isAnnouncementEdit =
    $editMode &&
    $editType === 'announcement' &&
    $editId > 0 &&
    is_array(
        $editItem
    );

$isEventEdit =
    $editMode &&
    $editType === 'event' &&
    $editId > 0 &&
    is_array(
        $editItem
    );

$isDocumentEdit =
    $editMode &&
    $editType === 'document' &&
    $editId > 0 &&
    is_array(
        $editItem
    );

$isSurveyEdit =
    $editMode &&
    $editType === 'survey' &&
    $editId > 0 &&
    is_array(
        $editItem
    );

$editTargets =
    (
        $isAnnouncementEdit ||
        $isEventEdit ||
        $isDocumentEdit ||
        $isSurveyEdit
    )
    ? (
        is_array(
            $editItem['targets']
                ?? null
        )
        ? $editItem['targets']
        : []
    )
    : [];

/*
|----------------------------------------------------------
| EDIT AUDIENCE STATE
|----------------------------------------------------------
|
| Recipient roles remain global. Academic filters are
| restored as unique scopes so one post can target several
| independent academic groups.
|
*/

$editRolePrefixes = [];

$editAudienceScopes = [];

$editAudienceScopeKeys = [];

foreach ($editTargets as $target) {
    if (!is_array($target)) {
        continue;
    }

    $rolePrefix =
        strtolower(
            trim(
                (string) (
                    $target['role_prefix']
                    ?? ''
                )
            )
        );

    if ($rolePrefix !== '') {
        $editRolePrefixes[] =
            $rolePrefix;
    }

    $scope = [

        'department_id' =>
        max(
            0,
            (int) (
                $target['department_id']
                ?? 0
            )
        ),

        'education_level_id' =>
        max(
            0,
            (int) (
                $target['education_level_id']
                ?? 0
            )
        ),

        'academic_program_id' =>
        max(
            0,
            (int) (
                $target['academic_program_id']
                ?? 0
            )
        ),

        'grade_level_id' =>
        max(
            0,
            (int) (
                $target['grade_level_id']
                ?? 0
            )
        ),

        'section_id' =>
        max(
            0,
            (int) (
                $target['section_id']
                ?? 0
            )
        )
    ];

    $hasAcademicFilter =
        $scope['department_id'] > 0 ||
        $scope['education_level_id'] > 0 ||
        $scope['academic_program_id'] > 0 ||
        $scope['grade_level_id'] > 0 ||
        $scope['section_id'] > 0;

    if (!$hasAcademicFilter) {
        continue;
    }

    $scopeKey =
        implode(
            ':',
            [
                $scope['department_id'],
                $scope['education_level_id'],
                $scope['academic_program_id'],
                $scope['grade_level_id'],
                $scope['section_id']
            ]
        );

    if (
        isset(
            $editAudienceScopeKeys[$scopeKey]
        )
    ) {
        continue;
    }

    $editAudienceScopeKeys[$scopeKey] = true;

    $editAudienceScopes[] =
        $scope;
}

$editRolePrefixes =
    array_values(
        array_unique(
            $editRolePrefixes
        )
    );

sort(
    $editRolePrefixes
);

/*
|----------------------------------------------------------
| SCHOOL-WIDE OR CUSTOM
|----------------------------------------------------------
|
| School-wide records contain Student, Parent, and Faculty
| target rows with no academic filters. Custom mode applies
| when roles are restricted or an academic scope exists.
|
*/

$schoolwideRolePrefixes = [
    'faculty',
    'parent',
    'student'
];

$editHasRestrictedRoles =
    $editRolePrefixes !== [] &&
    $editRolePrefixes !==
    $schoolwideRolePrefixes;

$editHasCustomAudience =
    (
        $isAnnouncementEdit ||
        $isEventEdit ||
        $isDocumentEdit ||
        $isSurveyEdit
    ) &&
    (
        $editAudienceScopes !== [] ||
        $editHasRestrictedRoles
    );

/*
|----------------------------------------------------------
| LEGACY SINGLE-SCOPE VALUES
|----------------------------------------------------------
|
| These remain temporarily available while the current
| single-scope markup is being replaced in the next step.
|
*/

$primaryEditAudienceScope =
    $editAudienceScopes[0]
    ?? [
        'department_id' => 0,
        'education_level_id' => 0,
        'academic_program_id' => 0,
        'grade_level_id' => 0,
        'section_id' => 0
    ];

$editDepartmentId =
    (int) (
        $primaryEditAudienceScope['department_id']
        ?? 0
    );

$editEducationLevelId =
    (int) (
        $primaryEditAudienceScope['education_level_id']
        ?? 0
    );

$editProgramId =
    (int) (
        $primaryEditAudienceScope['academic_program_id']
        ?? 0
    );

$editGradeLevelId =
    (int) (
        $primaryEditAudienceScope['grade_level_id']
        ?? 0
    );

$editSectionId =
    (int) (
        $primaryEditAudienceScope['section_id']
        ?? 0
    );

$departments =
    $viewData['departments']
    ?? [];

$educationLevels =
    $viewData['education_levels']
    ?? [];

$academicPrograms =
    $viewData['academic_programs']
    ?? [];

$gradeLevels =
    $viewData['grade_levels']
    ?? [];

$sections =
    $viewData['sections']
    ?? [];

$upcomingEvents =
    $viewData['upcoming_events']
    ?? [];

$currentRole =
    $_SESSION['role']
    ?? 'Guest';

$facultyScope = $viewData['faculty_scope'] ?? null;
$facultyScopeError = $viewData['faculty_scope_error'] ?? '';
$currentDepartmentId = (int) ($facultyScope['department_id'] ?? $_SESSION['department_id'] ?? 0);

/*
|----------------------------------------------------------
| ACADEMIC AUDIENCE SCOPES
|----------------------------------------------------------
|
| Existing edit targets become repeatable academic scopes.
| New posts begin with one blank scope. Faculty members
| begin inside their assigned department.
|
*/

$audienceScopesForDisplay =
    $editAudienceScopes;

if ($audienceScopesForDisplay === []) {
    $audienceScopesForDisplay[] = [
        'department_id' =>
        $currentRole === 'Faculty'
            ? $currentDepartmentId
            : 0,

        'education_level_id' => 0,
        'academic_program_id' => 0,
        'grade_level_id' => 0,
        'section_id' => 0
    ];
}

$academicAudienceConfiguration = [
    'current_role' =>
    $currentRole,

    'current_department_id' =>
    $currentDepartmentId,

    'faculty_scope' => $facultyScope,

    'initial_scopes' =>
    $audienceScopesForDisplay,

    'departments' =>
    array_values(
        $departments
    ),

    'education_levels' =>
    array_values(
        $educationLevels
    ),

    'academic_programs' =>
    array_values(
        $academicPrograms
    ),

    'grade_levels' =>
    array_values(
        $gradeLevels
    ),

    'sections' =>
    array_values(
        $sections
    )
];

$academicAudienceConfigurationJson =
    json_encode(
        $academicAudienceConfiguration,
        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
    );

if (
    $academicAudienceConfigurationJson ===
    false
) {
    $academicAudienceConfigurationJson =
        '{}';
}

$isAdmin =
    $currentRole === 'Admin';

$isFaculty =
    $currentRole === 'Faculty';

$defaultWorkflowAction =
    $isFaculty
    ? 'submit_review'
    : 'publish';

$successMessage =
    trim(
        (string) (
            $_GET['success']
            ?? ''
        )
    );

$errorMessage =
    trim(
        (string) (
            $_GET['error']
            ?? ''
        )
    );

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($successMessage !== ''): ?>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Content Saved',
                    text: <?= json_encode(
                                $successMessage,
                                JSON_HEX_TAG |
                                    JSON_HEX_APOS |
                                    JSON_HEX_AMP |
                                    JSON_HEX_QUOT
                            ) ?>,
                    confirmButtonColor: '#8B0000'
                });

                /*
                 * Remove the success message from the URL
                 * after it has been displayed once.
                 */
                const cleanUrl =
                    new URL(
                        window.location.href
                    );

                cleanUrl.searchParams.delete(
                    'success'
                );

                window.history.replaceState({},
                    document.title,
                    cleanUrl.pathname +
                    cleanUrl.search +
                    cleanUrl.hash
                );
            }
        );
    </script>

<?php endif; ?>

<?php if ($errorMessage !== ''): ?>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to Save Content',
                    text: <?= json_encode(
                                $errorMessage,
                                JSON_HEX_TAG |
                                    JSON_HEX_APOS |
                                    JSON_HEX_AMP |
                                    JSON_HEX_QUOT
                            ) ?>,
                    confirmButtonColor: '#8B0000'
                });
            }
        );
    </script>

<?php endif; ?>

<section class="app-page publisher-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->
    <header class="publisher-workspace-header">

        <div>
            <span class="page-eyebrow">
                Content Management
            </span>

            <h1>
                <?= $editMode
                    ? 'Edit Draft'
                    : 'Create School Content'
                ?>
            </h1>

            <p>
                <?php if ($editMode): ?>

                    You are editing an existing
                    <strong>
                        <?= htmlspecialchars(
                            ucfirst($editType),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                    draft. Changes will update this draft instead of
                    creating new content.

                <?php else: ?>

                    Create announcements, calendar events, official documents,
                    and surveys. Configure recipients, release settings,
                    interactions, and publishing workflow.

                <?php endif; ?>

                <?php if ($editMode): ?>

            <div class="publisher-edit-notice">

                <i class="fa-solid fa-pen-to-square"></i>

                <div>
                    <strong>
                        Editing <?= htmlspecialchars(
                                    ucfirst($editType),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?> Draft
                    </strong>

                    <span>
                        ID #<?= $editId ?> · Content type is locked while editing.
                    </span>
                </div>

            </div>

        <?php endif; ?>

        </p>
        </div>

        <span class="publisher-role-badge">

            <i class="fa-solid fa-user-shield"></i>

            <?= htmlspecialchars(
                $currentRole,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </span>

    </header>


    <!-- ======================================
         MAIN PUBLISHER LAYOUT
    ======================================= -->

    <div class="publisher-layout">

        <!-- ==================================
             CONTENT TYPE SIDEBAR
        =================================== -->

        <aside class="publisher-guide">

            <section class="publisher-guide-card">

                <div class="guide-heading">

                    <span class="page-eyebrow">
                        Content Type
                    </span>

                    <h2>
                        What will you create?
                    </h2>

                    <p>
                        Select one content type to display its fields.
                    </p>

                </div>

                <div class="type-selector">

                    <button
                        type="button"
                        class="type-btn <?= (
                                            (!$editMode && $editType === '') ||
                                            ($editMode && $editType === 'announcement')
                                        ) ? 'active' : '' ?> <?= (
                                                                    $editMode &&
                                                                    $editType !== 'announcement'
                                                                ) ? 'locked' : '' ?>"
                        data-post-type="announcement"
                        <?= (
                            $editMode &&
                            $editType !== 'announcement'
                        ) ? 'disabled' : '' ?>>

                        <span>

                            <strong>
                                Announcement
                            </strong>

                            <small>
                                Advisories, updates, and reminders
                            </small>

                        </span>

                        <i class="fa-solid fa-check type-check"></i>

                    </button>

                    <button
                        type="button"
                        class="type-btn <?= (
                                            $editMode &&
                                            $editType === 'event'
                                        ) ? 'active' : '' ?> <?= (
                                                                    $editMode &&
                                                                    $editType !== 'event'
                                                                ) ? 'locked' : '' ?>"
                        data-post-type="event"
                        <?= (
                            $editMode &&
                            $editType !== 'event'
                        ) ? 'disabled' : '' ?>>
                        <span>

                            <strong>
                                Event
                            </strong>

                            <small>
                                Activities and school schedules
                            </small>

                        </span>

                        <i class="fa-solid fa-check type-check"></i>

                    </button>

                    <button
                        type="button"
                        class="type-btn <?= (
                                            $editMode &&
                                            $editType === 'document'
                                        ) ? 'active' : '' ?> <?= (
                                                                    $editMode &&
                                                                    $editType !== 'document'
                                                                ) ? 'locked' : '' ?>"
                        data-post-type="document"
                        <?= (
                            $editMode &&
                            $editType !== 'document'
                        ) ? 'disabled' : '' ?>>

                        <span>

                            <strong>
                                Document
                            </strong>

                            <small>
                                Forms, memoranda, and resources
                            </small>

                        </span>

                        <i class="fa-solid fa-check type-check"></i>

                    </button>

                    <button
                        type="button"
                        class="type-btn <?= (
                                            $editMode &&
                                            $editType === 'survey'
                                        ) ? 'active' : '' ?> <?= (
                                                                    $editMode &&
                                                                    $editType !== 'survey'
                                                                ) ? 'locked' : '' ?>"
                        data-post-type="survey"
                        <?= (
                            $editMode &&
                            $editType !== 'survey'
                        ) ? 'disabled' : '' ?>>

                        <span>

                            <strong>
                                Survey
                            </strong>

                            <small>
                                Feedback and data collection
                            </small>

                        </span>

                        <i class="fa-solid fa-check type-check"></i>

                    </button>

                </div>

            </section>

            <?php if ($isAdmin): ?>
            <details class="posting-trusted-sources">
                <summary>Trusted government sources</summary>
                <ul>
                    <?php
                    $visibleTrustedSources = 0;
                    foreach (($viewData['trusted_government_sources'] ?? []) as $source):
                        $sourceUrl = (string) ($source['base_url'] ?? '');
                        $parts = parse_url($sourceUrl);
                        if (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !$parts
                            || strtolower($parts['scheme'] ?? '') !== 'https'
                            || isset($parts['user']) || isset($parts['pass'])) continue;
                        $visibleTrustedSources++;
                    ?>
                    <li><a href="<?= htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars((string) $source['source_name'], ENT_QUOTES, 'UTF-8') ?>
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        <span class="posting-source-new-tab">(new tab)</span>
                    </a></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($visibleTrustedSources === 0): ?>
                <p><?= !empty($viewData['trusted_government_sources_unavailable'])
                    ? 'Sources are temporarily unavailable.' : 'No active trusted sources available.' ?></p>
                <?php endif; ?>
            </details>
            <?php endif; ?>

        </aside>

        <div
            id="publisherMobilePreviewSlot"
            class="publisher-mobile-preview-slot">
        </div>

        <!-- ==================================
             MAIN FORM
        =================================== -->

        <main class="publisher-card">

            <form
                id="postForm"
                action="index.php?page=post_store"
                method="POST"
                enctype="multipart/form-data"
                data-user-role="<?= htmlspecialchars(
                                    $currentRole,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"


                novalidate>

                <?= csrfInput() ?>

                <?php if (
                    $isAnnouncementEdit ||
                    $isEventEdit ||
                    $isDocumentEdit ||
                    $isSurveyEdit
                ): ?>

                    <input
                        type="hidden"
                        name="edit_id"
                        value="<?= $editId ?>">

                <?php endif; ?>

                <input
                    type="hidden"
                    name="post_type"
                    id="post_type"
                    value="<?= htmlspecialchars(
                                $initialPostType,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">



                <input
                    type="hidden"
                    name="workflow_action"
                    id="workflowAction"
                    value="<?= htmlspecialchars(
                                $defaultWorkflowAction,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">



                <input
                    type="hidden"
                    name="redundancy_override"
                    id="redundancyOverride"
                    value="0">

                <input
                    type="hidden"
                    name="redundancy_override_reason"
                    id="redundancyOverrideReason"
                    value="">

                <!-- =================================
     TARGET RECIPIENTS
================================== -->

                <section class="form-section">

                    <div class="form-section-heading">

                        <span>01</span>

                        <div>
                            <h2>Target Recipients</h2>

                            <p>
                                Select at least one recipient group, then optionally
                                narrow the audience using academic filters.
                            </p>
                        </div>

                    </div>

                    <!-- =================================
     AUDIENCE SCOPE
================================= -->

                    <div class="publisher-subsection audience-scope-section">

                        <div class="publisher-subsection-heading">

                            <div>
                                <h3>Audience scope</h3>

                                <p>
                                    Choose whether this content is intended for
                                    the whole school community or a specific audience.
                                </p>
                            </div>

                            <span class="publisher-prepared-badge">
                                Required
                            </span>

                        </div>

                        <?php if ($isFaculty): ?>
                            <p role="<?= $facultyScopeError !== '' ? 'alert' : 'note' ?>">
                                <?= htmlspecialchars($facultyScopeError !== '' ? $facultyScopeError : 'Recipients are restricted to your assigned education level and College program. You may select narrower groups within that scope.', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>

                        <div class="audience-scope-grid">

                            <?php if ($isAdmin): ?>
                            <label class="audience-scope-option">

                                <input
                                    type="radio"
                                    name="audience_scope"
                                    value="schoolwide"
                                    <?= (
                                        !(
                                            $isAnnouncementEdit ||
                                            $isEventEdit ||
                                            $isDocumentEdit ||
                                            $isSurveyEdit
                                        ) ||
                                        !$editHasCustomAudience
                                    ) ? 'checked' : '' ?>>

                                <span class="audience-scope-icon">
                                    <i class="fa-solid fa-earth-asia"></i>
                                </span>

                                <div>
                                    <strong>School-wide</strong>

                                    <small>
                                        Students, parents, and faculty
                                        across the school.
                                    </small>
                                </div>

                                <i class="fa-solid fa-circle-check audience-scope-check"></i>

                            </label>
                            <?php endif; ?>

                            <label class="audience-scope-option">

                                <input
                                    type="radio"
                                    name="audience_scope"
                                    value="custom"
                                    <?= $isFaculty || (
                                        (
                                            $isAnnouncementEdit ||
                                            $isEventEdit ||
                                            $isDocumentEdit ||
                                            $isSurveyEdit
                                        ) &&
                                        $editHasCustomAudience
                                    ) ? 'checked' : '' ?>>

                                <span class="audience-scope-icon">
                                    <i class="fa-solid fa-users-viewfinder"></i>
                                </span>

                                <div>
                                    <strong>Custom recipients</strong>

                                    <small>
                                        Choose specific roles and academic groups.
                                    </small>
                                </div>

                                <i class="fa-solid fa-circle-check audience-scope-check"></i>

                            </label>

                        </div>

                        <div class="audience-summary-card">

                            <span>
                                <i class="fa-solid fa-users"></i>
                            </span>

                            <div>
                                <small>Recipient summary</small>

                                <strong id="audienceSummaryText">
                                    <?= $isFaculty ? 'Assigned academic scope' : 'School-wide' ?>
                                </strong>
                            </div>

                        </div>

                    </div>

                    <div id="customAudienceFields" hidden>

                        <!-- =================================
         RECIPIENT ROLES
    ================================== -->

                        <div class="publisher-subsection">

                            <div class="publisher-subsection-heading">

                                <div>
                                    <h3>Recipient groups</h3>

                                    <p>
                                        At least one recipient group must remain selected.
                                    </p>
                                </div>

                                <span class="publisher-prepared-badge">
                                    Required
                                </span>

                            </div>

                            <div class="audience-option-grid audience-three-column">

                                <label class="audience-option">

                                    <input
                                        type="checkbox"
                                        name="target_roles[]"
                                        value="Student"
                                        <?= (
                                            (!$editMode) ||
                                            (
                                                (
                                                    $isAnnouncementEdit ||
                                                    $isEventEdit ||
                                                    $isDocumentEdit ||
                                                    $isSurveyEdit
                                                ) &&
                                                in_array(
                                                    'student',
                                                    $editRolePrefixes,
                                                    true
                                                )
                                            )
                                        ) ? 'checked' : '' ?>>

                                    <span>
                                        <i class="fa-solid fa-user-graduate"></i>
                                    </span>

                                    <div>
                                        <strong>Students</strong>
                                        <small>Enrolled student accounts</small>
                                    </div>

                                </label>

                                <label class="audience-option">

                                    <input
                                        type="checkbox"
                                        name="target_roles[]"
                                        value="Parent"
                                        <?= (
                                            (
                                                $isAnnouncementEdit ||
                                                $isEventEdit ||
                                                $isDocumentEdit ||
                                                $isSurveyEdit
                                            ) &&
                                            in_array(
                                                'parent',
                                                $editRolePrefixes,
                                                true
                                            )
                                        ) ? 'checked' : '' ?>>

                                    <span>
                                        <i class="fa-solid fa-people-roof"></i>
                                    </span>

                                    <div>
                                        <strong>Parents</strong>
                                        <small>Linked parent accounts</small>
                                    </div>

                                </label>
                                <label class="audience-option">

                                    <input
                                        type="checkbox"
                                        name="target_roles[]"
                                        value="Faculty"
                                        <?= (
                                            (
                                                $isAnnouncementEdit ||
                                                $isEventEdit ||
                                                $isDocumentEdit ||
                                                $isSurveyEdit
                                            ) &&
                                            in_array(
                                                'faculty',
                                                $editRolePrefixes,
                                                true
                                            )
                                        ) ? 'checked' : '' ?>>

                                    <span>
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                    </span>

                                    <div>
                                        <strong>Faculty</strong>
                                        <small>Teaching personnel</small>
                                    </div>

                                </label>

                            </div>

                        </div>

                        <!-- =================================
     ACADEMIC CLASSIFICATION
================================== -->

                        <div
                            class="publisher-subsection"
                            id="academicAudienceSection">

                            <div class="publisher-subsection-heading">

                                <div>
                                    <h3>
                                        Academic classification
                                    </h3>

                                    <p>
                                        Add one or more academic targets for the
                                        selected recipient groups.
                                    </p>
                                </div>

                                <span class="publisher-prepared-badge">
                                    Optional
                                </span>

                            </div>

                            <script
                                type="application/json"
                                id="academicAudienceConfiguration">
                                <?= $academicAudienceConfigurationJson ?>
                            </script>

                            <div
                                class="academic-audience-scope-list"
                                id="academicAudienceScopes"
                                aria-live="polite">
                            </div>

                            <p
                                class="academic-audience-validation-message"
                                id="academicAudienceValidationMessage"
                                role="status"
                                hidden>
                            </p>

                            <div class="academic-audience-scope-actions">

                                <button
                                    type="button"
                                    class="app-button secondary"
                                    id="addAcademicAudienceScope">

                                    <i class="fa-solid fa-plus"></i>

                                    <span>
                                        Add Academic Target
                                    </span>

                                </button>

                                <small>
                                    The same selected recipient groups will receive
                                    this post across every academic target added here.
                                </small>

                            </div>

                        </div>


                    </div>

                </section>

                <!-- =================================
                     CONTENT TOPICS
                ================================== -->

                <section
                    class="content-topic-compact"
                    data-content-topic-section>

                    <div class="content-topic-compact-header">

                        <div>

                            <span class="page-eyebrow">
                                Classification
                            </span>

                            <h2>
                                Content Topics
                            </h2>

                            <p>
                                Topics improve organization and personalized
                                ranking without changing recipient access.
                            </p>

                        </div>

                        <span class="content-topic-required">
                            Required
                        </span>

                    </div>

                    <div class="content-topic-summary">

                        <div
                            class="content-topic-selected"
                            data-content-topic-selected>

                            <?php foreach (
                                $contentInterests
                                as $contentInterest
                            ): ?>

                                <?php

                                $summaryInterestId =
                                    (int) (
                                        $contentInterest['interest_id']
                                        ?? 0
                                    );

                                if (
                                    !in_array(
                                        $summaryInterestId,
                                        $editContentInterestIds,
                                        true
                                    )
                                ) {
                                    continue;
                                }

                                ?>

                                <span>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $contentInterest['interest_name']
                                            ?? 'Topic'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            <?php endforeach; ?>

                            <small
                                data-content-topic-placeholder
                                <?= $editContentInterestIds !== []
                                    ? 'hidden'
                                    : ''
                                ?>>

                                No topics selected
                            </small>

                        </div>

                        <button
                            type="button"
                            class="app-button secondary"
                            id="toggleContentTopicChooser"
                            aria-expanded="false"
                            aria-controls="contentTopicChooser">

                            <i class="fa-solid fa-tags"></i>

                            <span>
                                Choose Topics
                            </span>

                            <i class="fa-solid fa-chevron-down"></i>

                        </button>

                    </div>

                    <div
                        class="content-topic-chooser"
                        id="contentTopicChooser"
                        hidden>

                        <div class="content-topic-chooser-heading">

                            <strong>
                                Select relevant topics
                            </strong>

                            <span data-content-topic-count>
                                <?= number_format(
                                    count(
                                        $editContentInterestIds
                                    )
                                ) ?>
                                selected
                            </span>

                        </div>

                        <div class="content-topic-choice-grid">

                            <?php foreach (
                                $contentInterests
                                as $contentInterest
                            ): ?>

                                <?php

                                $contentInterestId =
                                    (int) (
                                        $contentInterest['interest_id']
                                        ?? 0
                                    );

                                $contentInterestName =
                                    trim(
                                        (string) (
                                            $contentInterest['interest_name']
                                            ?? 'Topic'
                                        )
                                    );

                                $contentInterestSlug =
                                    trim(
                                        (string) (
                                            $contentInterest['interest_slug']
                                            ?? ''
                                        )
                                    );

                                $contentInterestSelected =
                                    in_array(
                                        $contentInterestId,
                                        $editContentInterestIds,
                                        true
                                    );

                                $contentInterestIcon =
                                    $contentInterestIcons[$contentInterestSlug]
                                    ?? 'fa-solid fa-tag';

                                ?>

                                <label
                                    class="content-topic-choice <?= $contentInterestSelected
                                                                    ? 'is-selected'
                                                                    : ''
                                                                ?>"
                                    data-content-topic-option>

                                    <input
                                        type="checkbox"
                                        name="content_interest_ids[]"
                                        value="<?= $contentInterestId ?>"
                                        data-content-topic-checkbox
                                        data-topic-label="<?= htmlspecialchars(
                                                                $contentInterestName,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                        <?= $contentInterestSelected
                                            ? 'checked'
                                            : ''
                                        ?>>

                                    <i class="<?= htmlspecialchars(
                                                    $contentInterestIcon,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"></i>

                                    <span>
                                        <?= htmlspecialchars(
                                            $contentInterestName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <i class="fa-solid fa-check"></i>

                                </label>

                            <?php endforeach; ?>

                        </div>

                        <div class="content-topic-chooser-footer">

                            <small>
                                Select every topic that meaningfully
                                describes this content.
                            </small>

                            <button
                                type="button"
                                class="app-button primary"
                                id="closeContentTopicChooser">

                                Done
                            </button>

                        </div>

                    </div>

                    <div
                        class="content-topic-validation"
                        id="contentTopicValidation"
                        role="alert"
                        hidden>

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <span>
                            Select at least one content topic.
                        </span>

                    </div>

                </section>
                <!-- =================================
                     ANNOUNCEMENT PANEL
                ================================== -->

                <section
                    class="form-section post-panel active"
                    data-panel="announcement">

                    <div class="form-section-heading">

                        <span>02</span>

                        <div>

                            <h2>
                                Announcement Details
                            </h2>

                            <p>
                                Prepare the official message that will
                                appear in the Information Hub.
                            </p>

                        </div>

                    </div>

                    <div class="form-grid">

                        <div class="form-field">

                            <label for="announcementCategory">

                                Category

                                <span class="required-mark">
                                    *
                                </span>

                            </label>

                            <select
                                id="announcementCategory"
                                name="announcement_category"
                                data-required-for="announcement"
                                required>

                                <option
                                    value="general"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['category']
                                            ?? ''
                                        ) === 'general'
                                    ) ? 'selected' : '' ?>>
                                    General Announcement
                                </option>

                                <option
                                    value="academic"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['category']
                                            ?? ''
                                        ) === 'academic'
                                    ) ? 'selected' : '' ?>>
                                    Academic
                                </option>

                                <option
                                    value="enrollment"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['category']
                                            ?? ''
                                        ) === 'enrollment'
                                    ) ? 'selected' : '' ?>>
                                    Enrollment
                                </option>

                                <option
                                    value="reminder"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['category']
                                            ?? ''
                                        ) === 'reminder'
                                    ) ? 'selected' : '' ?>>
                                    Reminder
                                </option>

                                <option
                                    value="event_related"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['category']
                                            ?? ''
                                        ) === 'event_related'
                                    ) ? 'selected' : '' ?>>
                                    Event Related
                                </option>

                                <option
                                    value="memorandum"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['category']
                                            ?? ''
                                        ) === 'memorandum'
                                    ) ? 'selected' : '' ?>>
                                    Memorandum
                                </option>

                                <option
                                    value="emergency"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['category']
                                            ?? ''
                                        ) === 'emergency'
                                    ) ? 'selected' : '' ?>>
                                    Emergency
                                </option>
                            </select>

                        </div>

                        <div class="form-field">

                            <label for="announcementPriority">

                                Priority

                                <span class="required-mark">
                                    *
                                </span>

                            </label>

                            <select
                                id="announcementPriority"
                                name="announcement_priority"
                                data-required-for="announcement"
                                required>
                                <option
                                    value="Normal"
                                    <?= (
                                        !$isAnnouncementEdit ||
                                        (
                                            $editItem['priority']
                                            ?? 'Normal'
                                        ) === 'Normal'
                                    ) ? 'selected' : '' ?>>
                                    Normal
                                </option>

                                <option
                                    value="Important"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['priority']
                                            ?? ''
                                        ) === 'Important'
                                    ) ? 'selected' : '' ?>>
                                    Important
                                </option>

                                <option
                                    value="Urgent"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['priority']
                                            ?? ''
                                        ) === 'Urgent'
                                    ) ? 'selected' : '' ?>>
                                    Urgent
                                </option>

                                <option
                                    value="Emergency"
                                    <?= (
                                        $isAnnouncementEdit &&
                                        (
                                            $editItem['priority']
                                            ?? ''
                                        ) === 'Emergency'
                                    ) ? 'selected' : '' ?>>
                                    Emergency
                                </option>
                            </select>

                        </div>

                    </div>

                    <?php if ($isAdmin && !$editMode): ?>
                    <details class="posting-advisory" id="postingAdvisory">
                        <summary>Use government advisory</summary>
                        <div class="posting-advisory-body">
                            <input type="hidden" name="government_advisory_id" id="postingAdvisoryId" value="<?= $governmentAdvisoryId ?>">
                            <input type="hidden" name="government_advisory_url" id="postingAdvisorySubmittedUrl" value="<?= htmlspecialchars((string) ($governmentAdvisoryPrefill['source_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                            <p>Attach the official article or bulletin for this announcement.</p>
                            <div class="posting-advisory-link-row">
                                <div class="form-field">
                                    <label for="postingAdvisoryUrl">Official link</label>
                                    <input type="url" id="postingAdvisoryUrl" data-advisory-field="source_url" placeholder="https://www.deped.gov.ph/...">
                                </div>
                                <button type="button" class="app-button primary" id="postingAdvisoryCheck">Check link</button>
                            </div>
                            <p id="postingAdvisoryStatus" role="status" aria-live="polite"></p>
                            <div id="postingAdvisoryPreview" hidden>
                                <h3 id="postingAdvisoryPreviewTitle"></h3>
                                <p id="postingAdvisoryPreviewText"></p>
                                <button type="button" class="app-button primary" id="postingAdvisoryUse">Attach advisory</button>
                            </div>
                            <button type="button" class="app-button secondary" id="postingAdvisoryRemove" hidden>Remove advisory link</button>
                        </div>
                    </details>
                    <?php endif; ?>

                    <div class="form-field">

                        <label for="announcementTitle">

                            Title

                            <span class="required-mark">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="announcementTitle"
                            name="announcement_title"
                            value="<?= htmlspecialchars(
                                        $isAnnouncementEdit
                                            ? (
                                                $editItem['title']
                                                ?? ''
                                            )
                                            : '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            placeholder="Example: Enrollment Schedule for AY 2026–2027"
                            maxlength="100"
                            required
                            data-required-for="announcement">

                    </div>

                    <div class="form-field">

                        <label for="announcementContent">

                            Announcement content

                            <span class="required-mark">
                                *
                            </span>

                        </label>

                        <div
                            class="rich-text-editor"
                            data-rich-editor
                            data-target="announcementContent">

                            <div
                                class="rich-text-toolbar"
                                role="toolbar"
                                aria-label="Announcement formatting">

                                <button
                                    type="button"
                                    data-rich-command="bold"
                                    title="Bold">
                                    <i class="fa-solid fa-bold"></i>
                                </button>

                                <button
                                    type="button"
                                    data-rich-command="italic"
                                    title="Italic">
                                    <i class="fa-solid fa-italic"></i>
                                </button>

                                <button
                                    type="button"
                                    data-rich-command="underline"
                                    title="Underline">
                                    <i class="fa-solid fa-underline"></i>
                                </button>

                                <span class="rich-text-divider"></span>

                                <button
                                    type="button"
                                    data-rich-command="insertUnorderedList"
                                    title="Bulleted list">
                                    <i class="fa-solid fa-list-ul"></i>
                                </button>

                                <button
                                    type="button"
                                    data-rich-command="insertOrderedList"
                                    title="Numbered list">
                                    <i class="fa-solid fa-list-ol"></i>
                                </button>

                                <span class="rich-text-divider"></span>

                                <button
                                    type="button"
                                    data-rich-link
                                    title="Insert link">
                                    <i class="fa-solid fa-link"></i>
                                </button>

                            </div>

                            <div
                                class="rich-text-surface"
                                contenteditable="true"
                                role="textbox"
                                aria-multiline="true"
                                data-rich-surface
                                data-placeholder="Write the announcement content here..."><?= $isAnnouncementEdit
                                                                                                ? (
                                                                                                    $editItem['content']
                                                                                                    ?? ''
                                                                                                )
                                                                                                : ''
                                                                                            ?></div>

                        </div>

                        <textarea
                            id="announcementContent"
                            name="announcement_content"
                            maxlength="5000"
                            required
                            data-required-for="announcement"
                            hidden><?= htmlspecialchars(
                                        $isAnnouncementEdit
                                            ? (
                                                $editItem['content']
                                                ?? ''
                                            )
                                            : '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?></textarea>


                        <div class="field-footer">

                            <small>
                                Provide complete and verified information.
                            </small>

                            <span
                                class="character-count"
                                data-counter-for="announcementContent">
                                0 / 5000
                            </span>

                        </div>

                    </div>

                    <div class="form-field">

                        <label for="announcementImage">

                            Pubmat or image

                            <span class="optional-mark">
                                Optional
                            </span>

                        </label>

                        <label
                            class="upload-zone"
                            for="announcementImage"
                            data-upload-zone>

                            <input
                                type="file"
                                id="announcementImage"
                                name="announcement_image"
                                accept=".jpg,.jpeg,.png,.webp"
                                data-image-input>

                            <span class="upload-icon">

                                <i class="fa-regular fa-image"></i>

                            </span>

                            <strong>
                                Upload announcement pubmat
                            </strong>

                            <small>
                                JPG, PNG, or WEBP. Maximum size: 5 MB.
                            </small>

                            <span class="upload-file-name">
                                No image selected
                            </span>

                        </label>


                        <?php

                        $existingAnnouncementImage =
                            $isAnnouncementEdit
                            ? trim(
                                (string) (
                                    $editItem['image_path']
                                    ?? ''
                                )
                            )
                            : '';

                        ?>

                        <div
                            class="image-preview"
                            data-image-preview
                            <?= $existingAnnouncementImage === ''
                                ? 'hidden'
                                : ''
                            ?>>

                            <img
                                src="<?= htmlspecialchars(
                                            $existingAnnouncementImage,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                alt="Announcement image preview">

                            <button
                                type="button"
                                class="remove-preview"
                                data-remove-image>
                                <i class="fa-solid fa-xmark"></i>

                                Remove
                            </button>

                        </div>

                    </div>


                    <?php

                    $existingAnnouncementAudio =
                        $isAnnouncementEdit
                        ? trim(
                            (string) (
                                $editItem['audio_path']
                                ?? ''
                            )
                        )
                        : '';

                    $existingAnnouncementAudioName =
                        $isAnnouncementEdit
                        ? trim(
                            (string) (
                                $editItem['audio_file_name']
                                ?? ''
                            )
                        )
                        : '';

                    $existingAnnouncementTranscript =
                        $isAnnouncementEdit
                        ? trim(
                            (string) (
                                $editItem['audio_transcript']
                                ?? ''
                            )
                        )
                        : '';

                    ?>

                    <div
                        class="form-field announcement-audio-field"
                        data-announcement-audio-field
                        data-existing-audio="<?= $existingAnnouncementAudio !== ''
                                                    ? 'true'
                                                    : 'false'
                                                ?>">

                        <label for="announcementAudio">

                            Audio broadcast

                            <span class="optional-mark">
                                Optional
                            </span>

                        </label>

                        <p class="field-guidance">
                            Upload an existing audio file or record the
                            spoken announcement directly. Playback never
                            starts automatically.
                        </p>

                        <label
                            class="upload-zone announcement-audio-upload-zone"
                            for="announcementAudio"
                            data-upload-zone>

                            <input
                                type="file"
                                id="announcementAudio"
                                name="announcement_audio"
                                accept=".mp3,.m4a,.wav,.webm,audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/webm"
                                data-announcement-audio-input>

                            <span class="upload-icon">

                                <i class="fa-solid fa-file-audio"></i>

                            </span>

                            <strong>
                                Upload an audio broadcast
                            </strong>

                            <small>
                                MP3, M4A, WAV, or WebM.
                                Maximum size: 15 MB.
                            </small>

                            <span class="upload-file-name">
                                No audio selected
                            </span>

                        </label>

                        <div
                            class="announcement-audio-recorder"
                            data-announcement-audio-recorder>

                            <div class="announcement-audio-recorder-heading">

                                <span>

                                    <i class="fa-solid fa-microphone"></i>

                                </span>

                                <div>

                                    <strong>
                                        Record audio now
                                    </strong>

                                    <small>
                                        Use your device microphone to record
                                        the spoken announcement directly.
                                    </small>

                                </div>

                            </div>

                            <div
                                class="announcement-recording-status"
                                data-recording-status
                                aria-live="polite">

                                <span
                                    class="announcement-recording-indicator"
                                    aria-hidden="true"></span>

                                <span data-recording-status-text>
                                    Ready to record
                                </span>

                                <time data-recording-timer>
                                    0:00
                                </time>

                            </div>

                            <div class="announcement-recording-actions">

                                <button
                                    type="button"
                                    class="announcement-record-button start"
                                    data-start-audio-recording>

                                    <i class="fa-solid fa-microphone"></i>

                                    Start Recording

                                </button>

                                <button
                                    type="button"
                                    class="announcement-record-button stop"
                                    data-stop-audio-recording
                                    hidden>

                                    <i class="fa-solid fa-stop"></i>

                                    Stop Recording

                                </button>

                                <button
                                    type="button"
                                    class="announcement-record-button discard"
                                    data-discard-audio-recording
                                    hidden>

                                    <i class="fa-regular fa-trash-can"></i>

                                    Discard

                                </button>

                            </div>

                            <div
                                class="announcement-edit-audio-player announcement-recording-player"
                                data-recorded-audio-player
                                hidden>

                                <audio
                                    data-recorded-audio-preview
                                    preload="metadata">
                                </audio>

                                <button
                                    type="button"
                                    class="announcement-edit-audio-play"
                                    data-recorded-audio-play
                                    aria-label="Play recorded audio preview">

                                    <i
                                        class="fa-solid fa-play"
                                        aria-hidden="true"></i>

                                </button>

                                <div class="announcement-edit-audio-content">

                                    <div class="announcement-edit-audio-heading">

                                        <strong>
                                            Recorded audio preview
                                        </strong>

                                        <small>
                                            Review the recording before saving
                                        </small>

                                    </div>

                                    <div class="announcement-edit-audio-timeline">

                                        <input
                                            type="range"
                                            class="announcement-edit-audio-progress"
                                            data-recorded-audio-progress
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            value="0"
                                            disabled
                                            aria-label="Recorded audio playback position">

                                        <div class="announcement-edit-audio-time">

                                            <span data-recorded-audio-current>
                                                0:00
                                            </span>

                                            <span data-recorded-audio-duration>
                                                0:00
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <p
                                class="announcement-recording-support"
                                data-recording-support-message
                                hidden>
                            </p>

                        </div>

                        <div
                            class="announcement-audio-existing"
                            data-existing-audio-preview
                            <?= $existingAnnouncementAudio === ''
                                ? 'hidden'
                                : ''
                            ?>>

                            <?php if (
                                $existingAnnouncementAudio !== ''
                            ): ?>

                                <div
                                    class="announcement-edit-audio-player"
                                    data-existing-audio-player>

                                    <audio
                                        preload="metadata"
                                        data-existing-audio-element
                                        src="<?= htmlspecialchars(
                                                    $existingAnnouncementAudio,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">
                                    </audio>

                                    <button
                                        type="button"
                                        class="announcement-edit-audio-play"
                                        data-existing-audio-play
                                        aria-label="Play existing audio broadcast">

                                        <i
                                            class="fa-solid fa-play"
                                            aria-hidden="true"></i>

                                    </button>

                                    <div class="announcement-edit-audio-content">

                                        <div class="announcement-edit-audio-heading">

                                            <strong>
                                                Existing audio broadcast
                                            </strong>

                                            <small>
                                                <?= htmlspecialchars(
                                                    $existingAnnouncementAudioName !== ''
                                                        ? $existingAnnouncementAudioName
                                                        : 'Announcement audio',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </small>

                                        </div>

                                        <div class="announcement-edit-audio-timeline">

                                            <input
                                                type="range"
                                                class="announcement-edit-audio-progress"
                                                data-existing-audio-progress
                                                min="0"
                                                max="100"
                                                step="0.1"
                                                value="0"
                                                aria-label="Existing audio playback position">

                                            <div class="announcement-edit-audio-time">

                                                <span data-existing-audio-current>
                                                    0:00
                                                </span>

                                                <span data-existing-audio-duration>
                                                    0:00
                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                    <label
                                        class="announcement-audio-remove"
                                        title="Remove existing audio">

                                        <input
                                            type="checkbox"
                                            name="remove_announcement_audio"
                                            value="1"
                                            data-remove-announcement-audio>

                                        <i
                                            class="fa-solid fa-xmark"
                                            aria-hidden="true"></i>

                                        <span>
                                            Remove existing audio
                                        </span>

                                    </label>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div
                            class="form-field announcement-audio-transcript-field"
                            data-audio-transcript-field
                            <?= $existingAnnouncementAudio === ''
                                ? 'hidden'
                                : ''
                            ?>>

                            <label for="announcementAudioTranscript">

                                Audio transcript

                                <span class="optional-mark">
                                    Optional
                                </span>

                            </label>

                            <textarea
                                id="announcementAudioTranscript"
                                name="announcement_audio_transcript"
                                maxlength="10000"
                                rows="6"
                                placeholder="Optional: provide the spoken content for accessibility."
                                data-announcement-audio-transcript><?= htmlspecialchars(
                                                                        $existingAnnouncementTranscript,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?></textarea>

                            <div class="field-footer">

                                <small>
                                    Recommended for accessibility and recipients who cannot play audio.
                                </small>

                                <span
                                    class="character-count"
                                    data-audio-transcript-counter>
                                    <?= number_format(
                                        mb_strlen(
                                            $existingAnnouncementTranscript,
                                            'UTF-8'
                                        )
                                    ) ?>
                                    / 10000
                                </span>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- =================================
                     EVENT PANEL
                ================================== -->

                <section
                    class="form-section post-panel"
                    data-panel="event"
                    hidden>

                    <div class="form-section-heading">

                        <span>02</span>

                        <div>

                            <h2>
                                Event Details
                            </h2>

                            <p>
                                Add the schedule, location, and event
                                information for the calendar.
                            </p>

                        </div>

                    </div>

                    <div class="form-field">

                        <label for="eventTitle">

                            Event title

                            <span class="required-mark">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="eventTitle"
                            name="event_title"
                            value="<?= htmlspecialchars(
                                        $isEventEdit
                                            ? (
                                                $editItem['title']
                                                ?? ''
                                            )
                                            : '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            placeholder="Example: College Orientation"
                            maxlength="100"
                            data-required-for="event"
                            disabled>

                    </div>

                    <div class="form-grid">

                        <div class="form-field">

                            <label for="eventDate">

                                Start date and time

                                <span class="required-mark">
                                    *
                                </span>

                            </label>

                            <input
                                type="datetime-local"
                                id="eventDate"
                                name="event_date"
                                value="<?= htmlspecialchars(
                                            (
                                                $isEventEdit &&
                                                !empty($editItem['event_date'])
                                            )
                                                ? date(
                                                    'Y-m-d\TH:i',
                                                    strtotime(
                                                        $editItem['event_date']
                                                    )
                                                )
                                                : $calendarEventStartValue,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                data-required-for="event"
                                disabled>
                        </div>

                        <div class="form-field">

                            <label for="eventEndDate">

                                End date and time

                                <span class="optional-mark">
                                    Optional
                                </span>

                            </label>

                            <input
                                type="datetime-local"
                                id="eventEndDate"
                                name="event_end_date"
                                value="<?= (
                                            $isEventEdit &&
                                            !empty($editItem['end_date'])
                                        )
                                            ? htmlspecialchars(
                                                date(
                                                    'Y-m-d\TH:i',
                                                    strtotime(
                                                        $editItem['end_date']
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : ''
                                        ?>"
                                disabled>
                        </div>

                    </div>

                    <div class="form-field">

                        <label for="eventLocation">
                            Location
                        </label>

                        <input
                            type="text"
                            id="eventLocation"
                            name="event_location"
                            value="<?= htmlspecialchars(
                                        $isEventEdit
                                            ? (
                                                $editItem['location']
                                                ?? ''
                                            )
                                            : '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            placeholder="Example: School Gymnasium"
                            maxlength="255"
                            disabled>
                    </div>

                    <div class="form-field">

                        <label for="eventDescription">

                            Event description

                            <span class="required-mark">
                                *
                            </span>

                        </label>

                        <div
                            class="rich-text-editor"
                            data-rich-editor
                            data-target="eventDescription">

                            <div
                                class="rich-text-toolbar"
                                role="toolbar"
                                aria-label="Event formatting">

                                <button
                                    type="button"
                                    data-rich-command="bold"
                                    title="Bold">
                                    <i class="fa-solid fa-bold"></i>
                                </button>

                                <button
                                    type="button"
                                    data-rich-command="italic"
                                    title="Italic">
                                    <i class="fa-solid fa-italic"></i>
                                </button>

                                <button
                                    type="button"
                                    data-rich-command="underline"
                                    title="Underline">
                                    <i class="fa-solid fa-underline"></i>
                                </button>

                                <span class="rich-text-divider"></span>

                                <button
                                    type="button"
                                    data-rich-command="insertUnorderedList"
                                    title="Bulleted list">
                                    <i class="fa-solid fa-list-ul"></i>
                                </button>

                                <button
                                    type="button"
                                    data-rich-command="insertOrderedList"
                                    title="Numbered list">
                                    <i class="fa-solid fa-list-ol"></i>
                                </button>

                                <span class="rich-text-divider"></span>

                                <button
                                    type="button"
                                    data-rich-link
                                    title="Insert link">
                                    <i class="fa-solid fa-link"></i>
                                </button>

                            </div>

                            <div
                                class="rich-text-surface"
                                contenteditable="true"
                                data-rich-surface
                                data-placeholder="Provide the event details, participants, and reminders..."></div>

                            <textarea
                                id="eventDescription"
                                name="event_description"
                                rows="7"
                                maxlength="5000"
                                placeholder="Provide the event details, participants, and reminders..."
                                data-required-for="event"
                                disabled
                                hidden><?= htmlspecialchars(
                                            $isEventEdit
                                                ? (
                                                    $editItem['description']
                                                    ?? ''
                                                )
                                                : '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?></textarea>
                        </div>

                        <div class="field-footer">

                            <small>
                                Include participation instructions.
                            </small>

                            <span
                                class="character-count"
                                data-counter-for="eventDescription">
                                0 / 5000
                            </span>

                        </div>

                    </div>

                    <div class="form-field">

                        <label for="eventImage">
                            Event poster
                        </label>

                        <label
                            class="upload-zone"
                            for="eventImage"
                            data-upload-zone>

                            <input
                                type="file"
                                id="eventImage"
                                name="event_image"
                                accept=".jpg,.jpeg,.png,.webp"
                                data-image-input
                                disabled>

                            <span class="upload-icon">

                                <i class="fa-regular fa-image"></i>

                            </span>

                            <strong>
                                Upload event poster
                            </strong>

                            <small>
                                JPG, PNG, or WEBP. Maximum size: 5 MB.
                            </small>

                            <span class="upload-file-name">
                                No image selected
                            </span>

                        </label>

                        <?php

                        $existingEventImage =
                            $isEventEdit
                            ? trim(
                                (string) (
                                    $editItem['image_path']
                                    ?? ''
                                )
                            )
                            : '';

                        ?>

                        <div
                            class="image-preview"
                            data-image-preview
                            <?= $existingEventImage === ''
                                ? 'hidden'
                                : ''
                            ?>>

                            <img
                                src="<?= htmlspecialchars(
                                            $existingEventImage,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                alt="Event poster preview">

                            <button
                                type="button"
                                class="remove-preview"
                                data-remove-image>
                                <i class="fa-solid fa-xmark"></i>

                                Remove
                            </button>

                        </div>
                    </div>

                </section>

                <!-- =================================
                     DOCUMENT PANEL
                ================================== -->

                <section
                    class="form-section post-panel"
                    data-panel="document"
                    hidden>

                    <div class="form-section-heading">

                        <span>02</span>

                        <div>

                            <h2>
                                Document Upload
                            </h2>

                            <p>
                                Upload an official file and an optional
                                cover image.
                            </p>

                        </div>

                    </div>

                    <div class="form-field">

                        <label for="documentTitle">

                            Document title

                            <span class="required-mark">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="documentTitle"
                            name="document_title"
                            value="<?= htmlspecialchars(
                                        $isDocumentEdit
                                            ? (
                                                $editItem['title']
                                                ?? ''
                                            )
                                            : '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            placeholder="Example: Student Handbook 2026"
                            maxlength="100"
                            data-required-for="document"
                            required
                            disabled>

                        <div class="field-footer">

                            <small>
                                Enter a clear title for the document.
                            </small>

                            <span
                                class="character-count"
                                data-counter-for="documentTitle">
                                0 / 100
                            </span>

                        </div>

                    </div>

                    <div class="form-field">

                        <label for="documentDescription">

                            Document description

                            <span class="required-mark">
                                *
                            </span>

                        </label>

                        <textarea
                            id="documentDescription"
                            name="document_description"
                            rows="7"
                            maxlength="5000"
                            placeholder="Describe the document and explain what information it contains."
                            data-required-for="document"
                            required
                            disabled><?= htmlspecialchars(
                                            $isDocumentEdit
                                                ? (
                                                    $editItem['description']
                                                    ?? ''
                                                )
                                                : '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?></textarea>

                        <div class="field-footer">

                            <small>
                                Provide a short and clear summary.
                            </small>

                            <span
                                class="character-count"
                                data-counter-for="documentDescription">
                                0 / 5000
                            </span>

                        </div>

                    </div>

                    <div class="form-field">

                        <label for="documentFile">

                            Document file

                            <span class="required-mark">
                                *
                            </span>

                        </label>

                        <label
                            class="upload-zone document-zone"
                            for="documentFile"
                            data-upload-zone>

                            <input
                                type="file"
                                id="documentFile"
                                name="document_file"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
                                data-existing-file="<?= htmlspecialchars(
                                                        $isDocumentEdit
                                                            ? (
                                                                !empty($editItem['file_path'])
                                                                    ? 'index.php?page=document_download&context=workspace&document_id=' . $editId
                                                                    : ''
                                                            )
                                                            : '',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                <?= !$isDocumentEdit
                                    ? 'data-required-for="document"'
                                    : ''
                                ?>
                                disabled>

                            <span class="upload-icon">

                                <i class="fa-solid fa-cloud-arrow-up"></i>

                            </span>

                            <strong>
                                Select document
                            </strong>

                            <small>
                                PDF, Word, Excel, PowerPoint, or text.
                                Maximum size: 20 MB.
                            </small>

                            <span class="upload-file-name">
                                <?= htmlspecialchars(
                                    $isDocumentEdit
                                        ? (
                                            'Current file: '
                                            . (
                                                $editItem['file_name']
                                                ?? 'Existing document'
                                            )
                                        )
                                        : 'No document selected',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </label>

                    </div>

                    <div class="form-field">

                        <label for="documentCover">
                            Document cover
                        </label>

                        <label
                            class="upload-zone"
                            for="documentCover"
                            data-upload-zone>

                            <input
                                type="file"
                                id="documentCover"
                                name="document_cover"
                                accept=".jpg,.jpeg,.png,.webp"
                                data-image-input
                                disabled>

                            <span class="upload-icon">

                                <i class="fa-regular fa-image"></i>

                            </span>

                            <strong>
                                Upload document cover
                            </strong>

                            <small>
                                Add a thumbnail or pubmat.
                            </small>

                            <span class="upload-file-name">
                                No cover selected
                            </span>

                        </label>

                        <?php

                        $existingDocumentCover =
                            $isDocumentEdit
                            ? trim(
                                (string) (
                                    $editItem['cover_image_path']
                                    ?? ''
                                )
                            )
                            : '';

                        ?>

                        <?php

                        $existingDocumentCover =
                            $isDocumentEdit
                            ? trim(
                                (string) (
                                    $editItem['cover_image_path']
                                    ?? ''
                                )
                            )
                            : '';

                        ?>

                        <div
                            class="image-preview"
                            data-image-preview
                            <?= $existingDocumentCover === ''
                                ? 'hidden'
                                : ''
                            ?>>

                            <img
                                src="<?= htmlspecialchars(
                                            $existingDocumentCover,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                alt="Document cover preview">

                            <button
                                type="button"
                                class="remove-preview"
                                data-remove-image>
                                <i class="fa-solid fa-xmark"></i>

                                Remove
                            </button>

                        </div>

                    </div>

                </section>

                <!-- =================================
     SURVEY PANEL
================================== -->

                <section
                    class="form-section post-panel"
                    data-panel="survey"
                    hidden>

                    <div class="form-section-heading">

                        <span>02</span>

                        <div>

                            <h2>
                                Survey Builder
                            </h2>

                            <p>
                                Create a structured survey for feedback,
                                interaction, and school decision-making.
                            </p>

                        </div>

                    </div>

                    <!-- =================================
         SURVEY INFORMATION
    ================================== -->

                    <div class="publisher-subsection">

                        <div class="publisher-subsection-heading">

                            <div>

                                <h3>
                                    Survey information
                                </h3>

                                <p>
                                    Provide the purpose and availability
                                    of the survey.
                                </p>

                            </div>

                            <span class="publisher-prepared-badge">
                                Required
                            </span>

                        </div>

                        <div class="form-field">

                            <label for="surveyTitle">

                                Survey title

                                <span class="required-mark">
                                    *
                                </span>

                            </label>

                            <input
                                type="text"
                                id="surveyTitle"
                                name="survey_title"
                                value="<?= htmlspecialchars(
                                            $isSurveyEdit
                                                ? (
                                                    $editItem['title']
                                                    ?? ''
                                                )
                                                : '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                maxlength="255"
                                placeholder="Example: Foundation Day 2026 Feedback Survey"
                                data-required-for="survey"
                                disabled>

                        </div>

                        <div class="form-field">

                            <label for="surveyDescription">

                                Description or purpose

                                <span class="required-mark">
                                    *
                                </span>

                            </label>
                            <div
                                class="rich-text-editor"
                                data-rich-editor
                                data-target="surveyDescription">

                                <div
                                    class="rich-text-toolbar"
                                    role="toolbar"
                                    aria-label="Survey description formatting">

                                    <button
                                        type="button"
                                        data-rich-command="bold"
                                        title="Bold">
                                        <i class="fa-solid fa-bold"></i>
                                    </button>

                                    <button
                                        type="button"
                                        data-rich-command="italic"
                                        title="Italic">
                                        <i class="fa-solid fa-italic"></i>
                                    </button>

                                    <button
                                        type="button"
                                        data-rich-command="underline"
                                        title="Underline">
                                        <i class="fa-solid fa-underline"></i>
                                    </button>

                                    <span class="rich-text-divider"></span>

                                    <button
                                        type="button"
                                        data-rich-command="insertUnorderedList"
                                        title="Bulleted list">
                                        <i class="fa-solid fa-list-ul"></i>
                                    </button>

                                    <button
                                        type="button"
                                        data-rich-command="insertOrderedList"
                                        title="Numbered list">
                                        <i class="fa-solid fa-list-ol"></i>
                                    </button>

                                    <span class="rich-text-divider"></span>

                                    <button
                                        type="button"
                                        data-rich-link
                                        title="Insert link">
                                        <i class="fa-solid fa-link"></i>
                                    </button>

                                </div>

                                <div
                                    class="rich-text-surface"
                                    contenteditable="true"
                                    data-rich-surface
                                    data-placeholder="Explain what the survey is for and how the responses will help improve future activities or decisions."></div>

                                <textarea
                                    id="surveyDescription"
                                    name="survey_description"
                                    maxlength="5000"
                                    data-required-for="survey"
                                    disabled
                                    hidden><?= htmlspecialchars(
                                                $isSurveyEdit
                                                    ? (
                                                        $editItem['description']
                                                        ?? ''
                                                    )
                                                    : '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?></textarea>

                            </div>

                            <div class="field-footer">

                                <small>
                                    Tell recipients why their response matters.
                                </small>

                                <span
                                    class="character-count"
                                    data-counter-for="surveyDescription">
                                    0 / 5000
                                </span>

                            </div>

                        </div>

                        <div class="form-grid">

                            <div class="form-field">

                                <label for="surveyOpensAt">
                                    Opens at
                                </label>

                                <input
                                    type="datetime-local"
                                    id="surveyOpensAt"
                                    name="opens_at"
                                    value="<?= (
                                                $isSurveyEdit &&
                                                !empty($editItem['opens_at'])
                                            )
                                                ? htmlspecialchars(
                                                    date(
                                                        'Y-m-d\TH:i',
                                                        strtotime(
                                                            $editItem['opens_at']
                                                        )
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                                : '' ?>"
                                    disabled>

                                <small>
                                    Optional. Leave blank to accept responses
                                    as soon as the survey is published.
                                </small>

                            </div>

                            <div class="form-field">

                                <label for="surveyClosesAt">
                                    Closes at
                                </label>

                                <input
                                    type="datetime-local"
                                    id="surveyClosesAt"
                                    name="closes_at"
                                    value="<?= (
                                                $isSurveyEdit &&
                                                !empty($editItem['closes_at'])
                                            )
                                                ? htmlspecialchars(
                                                    date(
                                                        'Y-m-d\TH:i',
                                                        strtotime(
                                                            $editItem['closes_at']
                                                        )
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                                : '' ?>"
                                    disabled>

                                <small>
                                    Optional. Leave blank if there is no
                                    response deadline.
                                </small>

                            </div>

                        </div>

                    </div>

                    <!-- =================================
         QUESTION BUILDER
    ================================== -->

                    <div class="publisher-subsection">

                        <div class="publisher-subsection-heading">

                            <div>

                                <h3>
                                    Questions
                                </h3>

                                <p>
                                    Add the questions recipients must answer.
                                </p>

                            </div>

                            <span
                                class="publisher-prepared-badge"
                                id="surveyQuestionCount">
                                0 Questions
                            </span>

                        </div>

                        <div
                            id="surveyQuestionList"
                            class="survey-question-list">

                            <!-- Questions will be created by posting.js -->

                        </div>

                        <script
                            type="application/json"
                            id="surveyEditQuestionsData">
                            <?= json_encode(
                                $isSurveyEdit
                                    ? (
                                        $editItem['questions']
                                        ?? []
                                    )
                                    : [],
                                JSON_HEX_TAG |
                                    JSON_HEX_AMP |
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT
                            ) ?>
                        </script>

                        <button
                            type="button"
                            id="addSurveyQuestion"
                            class="survey-add-question-button"
                            disabled>

                            <i class="fa-solid fa-plus"></i>

                            Add Question

                        </button>

                    </div>

                </section>

                <!-- =================================
                     RELEASE SETTINGS
                ================================== -->

                <section class="form-section">

                    <div class="form-section-heading">

                        <span>03</span>

                        <div>

                            <h2>
                                Release Settings
                            </h2>

                            <p>
                                Choose how the content should be released.
                            </p>

                        </div>

                    </div>

                    <div class="release-mode-grid">

                        <label class="release-option">

                            <input
                                type="radio"
                                name="release_mode"
                                value="immediate"
                                <?= (
                                    !(
                                        $isAnnouncementEdit ||
                                        $isEventEdit ||
                                        $isDocumentEdit ||
                                        $isSurveyEdit
                                    ) ||
                                    (
                                        $editItem['release_mode']
                                        ?? 'immediate'
                                    ) === 'immediate'
                                ) ? 'checked' : '' ?>>

                            <span>

                                <i class="fa-solid fa-bolt"></i>

                            </span>

                            <div>

                                <strong>
                                    Immediate
                                </strong>

                                <small>
                                    Release after approval
                                </small>

                            </div>

                        </label>

                        <label class="release-option">

                            <input
                                type="radio"
                                name="release_mode"
                                value="scheduled"
                                <?= (
                                    (
                                        $isAnnouncementEdit ||
                                        $isEventEdit ||
                                        $isDocumentEdit ||
                                        $isSurveyEdit
                                    ) &&
                                    (
                                        $editItem['release_mode']
                                        ?? ''
                                    ) === 'scheduled'
                                ) ? 'checked' : '' ?>>

                            <span>

                                <i class="fa-regular fa-clock"></i>

                            </span>

                            <div>

                                <strong>
                                    Scheduled
                                </strong>

                                <small>
                                    Release on a future date
                                </small>

                            </div>

                        </label>

                        <label
                            class="release-option"
                            data-release-option="calendar">

                            <input
                                type="radio"
                                name="release_mode"
                                value="calendar"
                                <?= (
                                    (
                                        $isAnnouncementEdit ||
                                        $isDocumentEdit ||
                                        $isSurveyEdit
                                    ) &&
                                    (
                                        $editItem['release_mode']
                                        ?? ''
                                    ) === 'calendar'
                                ) ? 'checked' : '' ?>>
                            <span>

                                <i class="fa-regular fa-calendar-check"></i>

                            </span>

                            <div>

                                <strong>
                                    Calendar-Based
                                </strong>

                                <small>
                                    Link to a calendar event
                                </small>

                            </div>

                        </label>

                    </div>

                    <div
                        id="scheduledReleaseFields"
                        class="publisher-subsection"
                        hidden>


                        <div class="form-grid">

                            <div class="form-field">

                                <label for="scheduledPublishAt">
                                    Scheduled release date and time
                                </label>

                                <input
                                    type="datetime-local"
                                    id="scheduledPublishAt"
                                    name="scheduled_publish_at"
                                    value="<?= htmlspecialchars(
                                                (
                                                    (
                                                        $isAnnouncementEdit ||
                                                        $isEventEdit ||
                                                        $isDocumentEdit ||
                                                        $isSurveyEdit
                                                    ) &&
                                                    !empty($editItem['scheduled_publish_at'])
                                                )
                                                    ? date(
                                                        'Y-m-d\TH:i',
                                                        strtotime(
                                                            $editItem['scheduled_publish_at']
                                                        )
                                                    )
                                                    : '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                    disabled>

                            </div>


                            <div class="form-field">

                                <label for="calendarReference">
                                    Calendar event
                                </label>



                                <select
                                    id="calendarReference"
                                    name="calendar_event_id"
                                    disabled>
                                    <option value="">
                                        Select calendar event
                                    </option>

                                    <?php foreach (
                                        $upcomingEvents as $event
                                    ): ?>

                                        <?php
                                        $eventTitle =
                                            trim(
                                                (string) (
                                                    $event['title']
                                                    ?? 'Event'
                                                )
                                            );

                                        $eventDate =
                                            !empty($event['event_date'])
                                            ? date(
                                                'Y-m-d H:i:s',
                                                strtotime(
                                                    $event['event_date']
                                                )
                                            )
                                            : '';

                                        $eventDateLabel =
                                            $eventDate !== ''
                                            ? date(
                                                'F d, Y \a\t h:i A',
                                                strtotime(
                                                    $eventDate
                                                )
                                            )
                                            : '';
                                        ?>

                                        <option
                                            value="<?= (int) (
                                                        $event['event_id']
                                                        ?? 0
                                                    ) ?>"
                                            <?= (
                                                (
                                                    $isAnnouncementEdit ||
                                                    $isDocumentEdit ||
                                                    $isSurveyEdit
                                                ) &&
                                                (int) (
                                                    $editItem['calendar_event_id']
                                                    ?? 0
                                                ) ===
                                                (int) (
                                                    $event['event_id']
                                                    ?? 0
                                                )
                                            ) ? 'selected' : '' ?>
                                            data-event-title="<?= htmlspecialchars(
                                                                    $eventTitle,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-event-date="<?= htmlspecialchars(
                                                                    $eventDate,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-event-date-label="<?= htmlspecialchars(
                                                                        $eventDateLabel,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>">
                                            <?= htmlspecialchars(
                                                $eventTitle
                                                    . (
                                                        $eventDateLabel !== ''
                                                        ? ' — ' . $eventDateLabel
                                                        : ''
                                                    ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>



                            </div>

                        </div>

                        <div
                            id="scheduledReleaseNotice"
                            class="scheduled-release-preview"
                            hidden>

                            <div class="scheduled-release-preview-header">

                                <span class="scheduled-release-preview-icon">
                                    <i class="fa-regular fa-clock"></i>
                                </span>

                                <div>

                                    <small>
                                        Scheduled Release
                                    </small>
                                    <div class="scheduled-release-time-display">

                                        <strong id="scheduledReleaseCountdown">
                                            Waiting for schedule
                                        </strong>

                                        <span id="scheduledReleaseNoticeDate">
                                            Select a release date and time
                                        </span>

                                    </div>

                                </div>

                            </div>

                            <div class="scheduled-release-preview-grid">

                                <div class="scheduled-release-preview-item">

                                    <i class="fa-regular fa-eye-slash"></i>

                                    <div>

                                        <small>
                                            Visibility
                                        </small>

                                        <?php if ($isFaculty): ?>

                                            <strong>
                                                Pending Admin approval before release
                                            </strong>

                                        <?php else: ?>

                                            <strong>
                                                Remains hidden until release time
                                            </strong>

                                        <?php endif; ?>

                                    </div>

                                </div>

                                <div class="scheduled-release-preview-item">

                                    <i class="fa-solid fa-rocket"></i>

                                    <div>

                                        <small>
                                            Automatic Release
                                        </small>

                                        <?php if ($isFaculty): ?>

                                            <strong>
                                                Publishes at the requested time only after Admin approval
                                            </strong>

                                        <?php else: ?>

                                            <strong>
                                                Automatically published at the scheduled release time
                                            </strong>

                                        <?php endif; ?>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div
                            id="calendarReleaseNotice"
                            class="calendar-link-preview"
                            hidden>

                            <div class="calendar-link-preview-header">

                                <span class="calendar-link-preview-icon">
                                    <i class="fa-solid fa-link"></i>
                                </span>

                                <div>

                                    <small>
                                        Linked Event
                                    </small>

                                    <strong id="calendarReleaseNoticeTitle">
                                        Select an event
                                    </strong>

                                    <span
                                        id="calendarReleaseCountdown"
                                        class="calendar-release-countdown">
                                        Waiting for event selection
                                    </span>

                                </div>

                            </div>

                            <div class="calendar-link-preview-grid">

                                <div class="calendar-link-preview-item">

                                    <i class="fa-regular fa-calendar"></i>

                                    <div>
                                        <small>
                                            Event Starts
                                        </small>

                                        <div class="calendar-release-time-display">

                                            <strong id="calendarReleaseNoticeDate">
                                                Select an event to view its schedule.
                                            </strong>

                                        </div>
                                    </div>

                                </div>

                                <div class="calendar-link-preview-item">

                                    <i class="fa-regular fa-eye-slash"></i>

                                    <div>
                                        <small>
                                            Visibility
                                        </small>

                                        <?php if ($isFaculty): ?>

                                            <strong>
                                                Pending Admin approval before release
                                            </strong>

                                        <?php else: ?>

                                            <strong>
                                                Remains hidden until the linked event begins
                                            </strong>

                                        <?php endif; ?>
                                    </div>

                                </div>

                                <div class="calendar-link-preview-item">

                                    <i class="fa-solid fa-rocket"></i>

                                    <div>
                                        <small>
                                            Automatic Release
                                        </small>

                                        <?php if ($isFaculty): ?>

                                            <strong>
                                                Uses the linked event as the requested release trigger after Admin approval
                                            </strong>

                                        <?php else: ?>

                                            <strong>
                                                Automatically published when the linked event begins
                                            </strong>

                                        <?php endif; ?>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- =================================
                     INTERACTION SETTINGS
                ================================== -->

                <section class="form-section">

                    <div class="form-section-heading">

                        <span>04</span>

                        <div>

                            <h2>
                                Interaction and Feedback
                            </h2>

                            <p>
                                Configure how recipients can respond.
                            </p>

                        </div>

                    </div>

                    <div class="interaction-option-grid">

                        <label class="publisher-toggle-card">

                            <input
                                type="checkbox"
                                name="allow_reactions"
                                value="1"
                                <?= (
                                    !(
                                        $isAnnouncementEdit ||
                                        $isEventEdit ||
                                        $isDocumentEdit ||
                                        $isSurveyEdit
                                    ) ||
                                    !empty($editItem['allow_reactions'])
                                ) ? 'checked' : '' ?>>
                            <span class="publisher-toggle-control"></span>

                            <div>

                                <strong>
                                    Allow voting
                                </strong>

                                <small>
                                    Let readers upvote or downvote this post.
                                </small>

                            </div>

                            <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>

                        </label>

                        <label class="publisher-toggle-card">

                            <input
                                type="checkbox"
                                name="allow_comments"
                                value="1"
                                <?= (
                                    !(
                                        $isAnnouncementEdit ||
                                        $isEventEdit ||
                                        $isDocumentEdit ||
                                        $isSurveyEdit
                                    ) ||
                                    !empty($editItem['allow_comments'])
                                ) ? 'checked' : '' ?>>
                            <span class="publisher-toggle-control"></span>

                            <div>

                                <strong>
                                    Allow discussion
                                </strong>

                                <small>
                                    Comments and suggestions
                                </small>

                            </div>

                            <i class="fa-regular fa-comment"></i>

                        </label>

                        <label class="publisher-toggle-card">

                            <input
                                type="checkbox"
                                name="require_acknowledgment"
                                value="1"
                                <?= (
                                    (
                                        $isAnnouncementEdit ||
                                        $isEventEdit ||
                                        $isDocumentEdit ||
                                        $isSurveyEdit
                                    ) &&
                                    !empty($editItem['require_acknowledgment'])
                                ) ? 'checked' : '' ?>>

                            <span class="publisher-toggle-control"></span>

                            <div>

                                <strong>
                                    Require acknowledgment
                                </strong>

                                <small>
                                    Track recipient acknowledgment
                                </small>

                            </div>

                            <i class="fa-solid fa-check-double"></i>

                        </label>

                        <label class="publisher-toggle-card">
                            <input
                                type="checkbox"
                                name="send_notification"
                                value="1"
                                <?= (
                                    !(
                                        $isAnnouncementEdit ||
                                        $isEventEdit ||
                                        $isDocumentEdit ||
                                        $isSurveyEdit
                                    ) ||
                                    !empty($editItem['send_notification'])
                                ) ? 'checked' : '' ?>>

                            <span class="publisher-toggle-control"></span>

                            <div>

                                <strong>
                                    Notify recipients
                                </strong>

                                <small>
                                    Create a system notification
                                </small>

                            </div>

                            <i class="fa-regular fa-bell"></i>

                        </label>

                    </div>

                </section>

                <!-- =================================
     PUBLISHING COMMAND BAR
================================== -->
                <footer class="publisher-command-bar">

                    <div class="publisher-command-status">

                        <span class="publisher-command-status-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>

                        <div>
                            <small>
                                Publishing Status
                            </small>

                            <strong id="commandReadinessText">
                                Review your content before publishing
                            </strong>
                        </div>

                    </div>

                    <div class="publisher-command-actions">

                        <button
                            type="button"
                            id="saveDraftButton"
                            class="publisher-draft-action">

                            <i class="fa-regular fa-floppy-disk"></i>

                            <span>
                                Save Draft
                            </span>

                        </button>

                        <!-- PRIMARY WORKFLOW ACTION -->
                        <button
                            type="submit"
                            id="publishButton"
                            class="publish-button publisher-primary-action">

                            <span>
                                <?= $isFaculty
                                    ? 'Submit Announcement for Review'
                                    : 'Publish Announcement'
                                ?>
                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </button>

                    </div>

                </footer>
            </form>

        </main>

        <!-- ==================================
     PUBLISHING INSPECTOR
=================================== -->

        <aside class="publisher-inspector">

            <div
                id="publisherDesktopPreviewSlot"
                class="publisher-desktop-preview-slot">
            </div>

            <!-- ==================================
         LIVE PREVIEW
    =================================== -->

            <section
                id="publisherPreviewCard"
                class="publisher-inspector-card">

                <div class="publisher-inspector-heading">

                    <span>
                        <i class="fa-regular fa-eye"></i>
                    </span>

                    <div>
                        <small>
                            Live Preview
                        </small>

                        <strong>
                            Content Preview
                        </strong>
                    </div>

                </div>

                <div class="publisher-live-preview">

                    <?php

                    $existingLivePreviewImage =
                        (
                            $isAnnouncementEdit ||
                            $isEventEdit
                        )
                        ? trim(
                            (string) (
                                $editItem['image_path']
                                ?? ''
                            )
                        )
                        : '';

                    ?>

                    <div
                        class="publisher-live-preview-media"
                        id="livePreviewMedia"
                        <?= $existingLivePreviewImage === ''
                            ? 'hidden'
                            : ''
                        ?>>

                        <img
                            id="livePreviewImage"
                            src="<?= htmlspecialchars(
                                        $existingLivePreviewImage,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            alt="Selected content preview">

                    </div>

                    <div class="publisher-live-preview-badges">

                        <span id="livePreviewType">
                            Announcement
                        </span>

                        <span id="livePreviewPriority">
                            Normal
                        </span>

                    </div>

                    <h3 id="livePreviewTitle">
                        Untitled Announcement
                    </h3>



                    <p id="livePreviewDescription">
                        Start entering content to see a live preview here.
                    </p>

                    <div
                        id="livePreviewAudio"
                        class="publisher-live-preview-audio"
                        hidden>

                        <i
                            class="fa-solid fa-volume-high"
                            aria-hidden="true"></i>

                        <span id="livePreviewAudioLabel">
                            Audio broadcast attached
                        </span>

                    </div>

                    <div
                        class="publisher-preview-topics"
                        id="livePreviewTopics"
                        hidden>
                    </div>

                    <div class="publisher-live-preview-meta">

                        <span id="livePreviewAudience">

                            <i class="fa-solid fa-users"></i>

                            <span>
                                School-wide
                            </span>

                        </span>

                        <span id="livePreviewRelease">

                            <i class="fa-regular fa-clock"></i>

                            <span>
                                Immediate
                            </span>

                        </span>

                    </div>

                </div>

            </section>


            <!-- ==================================
         VISIBILITY
    =================================== -->

            <section class="publisher-inspector-card">

                <div class="publisher-inspector-heading">

                    <span>
                        <i class="fa-solid fa-users"></i>
                    </span>

                    <div>
                        <small>
                            Visibility
                        </small>

                        <strong id="inspectorAudience">
                            School-wide
                        </strong>
                    </div>

                </div>

            </section>


            <!-- ==================================
         RELEASE
    =================================== -->

            <section class="publisher-inspector-card">

                <div class="publisher-inspector-heading">

                    <span>
                        <i class="fa-regular fa-clock"></i>
                    </span>

                    <div>
                        <small>
                            Release
                        </small>

                        <strong id="inspectorRelease">
                            Immediate release
                        </strong>
                    </div>

                </div>

            </section>


            <!-- ==================================
         PUBLISHING REQUIREMENTS
    =================================== -->

            <section
                class="publisher-inspector-card
               publisher-inspector-readiness">

                <div class="publisher-inspector-heading">

                    <span>
                        <i class="fa-solid fa-list-check"></i>
                    </span>

                    <div>
                        <small>
                            Publishing Requirements
                        </small>

                        <strong>
                            Readiness
                        </strong>
                    </div>

                </div>

                <div class="publisher-inspector-placeholder">
                    Live requirements will appear here in the next step.
                </div>

            </section>

        </aside>
        <!-- ==========================================
     PREVIEW MODAL
=========================================== -->

        <div
            id="postPreviewModal"
            class="publisher-preview-modal"
            aria-hidden="true"
            hidden>

            <div class="publisher-preview-dialog">

                <header class="publisher-preview-header">

                    <div>

                        <span class="page-eyebrow">
                            Content Preview
                        </span>

                        <h2>
                            Review before submission
                        </h2>

                    </div>

                    <button
                        type="button"
                        id="closePostPreview"
                        aria-label="Close preview">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </header>

                <div class="publisher-preview-body">

                    <article class="publisher-preview-card">

                        <div class="publisher-preview-badges">

                            <span id="previewContentType">
                                Announcement
                            </span>

                            <span id="previewPriority">
                                Normal
                            </span>

                            <span id="previewAudience">
                                Schoolwide
                            </span>

                        </div>

                        <div
                            id="previewImageWrap"
                            class="publisher-preview-image"
                            hidden>
                            <img
                                id="previewImage"
                                src=""
                                alt="Content preview">
                        </div>

                        <h3 id="previewTitle">
                            Content title
                        </h3>

                        <p id="previewContent">
                            Content details will appear here.
                        </p>


                        <div
                            class="publisher-preview-topics"
                            id="previewTopics"
                            hidden>
                        </div>

                        <div class="publisher-preview-meta">

                            <span>

                                <i class="fa-regular fa-clock"></i>

                                <span id="previewReleaseMode">
                                    Immediate release
                                </span>

                            </span>

                            <span>

                                <i class="fa-solid fa-route"></i>

                                <span id="previewWorkflow">
                                    Publish
                                </span>

                            </span>

                        </div>

                    </article>

                </div>

                <footer class="publisher-preview-footer">

                    <button
                        type="button"
                        id="returnToEditorButton"
                        class="cancel-button">
                        Return to Editor
                    </button>

                </footer>

            </div>

        </div>

    </div>

</section>
