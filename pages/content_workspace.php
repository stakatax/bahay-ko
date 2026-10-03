<?php

$activeStatus = strtolower(
    trim(
        (string) (
            $viewData['active_status']
            ?? 'draft'
        )
    )
);

$items =
    $viewData['items']
    ?? [];

$counts =
    $viewData['counts']
    ?? [];

$supportedTypes =
    $viewData['supported_types']
    ?? [
        'announcement',
        'event',
        'document',
        'survey'
    ];

$currentRole =
    (string) (
        $_SESSION['role']
        ?? 'Guest'
    );

$currentUserId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );

$isAdmin =
    $currentRole === 'Admin';

$isFaculty =
    $currentRole === 'Faculty';

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

/* ==========================================
   WORKSPACE CONFIGURATION
========================================== */

$statusConfig = [
    'draft' => [
        'label' =>
        'Drafts',

        'description' =>
        'Unsubmitted content that can still be revised.',

        'icon' =>
        'fa-regular fa-pen-to-square'
    ],

    'pending_review' => [
        'label' =>
        'Pending Review',

        'description' =>
        'Faculty submissions waiting for administrator review.',

        'icon' =>
        'fa-regular fa-clock'
    ],

    'scheduled' => [
        'label' =>
        'Scheduled',

        'description' =>
        'Approved announcements waiting for their release time.',

        'icon' =>
        'fa-regular fa-calendar-check'
    ],

    'published' => [
        'label' =>
        'Published',

        'description' =>
        'Active content available to its intended recipients.',

        'icon' =>
        'fa-solid fa-circle-check'
    ],

    'rejected' => [
        'label' =>
        'Rejected',

        'description' =>
        'Content returned with administrator review notes.',

        'icon' =>
        'fa-solid fa-circle-xmark'
    ],

    'archived' => [
        'label' =>
        'Archived',

        'description' =>
        'Published content removed from active circulation.',

        'icon' =>
        'fa-solid fa-box-archive'
    ]
];

$typeConfig = [
    'announcement' => [
        'label' =>
        'Announcement',

        'icon' =>
        'fa-solid fa-bullhorn'
    ],

    'event' => [
        'label' =>
        'Event',

        'icon' =>
        'fa-regular fa-calendar'
    ],

    'document' => [
        'label' =>
        'Document',

        'icon' =>
        'fa-regular fa-file-lines'
    ],

    'survey' => [
        'label' =>
        'Survey',

        'icon' =>
        'fa-solid fa-square-poll-horizontal'
    ]
];

$activeConfig =
    $statusConfig[$activeStatus]
    ?? $statusConfig['draft'];

/* ==========================================
   HELPERS
========================================== */

function workspaceDate(
    mixed $value,
    string $fallback = 'Date unavailable'
): string {
    $value =
        trim(
            (string) (
                $value
                ?? ''
            )
        );

    if ($value === '') {
        return $fallback;
    }

    $timestamp =
        strtotime(
            $value
        );

    return $timestamp === false
        ? $fallback
        : date(
            'M d, Y • h:i A',
            $timestamp
        );
}

function workspacePreview(
    mixed $value,
    int $limit = 180
): string {
    $text =
        trim(
            preg_replace(
                '/\s+/',
                ' ',
                strip_tags(
                    (string) $value
                )
            )
                ?? ''
        );

    if ($text === '') {
        return 'No additional description provided.';
    }

    return mb_strlen($text) > $limit
        ? mb_substr(
            $text,
            0,
            $limit
        ) . '...'
        : $text;
}

function workspaceStatusLabel(
    string $value
): string {
    return ucwords(
        str_replace(
            '_',
            ' ',
            $value
        )
    );
}

