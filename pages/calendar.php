<?php

$isLoggedIn =
    !empty($_SESSION['user_id']);

$currentRole =
    $_SESSION['role']
    ?? 'Guest';

$pageWrapperClass =
    $isLoggedIn
    ? 'app-page calendar-app-page'
    : 'public-calendar-page';


$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$month =
    $viewData['month']
    ?? null;

$year =
    $viewData['year']
    ?? null;

$monthName =
    $viewData['monthName']
    ?? null;

$daysInMonth =
    $viewData['daysInMonth']
    ?? null;

$dayOfWeek =
    $viewData['dayOfWeek']
    ?? null;

$currentDate =
    $viewData['currentDate']
    ?? null;

$selectedDate =
    $viewData['selectedDate']
    ?? null;

$prevMonth =
    $viewData['prevMonth']
    ?? null;

$prevYear =
    $viewData['prevYear']
    ?? null;

$nextMonth =
    $viewData['nextMonth']
    ?? null;

$nextYear =
    $viewData['nextYear']
    ?? null;

$events =
    $viewData['events']
    ?? [];

$selectedEvents =
    $viewData['selectedEvents']
    ?? [];


$holidays =
    is_array(
        $viewData['holidays']
            ?? null
    )
    ? $viewData['holidays']
    : [];

$selectedHolidays =
    is_array(
        $viewData['selectedHolidays']
            ?? null
    )
    ? $viewData['selectedHolidays']
    : [];
/*
|--------------------------------------------------------------------------
| SAFE DATA INITIALIZATION
|--------------------------------------------------------------------------
| These values should come from EventController::calendar().
| The fallbacks prevent undefined-variable warnings.
*/

$month =
    isset($month)
    ? (int) $month
    : (int) date('n');

$year =
    isset($year)
    ? (int) $year
    : (int) date('Y');

$monthName =
    $monthName
    ?? date(
        'F',
        mktime(
            0,
            0,
            0,
            $month,
            1,
            $year
        )
    );

$daysInMonth =
    $daysInMonth
    ?? cal_days_in_month(
        CAL_GREGORIAN,
        $month,
        $year
    );

$dayOfWeek =
    $dayOfWeek
    ?? (int) date(
        'w',
        strtotime(
            sprintf(
                '%04d-%02d-01',
                $year,
                $month
            )
        )
    );

$currentDate =
    $currentDate
    ?? date('Y-m-d');

$selectedDate =
    $selectedDate
    ?? $currentDate;

$prevMonth =
    isset($prevMonth)
    ? (int) $prevMonth
    : (
        $month === 1
        ? 12
        : $month - 1
    );

$prevYear =
    isset($prevYear)
    ? (int) $prevYear
    : (
        $month === 1
        ? $year - 1
        : $year
    );

$nextMonth =
    isset($nextMonth)
    ? (int) $nextMonth
    : (
        $month === 12
        ? 1
        : $month + 1
    );

$nextYear =
    isset($nextYear)
    ? (int) $nextYear
    : (
        $month === 12
        ? $year + 1
        : $year
    );

$events =
    $events
    ?? (
        $viewData['events']
        ?? []
    );

$selectedEvents =
    $selectedEvents
    ?? (
        $viewData['selectedEvents']
        ?? []
    );

$recentEvents =
    $viewData['recentEvents']
    ?? $viewData['recent_events']
    ?? [];

if (
    empty($recentEvents)
    && !empty($events)
) {
    /*
     * The calendar may store events grouped by date.
     * Flatten them into one list for the upcoming-events cards.
     */
    foreach (
        $events as $eventDate => $dateEvents
    ) {
        if (!is_array($dateEvents)) {
            continue;
        }

        /*
         * Supports either:
         * [date => [event, event]]
         * or a single event array.
         */
        $isSingleEvent =
            isset($dateEvents['title']);

        if ($isSingleEvent) {
            $dateEvents = [
                $dateEvents
            ];
        }

        foreach ($dateEvents as $event) {
            if (!is_array($event)) {
                continue;
            }

            if (
                empty($event['event_date'])
            ) {
                $event['event_date'] =
                    $eventDate;
            }

            $recentEvents[] =
                $event;
        }
    }
}

