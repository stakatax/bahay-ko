<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

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

$counts =
    $viewData['counts']
    ?? [];


$academicHistory =
    is_array(
        $viewData['history']
            ?? null
    )
    ? $viewData['history']
    : [];

$flash =
    is_array(
        $viewData['flash']
            ?? null
    )
    ? $viewData['flash']
    : [];

$flashType =
    trim(
        (string) (
            $flash['type']
            ?? ''
        )
    );

$flashMessage =
    trim(
        (string) (
            $flash['message']
            ?? ''
        )
    );

$oldAcademicInput =
    is_array(
        $flash['old_input']
            ?? null
    )
    ? $flash['old_input']
    : [];

$openAcademicForm =
    trim(
        (string) (
            $flash['open_form']
            ?? ''
        )
    );

$openAcademicEntityType =
    trim(
        (string) (
            $flash['entity_type']
            ?? ''
        )
    );


$openAcademicEntityId =
    max(
        0,
        (int) (
            $flash['entity_id']
            ?? 0
        )
    );


$escape =
    static fn(
        mixed $value
    ): string =>
    htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

$collections = [
    'department' => [
        'label' => 'School Divisions',
        'singular' => 'School Division',
        'description' =>
        'Top-level organizational divisions used for broad targeting.',
        'icon' => 'fa-solid fa-building-columns',
        'records' => $departments,
        'id_field' => 'department_id',
        'name_field' => 'department_name'
    ],

    'education_level' => [
        'label' => 'Education Levels',
        'singular' => 'Education Level',
        'description' =>
        'Curriculum levels grouped under each School Division.',
        'icon' => 'fa-solid fa-layer-group',
        'records' => $educationLevels,
        'id_field' => 'education_level_id',
        'name_field' => 'education_level_name'
    ],

    'academic_program' => [
        'label' => 'Programs & Strands',
        'singular' => 'Program or Strand',
        'description' =>
        'Senior High strands and College academic programs.',
        'icon' => 'fa-solid fa-graduation-cap',
        'records' => $academicPrograms,
        'id_field' => 'academic_program_id',
        'name_field' => 'program_name'
    ],

    'grade_level' => [
        'label' => 'Grades & Years',
        'singular' => 'Grade or Year Level',
        'description' =>
        'Grade and year-level options available during registration.',
        'icon' => 'fa-solid fa-list-ol',
        'records' => $gradeLevels,
        'id_field' => 'grade_level_id',
        'name_field' => 'grade_level_name'
    ],

    'section' => [
        'label' => 'Sections',
        'singular' => 'Section',
        'description' =>
        'The most precise academic grouping available for targeting.',
        'icon' => 'fa-solid fa-people-group',
        'records' => $sections,
        'id_field' => 'section_id',
        'name_field' => 'section_name'
    ]
];

$recordSubtitle =
    static function (
        string $entityType,
        array $record
    ): string {
        return match ($entityType) {
            'department' =>
            trim(
                (string) (
                    $record['department_code']
                    ?? ''
                )
            ),

            'education_level' =>
            $record['department_name']
                ?? 'Unassigned Division',

            'academic_program' =>
            implode(
                ' · ',
                array_filter([
                    $record['program_type']
                        ?? '',
                    $record['program_code']
                        ?? '',
                    $record['education_level_name']
                        ?? ''
                ])
            ),

            'grade_level' =>
            $record['education_level_name']
                ?? 'Unassigned Level',

            'section' =>
            implode(
                ' · ',
                array_filter([
                    $record['grade_level_name']
                        ?? '',
                    $record['program_code']
                        ?? 'General'
                ])
            ),

            default =>
            ''
        };
    };

$recordMetrics =
    static function (
        string $entityType,
        array $record
    ): array {
        return match ($entityType) {
            'department' => [
                'Children' =>
                $record['education_level_count'] ?? 0,

                'Assigned Users' =>
                $record['assigned_user_count'] ?? 0
            ],

            'education_level' => [
                'Programs' =>
                $record['program_count']
                    ?? 0,

                'Grades' =>
                $record['grade_level_count'] ?? 0,

                'Assigned Users' =>
                $record['assigned_user_count'] ?? 0
            ],

            'academic_program',
            'grade_level' => [
                'Sections' =>
                $record['section_count']
                    ?? 0,

                'Assigned Users' =>
                $record['assigned_user_count'] ?? 0
            ],

            'section' => [
                'Assigned Users' =>
                $record['assigned_user_count'] ?? 0
            ],

            default => []
        };
    };

$historyActionConfig = [
    'create' => [
        'label' => 'Created',
        'icon' => 'fa-solid fa-circle-plus',
        'class' => 'create'
    ],

    'update' => [
        'label' => 'Updated',
        'icon' => 'fa-solid fa-pen-to-square',
        'class' => 'update'
    ],

    'activate' => [
        'label' => 'Activated',
        'icon' => 'fa-solid fa-circle-play',
        'class' => 'activate'
    ],

    'deactivate' => [
        'label' => 'Deactivated',
        'icon' => 'fa-solid fa-circle-pause',
        'class' => 'deactivate'
    ]
];

