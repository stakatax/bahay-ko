<?php

$content =
    $viewData['content']
    ?? [];

$contentType =
    strtolower(
        trim(
            (string) (
                $viewData['content_type']
                ?? ''
            )
        )
    );

$contentId =
    (int) (
        $viewData['content_id']
        ?? 0
    );

$departmentName =
    trim(
        (string) (
            $viewData['department_name']
            ?? 'Faculty Department'
        )
    );

$returnRange =
    (string) (
        $viewData['return_range']
        ?? '30'
    );

$title =
    trim(
        (string) (
            $contentType === 'document'
            ? (
                $content['raw']['title']
                ?? $content['title']
                ?? $content['file_name']
                ?? 'Untitled Document'
            )
            : (
                $content['title']
                ?? 'Untitled Content'
            )
        )
    );

$description =
    trim(
        (string) (
            $contentType === 'document'
            ? (
                $content['raw']['description']
                ?? ''
            )
            : (
                $content['description']
                ?? ''
            )
        )
    );

$workflowStatus =
    strtolower(
        trim(
            (string) (
                $content['workflow_status']
                ?? 'draft'
            )
        )
    );

$authorName =
    trim(
        (string) (
            $content['author_name']
            ?? 'Unknown Faculty Author'
        )
    );

$createdAt =
    $content['created_at']
    ?? null;

$currentUserId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );

$authorId =
    (int) (
        $content['author_id']
        ?? 0
    );

