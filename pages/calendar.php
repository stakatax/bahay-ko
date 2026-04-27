<section class="calendar-hero">
    <div class="hero-overlay"></div>
    <img src="Assets/Images/about 2.jpg" alt="" class="hero-img"/>
    

    <div class="hero-content">
        <h2 class="hero-title">Recent Events</h2>

        <div class="event-container">

            <div class="recent-event">
                <img src="Assets/Images/default.png" alt="" class="event-image">
                <h3 class="event-title">Event TItle</h3>
                <p>
                    <strong>Event Date: </strong> 
                    <span> render date here </span>
                </p>
            </div>

            <div class="recent-event">
                <img src="Assets/Images/default.png" alt="" class="event-image">
                <h3 class="event-title">Event TItle</h3>
                <p>
                    <strong>Event Date: </strong> 
                    <span> render date here </span>
                </p>
            </div>

            <div class="recent-event">
                <img src="Assets/Images/default.png" alt="" class="event-image">
                <h3 class="event-title">Event TItle</h3>
                <p>
                    <strong>Event Date: </strong> 
                    <span> render date here </span>
                </p>
            </div>

            <div class="recent-event">
                <img src="Assets/Images/default.png" alt="" class="event-image">
                <h3 class="event-title">Event TItle</h3>
                <p>
                    <strong>Event Date: </strong> 
                    <span> render date here </span>
                </p>
            </div>

            <div class="recent-event">
                <img src="Assets/Images/default.png" alt="" class="event-image">
                <h3 class="event-title">Event TItle</h3>
                <p>
                    <strong>Event Date: </strong> 
                    <span> render date here </span>
                </p>
            </div>

            <div class="recent-event">
                <img src="Assets/Images/default.png" alt="" class="event-image">
                <h3 class="event-title">Event TItle</h3>
                <p>
                    <strong>Event Date: </strong> 
                    <span> render date here </span>
                </p>
            </div>

            <div class="recent-event">
                <img src="Assets/Images/default.png" alt="" class="event-image">
                <h3 class="event-title">Event TItle</h3>
                <p>
                    <strong>Event Date: </strong> 
                    <span> render date here </span>
                </p>
            </div>

        </div>
    </div>
</section>

<?php
    // variables are inside the controller
?>

<section id="calendar" class="calendar-remodel-section">
    <div class="calendar-header-text">
        <h1>Event Calendar</h1>
        <p>Stay up to date with our schedule.</p>
    </div>

    <div class="calendar-container">
        <!-- LEFT PANEL -->
        <div class="cal-left-panel">
            <div class="cal-nav-card">
                <div class="month-controls">
                    <a href="?page=calendar&month=<?= $prevMonth ?>&year=<?= $prevYear ?>#calendar">&lt;</a>
                    <div>
                        <h2><?= $monthName ?></h2>
                        <p><?= $year ?></p>
                    </div>
                    <a href="?page=calendar&month=<?= $nextMonth ?>&year=<?= $nextYear ?>#calendar">&gt;</a>
                </div>
            </div>

            <!-- EVENT LIST -->
            <div id='eventList' class="event-list-container">

                <?php if (empty($selectedEvents)): ?>
                    <div class="event-mini-card">
                        <h4>No Events</h4>
                        <p>No scheduled events.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($selectedEvents as $event): ?>
                        <div class="event-mini-card">
                            <h4><?= htmlspecialchars($event['title']) ?></h4>
                            <p><?= htmlspecialchars($event['type']) ?></p>
                            <small>
                                <?= date('F d, Y', strtotime($selectedDate)) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- RIGHT PANEL -->
        <div class="cal-right-panel">
            <h3><?= $monthName ?> <?= $year ?></h3>
            <div class="grid-wrapper">
                <?php
                    $dayNames = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
                        
                    foreach ($dayNames as $dn) {
                        echo "<div class='day-name'>$dn</div>";
                    }
                        
                    for ($i=0; $i<$dayOfWeek; $i++) {
                        echo "<div class='day-cell empty'></div>";
                    }
                        
                    for ($day=1; $day <= $daysInMonth; $day++) {
                        $cellDate = sprintf(
                            '%04d-%02d-%02d',
                            $year,
                            $month,
                            $day
                        );
                        $isToday = ($cellDate === $currentDate);
                        $isSelected = ($cellDate === $selectedDate);
                        $hasEvent = isset($events[$cellDate]);

                        $class = "day-cell";
                        
                        if ($isToday) $class .= " today";
                    
                        if ($isSelected) $class .= " selected";
                    
                        if ($hasEvent) $class .= " has-event";
                    
                        echo "
                        <a href='?page=calendar
                        &month=$month
                        &year=$year
                        &date=$cellDate#calendar'
    
                        class='$class' data-date='$cellDate'>
    
                            $day
    
                            ".($isToday
                                ? "<span class='today-dot'></span>"
                                : ""
                            )."
    
                            ".($hasEvent
                                ? "<span class='event-dot'></span>"
                                : ""
                            )."
    
                        </a>";
                    }
                ?>
            </div>

            <?php if ($role != 'Student'): ?>
                <button class="add-event-btn">+ Add Event</button>
            <?php endif; ?>

        </div>
    </div>
</section>

<div id="eventModal" class="modal">
    <div class="modal-content">
        <h2>Add Event</h2>
        <h3 id="modalDate">Selected Date</h3>
        
        <input type="text" id="eventTitle" placeholder="Event Title">

        <button onclick="saveEvent()">Save</button>
        <button onclick="closeModal()">Cancel</button>
    </div>
</div>

<script>
    const EVENTS =<?= json_encode($events) ?>;
</script>