$formatHistoryDate =
    static function (
        mixed $value
    ): string {
        $timestamp =
            strtotime(
                (string) $value
            );

        if ($timestamp === false) {
            return 'Unknown date';
        }

        return date(
            'M j, Y \a\t g:i A',
            $timestamp
        );
    };

?>

<section class="app-page academic-management-page">

    <header class="page-header academic-management-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                Curriculum Configuration
            </span>

            <h1>
                Academic Structure
            </h1>

            <p>
                Maintain the hierarchy used by registration,
                audience targeting, content delivery, and analytics.
            </p>

        </div>

        <div class="academic-management-header-note">

            <i class="fa-solid fa-shield-halved"></i>

            <span>

                <strong>
                    Administrator controlled
                </strong>

                <small>
                    Structural changes are permanently audited.
                </small>

            </span>

        </div>

    </header>

    <?php if ($flashMessage !== ''): ?>

        <div
            class="academic-management-alert <?= $escape(
                                                    $flashType
                                                ) ?>"
            data-academic-flash-alert
            role="<?= $flashType === 'error'
                        ? 'alert'
                        : 'status'
                    ?>"
            aria-live="<?= $flashType === 'error'
                            ? 'assertive'
                            : 'polite'
                        ?>"
            tabindex="0">

            <i class="<?= $flashType === 'success'
                            ? 'fa-solid fa-circle-check'
                            : 'fa-solid fa-circle-exclamation'
                        ?>"></i>

            <span>
                <?= $escape(
                    $flashMessage
                ) ?>
            </span>

        </div>

    <?php endif; ?>

    <section class="academic-management-stats">

        <?php foreach (
            [
                [
                    'label' => 'Divisions',
                    'value' => $counts['departments'] ?? 0,
                    'icon' =>
                    'fa-solid fa-building-columns'
                ],
                [
                    'label' => 'Levels',
                    'value' => $counts['education_levels'] ?? 0,
                    'icon' =>
                    'fa-solid fa-layer-group'
                ],
                [
                    'label' => 'Programs & Strands',
                    'value' => $counts['academic_programs'] ?? 0,
                    'icon' =>
                    'fa-solid fa-graduation-cap'
                ],
                [
                    'label' => 'Grades & Years',
                    'value' => $counts['grade_levels'] ?? 0,
                    'icon' =>
                    'fa-solid fa-list-ol'
                ],
                [
                    'label' => 'Sections',
                    'value' => $counts['sections'] ?? 0,
                    'icon' =>
                    'fa-solid fa-people-group'
                ]
            ]
            as $stat
        ): ?>

            <article class="academic-management-stat">

                <span>
                    <i class="<?= $escape(
                                    $stat['icon']
                                ) ?>"></i>
                </span>

                <div>

                    <strong>
                        <?= number_format(
                            (int) $stat['value']
                        ) ?>
                    </strong>

                    <small>
                        <?= $escape(
                            $stat['label']
                        ) ?>
                    </small>

                </div>

            </article>

        <?php endforeach; ?>

    </section>

    <!-- ==================================
     ACADEMIC AUDIT HISTORY
