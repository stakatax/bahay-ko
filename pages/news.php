<?php

include './config/dbconnect.php';

$events = [];

$query = "SELECT title, event_date FROM events WHERE status = 'active' ORDER BY event_date ASC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = [
            'title' => $row['title'],
            'date' => date('M d, Y', strtotime($row['event_date']))
        ];
    }
}

$announcements = [
    [
        'title' => 'Welcome Back Students!',
        'content' => 'Classes resume on Monday. Please check your schedules.',
        'date' => 'Aug 20, 2026'
    ],
    [
        'title' => 'Birthday ni Ky',
        'content' => 'Celebration of 21st Birthday of Ky',
        'date' => 'Apr 22, 2026'
    ]
];


$uploadedFiles = [
    ['filename' => 'Syllabus_2026.pdf', 'size' => '2.4 MB'],
    ['filename' => 'Campus_Map.png', 'size' => '1.1 MB']
];
?>

<section class="news-section">
    <div class="news-content">
        
        <div class="announcement-section">
            <h2 class="section-title">Announcements</h2>
            <div class="news-card box-announcement announcement-slider">
                
                <button class="slider-btn prev" onclick="moveSlide(-1)">&#10094;</button>

                <div class="slides-container">
                    <?php 
                    // Example PHP Loop
                    $active = true;
                    foreach ($announcements as $index => $item): 
                    ?>
                        <div class="slide <?= $active ? 'active' : '' ?>">
                            <span class="info-label">LATEST UPDATE</span>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p><?= htmlspecialchars($item['content']) ?></p>
                        </div>
                    <?php 
                        $active = false;
                    endforeach; 
                    ?>
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
                        <p><?= htmlspecialchars($event['date']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="uploaded-files-section">
            <h2 class="section-title">Uploaded Files</h2>
            <div class="files-container">
                <?php foreach ($uploadedFiles as $file): ?>
                    <div class="news-card box-file">
                        <div class="file-info">
                            <span class="info-label">FILE</span>
                            <p class="file-name"><?= htmlspecialchars($file['name']) ?></p>
                        </div>

                        <!-- <div class="file-actions">
                            <a href="uploads/" target="_blank" class="action-btn view" title="View">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="uploads/" download class="action-btn download" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div> -->
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

