<?php

require_once __DIR__
    . '/BaseModel.php';

class CalendarHoliday extends BaseModel
{
    /* ==========================================
       ACTIVE HOLIDAYS BY YEAR
    ========================================== */

    public function getActiveByYear(
        int $year
    ): array {
        $year =
            max(
                2000,
                min(
                    2100,
                    $year
                )
            );

        $startDate =
            sprintf(
                '%04d-01-01',
                $year
            );

        $endDate =
            sprintf(
                '%04d-01-01',
                $year + 1
            );

        $stmt =
            $this->prepare("
                SELECT
                    calendar_holiday_id,
                    holiday_date,
                    title,
                    holiday_type,
                    scope,
                    description,
                    proclamation_reference

                FROM calendar_holiday

                WHERE status = 'Active'
                  AND holiday_date >= ?
                  AND holiday_date < ?

                ORDER BY
                    holiday_date ASC,
                    title ASC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the calendar holiday lookup.'
            );
        }

        $stmt->bind_param(
            'ss',
            $startDate,
            $endDate
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );
    }

    /* ==========================================
       HOLIDAYS GROUPED BY DATE
    ========================================== */

    public function getActiveMapByYear(
        int $year
    ): array {
        $holidayMap = [];

        foreach (
            $this->getActiveByYear(
                $year
            )
            as $holiday
        ) {
            $holidayDate =
                trim(
                    (string) (
                        $holiday['holiday_date']
                        ?? ''
                    )
                );

            if ($holidayDate === '') {
                continue;
            }

            $holidayMap[$holidayDate][] =
                $holiday;
        }

        return $holidayMap;
    }
}