=================================== -->

    <details class="academic-audit-panel">

        <summary class="academic-audit-summary">

            <span class="academic-audit-summary-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </span>

            <span class="academic-audit-summary-copy">

                <strong>
                    Academic Audit History
                </strong>

                <small>
                    Review permanent create, update, activation,
                    and deactivation records.
                </small>

            </span>

            <span class="academic-audit-count">
                <?= number_format(
                    count(
                        $academicHistory
                    )
                ) ?>
                entries
            </span>

            <i class="fa-solid fa-chevron-down academic-audit-chevron"></i>

        </summary>

        <div class="academic-audit-content">

            <?php if ($academicHistory === []): ?>

                <div class="academic-audit-empty">

                    <i class="fa-solid fa-clock"></i>

                    <div>

                        <strong>
                            No academic history yet
                        </strong>

                        <p>
                            Audited academic structure changes will appear here.
                        </p>

                    </div>

                </div>

            <?php else: ?>

                <div class="academic-audit-list">

                    <?php foreach (
                        $academicHistory
                        as $historyEntry
                    ): ?>

                        <?php

                        $historyChangeType =
                            trim(
                                (string) (
                                    $historyEntry['change_type']
                                    ?? ''
                                )
                            );

                        $actionConfig =
                            $historyActionConfig[$historyChangeType]
                            ?? [
                                'label' => 'Changed',
                                'icon' =>
                                'fa-solid fa-pen',
                                'class' => 'update'
                            ];

                        $historyEntityType =
                            trim(
                                (string) (
                                    $historyEntry['entity_type']
                                    ?? ''
                                )
                            );

                        $historyEntityLabel =
                            $collections[$historyEntityType]['singular']
                            ?? 'Academic Record';

                        $historyEntityName =
                            trim(
                                (string) (
                                    $historyEntry['entity_name']
                                    ?? $historyEntityLabel
                                )
                            );

                        $historyReason =
                            trim(
                                (string) (
                                    $historyEntry['reason']
                                    ?? ''
                                )
                            );

                        $historyActor =
                            trim(
                                (string) (
                                    $historyEntry['changed_by_name']
                                    ?? 'Unknown User'
                                )
                            );

                        $historyActorIdentifier =
                            trim(
                                (string) (
                                    $historyEntry['changed_by_identifier']
                                    ?? ''
                                )
                            );

                        $previousSnapshot =
                            is_array(
                                $historyEntry['previous_data']
                                    ?? null
                            )
                            ? $historyEntry['previous_data']
                            : [];

                        $newSnapshot =
                            is_array(
                                $historyEntry['new_data']
                                    ?? null
                            )
                            ? $historyEntry['new_data']
                            : [];

                        $previousStatus =
                            trim(
                                (string) (
                                    $previousSnapshot['status']
                                    ?? ''
                                )
                            );

                        $newStatus =
                            trim(
                                (string) (
                                    $newSnapshot['status']
                                    ?? ''
                                )
                            );

                        $hasStatusTransition =
                            $previousStatus !== '' &&
                            $newStatus !== '' &&
                            $previousStatus !==
                            $newStatus;

                        ?>

                        <article class="academic-audit-entry">

                            <span class="academic-audit-action-icon <?= $escape(
                                                                        $actionConfig['class']
                                                                    ) ?>">

                                <i class="<?= $escape(
                                                $actionConfig['icon']
                                            ) ?>"></i>

                            </span>

                            <div class="academic-audit-entry-main">

                                <div class="academic-audit-entry-heading">

                                    <div>

                                        <span class="academic-audit-action-label <?= $escape(
                                                                                        $actionConfig['class']
                                                                                    ) ?>">
                                            <?= $escape(
                                                $actionConfig['label']
                                            ) ?>
                                        </span>

                                        <span class="academic-audit-entity-type">
                                            <?= $escape(
                                                $historyEntityLabel
                                            ) ?>
                                        </span>

                                    </div>

                                    <time datetime="<?= $escape(
                                                        $historyEntry['created_at']
                                                            ?? ''
                                                    ) ?>">
                                        <?= $escape(
                                            $formatHistoryDate(
                                                $historyEntry['created_at']
                                                    ?? ''
                                            )
                                        ) ?>
                                    </time>

                                </div>

                                <h3>
                                    <?= $escape(
                                        $historyEntityName
                                    ) ?>
                                </h3>

                                <?php if ($hasStatusTransition): ?>

                                    <p class="academic-audit-transition">

                                        <span class="<?= $escape(
                                                            strtolower(
                                                                $previousStatus
                                                            )
                                                        ) ?>">
                                            <?= $escape(
                                                $previousStatus
                                            ) ?>
                                        </span>

                                        <i class="fa-solid fa-arrow-right"></i>

                                        <span class="<?= $escape(
                                                            strtolower(
                                                                $newStatus
                                                            )
                                                        ) ?>">
                                            <?= $escape(
                                                $newStatus
                                            ) ?>
                                        </span>

                                    </p>

                                <?php endif; ?>

                                <p class="academic-audit-reason">
                                    <?= $escape(
                                        $historyReason !== ''
                                            ? $historyReason
                                            : 'No reason was recorded.'
                                    ) ?>
                                </p>

                                <footer class="academic-audit-meta">

                                    <span>

                                        <i class="fa-solid fa-user-shield"></i>

                                        <?= $escape(
                                            $historyActor
                                        ) ?>

                                        <?php if (
                                            $historyActorIdentifier !== ''
                                        ): ?>

                                            <small>
                                                <?= $escape(
                                                    $historyActorIdentifier
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </span>

                                    <span>

                                        <i class="fa-solid fa-fingerprint"></i>

                                        Audit
                                        #<?= number_format(
                                                (int) (
                                                    $historyEntry['academic_history_id']
                                                    ?? 0
                                                )
                                            ) ?>

                                    </span>

                                </footer>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </details>

    <section class="page-card academic-structure-workspace">

        <nav
            class="academic-structure-tabs"
            aria-label="Academic structure categories">

            <?php foreach (
                $collections
                as $entityType => $collection
            ): ?>

                <button
                    type="button"
                    class="<?= $entityType === 'department'
                                ? 'active'
                                : ''
                            ?>"
                    data-academic-tab="<?= $escape(
                                            $entityType
                                        ) ?>"
                    aria-selected="<?= $entityType === 'department'
                                        ? 'true'
                                        : 'false'
                                    ?>">

                    <i class="<?= $escape(
                                    $collection['icon']
                                ) ?>"></i>

                    <span>
                        <?= $escape(
                            $collection['label']
                        ) ?>
                    </span>

                    <b>
                        <?= number_format(
                            count(
                                $collection['records']
                            )
                        ) ?>
                    </b>

                </button>

            <?php endforeach; ?>

        </nav>

        <?php foreach (
            $collections
            as $entityType => $collection
        ): ?>

            <section
                class="academic-structure-panel"
                data-academic-panel="<?= $escape(
                                            $entityType
                                        ) ?>"
                <?= $entityType !== 'department'
                    ? 'hidden'
                    : ''
                ?>>

                <header class="academic-structure-panel-header">

                    <div>

                        <span class="page-eyebrow">
                            <?= $escape(
                                $collection['singular']
                            ) ?>
                        </span>

                        <h2>
                            <?= $escape(
                                $collection['label']
                            ) ?>
                        </h2>

                        <p>
                            <?= $escape(
                                $collection['description']
                            ) ?>
                        </p>

                    </div>

                    <button
                        type="button"
                        class="app-button primary"
                        data-create-academic="<?= $escape(
                                                    $entityType
                                                ) ?>"
                        disabled
                        title="Creation controls are added in the next checkpoint">

                        <i class="fa-solid fa-plus"></i>

                        Add
                        <?= $escape(
                            $collection['singular']
                        ) ?>

                    </button>

                </header>

                <?php if (
                    empty($collection['records'])
                ): ?>

                    <div class="academic-structure-empty">

                        <i class="<?= $escape(
                                        $collection['icon']
                                    ) ?>"></i>

                        <h3>
                            No records available
                        </h3>

                        <p>
                            Add the first
                            <?= $escape(
                                strtolower(
                                    $collection['singular']
                                )
                            ) ?>
                            when creation controls are enabled.
                        </p>

                    </div>

                <?php else: ?>

                    <div
                        class="academic-directory-toolbar"
                        data-academic-directory-toolbar>

                        <label class="academic-directory-search">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="search"
                                data-academic-directory-search
                                placeholder="Search <?= $escape(
                                                        $collection['label']
                                                    ) ?>"
                                aria-label="Search <?= $escape(
                                                        $collection['label']
                                                    ) ?>"
                                autocomplete="off"
                                disabled>

                        </label>

                        <div
                            class="academic-directory-filters"
                            role="group"
                            aria-label="Filter records by status">

                            <button
                                type="button"
                                class="active"
                                data-academic-status-filter="all"
                                aria-pressed="true"
                                disabled>
                                All
                            </button>

                            <button
                                type="button"
                                data-academic-status-filter="active"
                                aria-pressed="false"
                                disabled>
                                Active
                            </button>

                            <button
                                type="button"
                                data-academic-status-filter="inactive"
                                aria-pressed="false"
                                disabled>
                                Inactive
                            </button>

                        </div>

                        <span
                            class="academic-directory-result-count"
                            data-academic-result-count>
                            <?= number_format(
                                count(
                                    $collection['records']
                                )
                            ) ?>
                            records
                        </span>

                    </div>

                    <div class="academic-structure-list">

                        <?php foreach (
                            $collection['records']
                            as $record
                        ): ?>

                            <?php

                            $recordId =
                                (int) (
                                    $record[$collection['id_field']] ?? 0
                                );

                            $recordName =
                                trim(
                                    (string) (
                                        $record[$collection['name_field']] ?? ''
                                    )
                                );

                            $subtitle =
                                $recordSubtitle(
                                    $entityType,
                                    $record
                                );

                            $metrics =
                                $recordMetrics(
                                    $entityType,
                                    $record
                                );

                            $status =
                                trim(
                                    (string) (
                                        $record['status']
                                        ?? 'Inactive'
                                    )
                                );


                            $activeChildCount =
                                match ($entityType) {
                                    'department' =>
                                    (int) (
                                        $record['active_education_level_count']
                                        ?? 0
                                    ),

                                    'education_level' =>
                                    (int) (
                                        $record['active_program_count']
                                        ?? 0
                                    ) +
                                        (int) (
                                            $record['active_grade_level_count']
                                            ?? 0
                                        ),

                                    'academic_program',
                                    'grade_level' =>
                                    (int) (
                                        $record['active_section_count']
                                        ?? 0
                                    ),

                                    default =>
                                    0
                                };

                            $assignedUserCount =
                                (int) (
                                    $record['assigned_user_count']
                                    ?? 0
                                );

                            $nextStatus =
                                $status === 'Active'
                                ? 'Inactive'
                                : 'Active';

                            $isDeactivationBlocked =
                                $status === 'Active' &&
                                (
                                    $activeChildCount > 0 ||
                                    $assignedUserCount > 0
                                );



                            $blockedStatusReasons = [];

                            if ($activeChildCount > 0) {
                                $blockedStatusReasons[] =
                                    number_format(
                                        $activeChildCount
                                    )
                                    . ' active child record'
                                    . (
                                        $activeChildCount === 1
                                        ? ''
                                        : 's'
                                    );
                            }

                            if ($assignedUserCount > 0) {
                                $blockedStatusReasons[] =
                                    number_format(
                                        $assignedUserCount
                                    )
                                    . ' assigned user'
                                    . (
                                        $assignedUserCount === 1
                                        ? ''
                                        : 's'
                                    );
                            }

                            $blockedStatusExplanation =
                                $isDeactivationBlocked
                                ? 'Deactivation blocked by '
                                . implode(
                                    ' and ',
                                    $blockedStatusReasons
                                )
                                . '. Resolve these dependencies first.'
                                : '';

                            ?>

                            <article
                                class="academic-structure-record"
                                data-record-status="<?= $escape(
                                                        strtolower(
                                                            $status
                                                        )
                                                    ) ?>"
                                data-record-education-level-id="<?= (int) (
                                                                    $record['education_level_id']
                                                                    ?? 0
                                                                ) ?>"
                                data-record-education-level-name="<?= $escape(
                                                                        $record['education_level_name']
                                                                            ?? ''
                                                                    ) ?>"
                                data-record-grade-level-id="<?= (int) (
                                                                $record['grade_level_id']
                                                                ?? 0
                                                            ) ?>"
                                data-record-grade-level-name="<?= $escape(
                                                                    $record['grade_level_name']
                                                                        ?? ''
                                                                ) ?>"
                                data-entity-type="<?= $escape(
                                                        $entityType
                                                    ) ?>"
                                data-entity-id="<?= $recordId ?>"
                                data-academic-record="<?= $escape(
                                                            json_encode(
                                                                $record,
                                                                JSON_UNESCAPED_UNICODE |
                                                                    JSON_UNESCAPED_SLASHES
                                                            )
                                                        ) ?>">

                                <span class="academic-record-icon">

                                    <i class="<?= $escape(
                                                    $collection['icon']
                                                ) ?>"></i>

                                </span>

                                <div class="academic-record-copy">

                                    <div>

                                        <h3>
                                            <?= $escape(
                                                $recordName
                                            ) ?>
                                        </h3>

                                        <span class="academic-record-status <?= $escape(
                                                                                strtolower(
                                                                                    $status
                                                                                )
                                                                            ) ?>">
                                            <?= $escape(
                                                $status
                                            ) ?>
                                        </span>

                                    </div>

                                    <?php if ($subtitle !== ''): ?>

                                        <p>
                                            <?= $escape(
                                                $subtitle
                                            ) ?>
                                        </p>

                                    <?php endif; ?>



                                </div>

                                <dl class="academic-record-metrics">

                                    <?php foreach (
                                        $metrics
                                        as $label => $value
                                    ): ?>

                                        <div>

                                            <dt>
                                                <?= $escape(
                                                    $label
                                                ) ?>
                                            </dt>

                                            <dd>
                                                <?= number_format(
                                                    (int) $value
                                                ) ?>
                                            </dd>

                                        </div>

                                    <?php endforeach; ?>

                                </dl>

                                <div class="academic-record-actions">

                                    <button
                                        type="button"
                                        class="academic-record-action"
                                        data-edit-academic
                                        disabled
                                        title="Edit controls are initializing"
                                        aria-label="Edit <?= $escape(
                                                                $recordName
                                                            ) ?>">

                                        <i class="fa-solid fa-pen"></i>

                                        <span>
                                            Edit
                                        </span>

                                    </button>

                                    <span
                                        class="academic-status-action-container"
                                        <?php if ($isDeactivationBlocked): ?>
                                        tabindex="0"
                                        role="note"
                                        aria-label="<?= $escape(
                                                        $blockedStatusExplanation
                                                    ) ?>"
                                        data-blocked-explanation="<?= $escape(
                                                                        $blockedStatusExplanation
                                                                    ) ?>"
                                        <?php endif; ?>>
                                        <button
                                            type="button"
                                            class="academic-record-action <?= $nextStatus === 'Inactive'
                                                                                ? 'danger'
                                                                                : 'success'
                                                                            ?>"
                                            data-change-academic-status
                                            data-current-status="<?= $escape(
                                                                        $status
                                                                    ) ?>"
                                            data-new-status="<?= $escape(
                                                                    $nextStatus
                                                                ) ?>"
                                            data-active-children="<?= $activeChildCount ?>"
                                            data-assigned-users="<?= $assignedUserCount ?>"
                                            disabled
                                            title="<?= $escape(
                                                        $isDeactivationBlocked
                                                            ? $blockedStatusExplanation
                                                            : 'Status controls are initializing.'
                                                    ) ?>"
                                            aria-label="<?= $escape(
                                                            $nextStatus
                                                                . ' '
                                                                . $recordName
                                                        ) ?>">

                                            <i class="<?= $nextStatus === 'Inactive'
                                                            ? 'fa-solid fa-circle-pause'
                                                            : 'fa-solid fa-circle-play'
                                                        ?>"></i>

                                            <span>
                                                <?= $escape(
                                                    $nextStatus === 'Inactive'
                                                        ? 'Deactivate'
                                                        : 'Activate'
                                                ) ?>
                                            </span>

                                        </button>
                                    </span>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <div
                        class="academic-directory-no-results"
                        data-academic-no-results
                        hidden>

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <h3>
                            No matching records
                        </h3>

                        <p>
                            Try another search term or status filter.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>




        <!-- ==================================
         CREATE ACADEMIC RECORD MODAL
    =================================== -->

        <div
            class="academic-modal"
            id="academicCreateModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="academicCreateModalTitle"
            hidden>

            <button
                type="button"
                class="academic-modal-backdrop"
                data-close-academic-modal
                aria-label="Close Create Academic Record dialog">
            </button>

            <section class="academic-modal-dialog">

                <header class="academic-modal-header">

                    <div>

                        <span class="page-eyebrow">
                            Curriculum Configuration
                        </span>

                        <h2 id="academicCreateModalTitle">
                            Add Academic Record
                        </h2>

                        <p id="academicCreateModalDescription">
                            Create a new record within the academic hierarchy.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="academic-modal-close"
                        data-close-academic-modal
                        aria-label="Close dialog">

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </header>

                <form
                    method="post"
                    action="index.php?page=academic_create"
                    id="academicCreateForm"
                    class="academic-modal-form"
                    novalidate>

                    <?= csrfInput() ?>

                    <input
                        type="hidden"
                        name="entity_type"
                        id="academicCreateEntityType"
                        value="">

                    <input
                        type="hidden"
                        name="entity_id"
                        id="academicEditEntityId"
                        value="0">

                    <!-- SCHOOL DIVISION -->

                    <fieldset
                        class="academic-create-fields"
                        data-academic-create-fields="department"
                        hidden>

                        <legend>
                            School Division information
                        </legend>

                        <div class="academic-form-grid two-columns">

                            <label>

                                <span>
                                    School Division Name *
                                </span>

                                <input
                                    type="text"
                                    name="department_name"
                                    maxlength="100"
                                    autocomplete="off"
                                    data-required-for="department">

                            </label>

                            <label>

                                <span>
                                    Division Code *
                                </span>

                                <input
                                    type="text"
                                    name="department_code"
                                    maxlength="20"
                                    autocomplete="off"
                                    data-uppercase
                                    data-required-for="department">

                            </label>

                        </div>

                        <label>

                            <span>
                                Description
                            </span>

                            <textarea
                                name="description"
                                maxlength="2000"
                                rows="4"
                                placeholder="Briefly explain the scope of this School Division."></textarea>

                        </label>

                    </fieldset>

                    <!-- EDUCATION LEVEL -->

                    <fieldset
                        class="academic-create-fields"
                        data-academic-create-fields="education_level"
                        hidden>

                        <legend>
                            Education Level information
                        </legend>

                        <div class="academic-form-grid two-columns">

                            <label>

                                <span>
                                    School Division *
                                </span>

                                <select
                                    name="department_id"
                                    data-locked-on-edit
                                    data-required-for="education_level">

                                    <option value="">
                                        Select a School Division
                                    </option>

                                    <?php foreach (
                                        $departments
                                        as $department
                                    ): ?>

                                        <?php if (
                                            (
                                                $department['status']
                                                ?? ''
                                            ) !== 'Active'
                                        ) {
                                            continue;
                                        } ?>

                                        <option
                                            value="<?= (int) (
                                                        $department['department_id']
                                                        ?? 0
                                                    ) ?>">

                                            <?= $escape(
                                                $department['department_name']
                                                    ?? ''
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </label>

                            <label>

                                <span>
                                    Education Level Name *
                                </span>

                                <input
                                    type="text"
                                    name="education_level_name"
                                    maxlength="100"
                                    autocomplete="off"
                                    data-required-for="education_level">

                            </label>

                        </div>

                    </fieldset>

                    <!-- PROGRAM OR STRAND -->

                    <fieldset
                        class="academic-create-fields"
                        data-academic-create-fields="academic_program"
                        hidden>

                        <legend>
                            Program or Strand information
                        </legend>

                        <div class="academic-form-grid two-columns">

                            <label>

                                <span>
                                    Education Level *
                                </span>

                                <select
                                    name="education_level_id"
                                    data-locked-on-edit
                                    data-required-for="academic_program">

                                    <option value="">
                                        Select an Education Level
                                    </option>

                                    <?php foreach (
                                        $educationLevels
                                        as $educationLevel
                                    ): ?>

                                        <?php if (
                                            (
                                                $educationLevel['status']
                                                ?? ''
                                            ) !== 'Active'
                                        ) {
                                            continue;
                                        } ?>

                                        <option
                                            value="<?= (int) (
                                                        $educationLevel['education_level_id']
                                                        ?? 0
                                                    ) ?>">

                                            <?= $escape(
                                                $educationLevel['education_level_name']
                                                    ?? ''
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </label>

                            <label>

                                <span>
                                    Classification *
                                </span>

                                <select
                                    name="program_type"
                                    data-locked-on-edit
                                    data-required-for="academic_program">

                                    <option value="">
                                        Select a classification
                                    </option>

                                    <option value="Program">
                                        Program
                                    </option>

                                    <option value="Strand">
                                        Strand
                                    </option>

                                </select>

                            </label>

                            <label>

                                <span>
                                    Program or Strand Name *
                                </span>

                                <input
                                    type="text"
                                    name="program_name"
                                    maxlength="150"
                                    autocomplete="off"
                                    data-required-for="academic_program">

                            </label>

                            <label>

                                <span>
                                    Program or Strand Code *
                                </span>

                                <input
                                    type="text"
                                    name="program_code"
                                    maxlength="30"
                                    autocomplete="off"
                                    data-uppercase
                                    data-required-for="academic_program">

                            </label>

                        </div>

                        <label>

                            <span>
                                Description
                            </span>

                            <textarea
                                name="description"
                                maxlength="2000"
                                rows="4"
                                placeholder="Briefly describe this Program or Strand."></textarea>

                        </label>

                    </fieldset>

                    <!-- GRADE OR YEAR LEVEL -->

                    <fieldset
                        class="academic-create-fields"
                        data-academic-create-fields="grade_level"
                        hidden>

                        <legend>
                            Grade or Year Level information
                        </legend>

                        <div class="academic-form-grid two-columns">

                            <label>

                                <span>
                                    Education Level *
                                </span>

                                <select
                                    name="education_level_id"
                                    data-locked-on-edit
                                    data-required-for="grade_level">

                                    <option value="">
                                        Select an Education Level
                                    </option>

                                    <?php foreach (
                                        $educationLevels
                                        as $educationLevel
                                    ): ?>

                                        <?php if (
                                            (
                                                $educationLevel['status']
                                                ?? ''
                                            ) !== 'Active'
                                        ) {
                                            continue;
                                        } ?>

                                        <option
                                            value="<?= (int) (
                                                        $educationLevel['education_level_id']
                                                        ?? 0
                                                    ) ?>">

                                            <?= $escape(
                                                $educationLevel['education_level_name']
                                                    ?? ''
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </label>

                            <label>

                                <span>
                                    Grade or Year Level Name *
                                </span>

                                <input
                                    type="text"
                                    name="grade_level_name"
                                    maxlength="100"
                                    autocomplete="off"
                                    data-required-for="grade_level">

                            </label>

                        </div>

                    </fieldset>

                    <!-- SECTION -->

                    <fieldset
                        class="academic-create-fields"
                        data-academic-create-fields="section"
                        hidden>

                        <legend>
                            Section information
                        </legend>

                        <div class="academic-form-grid two-columns">

                            <label>

                                <span>
                                    Grade or Year Level *
                                </span>

                                <select
                                    name="grade_level_id"
                                    id="academicSectionGrade"
                                    data-locked-on-edit
                                    data-required-for="section">

                                    <option value="">
                                        Select a Grade or Year Level
                                    </option>

                                    <?php foreach (
                                        $gradeLevels
                                        as $gradeLevel
                                    ): ?>

                                        <?php if (
                                            (
                                                $gradeLevel['status']
                                                ?? ''
                                            ) !== 'Active'
                                        ) {
                                            continue;
                                        } ?>

                                        <option
                                            value="<?= (int) (
                                                        $gradeLevel['grade_level_id']
                                                        ?? 0
                                                    ) ?>"
                                            data-education-level-id="<?= (int) (
                                                                            $gradeLevel['education_level_id']
                                                                            ?? 0
                                                                        ) ?>">

                                            <?= $escape(
                                                $gradeLevel['grade_level_name']
                                                    ?? ''
                                            ) ?>

                                            ·

                                            <?= $escape(
                                                $gradeLevel['education_level_name']
                                                    ?? ''
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </label>

                            <label>

                                <span>
                                    Program or Strand
                                </span>

                                <select
                                    name="academic_program_id"
                                    data-locked-on-edit
                                    id="academicSectionProgram">

                                    <option value="">
                                        General / Not applicable
                                    </option>

                                    <?php foreach (
                                        $academicPrograms
                                        as $academicProgram
                                    ): ?>

                                        <?php if (
                                            (
                                                $academicProgram['status']
                                                ?? ''
                                            ) !== 'Active'
                                        ) {
                                            continue;
                                        } ?>

                                        <option
                                            value="<?= (int) (
                                                        $academicProgram['academic_program_id']
                                                        ?? 0
                                                    ) ?>"
                                            data-education-level-id="<?= (int) (
                                                                            $academicProgram['education_level_id']
                                                                            ?? 0
                                                                        ) ?>">

                                            <?= $escape(
                                                $academicProgram['program_code']
                                                    ?? $academicProgram['program_name']
                                                    ?? ''
                                            ) ?>

                                            ·

                                            <?= $escape(
                                                $academicProgram['program_name']
                                                    ?? ''
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <small>
                                    Available Programs or Strands will follow the selected level.
                                </small>

                            </label>

                            <label class="academic-form-span-two">

                                <span>
                                    Section Name *
                                </span>

                                <input
                                    type="text"
                                    name="section_name"
                                    maxlength="100"
                                    autocomplete="off"
                                    data-required-for="section">

                            </label>

                        </div>

                    </fieldset>

                    <!-- AUDIT REASON -->

                    <label class="academic-create-reason">

                        <span>
                            Reason for creation *
                        </span>

                        <textarea
                            name="reason"
                            maxlength="1000"
                            minlength="5"
                            rows="3"
                            required
                            placeholder="Explain why this academic record is being added."></textarea>

                        <small>
                            This reason will be retained in the permanent academic audit history.
                        </small>

                    </label>

                    <div
                        class="academic-form-error-summary"
                        id="academicCreateErrorSummary"
                        role="alert"
                        hidden>
                    </div>

                    <footer class="academic-modal-footer">

                        <button
                            type="button"
                            class="app-button secondary"
                            data-close-academic-modal>

                            Cancel

                        </button>

                        <button
                            type="submit"
                            class="app-button primary"
                            id="academicModalSubmit">

                            <i class="fa-solid fa-floppy-disk"></i>

                            Review Creation

                        </button>

                    </footer>

                </form>

            </section>

        </div>

        <!-- ==================================
     ACADEMIC STATUS MODAL
=================================== -->

        <div
            class="academic-modal"
            id="academicStatusModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="academicStatusModalTitle"
            hidden>

            <button
                type="button"
                class="academic-modal-backdrop"
                data-close-academic-status
                aria-label="Close Academic Status dialog">
            </button>

            <section class="academic-modal-dialog academic-status-dialog">

                <header class="academic-modal-header">

                    <div>

                        <span class="page-eyebrow">
                            Academic Record Status
                        </span>

                        <h2 id="academicStatusModalTitle">
                            Change Record Status
                        </h2>

                        <p id="academicStatusModalDescription">
                            Review the impact before changing availability.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="academic-modal-close"
                        data-close-academic-status
                        aria-label="Close dialog">

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </header>

                <form
                    method="post"
                    action="index.php?page=academic_change_status"
                    id="academicStatusForm"
                    class="academic-modal-form"
                    novalidate>

                    <?= csrfInput() ?>

                    <input
                        type="hidden"
                        name="entity_type"
                        id="academicStatusEntityType"
                        value="">

                    <input
                        type="hidden"
                        name="entity_id"
                        id="academicStatusEntityId"
                        value="0">

                    <input
                        type="hidden"
                        name="new_status"
                        id="academicStatusNewStatus"
                        value="">

                    <input
                        type="hidden"
                        name="confirm_status_change"
                        id="academicStatusConfirmation"
                        value="0">

                    <section class="academic-status-summary">

                        <span
                            class="academic-status-summary-icon"
                            id="academicStatusSummaryIcon">

                            <i class="fa-solid fa-circle-pause"></i>

                        </span>

                        <div>

                            <small>
                                Selected Academic Record
                            </small>

                            <h3 id="academicStatusRecordName">
                                Academic Record
                            </h3>

                            <p id="academicStatusTransition">
                                Active → Inactive
                            </p>

                        </div>

                    </section>

                    <section
                        class="academic-status-impact"
                        id="academicStatusImpact">

                        <header>

                            <i class="fa-solid fa-diagram-project"></i>

                            <div>

                                <strong>
                                    Dependency impact
                                </strong>

                                <small id="academicStatusImpactMessage">
                                    This record has no blocking dependencies.
                                </small>

                            </div>

                        </header>

                        <dl>

                            <div>

                                <dt>
                                    Active Children
                                </dt>

                                <dd id="academicStatusActiveChildren">
                                    0
                                </dd>

                            </div>

                            <div>

                                <dt>
                                    Assigned Users
                                </dt>

                                <dd id="academicStatusAssignedUsers">
                                    0
                                </dd>

                            </div>

                        </dl>

                    </section>

                    <label class="academic-create-reason">

                        <span id="academicStatusReasonLabel">
                            Reason for status change *
                        </span>

                        <textarea
                            name="reason"
                            id="academicStatusReason"
                            maxlength="1000"
                            minlength="5"
                            rows="4"
                            required
                            placeholder="Explain why this academic record's availability is changing."></textarea>

                        <small>
                            This reason will be retained in the permanent academic audit history.
                        </small>

                    </label>

                    <div
                        class="academic-form-error-summary"
                        id="academicStatusErrorSummary"
                        role="alert"
                        hidden>
                    </div>

                    <footer class="academic-modal-footer">

                        <button
                            type="button"
                            class="app-button secondary"
                            data-close-academic-status>

                            Cancel

                        </button>

                        <button
                            type="submit"
                            class="app-button primary"
                            id="academicStatusSubmit">

                            <i class="fa-solid fa-circle-check"></i>

                            Review Status Change

                        </button>

                    </footer>

                </form>

            </section>

        </div>

        <div
            id="academicCreateState"
            hidden
            data-open-form="<?= $escape(
                                $openAcademicForm
                            ) ?>"
            data-entity-type="<?= $escape(
                                    $openAcademicEntityType
                                ) ?>"
            data-entity-id="<?= $openAcademicEntityId ?>"
            data-old-input="<?= $escape(
                                json_encode(
                                    $oldAcademicInput,
                                    JSON_UNESCAPED_UNICODE |
                                        JSON_UNESCAPED_SLASHES
                                )
                            ) ?>">
        </div>

    </section>

</section>