<section class="hub-widget">

    <div class="widget-header">

        <div>
            <span class="widget-eyebrow">Schedule</span>
            <h3>Upcoming Events</h3>
        </div>

        <a href="index.php?page=calendar">View all</a>

    </div>

    <div class="widget-list">

        <?php if (!empty($events)): ?>

            <?php foreach ($events as $event): ?>
                <?php include __DIR__ . '/../components/event-card.php'; ?>
            <?php endforeach; ?>

        <?php else: ?>

            <div class="widget-empty">
                <i class="fa-regular fa-calendar-xmark"></i>
                <p>No upcoming events.</p>
            </div>

        <?php endif; ?>

    </div>

</section>