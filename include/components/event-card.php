<?php

if (!isset($event) || !is_array($event)) {
    return;
}

$title = $event['title'] ?? 'Untitled Event';
$eventDate = $event['event_date'] ?? null;
?>

<a href="index.php?page=calendar" class="widget-item">

    <span class="widget-icon">
        <i class="fa-regular fa-calendar"></i>
    </span>

    <span class="widget-item-content">

        <strong><?= htmlspecialchars($title) ?></strong>

        <small>
            <?= $eventDate
                ? date('M d, Y', strtotime($eventDate))
                : 'Date to be announced' ?>
        </small>

    </span>

    <i class="fa-solid fa-chevron-right widget-arrow"></i>

</a>