$isOwner =
    $currentUserId > 0 &&
    $currentUserId === $authorId;

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
                Read-only Department Preview
            </span>

            <h1>
                <?= htmlspecialchars(
                    $title,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <p>
                Previewing
                <?= htmlspecialchars(
                    ucfirst(
                        $contentType
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
                content from
                <?= htmlspecialchars(
                    $departmentName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>.
            </p>

        </div>

        <div class="page-actions">

            <a
                href="index.php?page=department_analytics&range=<?= urlencode(
                                                                    $returnRange
                                                                ) ?>"
                class="app-button secondary">

                <i class="fa-solid fa-arrow-left"></i>

                Department Analytics
            </a>

            <?php if (
                $isOwner &&
                in_array(
                    $workflowStatus,
                    [
                        'draft',
                        'rejected'
                    ],
                    true
                )
            ): ?>

                <a
                    href="index.php?page=postings&edit_type=<?= urlencode(
                                                                $contentType
                                                            ) ?>&edit_id=<?= $contentId ?>"
                    class="app-button primary">

                    <i class="fa-solid fa-pen-to-square"></i>

                    <?= $workflowStatus === 'rejected'
                        ? 'Revise Content'
                        : 'Edit Draft'
                    ?>
                </a>

            <?php endif; ?>

        </div>

    </header>

    <!-- ======================================
         CONTENT PREVIEW
    ======================================= -->

    <section class="page-card department-preview-card">

        <div class="department-preview-heading">

            <span class="department-preview-icon">

                <i class="<?= htmlspecialchars(
                                $contentIcons[$contentType]
                                    ?? 'fa-regular fa-file',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"></i>

            </span>

            <div>

                <span class="page-eyebrow">
                    <?= htmlspecialchars(
                        ucfirst(
                            $contentType
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $title,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <p>
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

                    <?php if ($authorName !== ''): ?>

                        ·

                        <?= htmlspecialchars(
                            $authorName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    <?php endif; ?>

                    <?php if (!empty($createdAt)): ?>

                        ·

                        <?= htmlspecialchars(
                            date(
                                'M d, Y g:i A',
                                strtotime(
                                    $createdAt
                                )
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    <?php endif; ?>
                </p>

            </div>

        </div>

        <?php if ($description !== ''): ?>

            <div class="department-preview-description">

                <h3>
                    Description
                </h3>

                <p>
                    <?= nl2br(
                        htmlspecialchars(
                            $description,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>
                </p>

            </div>

        <?php endif; ?>

        <!-- ======================================
             TYPE-SPECIFIC DETAILS
        ======================================= -->

        <div class="dashboard-status-list">

            <?php if (
                $contentType === 'announcement'
            ): ?>

                <div>

                    <span class="dashboard-status-dot published"></span>

                    <div>

                        <strong>
                            Priority
                        </strong>

                        <small>
                            Announcement urgency classification
                        </small>

                    </div>

                    <b>
                        <?= htmlspecialchars(
                            $content['priority']
                                ?? 'Normal',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </b>

                </div>

                <div>

                    <span class="dashboard-status-dot scheduled"></span>

                    <div>

                        <strong>
                            Release Mode
                        </strong>

                        <small>
                            Publishing configuration
                        </small>

                    </div>

                    <b>
                        <?= htmlspecialchars(
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $content['release_mode']
                                        ?? 'immediate'
                                )
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </b>

                </div>

            <?php elseif (
                $contentType === 'event'
            ): ?>

                <div>

                    <span class="dashboard-status-dot scheduled"></span>

                    <div>

                        <strong>
                            Event Start
                        </strong>

                        <small>
                            Scheduled start date and time
                        </small>

                    </div>

                    <b>
                        <?= !empty($content['event_date'])
                            ? htmlspecialchars(
                                date(
                                    'M d, Y g:i A',
                                    strtotime(
                                        $content['event_date']
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'Not specified'
                        ?>
                    </b>

                </div>

                <div>

                    <span class="dashboard-status-dot published"></span>

                    <div>

                        <strong>
                            Location
                        </strong>

                        <small>
                            Event venue
                        </small>

                    </div>

                    <b>
                        <?= htmlspecialchars(
                            $content['location']
                                ?? 'Not specified',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </b>

                </div>

            <?php elseif (
                $contentType === 'document'
            ): ?>

                <div>

                    <span class="dashboard-status-dot published"></span>

                    <div>

                        <strong>
                            File
                        </strong>

                        <small>
                            <?= htmlspecialchars(
                                strtoupper(
                                    $content['file_type']
                                        ?? 'FILE'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                            ·

                            <?= number_format(
                                (
                                    (int) (
                                        $content['file_size']
                                        ?? 0
                                    )
                                ) / 1024,
                                1
                            ) ?>
                            KB
                        </small>

                    </div>

                    <?php if (
                        !empty($content['file_path'])
                    ): ?>

                        <a
                            href="<?= htmlspecialchars(
                                        'index.php?page=document_download&context=department&inline=1&document_id=' . $contentId,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="dashboard-card-link">

                            Open File

                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>

                    <?php endif; ?>

                </div>

            <?php elseif (
                $contentType === 'survey'
            ): ?>

                <div>

                    <span class="dashboard-status-dot published"></span>

                    <div>

                        <strong>
                            Responses
                        </strong>

                        <small>
                            Recorded survey submissions
                        </small>

                    </div>

                    <b>
                        <?= number_format(
                            (int) (
                                $content['response_count']
                                ?? 0
                            )
                        ) ?>
                    </b>

                </div>

                <div>

                    <span class="dashboard-status-dot scheduled"></span>

                    <div>

                        <strong>
                            Survey Window
                        </strong>

                        <small>
                            Opening and closing schedule
                        </small>

                    </div>

                    <b>
                        <?= !empty($content['closes_at'])
                            ? 'Closes '
                            . htmlspecialchars(
                                date(
                                    'M d, Y',
                                    strtotime(
                                        $content['closes_at']
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'No closing date'
                        ?>
                    </b>

                </div>

            <?php endif; ?>

        </div>

        <?php if (
            !empty($content['review_notes'])
        ): ?>

            <div class="department-preview-review">

                <h3>
                    Review Notes
                </h3>

                <p>
                    <?= nl2br(
                        htmlspecialchars(
                            $content['review_notes'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>
                </p>

            </div>

        <?php endif; ?>

    </section>

</section>