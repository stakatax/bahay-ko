<?php
// Content is loaded through content_open, preserving its POST, CSRF and audience checks.
?>
<section class="post-detail-page" data-post-page
    data-post-type="<?= htmlspecialchars($postContentType, ENT_QUOTES, 'UTF-8') ?>"
    data-post-id="<?= (int) $postContentId ?>">
    <a href="index.php?page=news" class="app-button app-button-secondary post-return-link">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to feed
    </a>
    <?php require __DIR__ . '/news.php'; ?>
    <article class="hub-list-item" data-hub-item
        data-type="<?= htmlspecialchars($postContentType, ENT_QUOTES, 'UTF-8') ?>"
        data-content-id="<?= (int) $postContentId ?>">
        <div class="hub-item-content"></div>
    </article>
</section>