/*
|--------------------------------------------------------------------------
| SORT AND LIMIT UPCOMING EVENTS
|--------------------------------------------------------------------------
*/

usort(
    $recentEvents,
    function (
        array $first,
        array $second
    ): int {
        $firstDate =
            strtotime(
                $first['event_date']
                    ?? $first['date']
                    ?? $first['created_at']
                    ?? '1970-01-01'
            );

        $secondDate =
            strtotime(
                $second['event_date']
                    ?? $second['date']
                    ?? $second['created_at']
                    ?? '1970-01-01'
            );

        return $firstDate
            <=> $secondDate;
    }
);

$upcomingEvents = array_values(
    array_filter(
        $recentEvents,
        function (
            array $event
        ) use (
            $currentDate
        ): bool {
            $eventDate =
                $event['event_date']
                ?? $event['date']
                ?? null;

            if (!$eventDate) {
                return true;
            }

            return date(
                'Y-m-d',
                strtotime($eventDate)
            ) >= $currentDate;
        }
    )
);



$upcomingEvents =
    array_slice(
        $upcomingEvents,
        0,
        4
    );

$canManageEvents =
    in_array(
        $currentRole,
        [
            'Admin',
            'Faculty'
        ],
        true
    );

$selectedDateFormatted =
    date(
        'F d, Y',
        strtotime($selectedDate)
    );

$isSelectedDatePast =
    $selectedDate <
    $currentDate;


?>


