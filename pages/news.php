<?php
// Mock data simulating database fetches
$announcements = [
    [
        'title' => 'Welcome Back Students!',
        'content' => 'Classes resume on Monday. Please check your schedules.',
        'date' => 'Aug 20, 2026'
    ]
];

$events = [
    ['title' => 'Science Fair', 'date' => 'Sep 15'],
    ['title' => 'Parent-Teacher Meet', 'date' => 'Sep 22'],
    ['title' => 'Sports Fest', 'date' => 'Oct 10']
];

$uploadedFiles = [
    ['filename' => 'Syllabus_2026.pdf', 'size' => '2.4 MB'],
    ['filename' => 'Campus_Map.png', 'size' => '1.1 MB']
];
?>

<section class="news-section">
    <div class="news-content">

        <div class="main-news-column">
            <div class="announcement-section">
                <h2 class="section-title">Announcements</h2>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="news-card box-announcement">
                        <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                        <small><?= htmlspecialchars($announcement['date']) ?></small>
                        <p><?= htmlspecialchars($announcement['content']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="uploaded-files-section" style="margin-top: 50px;">
                <h2 class="section-title">Resources</h2>
                <div class="files-container">
                    <?php foreach ($uploadedFiles as $file): ?>
                        <div class="news-card box-file">
                            <strong><?= htmlspecialchars($file['filename']) ?></strong>
                            <small><?= htmlspecialchars($file['size']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="events-view-section">
            <h2 class="section-title">Upcoming Events</h2>
            <div class="events-container">
                <?php foreach ($events as $event): ?>
                    <div class="news-card box-event">
                        <h3><?= htmlspecialchars($event['title']) ?></h3>
                        <p><?= htmlspecialchars($event['date']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>