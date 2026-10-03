<?php

class PhilippineHolidayGenerator
{
    /* ==========================================
       AUTOMATIC HOLIDAYS BY YEAR
    ========================================== */

    public function generate(
        int $year
    ): array {
        $year =
            max(
                1970,
                min(
                    2100,
                    $year
                )
            );

        $easterSunday =
            $this->calculateEasterSunday(
                $year
            );

        $holidays = [
            $this->holiday(
                "{$year}-01-01",
                "New Year's Day",
                'Regular'
            ),

            $this->holiday(
                $easterSunday
                    ->modify('-3 days')
                    ->format('Y-m-d'),
                'Maundy Thursday',
                'Regular'
            ),

            $this->holiday(
                $easterSunday
                    ->modify('-2 days')
                    ->format('Y-m-d'),
                'Good Friday',
                'Regular'
            ),

            $this->holiday(
                $easterSunday
                    ->modify('-1 day')
                    ->format('Y-m-d'),
                'Black Saturday',
                'SpecialNonWorking'
            ),

            $this->holiday(
                "{$year}-04-09",
                'Araw ng Kagitingan',
                'Regular'
            ),

            $this->holiday(
                "{$year}-05-01",
                'Labor Day',
                'Regular'
            ),

            $this->holiday(
                "{$year}-06-12",
                'Independence Day',
                'Regular'
            ),

            $this->holiday(
                "{$year}-08-21",
                'Ninoy Aquino Day',
                'SpecialNonWorking'
            ),

            $this->holiday(
                date(
                    'Y-m-d',
                    strtotime(
                        "last monday of august {$year}"
                    )
                ),
                'National Heroes Day',
                'Regular'
            ),

            $this->holiday(
                "{$year}-11-01",
                "All Saints' Day",
                'SpecialNonWorking'
            ),

            $this->holiday(
                "{$year}-11-30",
                'Bonifacio Day',
                'Regular'
            ),

            $this->holiday(
                "{$year}-12-08",
                'Feast of the Immaculate Conception of Mary',
                'SpecialNonWorking'
            ),

            $this->holiday(
                "{$year}-12-25",
                'Christmas Day',
                'Regular'
            ),

            $this->holiday(
                "{$year}-12-30",
                'Rizal Day',
                'Regular'
            ),

            $this->holiday(
                "{$year}-12-31",
                'Last Day of the Year',
                'SpecialNonWorking'
            )
        ];

        $holidayMap = [];

        foreach ($holidays as $holiday) {
            $holidayMap[$holiday['holiday_date']][] = $holiday;
        }

        ksort(
            $holidayMap
        );

        return $holidayMap;
    }

    /* ==========================================
       PROVISIONAL HOLIDAY RECORD
    ========================================== */

    private function holiday(
        string $date,
        string $title,
        string $type
    ): array {
        return [
            'calendar_holiday_id' =>
            0,

            'holiday_date' =>
            $date,

            'title' =>
            $title,

            'holiday_type' =>
            $type,

            'scope' =>
            'Nationwide',

            'description' =>
            'Philippine holiday.',

            'proclamation_reference' =>
            '',

            'is_provisional' =>
            true
        ];
    }

    /* ==========================================
       GREGORIAN EASTER CALCULATION
    ========================================== */

    private function calculateEasterSunday(
        int $year
    ): DateTimeImmutable {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);

        $h =
            (
                19 * $a +
                $b -
                $d -
                $g +
                15
            ) % 30;

        $i = intdiv($c, 4);
        $k = $c % 4;

        $l =
            (
                32 +
                2 * $e +
                2 * $i -
                $h -
                $k
            ) % 7;

        $m =
            intdiv(
                $a +
                    11 * $h +
                    22 * $l,
                451
            );

        $month =
            intdiv(
                $h +
                    $l -
                    7 * $m +
                    114,
                31
            );

        $day =
            (
                $h +
                $l -
                7 * $m +
                114
            ) % 31 + 1;

        return new DateTimeImmutable(
            sprintf(
                '%04d-%02d-%02d',
                $year,
                $month,
                $day
            ),
            new DateTimeZone(
                'Asia/Manila'
            )
        );
    }
}
