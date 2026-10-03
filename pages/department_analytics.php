<?php

$departmentName =
    trim(
        (string) (
            $viewData['department_name']
            ?? 'Faculty Department'
        )
    );

$selectedRange =
    (string) (
        $viewData['selected_range']
        ?? '30'
    );

$rangeLabel =
    (string) (
        $viewData['range_label']
        ?? 'Last 30 days'
    );

$rangeOptions =
    $viewData['range_options']
    ?? [];

$summary =
    $viewData['summary']
    ?? [];

$engagement =
    $viewData['engagement']
    ?? [];

$rankings =
    $viewData['content_view_rankings']
    ?? [];

$mostViewedContent =
    $rankings['most_viewed']
    ?? [];

$leastViewedContent =
    $rankings['least_viewed']
    ?? [];

$contentItems =
    $viewData['content_items']
    ?? [];

$recommendations =
    $viewData['recommendations']
    ?? [];

$currentUserId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );

$totalPosts =
    (int) (
        $summary['total_posts']
        ?? 0
    );

$publishedPosts =
    (int) (
        $summary['published_posts']
        ?? 0
    );

$publicationRate =
    $totalPosts > 0
    ? (
        $publishedPosts
        / $totalPosts
    ) * 100
    : 0;

$contentIcons = [
    'announcement' =>
    'fa-solid fa-bullhorn',

    'event' =>
    'fa-regular fa-calendar',

    'document' =>
    'fa-regular fa-file-lines',

    'survey' =>
    'fa-solid fa-square-poll-horizontal'
];

?>

