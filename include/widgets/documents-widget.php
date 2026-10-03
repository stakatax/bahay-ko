<section class="hub-widget">

    <div class="widget-header">

        <div>
            <span class="widget-eyebrow">Resources</span>
            <h3>Recent Documents</h3>
        </div>

        <a href="index.php?page=news&type=document">View all</a>

    </div>

    <div class="widget-list">

        <?php if (!empty($documents)): ?>

            <?php foreach ($documents as $document): ?>
                <?php include __DIR__ . '/../components/document-card.php'; ?>
            <?php endforeach; ?>

        <?php else: ?>

            <div class="widget-empty">
                <i class="fa-regular fa-folder-open"></i>
                <p>No recent documents.</p>
            </div>

        <?php endif; ?>

    </div>

</section>