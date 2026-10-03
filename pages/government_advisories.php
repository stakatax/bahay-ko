<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$sources =
    is_array(
        $viewData['sources']
            ?? null
    )
    ? $viewData['sources']
    : [];

$advisories =
    is_array(
        $viewData['advisories']
            ?? null
    )
    ? $viewData['advisories']
    : [];

$counts =
    is_array(
        $viewData['counts']
            ?? null
    )
    ? $viewData['counts']
    : [];

$selectedStatus =
    trim(
        (string) (
            $viewData['selected_status']
            ?? ''
        )
    );

$flash =
    is_array(
        $viewData['flash']
            ?? null
    )
    ? $viewData['flash']
    : null;

$statusFilters = [
    '' =>
    'All',

    'Pending' =>
    'Pending',

    'Relevant' =>
    'Relevant',

    'Irrelevant' =>
    'Irrelevant',

    'Converted' =>
    'Converted',

    'Archived' =>
    'Archived'
];

$statusIcons = [
    'Pending' =>
    'fa-regular fa-clock',

    'Relevant' =>
    'fa-solid fa-check',

    'Irrelevant' =>
    'fa-solid fa-ban',

    'Converted' =>
    'fa-solid fa-arrow-right-arrow-left',

    'Archived' =>
    'fa-solid fa-box-archive'
];

$typeIcons = [
    'Holiday' =>
    'fa-solid fa-star',

    'EducationPolicy' =>
    'fa-solid fa-graduation-cap',

    'ClassSuspension' =>
    'fa-solid fa-school-circle-xmark',

    'Emergency' =>
    'fa-solid fa-triangle-exclamation',

    'Weather' =>
    'fa-solid fa-cloud-bolt',

    'HealthSafety' =>
    'fa-solid fa-heart-pulse',

    'Scholarship' =>
    'fa-solid fa-award',

    'Compliance' =>
    'fa-solid fa-clipboard-check',

    'Other' =>
    'fa-solid fa-file-lines'
];

?>