<section class="app-page dashboard-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->

    <header class="page-header dashboard-page-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                Faculty Analytics
            </span>

            <h1>
                <?= htmlspecialchars(
                    $departmentName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <p>
                Monitor your department’s posting activity,
                publication progress, reach, and audience
                engagement without accessing another department.
            </p>

        </div>

        <div class="page-actions">

            <form
                method="get"
                action="index.php"
                class="dashboard-range-filter">

                <input
                    type="hidden"
                    name="page"
                    value="department_analytics">

                <label for="departmentAnalyticsRange">

                    <span>
                        Analytics period
                    </span>

                    <select
                        id="departmentAnalyticsRange"
                        name="range"
                        onchange="
                            sessionStorage.setItem(
                                'dashboardScrollPosition',
                                String(window.scrollY)
                            );
                            this.form.submit();
                        ">

                        <?php foreach (
                            $rangeOptions
                            as $rangeValue =>
                            $rangeOptionLabel
                        ): ?>

                            <option
                                value="<?= htmlspecialchars(
                                            (string) $rangeValue,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                <?= $selectedRange ===
                                    (string) $rangeValue
                                    ? 'selected'
                                    : ''
                                ?>>

                                <?= htmlspecialchars(
                                    (string) $rangeOptionLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>

            </form>

            <a
                href="index.php?page=department_analytics_export&amp;range=<?= htmlspecialchars(
                                                                                $selectedRange,
                                                                                ENT_QUOTES,
                                                                                'UTF-8'
                                                                            ) ?>"
                class="app-button secondary">

                <i class="fa-solid fa-file-excel"></i>

                Export Department Report
            </a>

            <a
                href="index.php?page=postings"
                class="app-button primary">

                <i class="fa-solid fa-pen-to-square"></i>

                Create Content
            </a>

            <a
                href="index.php?page=content_workspace"
                class="app-button secondary">

                <i class="fa-solid fa-layer-group"></i>

                Content Workspace
            </a>

        </div>

    </header>

    <!-- ======================================
         DEPARTMENT SUMMARY
    ======================================= -->

    <section class="dashboard-stat-grid">

        <article class="dashboard-stat-card">

            <span class="dashboard-stat-icon announcements">

                <i class="fa-solid fa-layer-group"></i>

            </span>

            <div>

                <span>
                    Total Content
                </span>

                <strong>
                    <?= number_format(
                        $totalPosts
                    ) ?>
                </strong>

                <small>
                    Created during
                    <?= htmlspecialchars(
                        strtolower(
                            $rangeLabel
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </small>

            </div>

        </article>

        <article class="dashboard-stat-card">

            <span class="dashboard-stat-icon documents">

                <i class="fa-solid fa-circle-check"></i>

            </span>

            <div>

                <span>
                    Published
                </span>

                <strong>
                    <?= number_format(
                        $publishedPosts
                    ) ?>
                </strong>

                <small>
                    <?= number_format(
                        $publicationRate,
                        2
                    ) ?>%
                    publication rate
                </small>

            </div>

        </article>

        <article class="dashboard-stat-card">

            <span class="dashboard-stat-icon announcements">

                <i class="fa-solid fa-bullhorn"></i>

            </span>

            <div>

                <span>
                    Announcements
                </span>

                <strong>
                    <?= number_format(
                        (int) (
                            $summary['announcement_count']
                            ?? 0
                        )
                    ) ?>
                </strong>

                <small>
                    Department advisories
                </small>

            </div>

        </article>

        <article class="dashboard-stat-card">

            <span class="dashboard-stat-icon events">

                <i class="fa-solid fa-calendar-days"></i>

            </span>

            <div>

                <span>
                    Events
                </span>

                <strong>
                    <?= number_format(
                        (int) (
                            $summary['event_count']
                            ?? 0
                        )
                    ) ?>
                </strong>

                <small>
                    Department activities
                </small>

            </div>

        </article>

        <article class="dashboard-stat-card">

            <span class="dashboard-stat-icon documents">

                <i class="fa-solid fa-file-lines"></i>

            </span>

            <div>

                <span>
                    Documents
                </span>

                <strong>
                    <?= number_format(
                        (int) (
                            $summary['document_count']
                            ?? 0
                        )
                    ) ?>
                </strong>

                <small>
                    Shared official files
                </small>

            </div>

        </article>

        <article class="dashboard-stat-card">

            <span class="dashboard-stat-icon surveys">

                <i class="fa-solid fa-square-poll-horizontal"></i>

            </span>

            <div>

                <span>
                    Surveys
                </span>

                <strong>
                    <?= number_format(
                        (int) (
                            $summary['survey_count']
                            ?? 0
                        )
                    ) ?>
                </strong>

                <small>
                    Feedback instruments
                </small>

            </div>

        </article>

    </section>

    <!-- ======================================
         RECOMMENDATIONS
    ======================================= -->

    <section class="page-card dashboard-action-center">

        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    Department Guidance
                </span>

                <h2>
                    Recommended actions
                </h2>

                <p>
                    Rule-based findings calculated only from
                    your assigned department.
                </p>

            </div>

        </div>

        <div class="dashboard-insight-grid">

            <?php foreach (
                $recommendations
                as $recommendation
            ): ?>

                <?php
                $severity =
                    strtolower(
                        (string) (
                            $recommendation['severity']
                            ?? 'information'
                        )
                    );
                ?>

                <article
                    class="dashboard-insight-item severity-<?= htmlspecialchars(
                                                                $severity,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>">

                    <span class="dashboard-insight-icon">

                        <?php if (
                            $severity === 'warning'
                        ): ?>

                            <i class="fa-solid fa-triangle-exclamation"></i>

                        <?php elseif (
                            $severity === 'success'
                        ): ?>

                            <i class="fa-solid fa-circle-check"></i>

                        <?php else: ?>

                            <i class="fa-solid fa-circle-info"></i>

                        <?php endif; ?>

                    </span>

                    <div>

                        <strong>
                            <?= htmlspecialchars(
                                $recommendation['title']
                                    ?? 'Department Finding',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <p>
                            <?= htmlspecialchars(
                                $recommendation['message']
                                    ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- ======================================
         ENGAGEMENT
    ======================================= -->

    <section class="page-card dashboard-engagement-card">

        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    Department Engagement
                </span>

                <h2>
                    Audience interaction
                </h2>

                <p>
                    Interactions recorded on published
                    Faculty-authored department content during
                    <?= htmlspecialchars(
                        strtolower(
                            $rangeLabel
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>.
                </p>

            </div>

        </div>

        <div class="dashboard-engagement-grid">

            <?php foreach (
                [
                    [
                        'label' => 'Views',
                        'key' => 'views',
                        'icon' => 'fa-regular fa-eye'
                    ],
                    [
                        'label' => 'Reactions',
                        'key' => 'reactions',
                        'icon' => 'fa-regular fa-heart'
                    ],
                    [
                        'label' => 'Comments',
                        'key' => 'comments',
                        'icon' => 'fa-regular fa-comment'
                    ],
                    [
                        'label' => 'Acknowledgments',
                        'key' => 'acknowledgments',
                        'icon' => 'fa-solid fa-check-double'
                    ]
                ]
                as $metric
            ): ?>

                <div>

                    <span>

                        <i class="<?= htmlspecialchars(
                                        $metric['icon'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"></i>

                    </span>

                    <strong>
                        <?= number_format(
                            (int) (
                                $engagement[$metric['key']]
                                ?? 0
                            )
                        ) ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars(
                            $metric['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </small>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- ======================================
         REACH RANKINGS
    ======================================= -->

    <section class="content-grid two-columns">

        <?php foreach (
            [
                [
                    'title' =>
                    'Most viewed content',

                    'items' =>
                    $mostViewedContent,

                    'icon' =>
                    'fa-regular fa-eye'
                ],
                [
                    'title' =>
                    'Least viewed content',

                    'items' =>
                    $leastViewedContent,

                    'icon' =>
                    'fa-solid fa-arrow-trend-down'
                ]
            ]
            as $rankingSection
        ): ?>

            <article class="page-card dashboard-list-card">

                <div class="card-header">

                    <div class="card-header-copy">

                        <span class="page-eyebrow">
                            Department Reach
                        </span>

                        <h2>
                            <?= htmlspecialchars(
                                $rankingSection['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                    </div>

                </div>

                <div class="dashboard-list">

                    <?php if (
                        empty($rankingSection['items'])
                    ): ?>

                        <div class="dashboard-empty">

                            <i class="fa-regular fa-eye-slash"></i>

                            <p>
                                No published department content
                                is available for this period.
                            </p>

                        </div>

                    <?php else: ?>

                        <?php foreach (
                            $rankingSection['items']
                            as $content
                        ): ?>

                            <?php

                            $contentType =
                                strtolower(
                                    (string) (
                                        $content['content_type']
                                        ?? ''
                                    )
                                );

                            $contentId =
                                (int) (
                                    $content['content_id']
                                    ?? 0
                                );

                            $contentUrl =
                                'index.php?page=department_content_preview'
                                . '&content_type='
                                . urlencode(
                                    $contentType
                                )
                                . '&content_id='
                                . $contentId
                                . '&range='
                                . urlencode(
                                    $selectedRange
                                );

                            ?>

                            <a
                                href="<?= htmlspecialchars(
                                            $contentUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                class="dashboard-list-item">

                                <span>

                                    <i class="<?= htmlspecialchars(
                                                    $rankingSection['icon'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"></i>

                                </span>

                                <div>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $content['title']
                                                ?? 'Untitled Content',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                    <small>
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $contentType
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        ·

                                        <?= number_format(
                                            (int) (
                                                $content['view_count']
                                                ?? 0
                                            )
                                        ) ?>

                                        views
                                    </small>

                                </div>

                                <i class="fa-solid fa-chevron-right"></i>

                            </a>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>

    </section>

    <!-- ======================================
         DEPARTMENT CONTENT
    ======================================= -->

    <section class="page-card dashboard-list-card department-analytics-content-list">

        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    Department Content
                </span>

                <h2>
                    Recent posting records
                </h2>

                <p>
                    Faculty-authored content belonging to
                    <?= htmlspecialchars(
                        $departmentName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>.
                </p>

            </div>

            <a
                href="index.php?page=content_workspace"
                class="dashboard-card-link">

                Open Workspace

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

        <div class="dashboard-list">

            <?php if (empty($contentItems)): ?>

                <div class="dashboard-empty">

                    <i class="fa-regular fa-folder-open"></i>

                    <p>
                        No department content was created
                        during this period.
                    </p>

                </div>

            <?php else: ?>

                <?php foreach (
                    $contentItems
                    as $content
                ): ?>

                    <?php

                    $contentType =
                        strtolower(
                            (string) (
                                $content['content_type']
                                ?? ''
                            )
                        );

                    $contentId =
                        (int) (
                            $content['content_id']
                            ?? 0
                        );

                    $workflowStatus =
                        strtolower(
                            (string) (
                                $content['workflow_status']
                                ?? ''
                            )
                        );

                    $ownerId =
                        (int) (
                            $content['user_id']
                            ?? 0
                        );

                    $contentUrl =
                        $contentId > 0
                        ? (
                            'index.php?page=department_content_preview'
                            . '&content_type='
                            . urlencode(
                                $contentType
                            )
                            . '&content_id='
                            . $contentId
                            . '&range='
                            . urlencode(
                                $selectedRange
                            )
                        )
                        : null;

                    $hasAction =
                        $contentUrl !== null;

                    ?>

                    <?php if ($hasAction): ?>

                        <a
                            href="<?= htmlspecialchars(
                                        $contentUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            class="dashboard-list-item">

                        <?php else: ?>

                            <div class="dashboard-list-item">

                            <?php endif; ?>

                            <span>

                                <i class="<?= htmlspecialchars(
                                                $contentIcons[$contentType]
                                                    ?? 'fa-regular fa-file',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $content['title']
                                            ?? 'Untitled Content',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $contentType
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    ·

                                    <?= htmlspecialchars(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $workflowStatus
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <?php if (
                                        !empty($content['author_name'])
                                    ): ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $content['author_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    <?php endif; ?>
                                </small>

                            </div>

                            <div>

                                <small>
                                    <?= htmlspecialchars(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $content['content_date']
                                                    ?? 'now'
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </div>

                            <?php if ($hasAction): ?>

                                <i class="fa-solid fa-chevron-right"></i>

                            <?php endif; ?>

                            <?php if ($hasAction): ?>

                        </a>

                    <?php else: ?>

        </div>

    <?php endif; ?>

<?php endforeach; ?>

<?php endif; ?>

</div>

    </section>

</section>