<section
    class="<?= htmlspecialchars(
                $pageWrapperClass,
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

    <!-- ======================================
         PAGE INTRODUCTION
    ======================================= -->

    <header class="calendar-page-header">

        <div class="calendar-header-copy">

            <span class="calendar-eyebrow">
                School Calendar
            </span>

            <h1>
                Stay informed about important dates.
            </h1>

            <p>
                View school activities, department events,
                scheduled programs, deadlines, and other
                calendar-based announcements in one place.
            </p>

            <div class="calendar-header-actions">

                <a
                    href="#calendar"
                    class="app-button primary">
                    <i class="fa-solid fa-calendar-days"></i>

                    Open Monthly Calendar
                </a>

                <?php if ($isLoggedIn): ?>

                    <a
                        href="index.php?page=news"
                        class="app-button secondary">
                        <i class="fa-solid fa-bullhorn"></i>

                        Information Hub
                    </a>

                <?php else: ?>

                    <a
                        href="index.php?page=login"
                        class="app-button secondary">
                        <i class="fa-solid fa-right-to-bracket"></i>

                        Sign In
                    </a>

                <?php endif; ?>

            </div>

        </div>

        <div class="calendar-summary-card">

            <span class="calendar-summary-icon">

                <i class="fa-regular fa-calendar-check"></i>

            </span>

            <div>

                <span>
                    Current Schedule
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $monthName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                    <?= (int) $year ?>
                </strong>

                <p>
                    Select a calendar date to view
                    its scheduled events and activities.
                </p>

            </div>

            <div class="calendar-summary-tags">

                <span>
                    <i class="fa-solid fa-circle calendar-tag-today"></i>
                    Today
                </span>

                <span>
                    <i class="fa-solid fa-circle calendar-tag-event"></i>
                    Has Event
                </span>

                <span>
                    <i class="fa-solid fa-star calendar-tag-holiday"></i>
                    Philippine Holiday
                </span>

                <span>
                    <i class="fa-solid fa-circle calendar-tag-selected"></i>
                    Selected
                </span>

            </div>

        </div>

    </header>

    <!-- ======================================
         UPCOMING EVENTS
    ======================================= -->

    <section class="calendar-content-section">

        <div class="calendar-section-heading">

            <div>

                <span class="calendar-eyebrow">
                    Upcoming Activities
                </span>

                <h2>
                    Events to watch
                </h2>

                <p>
                    Review the nearest scheduled school
                    and department activities.
                </p>

            </div>

            <a
                href="#calendar"
                class="calendar-inline-action">
                View Full Calendar

                <i class="fa-solid fa-arrow-down"></i>
            </a>

        </div>

        <?php if (
            empty($upcomingEvents)
        ): ?>

            <div class="calendar-empty-state">

                <span>

                    <i class="fa-regular fa-calendar-xmark"></i>

                </span>

                <h3>
                    No upcoming events
                </h3>

                <p>
                    There are no scheduled activities
                    available at this time.
                </p>

            </div>

        <?php else: ?>

            <div class="calendar-upcoming-grid">

                <?php foreach (
                    $upcomingEvents as $event
                ): ?>

                    <?php

                    $eventTitle =
                        trim(
                            (string) (
                                $event['title']
                                ?? 'Untitled Event'
                            )
                        );

                    $eventDescription =
                        trim(
                            (string) (
                                $event['description']
                                ?? ''
                            )
                        );

                    $eventLocation =
                        trim(
                            (string) (
                                $event['location']
                                ?? 'Location to be announced'
                            )
                        );

                    $eventDate =
                        $event['event_date']
                        ?? $event['date']
                        ?? $selectedDate;

                    $eventTimestamp =
                        strtotime($eventDate);

                    $eventImage =
                        trim(
                            (string) (
                                $event['image_path']
                                ?? ''
                            )
                        );

                    $hasEventImage =
                        $eventImage !== '';

                    ?>

                    <article class="calendar-event-card">

                        <div class="calendar-event-image">

                            <?php if ($hasEventImage): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                                $eventImage,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                    alt="<?= htmlspecialchars(
                                                $eventTitle,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                            <?php else: ?>

                                <span
                                    class="calendar-event-placeholder"
                                    aria-hidden="true">

                                    <i class="fa-regular fa-calendar"></i>

                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="calendar-event-content">

                            <span class="calendar-event-label">
                                School Event
                            </span>

                            <h3>
                                <?= htmlspecialchars(
                                    $eventTitle,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <?php if (
                                $eventDescription !== ''
                            ): ?>

                                <p>
                                    <?= htmlspecialchars(
                                        $eventDescription,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <div class="calendar-event-meta">

                                <span>

                                    <i class="fa-regular fa-clock"></i>

                                    <?= htmlspecialchars(
                                        date(
                                            'F d, Y',
                                            $eventTimestamp
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                                <span>

                                    <i class="fa-solid fa-location-dot"></i>

                                    <?= htmlspecialchars(
                                        $eventLocation,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

    <!-- ======================================
         MONTHLY CALENDAR
    ======================================= -->

    <section
        id="calendar"
        class="calendar-content-section calendar-main-section">

        <div class="calendar-section-heading">

            <div>

                <span class="calendar-eyebrow">
                    Monthly Schedule
                </span>

                <h2>
                    Event calendar
                </h2>

                <p>
                    Select a date to review its activities.
                </p>

            </div>

            <?php if ($canManageEvents): ?>

                <a
                    id="createCalendarEventLink"
                    <?php if (!$isSelectedDatePast): ?>
                    href="index.php?page=postings&type=event&calendar_date=<?= urlencode(
                                                                                $selectedDate
                                                                            ) ?>"
                    <?php endif; ?>
                    class="calendar-add-event-button<?= $isSelectedDatePast
                                                        ? ' is-disabled'
                                                        : '' ?>"
                    data-current-date="<?= htmlspecialchars(
                                            $currentDate,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                    aria-disabled="<?= $isSelectedDatePast
                                        ? 'true'
                                        : 'false' ?>">

                    <i
                        class="fa-solid fa-calendar-plus"
                        aria-hidden="true"></i>

                    <span>
                        <?= $isSelectedDatePast
                            ? 'Past Date'
                            : 'Create Event'
                        ?>
                    </span>

                </a>

            <?php endif; ?>

        </div>

        <div class="calendar-layout">

            <!-- ==============================
                 CALENDAR GRID
            =============================== -->

            <article class="calendar-grid-card">

                <div class="calendar-month-navigation">

                    <a
                        href="index.php?page=calendar&month=<?= (int) $prevMonth ?>&year=<?= (int) $prevYear ?>#calendar"
                        aria-label="Previous month">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>

                    <div>

                        <span>
                            Monthly Calendar
                        </span>

                        <h3>
                            <?= htmlspecialchars(
                                $monthName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                            <?= (int) $year ?>
                        </h3>

                    </div>

                    <a
                        href="index.php?page=calendar&month=<?= (int) $nextMonth ?>&year=<?= (int) $nextYear ?>#calendar"
                        aria-label="Next month">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>

                </div>

                <div class="calendar-week-grid">

                    <?php

                    $dayNames = [
                        'Sun',
                        'Mon',
                        'Tue',
                        'Wed',
                        'Thu',
                        'Fri',
                        'Sat'
                    ];

                    foreach (
                        $dayNames as $dayName
                    ):

                    ?>

                        <div class="calendar-day-name">

                            <?= htmlspecialchars(
                                $dayName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                    <?php endforeach; ?>

                    <?php for (
                        $emptyDay = 0;
                        $emptyDay < $dayOfWeek;
                        $emptyDay++
                    ): ?>

                        <div
                            class="calendar-day-cell empty"
                            aria-hidden="true"></div>

                    <?php endfor; ?>

                    <?php for (
                        $day = 1;
                        $day <= $daysInMonth;
                        $day++
                    ): ?>

                        <?php

                        $cellDate =
                            sprintf(
                                '%04d-%02d-%02d',
                                $year,
                                $month,
                                $day
                            );

                        $isToday =
                            $cellDate ===
                            $currentDate;

                        $isSelected =
                            $cellDate ===
                            $selectedDate;

                        $hasEvent =
                            isset(
                                $events[$cellDate]
                            )
                            && !empty($events[$cellDate]);

                        $cellHolidays =
                            is_array(
                                $holidays[$cellDate]
                                    ?? null
                            )
                            ? $holidays[$cellDate]
                            : [];

                        $hasHoliday =
                            !empty($cellHolidays);

                        $primaryHoliday =
                            $hasHoliday
                            ? $cellHolidays[0]
                            : [];

                        $holidayType =
                            trim(
                                (string) (
                                    $primaryHoliday['holiday_type']
                                    ?? ''
                                )
                            );

                        $holidayTitle =
                            trim(
                                (string) (
                                    $primaryHoliday['title']
                                    ?? 'Holiday'
                                )
                            );

                        $dayClasses = [
                            'calendar-day-cell',
                            'day-cell'
                        ];

                        if ($isToday) {
                            $dayClasses[] =
                                'today';
                        }

                        if ($isSelected) {
                            $dayClasses[] =
                                'selected';
                        }

                        if ($hasEvent) {
                            $dayClasses[] =
                                'has-event';
                        }

                        if ($hasHoliday) {
                            $dayClasses[] =
                                'has-holiday';

                            if ($holidayType !== '') {
                                $dayClasses[] =
                                    'holiday-'
                                    . strtolower(
                                        preg_replace(
                                            '/(?<!^)[A-Z]/',
                                            '-$0',
                                            $holidayType
                                        )
                                            ?? $holidayType
                                    );
                            }
                        }

                        ?>

                        <a
                            href="index.php?page=calendar&month=<?= (int) $month ?>&year=<?= (int) $year ?>&date=<?= urlencode($cellDate) ?>#calendar"
                            class="<?= htmlspecialchars(
                                        implode(
                                            ' ',
                                            $dayClasses
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            data-date="<?= htmlspecialchars(
                                            $cellDate,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                            aria-label="<?= htmlspecialchars(
                                            date(
                                                'F d, Y',
                                                strtotime($cellDate)
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                            <span class="calendar-day-number">
                                <?= (int) $day ?>
                            </span>

                            <?php if ($hasEvent): ?>

                                <span class="calendar-event-indicator">

                                    <i class="fa-solid fa-circle"></i>

                                    Event

                                </span>

                            <?php endif; ?>

                            <?php if ($hasHoliday): ?>

                                <span
                                    class="calendar-holiday-indicator"
                                    title="<?= htmlspecialchars(
                                                $holidayTitle,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                    <i
                                        class="fa-solid fa-star"
                                        aria-hidden="true"></i>

                                    <?= htmlspecialchars(
                                        $holidayTitle,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                            <?php if ($isToday): ?>

                                <span class="calendar-today-label">
                                    Today
                                </span>

                            <?php endif; ?>

                        </a>

                    <?php endfor; ?>

                </div>

            </article>

            <!-- ==============================
                 SELECTED DATE EVENTS
            =============================== -->

            <aside class="calendar-selected-card">

                <div class="calendar-selected-header">

                    <span class="calendar-selected-date-icon">

                        <strong>
                            <?= htmlspecialchars(
                                date(
                                    'd',
                                    strtotime($selectedDate)
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <small>
                            <?= htmlspecialchars(
                                strtoupper(
                                    date(
                                        'M',
                                        strtotime($selectedDate)
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </small>

                    </span>

                    <div>

                        <span>
                            Selected Date
                        </span>

                        <h3>
                            <?= htmlspecialchars(
                                $selectedDateFormatted,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                    </div>

                </div>

                <div
                    id="eventList"
                    class="calendar-selected-events">

                    <?php if (
                        empty($selectedEvents)
                    ): ?>

                        <div class="calendar-no-date-events">

                            <span>

                                <i class="fa-regular fa-calendar"></i>

                            </span>

                            <h4>
                                No scheduled events
                            </h4>

                            <p>
                                Nothing is currently scheduled
                                for this date.
                            </p>

                        </div>

                    <?php else: ?>

                        <?php foreach (
                            $selectedEvents as $event
                        ): ?>

                            <?php

                            $selectedEventTitle =
                                $event['title']
                                ?? 'Untitled Event';

                            $selectedEventType =
                                $event['type']
                                ?? 'School Event';

                            $selectedEventLocation =
                                $event['location']
                                ?? '';

                            $selectedEventDescription =
                                $event['description']
                                ?? '';

                            ?>

                            <article class="calendar-selected-event">

                                <span class="calendar-selected-event-icon">

                                    <i class="fa-solid fa-calendar-check"></i>

                                </span>

                                <div>

                                    <span>
                                        <?= htmlspecialchars(
                                            $selectedEventType,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <h4>
                                        <?= htmlspecialchars(
                                            $selectedEventTitle,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h4>

                                    <?php if (
                                        $selectedEventDescription !== ''
                                    ): ?>

                                        <p>
                                            <?= htmlspecialchars(
                                                $selectedEventDescription,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </p>

                                    <?php endif; ?>

                                    <?php if (
                                        $selectedEventLocation !== ''
                                    ): ?>

                                        <small>

                                            <i class="fa-solid fa-location-dot"></i>

                                            <?= htmlspecialchars(
                                                $selectedEventLocation,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </aside>

        </div>

    </section>


</section>



<script>
    const EVENTS = <?= json_encode(
                        $events,
                        JSON_HEX_TAG |
                            JSON_HEX_APOS |
                            JSON_HEX_AMP |
                            JSON_HEX_QUOT
                    ) ?>;

    const HOLIDAYS = <?= json_encode(
                            $holidays,
                            JSON_HEX_TAG |
                                JSON_HEX_APOS |
                                JSON_HEX_AMP |
                                JSON_HEX_QUOT
                        ) ?>;
</script>