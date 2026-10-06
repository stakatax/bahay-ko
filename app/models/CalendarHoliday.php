<?php

require_once __DIR__
    . '/BaseModel.php';

class CalendarHoliday extends BaseModel
{
    private bool $sharedHolidayCache = false;

    public function __construct(?mysqli $connection = null)
    {
        parent::__construct($connection);
        $this->sharedHolidayCache = $connection === null;
    }

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

        if ($this->sharedHolidayCache) {
            require_once __DIR__ . '/PublicCatalogCache.php';
            $cache = PublicCatalogCache::directory('calendar-holidays', 300, $year);
            return $cache->remember(fn(): array => $this->loadActiveByYear($year));
        }
        return $this->loadActiveByYear($year);
    }

    private function loadActiveByYear(int $year): array
    {
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

        try {
            return $stmt
                ->get_result()
                ->fetch_all(
                    MYSQLI_ASSOC
                );
        } finally {
            $stmt->close();
        }
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