<section class="app-page government-advisory-page">

    <header class="government-advisory-header">

        <div>

            <h1>
                Government advisories
            </h1>

            <p>
                Check an official link, review the advisory, then create an announcement.
            </p>

        </div>

        <div class="government-advisory-header-badge">

            <i
                class="fa-solid fa-shield-halved"
                aria-hidden="true"></i>

            <div>

                <strong>
                    <?= number_format(
                        count(
                            $sources
                        )
                    ) ?>
                </strong>

                <span>
                    Trusted sources
                </span>

            </div>

        </div>

    </header>

    <?php if ($flash): ?>

        <div
            class="government-advisory-flash <?= (
                                                    $flash['type']
                                                    ?? ''
                                                ) === 'success'
                                                    ? 'is-success'
                                                    : 'is-error' ?>">

            <i
                class="<?= (
                            $flash['type']
                            ?? ''
                        ) === 'success'
                            ? 'fa-solid fa-circle-check'
                            : 'fa-solid fa-circle-exclamation' ?>"
                aria-hidden="true"></i>

            <span>
                <?= htmlspecialchars(
                    (string) (
                        $flash['message']
                        ?? ''
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>

    <?php endif; ?>

    <section class="government-advisory-metrics">

        <?php foreach (
            [
                [
                    'label' => 'Pending Review',
                    'value' => $counts['Pending'] ?? 0,
                    'icon' => 'fa-regular fa-clock'
                ],
                [
                    'label' => 'Relevant',
                    'value' => $counts['Relevant'] ?? 0,
                    'icon' => 'fa-solid fa-check'
                ],
                [
                    'label' => 'Converted',
                    'value' => $counts['Converted'] ?? 0,
                    'icon' => 'fa-solid fa-arrow-right-arrow-left'
                ],
                [
                    'label' => 'Total Intake',
                    'value' => $counts['All'] ?? 0,
                    'icon' => 'fa-solid fa-layer-group'
                ]
            ]
            as $metric
        ): ?>

            <article class="government-advisory-metric">

                <span>

                    <i
                        class="<?= htmlspecialchars(
                                    $metric['icon'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        aria-hidden="true"></i>

                </span>

                <div>

                    <strong>
                        <?= number_format(
                            (int) $metric['value']
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

            </article>

        <?php endforeach; ?>

    </section>

    <div class="government-advisory-layout">

        <section class="government-advisory-intake-card">

            <div class="government-advisory-section-heading">

                <span>

                    <i
                        class="fa-solid fa-link"
                        aria-hidden="true"></i>

                </span>

                <div>

                    <h2>
                        Check an official advisory
                    </h2>

                    <p>
                        Use a link from one of the trusted sources listed below.
                    </p>

                </div>

            </div>

            <form
                id="governmentAdvisoryIntakeForm"
                action="index.php?page=government_advisory_submit"
                method="POST"
                data-preview-url="index.php?page=government_advisory_preview"
                novalidate>
                <?= csrfInput() ?>

                <div class="government-advisory-field">

                    <label for="governmentSourceUrl">
                        Official advisory link
                        <span>*</span>
                    </label>

                    <input
                        type="url"
                        id="governmentSourceUrl"
                        name="source_url"
                        placeholder="https://www.deped.gov.ph/..."
                        required>

                    <small>
                        HTTPS links from active trusted
                        sources only.
                    </small>

                </div>

                <details class="government-advisory-options" id="governmentAdvisoryOptions">
                    <summary>Live page or additional details</summary>
                    <div class="government-advisory-options-body">
                <fieldset
                    class="government-advisory-source-mode"
                    id="governmentSourcePageMode">

                    <legend>
                        Source page type
                        <span>*</span>
                    </legend>

                    <div class="government-advisory-source-mode-options">

                        <label class="government-advisory-source-mode-option">

                            <input
                                type="radio"
                                name="source_page_mode"
                                value="specific"
                                checked
                                required>

                            <span>

                                <strong>
                                    Single advisory
                                </strong>

                                <small>
                                    Use the link to one article or bulletin.
                                </small>

                            </span>

                        </label>

                        <label class="government-advisory-source-mode-option">

                            <input
                                type="radio"
                                name="source_page_mode"
                                value="reusable"
                                required>

                            <span>

                                <strong>
                                    Live updates page
                                </strong>

                                <small>
                                    For pages that change, such as PAGASA updates. Enter a title, unique reference and summary.
                                </small>

                            </span>

                        </label>

                    </div>

                </fieldset>

                <div class="government-advisory-form-grid">

                    <div class="government-advisory-field">

                        <label for="governmentAdvisoryTitle">
                            Advisory title
                            <span class="is-optional">
                                Optional
                            </span>
                        </label>

                        <input
                            type="text"
                            id="governmentAdvisoryTitle"
                            name="title"
                            maxlength="255"
                            placeholder="Use when the page title is too general">

                    </div>

                    <div class="government-advisory-field">

                        <label for="governmentExternalReference">
                            Official reference
                            <span class="is-optional">
                                Optional
                            </span>
                        </label>

                        <input
                            type="text"
                            id="governmentExternalReference"
                            name="external_reference"
                            maxlength="150"
                            placeholder="Example: DepEd Order No. 001, s. 2026">

                    </div>

                </div>

                <div class="government-advisory-field">

                    <label for="governmentAdvisorySummary">
                        What this means for the school
                        <span class="is-optional">
                            Optional
                        </span>
                    </label>

                    <textarea
                        id="governmentAdvisorySummary"
                        name="summary"
                        maxlength="2000"
                        rows="4"
                        placeholder="Briefly explain the possible effect on school operations."></textarea>

                </div>

                <div class="government-advisory-form-grid">

                    <div class="government-advisory-field">

                        <label for="governmentIssuedAt">
                            Issued date
                            <span class="is-optional">
                                Optional
                            </span>
                        </label>

                        <input
                            type="datetime-local"
                            id="governmentIssuedAt"
                            name="issued_at">

                    </div>

                    <div class="government-advisory-field">

                        <label for="governmentGeographicScope">
                            Geographic scope
                        </label>

                        <select
                            id="governmentGeographicScope"
                            name="geographic_scope">

                            <option value="">
                                Detect automatically
                            </option>

                            <option value="Nationwide">
                                Nationwide
                            </option>

                            <option value="Region">
                                Region
                            </option>

                            <option value="Province">
                                Province
                            </option>

                            <option value="Municipality">
                                Municipality
                            </option>

                            <option value="School">
                                School
                            </option>

                        </select>

                    </div>

                </div>

                <div class="government-advisory-field">

                    <label for="governmentScopeValue">
                        Scope name
                        <span class="is-optional">
                            Optional
                        </span>
                    </label>

                    <input
                        type="text"
                        id="governmentScopeValue"
                        name="scope_value"
                        maxlength="150"
                        placeholder="Example: Nueva Ecija or Guimba">

                </div>

                    </div>
                </details>

                <div class="government-advisory-intake-actions">

                    <button
                        type="button"
                        id="governmentAdvisoryPreviewButton"
                        class="government-advisory-secondary-button">

                        <i
                            class="fa-solid fa-magnifying-glass"
                            aria-hidden="true"></i>

                        <span>
                            Check link
                        </span>

                    </button>

                    <button
                        type="submit"
                        id="governmentAdvisorySubmitButton"
                        class="government-advisory-primary-button"
                        disabled>

                        <i
                            class="fa-solid fa-inbox"
                            aria-hidden="true"></i>

                        <span>
                            Add to Review Queue
                        </span>

                    </button>

                </div>

            </form>

            <div id="governmentAdvisoryLoading" class="government-advisory-loading" role="status" hidden>
                <p>Checking the source and its relevance...</p>
                <div class="government-advisory-skeleton" aria-hidden="true">
                    <span></span><span></span><span></span><span></span>
                </div>
            </div>

            <div
                id="governmentAdvisoryPreview"
                class="government-advisory-preview"
                hidden
                aria-live="polite">

                <div class="government-advisory-preview-header">

                    <div>

                        <span
                            id="governmentPreviewSource"
                            class="government-advisory-source">
                        </span>

                        <h3 id="governmentPreviewTitle"></h3>

                    </div>

                    <strong id="governmentPreviewScore"></strong>

                </div>

                <div class="government-advisory-preview-tags">

                    <span id="governmentPreviewType"></span>

                    <span id="governmentPreviewScope"></span>

                    <span id="governmentPreviewLevel"></span>

                </div>

                <p id="governmentPreviewRecommendation"></p>

                <div
                    id="governmentPreviewReasons"
                    class="government-advisory-reasons">
                </div>

                <p
                    id="governmentPreviewExcerpt"
                    class="government-advisory-preview-excerpt">
                </p>

            </div>

            <div
                id="governmentAdvisoryPreviewError"
                class="government-advisory-preview-error"
                hidden
                role="alert">
            </div>

        </section>

        <aside class="government-advisory-source-card">

            <div class="government-advisory-section-heading">

                <span>

                    <i
                        class="fa-solid fa-landmark"
                        aria-hidden="true"></i>

                </span>

                <div>

                    <h2>
                        Trusted source directory
                    </h2>

                    <p>
                        Sources remain configurable and
                        inactive entries cannot be imported.
                    </p>

                </div>

            </div>

            <div class="government-advisory-source-list">

                <?php foreach ($sources as $source): ?>

                    <article>

                        <span>
                            <?= htmlspecialchars(
                                (string) (
                                    $source['agency_code']
                                    ?? 'GOV'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                        <div>

                            <strong>
                                <?= htmlspecialchars(
                                    (string) (
                                        $source['source_name']
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                            <small>
                                <?= htmlspecialchars(
                                    (string) (
                                        $source['allowed_host']
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </small>

                        </div>

                        <b>
                            <?= number_format(
                                (int) (
                                    $source['authority_weight']
                                    ?? 0
                                )
                            ) ?>
                        </b>

                    </article>

                <?php endforeach; ?>

            </div>

        </aside>

    </div>

    <section class="government-advisory-queue-card">

        <div class="government-advisory-queue-heading">

            <div>

                <span class="government-advisory-eyebrow">
                    Administrative Review
                </span>

                <h2>
                    Advisory review queue
                </h2>

            </div>

            <nav
                class="government-advisory-filters"
                aria-label="Advisory status filters">

                <?php foreach (
                    $statusFilters
                    as $statusValue => $statusLabel
                ): ?>

                    <?php
                    $isActive =
                        $selectedStatus ===
                        $statusValue;

                    $countKey =
                        $statusValue !== ''
                        ? $statusValue
                        : 'All';
                    ?>

                    <a
                        href="index.php?page=government_advisories<?= $statusValue !== ''
                                                                        ? '&status='
                                                                        . urlencode(
                                                                            $statusValue
                                                                        )
                                                                        : '' ?>"
                        class="<?= $isActive
                                    ? 'active'
                                    : '' ?>"
                        <?= $isActive
                            ? 'aria-current="page"'
                            : '' ?>>

                        <?= htmlspecialchars(
                            $statusLabel,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        <strong>
                            <?= number_format(
                                (int) (
                                    $counts[$countKey]
                                    ?? 0
                                )
                            ) ?>
                        </strong>

                    </a>

                <?php endforeach; ?>

            </nav>

        </div>

        <div class="government-advisory-queue">

            <?php if ($advisories === []): ?>

                <div class="government-advisory-empty">

                    <i
                        class="fa-regular fa-folder-open"
                        aria-hidden="true"></i>

                    <h3>
                        No advisories in this view
                    </h3>

                    <p>
                        Submit a verified government URL or
                        select another review filter.
                    </p>

                </div>

            <?php else: ?>

                <?php foreach (
                    $advisories
                    as $advisory
                ): ?>

                    <?php
                    $status =
                        (string) (
                            $advisory['review_status']
                            ?? 'Pending'
                        );

                    $type =
                        (string) (
                            $advisory['advisory_type']
                            ?? 'Other'
                        );

                    $score =
                        (int) (
                            $advisory['relevance_score']
                            ?? 0
                        );

                    $sourcePageMode =
                        (
                            $advisory['source_page_mode']
                            ?? 'Specific'
                        ) === 'Reusable'
                        ? 'Reusable'
                        : 'Specific';

                    $sourcePageModeLabel =
                        $sourcePageMode ===
                        'Reusable'
                        ? 'Live/reusable source'
                        : 'Specific source';

                    $encodedReasons =
                        $advisory['relevance_reasons']
                        ?? '';

                    $decodedReasons =
                        is_string(
                            $encodedReasons
                        )
                        ? json_decode(
                            $encodedReasons,
                            true
                        )
                        : [];

                    $reasons =
                        is_array(
                            $decodedReasons['reasons']
                                ?? null
                        )
                        ? $decodedReasons['reasons']
                        : [];
                    ?>

                    <article class="government-advisory-item">

                        <header>

                            <div class="government-advisory-item-icon">

                                <i
                                    class="<?= htmlspecialchars(
                                                $typeIcons[$type]
                                                    ?? $typeIcons['Other'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                    aria-hidden="true"></i>

                            </div>

                            <div class="government-advisory-item-title">

                                <div>

                                    <span class="government-advisory-source">
                                        <?= htmlspecialchars(
                                            (string) (
                                                $advisory['agency_code']
                                                ?? 'GOV'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <span class="government-advisory-status is-<?= strtolower(
                                                                                    htmlspecialchars(
                                                                                        $status,
                                                                                        ENT_QUOTES,
                                                                                        'UTF-8'
                                                                                    )
                                                                                ) ?>">

                                        <i
                                            class="<?= htmlspecialchars(
                                                        $statusIcons[$status]
                                                            ?? 'fa-solid fa-circle',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                            aria-hidden="true"></i>

                                        <?= htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </div>

                                <h3>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $advisory['title']
                                            ?? 'Untitled Advisory'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h3>

                                <?php if (
                                    !empty($advisory['external_reference'])
                                ): ?>

                                    <p>
                                        <?= htmlspecialchars(
                                            (string) $advisory['external_reference'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                            <div class="government-advisory-score">

                                <strong>
                                    <?= number_format(
                                        $score
                                    ) ?>
                                </strong>

                                <small>
                                    relevance
                                </small>

                            </div>

                        </header>

                        <div class="government-advisory-item-meta">

                            <span>

                                <i
                                    class="fa-solid fa-tag"
                                    aria-hidden="true"></i>

                                <?= htmlspecialchars(
                                    $type,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                            <span>

                                <i
                                    class="<?= $sourcePageMode ===
                                                'Reusable'
                                                ? 'fa-solid fa-arrows-rotate'
                                                : 'fa-regular fa-file-lines'
                                            ?>"
                                    aria-hidden="true"></i>

                                <?= htmlspecialchars(
                                    $sourcePageModeLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                            <span>

                                <i
                                    class="fa-solid fa-location-dot"
                                    aria-hidden="true"></i>

                                <?= htmlspecialchars(
                                    trim(
                                        (string) (
                                            $advisory['scope_value']
                                            ?? ''
                                        )
                                    ) !== ''
                                        ? (
                                            $advisory['scope_value']
                                        )
                                        : (
                                            $advisory['geographic_scope']
                                            ?? 'Nationwide'
                                        ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                            <span>

                                <i
                                    class="fa-regular fa-calendar"
                                    aria-hidden="true"></i>

                                <?= htmlspecialchars(
                                    !empty($advisory['issued_at'])
                                        ? date(
                                            'M d, Y g:i A',
                                            strtotime(
                                                $advisory['issued_at']
                                            )
                                        )
                                        : date(
                                            'M d, Y g:i A',
                                            strtotime(
                                                $advisory['created_at']
                                            )
                                        ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </div>

                        <?php if (
                            !empty($advisory['summary'])
                        ): ?>

                            <p class="government-advisory-item-summary">

                                <?= htmlspecialchars(
                                    (string) $advisory['summary'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </p>

                        <?php endif; ?>

                        <div class="government-advisory-reasons">

                            <?php foreach (
                                array_slice(
                                    $reasons,
                                    0,
                                    6
                                )
                                as $reason
                            ): ?>

                                <?php
                                $adjustment =
                                    (int) (
                                        $reason['score_adjustment']
                                        ?? 0
                                    );
                                ?>

                                <span>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $reason['rule_name']
                                            ?? 'Matched rule'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <strong>
                                        <?= $adjustment >= 0
                                            ? '+'
                                            : '' ?><?= $adjustment ?>
                                    </strong>
                                </span>

                            <?php endforeach; ?>

                        </div>

                        <footer>

                            <a
                                href="<?= htmlspecialchars(
                                            (string) (
                                                $advisory['source_url']
                                                ?? '#'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                target="_blank"
                                rel="noopener noreferrer">

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square"
                                    aria-hidden="true"></i>

                                Open official source

                            </a>

                            <?php if (
                                $status ===
                                'Relevant'
                            ): ?>

                                <a
                                    href="index.php?page=postings&type=announcement&government_advisory_id=<?= (int) (
                                                                                                                $advisory['government_advisory_id']
                                                                                                                ?? 0
                                                                                                            ) ?>"
                                    class="government-advisory-convert-link">

                                    <i
                                        class="fa-solid fa-pen-to-square"
                                        aria-hidden="true"></i>

                                    <span>
                                        Create Announcement Draft
                                    </span>

                                </a>

                            <?php endif; ?>

                            <?php if (
                                in_array(
                                    $status,
                                    [
                                        'Pending',
                                        'Relevant',
                                        'Irrelevant'
                                    ],
                                    true
                                )
                            ): ?>

                                <form
                                    action="index.php?page=government_advisory_review"
                                    method="POST"
                                    class="government-advisory-review-form">

                                    <?= csrfInput() ?>

                                    <input
                                        type="hidden"
                                        name="government_advisory_id"
                                        value="<?= (int) (
                                                    $advisory['government_advisory_id']
                                                    ?? 0
                                                ) ?>">

                                    <input
                                        type="text"
                                        name="review_notes"
                                        maxlength="1000"
                                        placeholder="Decision notes">

                                    <button
                                        type="submit"
                                        name="review_status"
                                        value="Relevant"
                                        class="is-relevant">

                                        Relevant

                                    </button>

                                    <button
                                        type="submit"
                                        name="review_status"
                                        value="Irrelevant"
                                        class="is-irrelevant">

                                        Irrelevant

                                    </button>

                                    <button
                                        type="submit"
                                        name="review_status"
                                        value="Archived"
                                        class="is-archive"
                                        aria-label="Archive advisory">

                                        <i
                                            class="fa-solid fa-box-archive"
                                            aria-hidden="true"></i>

                                    </button>

                                </form>

                            <?php endif; ?>

                        </footer>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </section>

</section>