function workspaceJson(
    array $value
): string {
    return htmlspecialchars(
        json_encode(
            $value,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_HEX_TAG |
                JSON_HEX_APOS |
                JSON_HEX_AMP |
                JSON_HEX_QUOT
        ) ?: '{}',
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<section class="app-page workspace-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->

    <header class="page-header workspace-page-header">

        <div class="page-header-copy">

            <h1>
                Content workspace
            </h1>

        </div>

        <div class="page-actions workspace-page-actions">

            <a
                href="index.php?page=news"
                class="app-button secondary">
                <i class="fa-solid fa-newspaper"></i>

                Information Hub
            </a>

            <a
                href="index.php?page=postings"
                class="app-button primary">
                <i class="fa-solid fa-plus"></i>

                Create Content
            </a>

        </div>

    </header>

    <!-- ======================================
         FEEDBACK MESSAGES
    ======================================= -->

    <?php if (
        $successMessage !== ''
    ): ?>

        <div
            class="workspace-alert success"
            role="status">
            <i class="fa-solid fa-circle-check"></i>

            <span>
                <?= htmlspecialchars(
                    $successMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </div>

    <?php endif; ?>

    <?php if (
        $errorMessage !== ''
    ): ?>

        <div
            class="workspace-alert error"
            role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </div>

    <?php endif; ?>

    <!-- ======================================
         WORKFLOW SUMMARY
    ======================================= -->

    <section
        class="workspace-summary-grid"
        aria-label="Workflow summary">

        <?php foreach (
            $statusConfig
            as $status =>
            $config
        ): ?>

            <?php

            $statusCount =
                (int) (
                    $counts[$status]
                    ?? 0
                );

            ?>

            <a
                href="index.php?page=content_workspace&amp;status=<?=
                                                                    urlencode(
                                                                        $status
                                                                    )
                                                                    ?>"
                class="workspace-summary-card<?=
                                                $activeStatus === $status
                                                    ? ' active'
                                                    : ''
                                                ?>"
                aria-current="<?=
                                $activeStatus === $status
                                    ? 'page'
                                    : 'false'
                                ?>">

                <span
                    class="workspace-summary-icon status-<?=
                                                            htmlspecialchars(
                                                                $status,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            )
                                                            ?>">
                    <i class="<?= htmlspecialchars(
                                    $config['icon'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"></i>
                </span>

                <span class="workspace-summary-copy">

                    <strong>
                        <?= number_format(
                            $statusCount
                        ) ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars(
                            $config['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </small>

                </span>

                <i
                    class="fa-solid fa-chevron-right workspace-summary-arrow"></i>

            </a>

        <?php endforeach; ?>

    </section>

    <!-- ======================================
         MAIN WORKSPACE PANEL
    ======================================= -->

    <section class="workspace-panel">

        <header class="workspace-panel-header">

            <div class="workspace-panel-copy">

                <h2>
                    <i class="<?= htmlspecialchars(
                                    $activeConfig['icon'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"></i>

                    <?= htmlspecialchars(
                        $activeConfig['label'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <p>
                    <?= htmlspecialchars(
                        $activeConfig['description'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            </div>

            <!-- ==============================
                 SEARCH, FILTER, AND SORT
            =============================== -->

            <div class="workspace-controls">

                <label
                    class="workspace-control workspace-search-control">

                    <span>
                        Search
                    </span>

                    <div>
                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="workspaceSearch"
                            placeholder="Title, author, or content"
                            autocomplete="off">
                    </div>

                </label>

                <label class="workspace-control">

                    <span>
                        Content type
                    </span>

                    <select id="workspaceTypeFilter">

                        <option value="all">
                            All content
                        </option>

                        <?php foreach (
                            $typeConfig
                            as $type =>
                            $config
                        ): ?>

                            <?php if (
                                !in_array(
                                    $type,
                                    $supportedTypes,
                                    true
                                )
                            ): ?>
                                <?php continue; ?>
                            <?php endif; ?>

                            <option
                                value="<?= htmlspecialchars(
                                            $type,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                                <?= htmlspecialchars(
                                    $config['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>

                <label class="workspace-control">

                    <span>
                        Sort by
                    </span>

                    <select id="workspaceSort">

                        <option value="newest">
                            Newest first
                        </option>

                        <option value="oldest">
                            Oldest first
                        </option>

                        <option value="title_asc">
                            Title A–Z
                        </option>

                        <option value="title_desc">
                            Title Z–A
                        </option>

                        <option value="reviewed">
                            Recently reviewed
                        </option>

                    </select>

                </label>

            </div>

        </header>

        <!-- ==================================
             EMPTY WORKFLOW STATE
        =================================== -->

        <?php if (
            empty($items)
        ): ?>

            <div class="workspace-empty-state">

                <span>
                    <i class="<?= htmlspecialchars(
                                    $activeConfig['icon'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"></i>
                </span>

                <h3>
                    No <?= htmlspecialchars(
                            strtolower(
                                $activeConfig['label']
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?> found
                </h3>

                <p>
                    Content assigned to this workflow stage
                    will appear here.
                </p>

                <?php if (
                    $activeStatus === 'draft'
                ): ?>

                    <a
                        href="index.php?page=postings"
                        class="app-button primary">
                        <i class="fa-solid fa-plus"></i>

                        Create Content
                    </a>

                <?php endif; ?>

            </div>

        <?php else: ?>

            <!-- ==================================
                 CONTENT LIST
            =================================== -->

            <div
                id="workspaceContentList"
                class="workspace-content-list">

                <?php foreach (
                    $items
                    as $item
                ): ?>

                    <?php

                    $contentType =
                        strtolower(
                            (string) (
                                $item['content_type']
                                ?? ''
                            )
                        );

                    $contentId =
                        (int) (
                            $item['content_id']
                            ?? 0
                        );

                    $contentConfig =
                        $typeConfig[$contentType]
                        ?? [
                            'label' =>
                            workspaceStatusLabel(
                                $contentType
                            ),

                            'icon' =>
                            'fa-regular fa-file'
                        ];

                    $title =
                        trim(
                            (string) (
                                $item['title']
                                ?? 'Untitled Content'
                            )
                        );

                    $description =
                        workspacePreview(
                            $item['description']
                                ?? ''
                        );

                    $fullDescription =
                        trim(
                            strip_tags(
                                (string) (
                                    $item['description']
                                    ?? ''
                                )
                            )
                        );

                    $authorName =
                        trim(
                            (string) (
                                $item['author_name']
                                ?? ''
                            )
                        );

                    if ($authorName === '') {
                        $authorName =
                            'Unknown Author';
                    }

                    $workflowStatus =
                        strtolower(
                            (string) (
                                $item['workflow_status']
                                ?? $activeStatus
                            )
                        );

                    $reviewNotes =
                        trim(
                            (string) (
                                $item['review_notes']
                                ?? ''
                            )
                        );

                    $reviewerName =
                        trim(
                            (string) (
                                $item['reviewer_name']
                                ?? ''
                            )
                        );

                    $createdAt =
                        (string) (
                            $item['created_at']
                            ?? ''
                        );

                    $updatedAt =
                        (string) (
                            $item['updated_at']
                            ?? ''
                        );

                    $reviewedAt =
                        (string) (
                            $item['reviewed_at']
                            ?? ''
                        );

                    $scheduledPublishAt =
                        (string) (
                            $item['scheduled_publish_at']
                            ?? ''
                        );

                    $searchValue =
                        strtolower(
                            implode(
                                ' ',
                                [
                                    $title,
                                    $authorName,
                                    $description,
                                    $contentConfig['label'],
                                    workspaceStatusLabel(
                                        $workflowStatus
                                    )
                                ]
                            )
                        );

                    $previewData = [
                        'type' =>
                        $contentConfig['label'],

                        'title' =>
                        $title,

                        'description' =>
                        $fullDescription !== ''
                            ? $fullDescription
                            : 'No additional description provided.',

                        'author' =>
                        $authorName,

                        'created' =>
                        workspaceDate(
                            $createdAt
                        ),

                        'updated_at' =>
                        $updatedAt !== ''
                            ? workspaceDate(
                                $updatedAt
                            )
                            : null,

                        'status' =>
                        workspaceStatusLabel(
                            $workflowStatus
                        ),

                        'category' =>
                        !empty($item['category'])
                            ? workspaceStatusLabel(
                                (string) $item['category']
                            )
                            : null,

                        'priority' =>
                        $item['priority']
                            ?? null,

                        'release_mode' =>
                        !empty($item['release_mode'])
                            ? workspaceStatusLabel(
                                (string) $item['release_mode']
                            )
                            : null,

                        'scheduled_publish_at' =>
                        !empty($item['scheduled_publish_at'])
                            ? workspaceDate(
                                $item['scheduled_publish_at']
                            )
                            : null,

                        'linked_event_title' =>
                        $item['linked_event_title']
                            ?? null,

                        'linked_event_date' =>
                        !empty($item['linked_event_date'])
                            ? workspaceDate(
                                $item['linked_event_date']
                            )
                            : null,

                        'audience' =>
                        $item['audience_label']
                            ?? $item['target_audience']
                            ?? null,

                        'event_date' =>
                        !empty($item['event_date'])
                            ? workspaceDate(
                                $item['event_date']
                            )
                            : null,

                        'end_date' =>
                        !empty($item['end_date'])
                            ? workspaceDate(
                                $item['end_date']
                            )
                            : null,

                        'location' =>
                        $item['location']
                            ?? null,

                        'file_name' =>
                        $item['file_name']
                            ?? null,

                        'file_type' =>
                        !empty($item['file_type'])
                            ? strtoupper(
                                (string) $item['file_type']
                            )
                            : null,

                        'file_size' =>
                        (int) (
                            $item['file_size']
                            ?? 0
                        ),

                        'file_path' =>
                        !empty($item['file_path'])
                            ? 'index.php?page=document_download&context=workspace&inline=1&document_id=' . $contentId
                            : null,

                        'allow_reactions' =>
                        isset(
                            $item['raw']['allow_reactions']
                        )
                            ? (
                                !empty($item['raw']['allow_reactions'])
                                ? 'Enabled'
                                : 'Disabled'
                            )
                            : null,

                        'allow_comments' =>
                        isset(
                            $item['raw']['allow_comments']
                        )
                            ? (
                                !empty($item['raw']['allow_comments'])
                                ? 'Enabled'
                                : 'Disabled'
                            )
                            : null,

                        'require_acknowledgment' =>
                        isset(
                            $item['raw']['require_acknowledgment']
                        )
                            ? (
                                !empty($item['raw']['require_acknowledgment'])
                                ? 'Required'
                                : 'Not Required'
                            )
                            : null,

                        'send_notification' =>
                        isset(
                            $item['raw']['send_notification']
                        )
                            ? (
                                !empty($item['raw']['send_notification'])
                                ? 'Enabled'
                                : 'Disabled'
                            )
                            : null,

                        'review_notes' =>
                        $reviewNotes !== ''
                            ? $reviewNotes
                            : null,

                        'reviewer' =>
                        $reviewerName !== ''
                            ? $reviewerName
                            : null,

                        'reviewed_at' =>
                        $reviewedAt !== ''
                            ? workspaceDate(
                                $reviewedAt
                            )
                            : null
                    ];

                    ?>

                    <article
                        class="workspace-content-card"
                        data-workspace-item
                        data-content-id="<?= (int) $contentId ?>"
                        data-content-type="<?= htmlspecialchars(
                                                $contentType,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                        data-search="<?= htmlspecialchars(
                                            $searchValue,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                        data-title="<?= htmlspecialchars(
                                        strtolower(
                                            $title
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                        data-created="<?= htmlspecialchars(
                                            $createdAt,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                        data-reviewed="<?= htmlspecialchars(
                                            $reviewedAt,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                        <div
                            class="workspace-card-accent type-<?=
                                                                htmlspecialchars(
                                                                    $contentType,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                )
                                                                ?>"></div>

                        <div
                            class="workspace-content-icon type-<?=
                                                                htmlspecialchars(
                                                                    $contentType,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                )
                                                                ?>">
                            <i class="<?= htmlspecialchars(
                                            $contentConfig['icon'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"></i>
                        </div>

                        <div class="workspace-content-main">

                            <div class="workspace-content-topline">

                                <div class="workspace-content-labels">

                                    <span
                                        class="workspace-type-badge type-<?=
                                                                            htmlspecialchars(
                                                                                $contentType,
                                                                                ENT_QUOTES,
                                                                                'UTF-8'
                                                                            )
                                                                            ?>">
                                        <?= htmlspecialchars(
                                            $contentConfig['label'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <?php if (
                                        $workflowStatus === 'scheduled' &&
                                        $scheduledPublishAt !== ''
                                    ): ?>

                                        <span
                                            class="workspace-meta-badge workspace-countdown-badge"
                                            data-scheduled-countdown
                                            data-release-at="<?= htmlspecialchars(
                                                                    $scheduledPublishAt,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">

                                            <i class="fa-regular fa-clock"></i>

                                            <span data-countdown-text>
                                                Calculating release time...
                                            </span>

                                        </span>

                                    <?php endif; ?>

                                    <?php if (
                                        $contentType ===
                                        'announcement' &&
                                        !empty($item['priority'])
                                    ): ?>

                                        <span class="workspace-meta-badge">

                                            <i class="fa-solid fa-flag"></i>

                                            <?= htmlspecialchars(
                                                (string) $item['priority'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                    <?php if (
                                        strtolower(
                                            (string) (
                                                $item['release_mode']
                                                ?? ''
                                            )
                                        ) === 'calendar' &&
                                        !empty($item['linked_event_title'])
                                    ): ?>

                                        <span
                                            class="workspace-meta-badge workspace-linked-event-badge"
                                            title="Publishes automatically when the linked event begins">

                                            <i class="fa-solid fa-link"></i>

                                            Linked to
                                            <?= htmlspecialchars(
                                                (string) $item['linked_event_title'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </div>



                            </div>

                            <h3>
                                <?= htmlspecialchars(
                                    $title,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <p class="workspace-content-description">
                                <?= htmlspecialchars(
                                    $description,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <div class="workspace-content-meta">

                                <span>
                                    <i class="fa-regular fa-user"></i>

                                    <?= htmlspecialchars(
                                        $authorName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                                <?php if ($updatedAt !== ''): ?>

                                    <span title="Last updated">
                                        <i class="fa-solid fa-rotate"></i>

                                        Updated
                                        <?= htmlspecialchars(
                                            workspaceDate(
                                                $updatedAt
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                <?php else: ?>

                                    <span title="Created date">
                                        <i class="fa-regular fa-calendar"></i>

                                        Created
                                        <?= htmlspecialchars(
                                            workspaceDate(
                                                $createdAt
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                                <?php if (
                                    $contentType ===
                                    'event' &&
                                    !empty($item['event_date'])
                                ): ?>

                                    <span>
                                        <i class="fa-solid fa-calendar-day"></i>

                                        <?= htmlspecialchars(
                                            workspaceDate(
                                                $item['event_date']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                                <?php if (
                                    $contentType ===
                                    'document' &&
                                    !empty($item['file_type'])
                                ): ?>

                                    <span>
                                        <i class="fa-regular fa-file"></i>

                                        <?= htmlspecialchars(
                                            strtoupper(
                                                (string) $item['file_type']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                                <?php if (
                                    $contentType ===
                                    'announcement' &&
                                    !empty($item['release_mode'])
                                ): ?>

                                    <span>
                                        <i class="fa-regular fa-clock"></i>

                                        <?= htmlspecialchars(
                                            workspaceStatusLabel(
                                                (string) $item['release_mode']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <?php if (
                                $reviewNotes !== ''
                            ): ?>

                                <div class="workspace-review-note">

                                    <div>
                                        <i class="fa-solid fa-message"></i>

                                        <strong>
                                            Review Notes
                                        </strong>
                                    </div>

                                    <p>
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $reviewNotes,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ) ?>
                                    </p>

                                    <?php if (
                                        $reviewerName !== ''
                                    ): ?>

                                        <small>
                                            Reviewed by
                                            <?= htmlspecialchars(
                                                $reviewerName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                            <?php if (
                                                $reviewedAt !== ''
                                            ): ?>

                                                •
                                                <?= htmlspecialchars(
                                                    workspaceDate(
                                                        $reviewedAt
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            <?php endif; ?>
                                        </small>

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- ==========================
                             CONTENT ACTIONS
                        =========================== -->

                        <div class="workspace-content-actions">

                            <button
                                type="button"
                                class="workspace-action-button secondary"
                                data-open-preview
                                data-preview="<?= workspaceJson(
                                                    $previewData
                                                ) ?>">
                                <i class="fa-regular fa-eye"></i>

                                Preview
                            </button>

                            <?php

                            $canViewSurveyResults =
                                $contentType === 'survey' &&
                                (
                                    $isAdmin ||
                                    (
                                        $isFaculty &&
                                        (int) (
                                            $item['author_id']
                                            ?? 0
                                        ) === $currentUserId
                                    )
                                );

                            ?>

                            <?php if (
                                $canViewSurveyResults
                            ): ?>

                                <a
                                    href="index.php?page=survey_results&amp;survey_id=<?= (int) $contentId ?>"
                                    class="workspace-action-button secondary">

                                    <i class="fa-solid fa-chart-column"></i>

                                    View Results

                                </a>

                            <?php endif; ?>

                            <?php

                            $canEdit =
                                in_array(
                                    $workflowStatus,
                                    [
                                        'draft',
                                        'rejected'
                                    ],
                                    true
                                ) &&
                                (int) (
                                    $item['author_id']
                                    ?? 0
                                ) === $currentUserId;

                            ?>

                            <?php if ($canEdit): ?>

                                <a
                                    href="index.php?page=postings&amp;edit_type=<?= urlencode(
                                                                                    $contentType
                                                                                ) ?>&amp;edit_id=<?= $contentId ?>"
                                    class="workspace-action-button secondary">

                                    <i class="fa-solid fa-pen"></i>

                                    Edit

                                </a>

                            <?php endif; ?>

                            <!-- FACULTY: SUBMIT -->

                            <?php if (
                                $isFaculty &&
                                in_array(
                                    $workflowStatus,
                                    [
                                        'draft',
                                        'rejected'
                                    ],
                                    true
                                )
                            ): ?>

                                <form
                                    method="POST"
                                    action="index.php?page=workspace_submit_review"
                                    data-confirm-form
                                    data-confirm-title="Submit for Review?"
                                    data-confirm-message="This content will be sent to an administrator for review."
                                    data-confirm-label="Submit Content"
                                    data-confirm-tone="primary">

                                    <?= csrfInput() ?>

                                    <input
                                        type="hidden"
                                        name="content_type"
                                        value="<?= htmlspecialchars(
                                                    $contentType,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">

                                    <input
                                        type="hidden"
                                        name="content_id"
                                        value="<?= $contentId ?>">

                                    <button
                                        type="submit"
                                        class="workspace-action-button primary">
                                        <i class="fa-solid fa-paper-plane"></i>

                                        Submit for Review
                                    </button>

                                </form>

                            <?php endif; ?>

                            <!-- ADMIN: APPROVE AND REJECT -->

                            <?php if (
                                $isAdmin &&
                                $workflowStatus ===
                                'pending_review'
                            ): ?>

                                <form
                                    method="POST"
                                    action="index.php?page=workspace_approve"
                                    data-confirm-form
                                    data-confirm-title="Approve and Publish?"
                                    data-confirm-message="This content will become visible to its intended recipients."
                                    data-confirm-label="Approve Content"
                                    data-confirm-tone="approve">

                                    <?= csrfInput() ?>

                                    <input
                                        type="hidden"
                                        name="content_type"
                                        value="<?= htmlspecialchars(
                                                    $contentType,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">

                                    <input
                                        type="hidden"
                                        name="content_id"
                                        value="<?= $contentId ?>">

                                    <button
                                        type="submit"
                                        class="workspace-action-button approve">
                                        <i class="fa-solid fa-check"></i>

                                        Approve
                                    </button>

                                </form>

                                <button
                                    type="button"
                                    class="workspace-action-button reject"
                                    data-open-reject
                                    data-content-type="<?= htmlspecialchars(
                                                            $contentType,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                    data-content-id="<?= $contentId ?>"
                                    data-content-title="<?= htmlspecialchars(
                                                            $title,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">
                                    <i class="fa-solid fa-xmark"></i>

                                    Reject
                                </button>

                            <?php endif; ?>

                            <!-- ADMIN: ARCHIVE -->

                            <?php if (
                                $isAdmin &&
                                in_array(
                                    $workflowStatus,
                                    [
                                        'published',
                                        'scheduled'
                                    ],
                                    true
                                )
                            ): ?>

                                <form
                                    method="POST"
                                    action="index.php?page=workspace_archive"
                                    data-confirm-form
                                    data-confirm-title="Archive Content?"
                                    data-confirm-message="This content will be removed from active publication. It can still be restored later."
                                    data-confirm-label="Archive Content"
                                    data-confirm-tone="archive">

                                    <?= csrfInput() ?>

                                    <input
                                        type="hidden"
                                        name="content_type"
                                        value="<?= htmlspecialchars(
                                                    $contentType,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">

                                    <input
                                        type="hidden"
                                        name="content_id"
                                        value="<?= $contentId ?>">

                                    <button
                                        type="submit"
                                        class="workspace-action-button archive">
                                        <i class="fa-solid fa-box-archive"></i>

                                        Archive
                                    </button>

                                </form>

                            <?php endif; ?>

                            <!-- RESTORE TO DRAFT -->

                            <?php

                            $canRestore =
                                in_array(
                                    $workflowStatus,
                                    [
                                        'rejected',
                                        'archived'
                                    ],
                                    true
                                ) &&
                                (
                                    $isFaculty ||
                                    (
                                        $isAdmin &&
                                        (int) (
                                            $item['author_id']
                                            ?? 0
                                        ) ===
                                        $currentUserId
                                    )
                                );

                            ?>

                            <?php if ($canRestore): ?>

                                <form
                                    method="POST"
                                    action="index.php?page=workspace_restore_draft"
                                    data-confirm-form
                                    data-confirm-title="Restore to Draft?"
                                    data-confirm-message="This content will return to Drafts where it can be revised."
                                    data-confirm-label="Restore Draft"
                                    data-confirm-tone="primary">

                                    <?= csrfInput() ?>

                                    <input
                                        type="hidden"
                                        name="content_type"
                                        value="<?= htmlspecialchars(
                                                    $contentType,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">

                                    <input
                                        type="hidden"
                                        name="content_id"
                                        value="<?= $contentId ?>">

                                    <button
                                        type="submit"
                                        class="workspace-action-button secondary">
                                        <i class="fa-solid fa-rotate-left"></i>

                                        Restore to Draft
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <!-- ==================================
                 FILTERED EMPTY STATE
            =================================== -->

            <div
                id="workspaceFilteredEmpty"
                class="workspace-filtered-empty"
                hidden>
                <i class="fa-solid fa-filter-circle-xmark"></i>

                <strong>
                    No content matches the selected filters.
                </strong>

                <button
                    type="button"
                    id="workspaceResetFilters"
                    class="app-button secondary"
                    hidden>
                    Reset Filters
                </button>
            </div>

        <?php endif; ?>

    </section>

</section>

<!-- ==========================================
     SHARED MODAL OVERLAY
========================================== -->

<div
    id="workspaceModalOverlay"
    class="workspace-modal-overlay"
    hidden></div>

<!-- ==========================================
     CONFIRMATION MODAL
========================================== -->

<section
    id="workspaceConfirmModal"
    class="workspace-modal workspace-confirm-modal"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="workspaceConfirmTitle"
    hidden>

    <header class="workspace-modal-header">

        <div>

            <h2 id="workspaceConfirmTitle">
                Confirm Action
            </h2>
        </div>

        <button
            type="button"
            class="workspace-modal-close"
            data-close-modal
            aria-label="Close confirmation dialog">
            <i class="fa-solid fa-xmark"></i>
        </button>

    </header>

    <div class="workspace-modal-body workspace-confirm-body">

        <span
            id="workspaceConfirmIcon"
            class="workspace-confirm-icon">
            <i class="fa-solid fa-circle-question"></i>
        </span>

        <p id="workspaceConfirmMessage">
            Continue with this action?
        </p>

    </div>

    <footer class="workspace-modal-footer">

        <button
            type="button"
            class="app-button secondary"
            data-close-modal>
            Cancel
        </button>

        <button
            type="button"
            id="workspaceConfirmAction"
            class="app-button primary">
            Continue
        </button>

    </footer>

</section>

<!-- ==========================================
     CONTENT PREVIEW MODAL
========================================== -->

<section
    id="workspacePreviewModal"
    class="workspace-modal workspace-preview-modal"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="workspacePreviewTitle"
    hidden>

    <header class="workspace-modal-header">

        <div>
            <span
                id="workspacePreviewType"
                class="page-eyebrow">
                Content Preview
            </span>

            <h2 id="workspacePreviewTitle">
                Preview
            </h2>
        </div>



        <button
            type="button"
            class="workspace-modal-close"
            data-close-modal
            aria-label="Close content preview">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>

    </header>

    <div class="workspace-modal-body workspace-preview-body">

        <div
            id="workspacePreviewBadges"
            class="workspace-preview-badges"></div>

        <p
            id="workspacePreviewDescription"
            class="workspace-preview-description"></p>

        <dl
            id="workspacePreviewDetails"
            class="workspace-preview-details"></dl>

        <div
            id="workspacePreviewReview"
            class="workspace-review-note"
            hidden></div>

    </div>

    <footer class="workspace-modal-footer">
        <div
            id="workspaceFilePreviewWrap"
            class="workspace-file-preview"
            hidden>
            <iframe
                id="workspaceFilePreviewFrame"
                src=""
                title="Document preview"
                loading="lazy"></iframe>
        </div>

        <div
            id="workspaceFileNotice"
            class="workspace-file-notice"
            hidden>
            <span>
                <i class="fa-solid fa-circle-info"></i>
            </span>

            <div>
                <strong>
                    File preview unavailable
                </strong>

                <p id="workspaceFileNoticeText">
                    This file type cannot be previewed in the browser.
                    Use the Access File button to download and open it
                    using a compatible application.
                </p>
            </div>
        </div>

        <a
            id="workspacePreviewFile"
            href="#"
            class="app-button primary"
            target="_blank"
            rel="noopener noreferrer"
            hidden>
            <i class="fa-solid fa-arrow-up-right-from-square"></i>

            Access File
        </a>

        <button
            type="button"
            class="app-button secondary"
            data-close-modal>
            Close
        </button>

    </footer>

</section>

<!-- ==========================================
     REJECTION MODAL
========================================== -->

<?php if ($isAdmin): ?>

    <section
        id="workspaceRejectModal"
        class="workspace-modal"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="workspaceRejectTitle"
        hidden>

        <header class="workspace-modal-header">

            <div>

                <h2 id="workspaceRejectTitle">
                    Reject Content
                </h2>
            </div>

            <button
                type="button"
                class="workspace-modal-close"
                data-close-modal
                aria-label="Close rejection dialog">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </header>

        <form
            method="POST"
            action="index.php?page=workspace_reject"
            id="workspaceRejectForm">

            <?= csrfInput() ?>

            <input
                type="hidden"
                name="content_type"
                id="rejectContentType">

            <input
                type="hidden"
                name="content_id"
                id="rejectContentId">

            <div class="workspace-modal-body">

                <p>
                    You are rejecting:
                </p>

                <strong id="rejectContentName">
                    Selected content
                </strong>

                <label for="workspaceReviewNotes">
                    Rejection reason

                    <span>
                        *
                    </span>
                </label>

                <textarea
                    id="workspaceReviewNotes"
                    name="review_notes"
                    rows="6"
                    maxlength="1000"
                    required
                    placeholder="Explain what the author needs to revise before resubmitting."></textarea>

                <div class="workspace-review-counter">

                    <span>
                        The author will see these notes
                        in the Rejected tab.
                    </span>

                    <strong id="workspaceReviewCount">
                        0 / 1000
                    </strong>

                </div>

            </div>

            <footer class="workspace-modal-footer">

                <button
                    type="button"
                    class="app-button secondary"
                    data-close-modal>
                    Cancel
                </button>

                <button
                    type="submit"
                    class="app-button danger">
                    <i class="fa-solid fa-circle-xmark"></i>

                    Reject Content
                </button>

            </footer>

        </form>

    </section>

<?php endif; ?>