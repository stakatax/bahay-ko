<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$statistics =
    $viewData['statistics']
    ?? [];

$announcements =
    $viewData['announcements']
    ?? [];

$events =
    $viewData['events']
    ?? [];

$recentActivity =
    $viewData['recent_activity']
    ?? [];

$notifications =
    $viewData['notifications']
    ?? [];

$engagement =
    $viewData['engagement']
    ?? [];

$workflowStatistics =
    $viewData['workflow_statistics']
    ?? [];

$actionableInsights =
    $viewData['actionable_insights']
    ?? [];

$studentSurveyAnalytics =
    is_array(
        $viewData['student_survey_analytics']
            ?? null
    )
    ? $viewData['student_survey_analytics']
    : [];

$studentSurveyCompletion =
    is_array(
        $studentSurveyAnalytics['completion']
            ?? null
    )
    ? $studentSurveyAnalytics['completion']
    : [];

$studentSurveySections =
    is_array(
        $studentSurveyAnalytics['sections']
            ?? null
    )
    ? $studentSurveyAnalytics['sections']
    : [];

$studentSurveyVersion =
    (int) (
        $studentSurveyAnalytics['survey_version']
        ?? 0
    );

$studentSurveyMinimumGroup =
    max(
        1,
        (int) (
            $studentSurveyAnalytics['minimum_group_size']
            ?? 5
        )
    );

$totalStudentSurveyAccounts =
    (int) (
        $studentSurveyCompletion['total_students']
        ?? 0
    );

$studentSurveyNotStarted =
    (int) (
        $studentSurveyCompletion['not_started']
        ?? 0
    );

$studentSurveyInProgress =
    (int) (
        $studentSurveyCompletion['in_progress']
        ?? 0
    );

$studentSurveyCompleted =
    (int) (
        $studentSurveyCompletion['completed']
        ?? 0
    );

$studentSurveyCompletionRate =
    (float) (
        $studentSurveyCompletion['completion_rate']
        ?? 0
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
    ?? [
        '7' =>
        'Last 7 days',

        '30' =>
        'Last 30 days',

        '90' =>
        'Last 90 days',

        'all' =>
        'All time'
    ];


$contentViewRankings =
    $viewData['content_view_rankings']
    ?? [];

$mostViewedContent =
    $contentViewRankings['most_viewed']
    ?? [];

$leastViewedContent =
    $contentViewRankings['least_viewed']
    ?? [];

$departmentPostingStatistics =
    $viewData['department_posting_statistics']
    ?? [];

$departmentBreakdown =
    $viewData['department_breakdown']
    ?? [];

$selectedDepartmentId =
    (int) (
        $viewData['selected_department_id']
        ?? 0
    );

$selectedDepartmentScope =
    strtolower(
        trim(
            (string) (
                $viewData['selected_department_scope']
                ?? ''
            )
        )
    );

$departmentBreakdownItems =
    $departmentBreakdown['items']
    ?? [];

$dashboardExportUrl =
    'index.php?page=dashboard_export'
    . '&range='
    . urlencode(
        $selectedRange
    );

if (
    $selectedDepartmentScope ===
    'schoolwide'
) {
    $dashboardExportUrl .=
        '&scope=schoolwide';
} elseif (
    $selectedDepartmentId > 0
) {
    $dashboardExportUrl .=
        '&department_id='
        . $selectedDepartmentId;
}

$totalUsers =
    (int) (
        $statistics['users']
        ?? 0
    );

$totalAnnouncements =
    (int) (
        $statistics['announcements']
        ?? 0
    );

$totalEvents =
    (int) (
        $statistics['events']
        ?? 0
    );

$totalDocuments =
    (int) (
        $statistics['documents']
        ?? 0
    );

$totalSurveys =
    (int) (
        $statistics['surveys']
        ?? 0
    );

$totalViews =
    (int) (
        $engagement['views']
        ?? 0
    );

$totalVotes =
    (int) (
        $engagement['reactions']
        ?? 0
    );

$totalComments =
    (int) (
        $engagement['comments']
        ?? 0
    );

$totalAcknowledgments =
    (int) (
        $engagement['acknowledgments']
        ?? 0
    );

$engagementMetrics = [
    [
        'label' => 'Views',
        'value' => $totalViews,
        'icon' => 'fa-regular fa-eye',
        'class' => 'views'
    ],
    [
        'label' => 'Votes',
        'value' => $totalVotes,
        'icon' => 'fa-regular fa-heart',
        'class' => 'reactions'
    ],
    [
        'label' => 'Comments',
        'value' => $totalComments,
        'icon' => 'fa-regular fa-comment',
        'class' => 'comments'
    ],
    [
        'label' => 'Acknowledgments',
        'value' => $totalAcknowledgments,
        'icon' => 'fa-solid fa-check-double',
        'class' => 'acknowledgments'
    ]
];

$maximumEngagement =
    max(
        1,
        $totalViews,
        $totalVotes,
        $totalComments,
        $totalAcknowledgments
    );

foreach (
    $engagementMetrics
    as &$engagementMetric
) {
    $engagementMetric['percentage'] =
        (int) round(
            (
                $engagementMetric['value']
                / $maximumEngagement
            ) * 100
        );
}

unset($engagementMetric);

$pendingReview =
    (int) (
        $workflowStatistics['pending_review']
        ?? 0
    );

$approvedContent =
    (int) (
        $workflowStatistics['approved']
        ?? 0
    );

$scheduledContent =
    (int) (
        $workflowStatistics['scheduled']
        ?? 0
    );

$publishedContent =
    (int) (
        $workflowStatistics['published']
        ?? $totalAnnouncements
    );


$currentDashboardUserId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );

?>



<section class="app-page dashboard-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->

    <header class="page-header dashboard-page-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                Administration
            </span>

            <h1>
                Digital Hub Dashboard
            </h1>

            <p>
                Monitor school content, scheduled events,
                user activity, engagement, and the development
                of the announcement review and release workflow.
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
                    value="dashboard">


                <?php if (
                    $selectedDepartmentScope ===
                    'schoolwide'
                ): ?>

                    <input
                        type="hidden"
                        name="scope"
                        value="schoolwide">

                <?php elseif (
                    $selectedDepartmentId > 0
                ): ?>

                    <input
                        type="hidden"
                        name="department_id"
                        value="<?= $selectedDepartmentId ?>">

                <?php endif; ?>

                <label for="dashboardRange">

                    <span>
                        Analytics period
                    </span>

                    <select
                        id="dashboardRange"
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

                <noscript>

                    <button
                        type="submit"
                        class="app-button secondary">
                        Apply
                    </button>

                </noscript>

            </form>

            <a
                href="<?= htmlspecialchars(
                            $dashboardExportUrl,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                class="app-button secondary"
                title="Exports the analytics currently shown on the Dashboard. Inventory and workflow remain current-state totals.">

                <i class="fa-solid fa-file-csv"></i>

                Export <?= htmlspecialchars(
                            $rangeLabel,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
            </a>

            <a
                href="index.php?page=postings"
                class="app-button primary">
                <i class="fa-solid fa-pen-to-square"></i>

                Create Content
            </a>

            <a
                href="index.php?page=news"
                class="app-button secondary">
                <i class="fa-solid fa-bullhorn"></i>

                Information Hub
            </a>

        </div>

    </header>

    <!-- ======================================
         SYSTEM STATISTICS
    ======================================= -->

    <section class="dashboard-stat-grid">

        <article class="dashboard-stat-card">

            <span class="dashboard-stat-icon users">

                <i class="fa-solid fa-users"></i>

            </span>

            <div>

                <span>
                    Registered Users
                </span>

                <strong>
                    <?= number_format(
                        $totalUsers
                    ) ?>
                </strong>

                <small>
                    Active and pending accounts
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
                        $totalAnnouncements
                    ) ?>
                </strong>

                <small>
                    Currently recorded content
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
                        $totalEvents
                    ) ?>
                </strong>

                <small>
                    Scheduled school activities
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
                        $totalDocuments
                    ) ?>
                </strong>

                <small>
                    Uploaded official files
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
                        $totalSurveys
                    ) ?>
                </strong>

                <small>
                    Available feedback instruments
                </small>

            </div>

        </article>

    </section>

    <!-- ======================================
         QUICK ACTIONS
    ======================================= -->

    <section class="page-card dashboard-quick-section">

        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    Quick Actions
                </span>

                <h2>
                    Manage school information
                </h2>

                <p>
                    Open the primary content-management tools.
                </p>

            </div>

        </div>

        <div class="dashboard-quick-grid">

            <a
                href="index.php?page=postings&type=announcement"
                class="dashboard-quick-card">

                <span>

                    <i class="fa-solid fa-bullhorn"></i>

                </span>

                <div>

                    <strong>
                        Create Announcement
                    </strong>

                    <small>
                        Prepare school or department updates.
                    </small>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>

            <a
                href="index.php?page=postings&type=event"
                class="dashboard-quick-card">

                <span>

                    <i class="fa-solid fa-calendar-plus"></i>

                </span>

                <div>

                    <strong>
                        Create Event
                    </strong>

                    <small>
                        Add a calendar-based activity.
                    </small>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>

            <a
                href="index.php?page=postings&type=document"
                class="dashboard-quick-card">

                <span>

                    <i class="fa-solid fa-file-arrow-up"></i>

                </span>

                <div>

                    <strong>
                        Upload Document
                    </strong>

                    <small>
                        Share an official school file.
                    </small>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>

            <a
                href="index.php?page=postings&type=survey"
                class="dashboard-quick-card">

                <span>

                    <i class="fa-solid fa-square-poll-horizontal"></i>

                </span>

                <div>

                    <strong>
                        Create Survey
                    </strong>

                    <small>
                        Prepare a structured feedback form.
                    </small>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </section>

    <!-- ======================================
     RULE-BASED ACTION CENTER
