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
    date_default_timezone_set('Asia/Manila');

    require_once './config/dbconnect.php';

    /* SAFE MONTH/YEAR */
    $month = isset($_GET['month']) && $_GET['month'] >= 1 && $_GET['month'] <= 12
        ? (int)$_GET['month']
        : date('n');

    $year = isset($_GET['year']) && $_GET['year'] >= 1970 && $_GET['year'] <= 2100
        ? (int)$_GET['year']
        : date('Y');

    /* FIRST DAY OF MONTH */
    $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $firstDayOfMonth);
    $dayOfWeek = date('w', $firstDayOfMonth);

    $monthName = date('F', $firstDayOfMonth);

    /* TODAY */
    $currentDate = date('Y-m-d');

    /* PREV / NEXT MONTH (SAFE SHIFT) */
    $prev = strtotime("-1 month", $firstDayOfMonth);
    $next = strtotime("+1 month", $firstDayOfMonth);

    $prevMonth = date('n', $prev);
    $prevYear  = date('Y', $prev);

    $nextMonth = date('n', $next);
    $nextYear  = date('Y', $next);

    function getFirstFridays($year) {
        $firstFridays = [];

        for ($month = 1; $month <= 12; $month++) {

            $date = strtotime("$year-$month-01");

            while (date('N', $date) != 5) { // 5 = Friday
                $date = strtotime("+1 day", $date);
            }

            $firstFridays[] = date('Y-m-d', $date);
        }

        return $firstFridays;
    }
    /* DYNAMIC: FIRST FRIDAY MASS (ALL MONTHS) */
    $firstFridays = getFirstFridays($year);

    $events = [];

    foreach ($firstFridays as $date) {
        $events[$date][] = [
            "title" => "FIRST FRIDAY MASS",
            "type" => "Church Event"
        ];
    }

    $stmt = $conn->prepare("
        SELECT event_id, title, event_date
        FROM events
        WHERE status = 'active'
        AND YEAR(event_date) = ?
    ");

    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $date = date('Y-m-d', strtotime($row['event_date']));

        $events[$date][] = [
            "title" => $row['title'],
            "type"  => "Event"
        ];
    }

    $selectedDate = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])): $currentDate;
    $selectedEvents = [];

    if (isset($events[$selectedDate])) {
        $selectedEvents = $events[$selectedDate];
    }
?>

<section id="calendar" class="calendar-remodel-section">

    <div class="calendar-header-text">
        <h1>Event Calendar</h1>
        <p>Stay up to date with our schedule. Reach out for event inquiries or support.</p>
    </div>

    <div class="calendar-container">

        <!-- LEFT PANEL -->
        <div class="cal-left-panel">

            <div class="cal-nav-card">
                <div class="month-controls">
                    <a href="?page=calendar&month=<?=$prevMonth?>&year=<?=$prevYear?>#calendar">&lt;</a>

                    <div>
                        <h2><?=$monthName?></h2>
                        <p><?=$year?></p>
                    </div>

                    <a href="?page=calendar&month=<?=$nextMonth?>&year=<?=$nextYear?>#calendar">&gt;</a>
                </div>
            </div>

            <div id="eventList">
                <?php if (empty($selectedEvents)): ?>
                    <div class="event-mini-card">
                        <h4>No Events</h4>
                        <p>No scheduled events for this date.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($selectedEvents as $event): ?>
                        <div class="event-mini-card">
                            <h4><?= htmlspecialchars($event['title']) ?></h4>
                            <p><?= htmlspecialchars($event['type']) ?></p>
                            <small style="color: var(--text-gray);">
                                <?= date('F d, Y', strtotime($selectedDate)) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- RIGHT PANEL -->
        <div class="cal-right-panel">

            <h3><?=$monthName?> <?=$year?></h3>

            <div class="grid-wrapper">

                <?php
                /* DAY HEADERS */
                $dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                foreach ($dayNames as $dn) {
                    echo "<div class='day-name'>$dn</div>";
                }

                /* EMPTY CELLS BEFORE START */
                for ($i = 0; $i < $dayOfWeek; $i++) {
                    echo "<div class='day-cell empty'></div>";
                }

                /* DAYS */
                for ($day = 1; $day <= $daysInMonth; $day++) {

                    $cellDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday = ($cellDate === $currentDate);
                    $isSelected = ($cellDate === $selectedDate);

                    $hasEvent = isset($events[$cellDate]);

                    $class = "day-cell";

                    if ($isToday) {$class .= " today";}
                    if ($isSelected) {$class .= " selected";}
                    if ($hasEvent) {$class .= " has-event";}

                    echo "
                        <a href='?page=calendar&month=$month&year=$year&date=$cellDate#calendar' class='$class day-cell' data-date='$cellDate'>
                            $day

                            " . ($isToday ? "<span class='today-dot'></span>" : "") . "

                            " . ($hasEvent ? "<span class='event-dot'></span>" : "") . "
                        </a>
                    ";
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
        <select id="eventType">
            <option>Holiday</option>
            <option>School Event</option>
            <option>Personal</option>
        </select>

        <button onclick="saveEvent()">Save</button>
        <button onclick="closeModal()">Cancel</button>
    </div>
</div>

<script>
    const EVENTS = <?= json_encode($events) ?>;
</script>