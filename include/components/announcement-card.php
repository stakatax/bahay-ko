<?php

if (!isset($announcement) || !is_array($announcement)) {
    return;
}

$title = $announcement['title'] ?? 'Untitled Announcement';
$content = $announcement['content'] ?? '';
$createdAt = $announcement['created_at'] ?? null;

$plainContent = trim(strip_tags($content));

if (mb_strlen($plainContent) > 260) {
    $preview = mb_substr($plainContent, 0, 260) . '...';
} else {
    $preview = $plainContent;
}
?>

<article
    class="hub-thread"
    data-hub-item
    data-type="announcement"
    data-search="<?= htmlspecialchars(
                        strtolower($title . ' ' . $plainContent),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">
    <div class="thread-accent"></div>

    <div class="thread-content">

        <div class="thread-header">

            <div class="thread-type">
                <span class="thread-icon">
                    <i class="fa-solid fa-bullhorn"></i>
                </span>

                <div>
                    <span class="thread-label">Announcement</span>

                    <?php if ($createdAt): ?>
                        <time datetime="<?= htmlspecialchars($createdAt) ?>">
                            <?= date('M d, Y', strtotime($createdAt)) ?>
                        </time>
                    <?php endif; ?>
                </div>
            </div>

            <button
                type="button"
                class="thread-menu-button"
                aria-label="Announcement options">
                <i class="fa-solid fa-ellipsis"></i>
            </button>

        </div>

        <div class="thread-body">

            <h2><?= htmlspecialchars($title) ?></h2>

            <p><?= nl2br(htmlspecialchars($preview)) ?></p>

        </div>

        <div class="thread-tags">

            <span class="thread-tag">
                <i class="fa-solid fa-school"></i>
                School Update
            </span>

            <span class="thread-tag">
                <i class="fa-solid fa-users"></i>
                Public
            </span>

        </div>

        <div class="thread-footer">

            <div class="thread-engagement">

                <span title="Views">
                    <i class="fa-regular fa-eye"></i>
                    <span>View</span>
                </span>

                <span title="Reactions">
                    <i class="fa-regular fa-heart"></i>
                    <span>React</span>
                </span>

                <span title="Comments">
                    <i class="fa-regular fa-comment"></i>
                    <span>Comment</span>
                </span>

            </div>

            <button
                type="button"
                class="thread-open-button"
                data-open-thread
                data-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                data-content="<?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?>"
                data-date="<?= $createdAt
                                ? htmlspecialchars(date('F d, Y', strtotime($createdAt)), ENT_QUOTES, 'UTF-8')
                                : '' ?>">
                Open Thread
                <i class="fa-solid fa-arrow-right"></i>
            </button>

        </div>

    </div>
</article>