======================================= -->

    <section class="page-card dashboard-insight-section">

        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    System Intelligence
                </span>

                <h2>
                    Admin Action Center
                </h2>

                <p>
                    Explainable rules identify content that
                    may require administrative attention.
                </p>

            </div>

            <span class="dashboard-operational-badge">
                Live Analysis
            </span>

        </div>

        <div class="dashboard-insight-list">

            <?php foreach (
                $actionableInsights
                as $insight
            ): ?>

                <article class="dashboard-insight-item <?= htmlspecialchars(
                                                            $insight['severity']
                                                                ?? 'information',
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">

                    <span class="dashboard-insight-icon">

                        <i class="<?= htmlspecialchars(
                                        $insight['icon']
                                            ?? 'fa-solid fa-circle-info',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"></i>

                    </span>

                    <div>

                        <strong>
                            <?= htmlspecialchars(
                                $insight['title']
                                    ?? 'System insight',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <p>
                            <?= htmlspecialchars(
                                $insight['message']
                                    ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    </div>

                    <a
                        href="<?= htmlspecialchars(
                                    $insight['action_url']
                                        ?? 'index.php?page=dashboard',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                        <?= htmlspecialchars(
                            $insight['action_label']
                                ?? 'Review',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- ======================================
         WORKFLOW STATUS AND ENGAGEMENT
    ======================================= -->

    <section class="content-grid two-columns">

        <article class="page-card dashboard-status-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Content Status
                    </span>

                    <h2>
                        Workflow overview
                    </h2>

                    <p>
                        Current content totals across review, approval, scheduling, and publication.
                    </p>

                </div>

            </div>

            <div class="dashboard-status-list">

                <div>

                    <span class="dashboard-status-dot pending"></span>

                    <div>

                        <strong>
                            Pending Review
                        </strong>

                        <small>
                            Submitted content waiting for review
                        </small>

                    </div>

                    <b>
                        <?= number_format(
                            $pendingReview
                        ) ?>
                    </b>

                </div>

                <div>

                    <span class="dashboard-status-dot approved"></span>

                    <div>

                        <strong>
                            Approved
                        </strong>

                        <small>
                            Content cleared for release
                        </small>

                    </div>

                    <b>
                        <?= number_format(
                            $approvedContent
                        ) ?>
                    </b>

                </div>

                <div>

                    <span class="dashboard-status-dot scheduled"></span>

                    <div>

                        <strong>
                            Scheduled
                        </strong>

                        <small>
                            Calendar-based future releases
                        </small>

                    </div>

                    <b>
                        <?= number_format(
                            $scheduledContent
                        ) ?>
                    </b>

                </div>

                <div>

                    <span class="dashboard-status-dot published"></span>

                    <div>

                        <strong>
                            Published
                        </strong>

                        <small>
                            Published content across all modules
                        </small>

                    </div>

                    <b>
                        <?= number_format(
                            $publishedContent
                        ) ?>
                    </b>

                </div>

            </div>

        </article>

        <article class="page-card dashboard-engagement-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Engagement
                    </span>

                    <h2>
                        Audience interaction
                    </h2>

                    <p>
                        Live interaction and acknowledgment totals across school content.
                    </p>

                </div>

            </div>

            <div class="dashboard-engagement-grid">

                <div>

                    <span>

                        <i class="fa-regular fa-eye"></i>

                    </span>

                    <strong>
                        <?= number_format(
                            $totalViews
                        ) ?>
                    </strong>

                    <small>
                        Views
                    </small>

                </div>

                <div>

                    <span>

                        <i class="fa-regular fa-heart"></i>

                    </span>

                    <strong>
                        <?= number_format(
                            $totalVotes
                        ) ?>
                    </strong>

                    <small>
                        Votes
                    </small>

                </div>

                <div>

                    <span>

                        <i class="fa-regular fa-comment"></i>

                    </span>

                    <strong>
                        <?= number_format(
                            $totalComments
                        ) ?>
                    </strong>

                    <small>
                        Comments
                    </small>

                </div>

                <div>

                    <span>

                        <i class="fa-solid fa-check-double"></i>

                    </span>

                    <strong>
                        <?= number_format(
                            $totalAcknowledgments
                        ) ?>
                    </strong>

                    <small>
                        Acknowledgments
                    </small>

                </div>

            </div>

            <div
                id="analyticsChart"
                class="dashboard-engagement-chart">

                <div class="dashboard-chart-heading">

                    <div>

                        <strong>
                            Engagement distribution
                        </strong>

                        <p>
                            Relative interaction totals recorded
                            across published content.
                        </p>

                    </div>

                </div>

                <div class="dashboard-engagement-bars">

                    <?php foreach (
                        $engagementMetrics
                        as $engagementMetric
                    ): ?>

                        <div
                            class="dashboard-engagement-bar <?= htmlspecialchars(
                                                                $engagementMetric['class'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>">

                            <div class="dashboard-engagement-bar-label">

                                <span>

                                    <i class="<?= htmlspecialchars(
                                                    $engagementMetric['icon'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"></i>

                                    <?= htmlspecialchars(
                                        $engagementMetric['label'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                                <strong>
                                    <?= number_format(
                                        (int) (
                                            $engagementMetric['value']
                                            ?? 0
                                        )
                                    ) ?>
                                </strong>

                            </div>

                            <div class="dashboard-engagement-track">

                                <span
                                    style="--engagement-width: <?= (int) (
                                                                    $engagementMetric['percentage']
                                                                    ?? 0
                                                                ) ?>%;">
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </article>

    </section>

    <!-- ======================================
         STUDENT SURVEY ANALYTICS
    ======================================= -->

    <section
        id="studentSurveyAnalytics"
        class="page-card dashboard-student-survey-card">

        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    Student Access Intelligence
                </span>

                <h2>
                    Student profile survey
                </h2>

                <p>
                    Aggregate access and learning conditions
                    from completed Student profiles. Small
                    response groups remain protected.
                </p>

            </div>

            <div class="dashboard-survey-privacy-badge">

                <i class="fa-solid fa-shield-halved"></i>

                <span>

                    <strong>
                        Privacy protected
                    </strong>

                    <small>
                        Minimum group:
                        <?= number_format(
                            $studentSurveyMinimumGroup
                        ) ?>
                    </small>

                </span>

            </div>

        </div>

        <?php if ($studentSurveyVersion <= 0): ?>

            <div class="dashboard-empty">

                <i class="fa-solid fa-clipboard-question"></i>

                <p>
                    No active Student profile survey is configured.
                </p>

            </div>

        <?php else: ?>

            <div class="dashboard-survey-overview">

                <article class="dashboard-survey-completion">

                    <div
                        class="dashboard-survey-completion-ring"
                        style="--survey-completion: <?= max(
                                                        0,
                                                        min(
                                                            100,
                                                            $studentSurveyCompletionRate
                                                        )
                                                    ) ?>%;">

                        <span>

                            <strong>
                                <?= number_format(
                                    $studentSurveyCompletionRate,
                                    1
                                ) ?>%
                            </strong>

                            <small>
                                completed
                            </small>

                        </span>

                    </div>

                    <div>

                        <span class="page-eyebrow">
                            Survey Version
                            <?= number_format(
                                $studentSurveyVersion
                            ) ?>
                        </span>

                        <h3>
                            <?= number_format(
                                $studentSurveyCompleted
                            ) ?>
                            of
                            <?= number_format(
                                $totalStudentSurveyAccounts
                            ) ?>
                            Students completed
                        </h3>

                        <p>
                            Answer distributions use completed
                            surveys only. Progress counts include
                            all active Student accounts.
                        </p>

                    </div>

                </article>

                <div class="dashboard-survey-status-grid">

                    <article class="not-started">

                        <span>
                            <i class="fa-regular fa-circle"></i>
                        </span>

                        <div>

                            <strong>
                                <?= number_format(
                                    $studentSurveyNotStarted
                                ) ?>
                            </strong>

                            <small>
                                Not started
                            </small>

                        </div>

                    </article>

                    <article class="in-progress">

                        <span>
                            <i class="fa-solid fa-spinner"></i>
                        </span>

                        <div>

                            <strong>
                                <?= number_format(
                                    $studentSurveyInProgress
                                ) ?>
                            </strong>

                            <small>
                                In progress
                            </small>

                        </div>

                    </article>

                    <article class="completed">

                        <span>
                            <i class="fa-solid fa-circle-check"></i>
                        </span>

                        <div>

                            <strong>
                                <?= number_format(
                                    $studentSurveyCompleted
                                ) ?>
                            </strong>

                            <small>
                                Completed
                            </small>

                        </div>

                    </article>

                </div>

            </div>

            <div class="dashboard-survey-privacy-note">

                <i class="fa-solid fa-user-shield"></i>

                <div>

                    <strong>
                        Individual answers are never displayed
                    </strong>

                    <p>
                        A response category appears only when at
                        least
                        <?= number_format(
                            $studentSurveyMinimumGroup
                        ) ?>
                        completed Students share it. Accessibility
                        and sensitive wellbeing responses are
                        excluded from Dashboard analytics.
                    </p>

                </div>

            </div>

            <div class="dashboard-survey-sections">

                <?php foreach (
                    $studentSurveySections
                    as $surveySectionIndex =>
                    $surveySection
                ): ?>

                    <?php

                    $surveySectionQuestions =
                        is_array(
                            $surveySection['questions']
                                ?? null
                        )
                        ? $surveySection['questions']
                        : [];

                    $reportableQuestionCount = 0;

                    foreach (
                        $surveySectionQuestions
                        as $surveySectionQuestion
                    ) {
                        if (
                            !empty($surveySectionQuestion['available'])
                        ) {
                            $reportableQuestionCount++;
                        }
                    }

                    ?>

                    <details
                        class="dashboard-survey-section"
                        <?= $surveySectionIndex === 0
                            ? 'open'
                            : ''
                        ?>>

                        <summary>

                            <span class="dashboard-survey-section-icon">

                                <i class="<?= match ($surveySection['section_key']
                                                ?? '') {
                                                'device' =>
                                                'fa-solid fa-laptop',

                                                'connectivity' =>
                                                'fa-solid fa-wifi',

                                                'geography' =>
                                                'fa-solid fa-map-location-dot',

                                                'learning' =>
                                                'fa-solid fa-book-open-reader',

                                                'wellbeing' =>
                                                'fa-solid fa-hands-holding-circle',

                                                default =>
                                                'fa-solid fa-chart-simple'
                                            } ?>"></i>

                            </span>

                            <span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $surveySection['section_label']
                                            ?? 'Survey section',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= number_format(
                                        $reportableQuestionCount
                                    ) ?>
                                    of
                                    <?= number_format(
                                        count(
                                            $surveySectionQuestions
                                        )
                                    ) ?>
                                    indicators currently reportable
                                </small>

                            </span>

                            <i class="fa-solid fa-chevron-down"></i>

                        </summary>

                        <div class="dashboard-survey-question-grid">

                            <?php foreach (
                                $surveySectionQuestions
                                as $surveyQuestion
                            ): ?>

                                <?php

                                $surveyQuestionItems =
                                    is_array(
                                        $surveyQuestion['items']
                                            ?? null
                                    )
                                    ? $surveyQuestion['items']
                                    : [];

                                $surveyQuestionAvailable =
                                    !empty($surveyQuestion['available']);

                                $surveyRespondentCount =
                                    (int) (
                                        $surveyQuestion['respondent_count']
                                        ?? 0
                                    );

                                $maximumSurveyAnswerCount =
                                    1;

                                foreach (
                                    $surveyQuestionItems
                                    as $surveyQuestionItem
                                ) {
                                    $maximumSurveyAnswerCount =
                                        max(
                                            $maximumSurveyAnswerCount,
                                            (int) (
                                                $surveyQuestionItem['count']
                                                ?? 0
                                            )
                                        );
                                }

                                ?>

                                <article class="dashboard-survey-question">

                                    <header>

                                        <div>

                                            <span>
                                                <?= number_format(
                                                    $surveyRespondentCount
                                                ) ?>
                                                completed responses
                                            </span>

                                            <h4>
                                                <?= htmlspecialchars(
                                                    $surveyQuestion['question_text']
                                                        ?? 'Survey indicator',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </h4>

                                        </div>

                                        <?php if (
                                            $surveyQuestionAvailable
                                        ): ?>

                                            <i
                                                class="fa-solid fa-chart-column"
                                                title="Aggregate data available">
                                            </i>

                                        <?php else: ?>

                                            <i
                                                class="fa-solid fa-lock"
                                                title="Protected small group">
                                            </i>

                                        <?php endif; ?>

                                    </header>

                                    <?php if (
                                        $surveyQuestionAvailable
                                    ): ?>

                                        <div class="dashboard-survey-bars">

                                            <?php foreach (
                                                $surveyQuestionItems
                                                as $surveyQuestionItem
                                            ): ?>

                                                <?php

                                                $surveyAnswerCount =
                                                    (int) (
                                                        $surveyQuestionItem['count']
                                                        ?? 0
                                                    );

                                                $surveyAnswerWidth =
                                                    (
                                                        $surveyAnswerCount /
                                                        $maximumSurveyAnswerCount
                                                    ) * 100;

                                                ?>

                                                <div class="dashboard-survey-bar">

                                                    <div>

                                                        <span>
                                                            <?= htmlspecialchars(
                                                                $surveyQuestionItem['label']
                                                                    ?? 'Response',
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        </span>

                                                        <strong>
                                                            <?= number_format(
                                                                $surveyAnswerCount
                                                            ) ?>

                                                            <small>
                                                                <?= number_format(
                                                                    (float) (
                                                                        $surveyQuestionItem['percentage']
                                                                        ?? 0
                                                                    ),
                                                                    1
                                                                ) ?>%
                                                            </small>

                                                        </strong>

                                                    </div>

                                                    <span>

                                                        <i
                                                            style="width: <?= max(
                                                                                0,
                                                                                min(
                                                                                    100,
                                                                                    $surveyAnswerWidth
                                                                                )
                                                                            ) ?>%;">
                                                        </i>

                                                    </span>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                        <?php if (
                                            (
                                                $surveyQuestion['suppressed_response_count']
                                                ?? 0
                                            ) > 0
                                        ): ?>

                                            <p class="dashboard-survey-suppressed">

                                                <i class="fa-solid fa-shield"></i>

                                                Additional small response
                                                categories are protected.
                                            </p>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <div class="dashboard-survey-protected">

                                            <i class="fa-solid fa-users-slash"></i>

                                            <span>

                                                <strong>
                                                    Aggregate unavailable
                                                </strong>

                                                <small>
                                                    This indicator needs at
                                                    least
                                                    <?= number_format(
                                                        $studentSurveyMinimumGroup
                                                    ) ?>
                                                    completed responses with
                                                    a reportable shared category.
                                                </small>

                                            </span>

                                        </div>

                                    <?php endif; ?>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    </details>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

    <!-- ======================================
     DEPARTMENT POSTING STATISTICS
======================================= -->

    <section
        id="departmentAnalytics"
        class="page-card dashboard-list-card">
        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    Department Analytics
                </span>

                <h2>
                    Posts per department
                </h2>

                <p>
                    Content created during
                    <?= htmlspecialchars(
                        strtolower($rangeLabel),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>,
                    grouped by the creator’s department
                    and current publication status.
                </p>

            </div>

        </div>

        <div class="dashboard-list">

            <?php if (
                empty($departmentPostingStatistics)
            ): ?>

                <div class="dashboard-empty">

                    <i class="fa-solid fa-building"></i>

                    <p>
                        No department posting data is available.
                    </p>

                </div>

            <?php else: ?>

                <?php foreach (
                    $departmentPostingStatistics
                    as $departmentStatistic
                ): ?>

                    <?php

                    $departmentId =
                        (int) (
                            $departmentStatistic['department_id']
                            ?? 0
                        );

                    $isSchoolwide =
                        $departmentId <= 0;

                    $isSelectedDepartment =
                        (
                            $isSchoolwide &&
                            $selectedDepartmentScope ===
                            'schoolwide'
                        )
                        ||
                        (
                            !$isSchoolwide &&
                            $departmentId ===
                            $selectedDepartmentId
                        );

                    $departmentUrl =
                        'index.php?page=dashboard'
                        . '&range='
                        . urlencode(
                            $selectedRange
                        );

                    if ($isSchoolwide) {
                        $departmentUrl .=
                            '&scope=schoolwide';
                    } else {
                        $departmentUrl .=
                            '&department_id='
                            . $departmentId;
                    }

                    $departmentUrl .=
                        '#departmentBreakdown';

                    ?>

                    <a
                        href="<?= htmlspecialchars(
                                    $departmentUrl,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        class="dashboard-list-item dashboard-department-link<?= $isSelectedDepartment
                                                                                ? ' is-selected'
                                                                                : ''
                                                                            ?>">

                        <span>

                            <?php if ($isSchoolwide): ?>

                                <i class="fa-solid fa-school"></i>

                            <?php else: ?>

                                <i class="fa-solid fa-building"></i>

                            <?php endif; ?>

                        </span>

                        <div>

                            <strong>
                                <?= htmlspecialchars(
                                    $departmentStatistic['department_name']
                                        ?? 'Unknown Department',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                            <small>
                                <?= number_format(
                                    (int) (
                                        $departmentStatistic['announcement_count']
                                        ?? 0
                                    )
                                ) ?>
                                announcements

                                ·

                                <?= number_format(
                                    (int) (
                                        $departmentStatistic['event_count']
                                        ?? 0
                                    )
                                ) ?>
                                events

                                ·

                                <?= number_format(
                                    (int) (
                                        $departmentStatistic['document_count']
                                        ?? 0
                                    )
                                ) ?>
                                documents

                                ·

                                <?= number_format(
                                    (int) (
                                        $departmentStatistic['survey_count']
                                        ?? 0
                                    )
                                ) ?>
                                surveys
                            </small>

                        </div>

                        <div>

                            <strong>
                                <?= number_format(
                                    (int) (
                                        $departmentStatistic['total_posts']
                                        ?? 0
                                    )
                                ) ?>
                                total
                            </strong>

                            <small>
                                <?= number_format(
                                    (int) (
                                        $departmentStatistic['published_posts']
                                        ?? 0
                                    )
                                ) ?>
                                published
                            </small>

                        </div>

                        <i class="fa-solid fa-chevron-right"></i>

                    </a>

                <?php endforeach; ?>


            <?php endif; ?>

        </div>

    </section>

    <?php if (
        !empty($departmentBreakdown)
    ): ?>

        <section
            id="departmentBreakdown"
            class="page-card dashboard-department-breakdown">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Department Drill-down
                    </span>

                    <h2>
                        <?= htmlspecialchars(
                            $departmentBreakdown['department_name']
                                ?? 'Department',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h2>

                    <p>
                        Content created during
                        <?= htmlspecialchars(
                            strtolower(
                                $rangeLabel
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>.
                    </p>

                </div>

                <a
                    href="index.php?page=dashboard&range=<?= urlencode(
                                                                $selectedRange
                                                            ) ?>#departmentAnalytics"
                    class="dashboard-card-link">

                    Clear department

                    <i class="fa-solid fa-xmark"></i>

                </a>

            </div>

            <div class="dashboard-list">

                <?php if (
                    empty($departmentBreakdownItems)
                ): ?>

                    <div class="dashboard-empty">

                        <i class="fa-regular fa-folder-open"></i>

                        <p>
                            This department has no content
                            during the selected period.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $departmentBreakdownItems
                        as $departmentContent
                    ): ?>

                        <?php

                        $contentType =
                            strtolower(
                                trim(
                                    (string) (
                                        $departmentContent['content_type']
                                        ?? ''
                                    )
                                )
                            );

                        $contentId =
                            (int) (
                                $departmentContent['content_id']
                                ?? 0
                            );

                        $contentOwnerId =
                            (int) (
                                $departmentContent['user_id']
                                ?? 0
                            );

                        $workflowStatus =
                            strtolower(
                                trim(
                                    (string) (
                                        $departmentContent['workflow_status']
                                        ?? ''
                                    )
                                )
                            );

                        $isOwnedByCurrentUser =
                            $contentOwnerId > 0 &&
                            $contentOwnerId ===
                            $currentDashboardUserId;

                        $contentUrl = null;
                        $actionLabel = '';

                        /*
     * Published content opens its exact
     * Information Hub item.
     */
                        if (
                            $workflowStatus ===
                            'published' &&
                            $contentId > 0
                        ) {
                            $contentUrl =
                                'index.php?page=news'
                                . '&open_type='
                                . urlencode(
                                    $contentType
                                )
                                . '&open_id='
                                . $contentId;

                            $actionLabel =
                                'Open published content';
                        }

                        /*
     * Only the original owner may edit
     * a draft or rejected item.
     */ elseif (
                            in_array(
                                $workflowStatus,
                                [
                                    'draft',
                                    'rejected'
                                ],
                                true
                            ) &&
                            $isOwnedByCurrentUser &&
                            $contentId > 0
                        ) {
                            $contentUrl =
                                'index.php?page=postings'
                                . '&edit_type='
                                . urlencode(
                                    $contentType
                                )
                                . '&edit_id='
                                . $contentId;

                            $actionLabel =
                                $workflowStatus ===
                                'rejected'
                                ? 'Revise rejected content'
                                : 'Edit draft';
                        }

                        /*
     * Workflow-controlled content opens
     * the matching administrative workspace.
     */ elseif (
                            in_array(
                                $workflowStatus,
                                [
                                    'pending_review',
                                    'approved',
                                    'scheduled',
                                    'archived'
                                ],
                                true
                            )
                        ) {
                            $contentUrl =
                                'index.php?page=content_workspace'
                                . '&status='
                                . urlencode(
                                    $workflowStatus
                                );

                            $actionLabel =
                                'Open workflow workspace';
                        }

                        $hasContentAction =
                            $contentUrl !== null;

                        ?>

                        <?php if ($hasContentAction): ?>

                            <a
                                href="<?= htmlspecialchars(
                                            $contentUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                class="dashboard-list-item"
                                title="<?= htmlspecialchars(
                                            $actionLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                            <?php else: ?>

                                <div
                                    class="dashboard-list-item"
                                    title="This item belongs to another content owner.">

                                <?php endif; ?>

                                <span>

                                    <?php if (
                                        $contentType ===
                                        'announcement'
                                    ): ?>

                                        <i class="fa-solid fa-bullhorn"></i>

                                    <?php elseif (
                                        $contentType ===
                                        'event'
                                    ): ?>

                                        <i class="fa-regular fa-calendar"></i>

                                    <?php elseif (
                                        $contentType ===
                                        'document'
                                    ): ?>

                                        <i class="fa-regular fa-file-lines"></i>

                                    <?php else: ?>

                                        <i class="fa-solid fa-square-poll-horizontal"></i>

                                    <?php endif; ?>

                                </span>

                                <div>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $departmentContent['title']
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
                                            !empty($departmentContent['author_name'])
                                        ): ?>

                                            ·

                                            <?= htmlspecialchars(
                                                $departmentContent['author_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        <?php endif; ?>

                                        <?php if (
                                            in_array(
                                                $workflowStatus,
                                                [
                                                    'draft',
                                                    'rejected'
                                                ],
                                                true
                                            ) &&
                                            !$isOwnedByCurrentUser
                                        ): ?>

                                            · View only

                                        <?php endif; ?>
                                    </small>

                                </div>

                                <div>

                                    <small>
                                        <?= htmlspecialchars(
                                            date(
                                                'M d, Y',
                                                strtotime(
                                                    $departmentContent['content_date']
                                                        ?? 'now'
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </small>

                                </div>

                                <?php if ($hasContentAction): ?>

                                    <i class="fa-solid fa-chevron-right"></i>

                                <?php endif; ?>

                                <?php if ($hasContentAction): ?>

                            </a>

                        <?php else: ?>

            </div>

        <?php endif; ?>

    <?php endforeach; ?>

<?php endif; ?>

</div>

        </section>

    <?php endif; ?>

    <!-- ======================================
     CONTENT VIEW RANKINGS
======================================= -->

    <section class="content-grid two-columns">

        <article class="page-card dashboard-list-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Audience Reach
                    </span>

                    <h2>
                        Most viewed content
                    </h2>

                    <p>
                        Published content receiving the highest
                        number of recorded views during
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

            <div class="dashboard-list">

                <?php if (
                    empty($mostViewedContent)
                ): ?>

                    <div class="dashboard-empty">

                        <i class="fa-regular fa-eye-slash"></i>

                        <p>
                            No published content is available.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $mostViewedContent
                        as $contentItem
                    ): ?>

                        <?php

                        $contentType =
                            strtolower(
                                trim(
                                    (string) (
                                        $contentItem['content_type']
                                        ?? ''
                                    )
                                )
                            );

                        $contentId =
                            (int) (
                                $contentItem['content_id']
                                ?? 0
                            );

                        $contentUrl =
                            'index.php?page=news';

                        if (
                            in_array(
                                $contentType,
                                [
                                    'announcement',
                                    'event',
                                    'document',
                                    'survey'
                                ],
                                true
                            ) &&
                            $contentId > 0
                        ) {
                            $contentUrl .=
                                '&open_type='
                                . urlencode(
                                    $contentType
                                )
                                . '&open_id='
                                . $contentId;
                        }

                        ?>

                        <a
                            href="<?= htmlspecialchars(
                                        $contentUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            class="dashboard-list-item">

                            <span>

                                <i class="fa-regular fa-eye"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $contentItem['title']
                                            ?? 'Untitled Content',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $contentType !== ''
                                                ? $contentType
                                                : 'content'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    ·

                                    <?= number_format(
                                        (int) (
                                            $contentItem['view_count']
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

        <article class="page-card dashboard-list-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Content Review
                    </span>

                    <h2>
                        Least viewed content
                    </h2>

                    <p>
                        Published content with the lowest reach
                        during
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

            <div class="dashboard-list">

                <?php if (
                    empty($leastViewedContent)
                ): ?>

                    <div class="dashboard-empty">

                        <i class="fa-regular fa-eye-slash"></i>

                        <p>
                            No published content is available.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $leastViewedContent
                        as $contentItem
                    ): ?>

                        <?php

                        $contentType =
                            strtolower(
                                trim(
                                    (string) (
                                        $contentItem['content_type']
                                        ?? ''
                                    )
                                )
                            );

                        $contentId =
                            (int) (
                                $contentItem['content_id']
                                ?? 0
                            );

                        $contentUrl =
                            'index.php?page=news';

                        if (
                            in_array(
                                $contentType,
                                [
                                    'announcement',
                                    'event',
                                    'document',
                                    'survey'
                                ],
                                true
                            ) &&
                            $contentId > 0
                        ) {
                            $contentUrl .=
                                '&open_type='
                                . urlencode(
                                    $contentType
                                )
                                . '&open_id='
                                . $contentId;
                        }

                        ?>

                        <a
                            href="<?= htmlspecialchars(
                                        $contentUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            class="dashboard-list-item">

                            <span>

                                <i class="fa-solid fa-arrow-trend-down"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $contentItem['title']
                                            ?? 'Untitled Content',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $contentType !== ''
                                                ? $contentType
                                                : 'content'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    ·

                                    <?= number_format(
                                        (int) (
                                            $contentItem['view_count']
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

    </section>


    <!-- ======================================
         ANNOUNCEMENTS AND EVENTS
    ======================================= -->

    <section class="content-grid two-columns">

        <article class="page-card dashboard-list-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Latest Content
                    </span>

                    <h2>
                        Recent announcements
                    </h2>

                </div>

                <a
                    href="index.php?page=news"
                    class="dashboard-card-link">
                    View All

                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>

            <div class="dashboard-list">

                <?php if (
                    empty($announcements)
                ): ?>

                    <div class="dashboard-empty">

                        <i class="fa-regular fa-folder-open"></i>

                        <p>
                            No announcements available.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $announcements as $announcement
                    ): ?>

                        <a
                            href="index.php?page=news"
                            class="dashboard-list-item">

                            <span>

                                <i class="fa-solid fa-bullhorn"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $announcement['title']
                                            ?? 'Untitled Announcement',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $announcement['created_at']
                                                    ?? 'now'
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </article>

        <article class="page-card dashboard-list-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Calendar
                    </span>

                    <h2>
                        Upcoming events
                    </h2>

                </div>

                <a
                    href="index.php?page=calendar"
                    class="dashboard-card-link">
                    View Calendar

                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>

            <div class="dashboard-list">

                <?php if (
                    empty($events)
                ): ?>

                    <div class="dashboard-empty">

                        <i class="fa-regular fa-calendar-xmark"></i>

                        <p>
                            No upcoming events.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $events as $event
                    ): ?>

                        <a
                            href="index.php?page=calendar"
                            class="dashboard-list-item">

                            <span>

                                <i class="fa-solid fa-calendar-day"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $event['title']
                                            ?? 'Untitled Event',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $event['event_date']
                                                    ?? 'now'
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </article>

    </section>

    <!-- ======================================
         ACTIVITY AND NOTIFICATIONS
    ======================================= -->

    <section class="content-grid two-columns">

        <article class="page-card dashboard-list-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Audit Trail
                    </span>

                    <h2>
                        Recent activity
                    </h2>

                </div>

            </div>

            <div class="dashboard-list">

                <?php if (
                    empty($recentActivity)
                ): ?>

                    <div class="dashboard-empty">

                        <i class="fa-solid fa-clock-rotate-left"></i>

                        <p>
                            Activity data is not connected yet.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $recentActivity as $activity
                    ): ?>

                        <div class="dashboard-list-item">

                            <span>

                                <i class="fa-solid fa-clock-rotate-left"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $activity['action']
                                            ?? 'System Activity',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        $activity['description']
                                            ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                                <?php if (
                                    !empty($activity['timestamp'])
                                ): ?>

                                    <small>
                                        <?= htmlspecialchars(
                                            date(
                                                'M d, Y g:i A',
                                                strtotime(
                                                    $activity['timestamp']
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </small>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </article>

        <article class="page-card dashboard-list-card">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        System Notices
                    </span>

                    <h2>
                        Notifications
                    </h2>

                </div>

            </div>

            <div class="dashboard-list">

                <?php if (
                    empty($notifications)
                ): ?>

                    <div class="dashboard-empty">

                        <i class="fa-regular fa-bell"></i>

                        <p>
                            No notifications are available.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $notifications as $notification
                    ): ?>

                        <?php

                        $notificationId =
                            (int) (
                                $notification['notification_id']
                                ?? 0
                            );

                        $notificationUrl =
                            $notificationId > 0
                            ? 'index.php?page=notification_open'
                            . '&notification_id='
                            . $notificationId
                            : 'index.php?page=notifications';

                        ?>

                        <a
                            href="<?= htmlspecialchars(
                                        $notificationUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            class="dashboard-list-item">

                            <span>

                                <i
                                    class="fa-regular fa-bell"
                                    aria-hidden="true"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $notification['title']
                                            ?? 'Notification',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        $notification['message']
                                            ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </div>

                            <i
                                class="fa-solid fa-chevron-right"
                                aria-hidden="true"></i>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </article>

    </section>

</section>