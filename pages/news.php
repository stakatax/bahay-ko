<?php

?>

<section class="news-section">
    <div class="news-content">

        <div class="announcement-section">
            <h2 class="section-title">Announcements</h2>
            <div class="news-card box-announcement announcement-slider">

                <button class="slider-btn prev" onclick="moveSlide(-1)">&#10094;</button>

                <div class="slides-container">
                    <?php if (!empty($announcements)): ?>
                        <?php $active = true;
                        foreach ($announcements as $item): ?>
                            <div class="slide <?= $active ? 'active' : '' ?>">
                                <span class="info-label">LATEST UPDATE</span>

                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <p><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                                <small><?= date('M d, Y', strtotime($item['created_at'])) ?></small>
                            </div>
                        <?php $active = false;
                        endforeach; ?>

                    <?php else: ?>
                        <div class="slide active">
                            <span class="info-label">LATEST UPDATE</span>
                            <h3>No Announcements Yet</h3>
                            <p>Please check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <button class="slider-btn next" onclick="moveSlide(1)">&#10095;</button>
            </div>
        </div>
        <div class="events-view-section">
            <h2 class="section-title">Events</h2>
            <div class="tags-container">
                <span class="tag active">All</span>
                <span class="tag">Academic</span>
                <span class="tag">Holidays</span>
                <span class="tag">Sports</span>
                <span class="tag">Faculty</span>
            </div>
            <div class="events-container">
                <?php foreach ($events as $event): ?>
                    <div class="news-card box-event">
                        <h3><?= htmlspecialchars($event['title']) ?></h3>
                        <p><?= date('M d, Y', strtotime($event['event_date'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="uploaded-files-section">
            <h2 class="section-title">Uploaded Files</h2>
            <div class="files-container">
                <?php if (!empty($documents)): ?>
                    <?php foreach ($documents as $file): ?>
                        <div class="news-card box-file">
                            <div class="file-info">
                                <span class="info-label"><?= htmlspecialchars($file['file_type']) ?></span>
                                <p class="file-name"><?= htmlspecialchars($file['file_name']) ?></p>
                                <small><?= htmlspecialchars($file['created_at']) ?></small>
                            </div>
                            <div class="file-actions">
                                <a href="./Assets/uploads<?= htmlspecialchars($file['file_name']) ?>"
                                    target="_blank"
                                    class="action-btn view"
                                    title="View Document">
                                    <i class="fa-regular fa-eye"></i>
                                </a>

                                <a href="./Assets/uploads<?= htmlspecialchars($file['file_name']) ?>"
                                    download
                                    class="action-btn download"
                                    title="Download File">
                                    <i class="fa-solid fa-arrow-down-long"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="news-card box-file">
                        <div class="file-info">
                            <span class="info-label">FILE</span>
                            <p class="file-name">No uploaded documents yet.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>