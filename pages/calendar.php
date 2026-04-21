<section class="calendar-hero">
    <div class="hero-overlay"></div>

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

/* GET MONTH + YEAR */
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

/* MONTH DETAILS */
$firstDayOfMonth = mktime(0,0,0,$month,1,$year);
$daysInMonth = date('t',$firstDayOfMonth);
$dayOfWeek = date('w',$firstDayOfMonth);

$monthName = date('F',$firstDayOfMonth);
$todayDay = date('j');
$currentMonth = date('n');
$currentYear = date('Y');

/* PREV + NEXT MONTH */
$prevMonth = $month - 1;
$prevYear  = $year;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear  = $year;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$months = [
        "January","February","March","April","May","June",
        "July","August","September","October","November","December"
    ];
?>

<section class="calendar-body">
    <h1 class="calendar-title">Event Calendar</h1>
    <div class="calendar-content">
        

        <aside class="month-list">

            <div class="year-nav">

                <a href="?month=<?=$prevMonth?>&year=<?=$prevYear?>" class="nav-btn">&lt;</a>

                <div class="year-display">
                    <h2><?=$year?></h2>
                    <p><?=$monthName?></p>
                </div>

                <a href="?month=<?=$nextMonth?>&year=<?=$nextYear?>" class="nav-btn">&gt;</a>

            </div>

            <ul class="months">

                <?php foreach ($months as $index => $m): ?>
                    <li class="<?=($index+1==$month)?'active':''?>">
                        <a href="?month=<?=($index+1)?>&year=<?=$year?>"><?=$m?></a>
                    </li>
                <?php endforeach; ?>
            
            </ul>
            
        </aside>
            
            
        <div class="calendar">
            <div class="calendar-header">
                <div class="date-title">

                    <span class="big-date"><?=date('j',$firstDayOfMonth)?></span>
            
                    <div>
                        <p class="month-year"><?=strtoupper($monthName)." ".$year?></p>
                        <h2><?=date('l',$firstDayOfMonth)?></h2>
                    </div>
                </div>
            </div>
            <div class="calendar-grid">
            
                <?php
                    $dayNames = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
                    
                    foreach ($dayNames as $dn) {
                        echo "<div class='day-name'>$dn</div>";
                    }
                    
                    /* EMPTY CELLS */
                    for ($i=0;$i<$dayOfWeek;$i++) {
                        echo "<div></div>";
                    }
            
                    /* DAYS LOOP */

                    for ($day=1;$day<=$daysInMonth;$day++) {

                    $isToday = (
                    $day==$todayDay &&
                    $month==$currentMonth &&
                    $year==$currentYear
                    );

                    $class = "day";

                    if ($isToday) {
                        $class .= " highlight";
                    }

                    echo "<div class='$class'>$day</div>";
                    }
                ?>

            </div>
        </div>
            
        
        <div class="calendar-event-panel">
            <div class="event-header">
            
                <p class="month-year"><?=strtoupper($monthName)." ".$year?></p>
            
                <h2><?=date('l',$firstDayOfMonth)?></h2>
            
                <p class="holiday">Sample Holiday</p>
            
            </div>
            
            <div class="event-list">
            
                <div class="event">
                    <span class="dot"></span>
                    FIRST FRIDAY MASS
                </div>
            
                <div class="event">
                    <span class="dot"></span>
                    BIRTHDAY NI KY
                </div>
            
            </div>
            
            <button class="add-event">+ Add Event</button>
        </div>
    </div>
</section>