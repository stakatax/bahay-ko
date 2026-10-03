<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/EventService.php';

require_once __DIR__
    . '/../models/CalendarHoliday.php';

require_once __DIR__
    . '/../services/PhilippineHolidayGenerator.php';

class EventController extends BaseController
{
    private EventService $service;

    private CalendarHoliday $calendarHoliday;

    private PhilippineHolidayGenerator $holidayGenerator;

    public function __construct()
    {
        $this->service =
            new EventService();

        $this->calendarHoliday =
            new CalendarHoliday();

        $this->holidayGenerator =
            new PhilippineHolidayGenerator();
    }

    /* ==========================================
       CALENDAR PAGE
    ========================================== */

    public function calendar(): array
    {
        date_default_timezone_set(
            'Asia/Manila'
        );

        $month =
            isset($_GET['month']) &&
            (int) $_GET['month'] >= 1 &&
            (int) $_GET['month'] <= 12
            ? (int) $_GET['month']
            : (int) date('n');

        $year =
            isset($_GET['year']) &&
            (int) $_GET['year'] >= 1970 &&
            (int) $_GET['year'] <= 2100
            ? (int) $_GET['year']
            : (int) date('Y');

        $firstDayOfMonth =
            mktime(
                0,
                0,
                0,
                $month,
                1,
                $year
            );

        $daysInMonth =
            (int) date(
                't',
                $firstDayOfMonth
            );

        $dayOfWeek =
            (int) date(
                'w',
                $firstDayOfMonth
            );

        $monthName =
            date(
                'F',
                $firstDayOfMonth
            );

        $currentDate =
            date('Y-m-d');

        $previousMonthTimestamp =
            strtotime(
                '-1 month',
                $firstDayOfMonth
            );

        $nextMonthTimestamp =
            strtotime(
                '+1 month',
                $firstDayOfMonth
            );

        $previousMonth =
            (int) date(
                'n',
                $previousMonthTimestamp
            );

        $previousYear =
            (int) date(
                'Y',
                $previousMonthTimestamp
            );

        $nextMonth =
            (int) date(
                'n',
                $nextMonthTimestamp
            );

        $nextYear =
            (int) date(
                'Y',
                $nextMonthTimestamp
            );

        $events =
            $this->service
            ->getCalendarEvents(
                $year
            );

        $officialHolidays =
            $this->calendarHoliday
            ->getActiveMapByYear(
                $year
            );

        $generatedHolidays =
            $this->holidayGenerator
            ->generate(
                $year
            );

        $holidays =
            $this->mergeHolidayMaps(
                $generatedHolidays,
                $officialHolidays
            );

        $selectedDate =
            (
                $month === (int) date('n') &&
                $year === (int) date('Y')
            )
            ? $currentDate
            : sprintf(
                '%04d-%02d-01',
                $year,
                $month
            );

        if (
            isset($_GET['date']) &&
            is_string($_GET['date'])
        ) {
            $requestedDate =
                DateTime::createFromFormat(
                    'Y-m-d',
                    $_GET['date']
                );

            if (
                $requestedDate &&
                $requestedDate->format(
                    'Y-m-d'
                ) === $_GET['date']
            ) {
                $selectedDate =
                    $_GET['date'];
            }
        }

        $selectedEvents =
            $events[$selectedDate]
            ?? [];


        $selectedHolidays =
            $holidays[$selectedDate]
            ?? [];



        return [
            'events' =>
            $events,

            'holidays' =>
            $holidays,

            'selectedEvents' =>
            $selectedEvents,

            'selectedHolidays' =>
            $selectedHolidays,

            'month' =>
            $month,

            'year' =>
            $year,

            'monthName' =>
            $monthName,

            'daysInMonth' =>
            $daysInMonth,

            'dayOfWeek' =>
            $dayOfWeek,

            'prevMonth' =>
            $previousMonth,

            'prevYear' =>
            $previousYear,

            'nextMonth' =>
            $nextMonth,

            'nextYear' =>
            $nextYear,

            'currentDate' =>
            $currentDate,

            'selectedDate' =>
            $selectedDate
        ];
    }

    /* ==========================================
   MERGE GENERATED AND OFFICIAL HOLIDAYS
========================================== */

    private function mergeHolidayMaps(
        array $generatedHolidays,
        array $officialHolidays
    ): array {
        $merged = [];

        foreach (
            [
                $generatedHolidays,
                $officialHolidays
            ]
            as $holidayMap
        ) {
            foreach (
                $holidayMap
                as $date => $dateHolidays
            ) {
                if (!is_array($dateHolidays)) {
                    continue;
                }

                foreach ($dateHolidays as $holiday) {
                    if (!is_array($holiday)) {
                        continue;
                    }

                    $title =
                        strtolower(
                            trim(
                                (string) (
                                    $holiday['title']
                                    ?? ''
                                )
                            )
                        );

                    if ($title === '') {
                        continue;
                    }

                    /*
                 * Official holidays are processed last,
                 * replacing provisional versions that
                 * have the same date and title.
                 */
                    $merged[$date][$title] =
                        $holiday;
                }
            }
        }

        foreach ($merged as $date => $dateHolidays) {
            $merged[$date] =
                array_values(
                    $dateHolidays
                );

            usort(
                $merged[$date],
                static function (
                    array $first,
                    array $second
                ): int {
                    return strcmp(
                        (string) (
                            $first['title']
                            ?? ''
                        ),
                        (string) (
                            $second['title']
                            ?? ''
                        )
                    );
                }
            );
        }

        ksort(
            $merged
        );

        return $merged;
    }
}
