<?php
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/ContentAudienceService.php';

class EventService
{

    private $model;

    public function __construct()
    {
        $this->model = new Event();
    }



    public function getCalendarEvents(
        int $year
    ): array {
        $events = [];

        // Fetch published candidates, then apply the same audience policy as the feed.
        $result = $this->model->getByYear($year, [], true);
        $rows = (new ContentAudienceService())->filterForUser(
            'event',
            'event_id',
            $result->fetch_all(MYSQLI_ASSOC),
            (int) ($_SESSION['user_id'] ?? 0)
        );
        $eventMap = array_column($rows, null, 'event_id');

        uasort(
            $eventMap,
            static function (
                array $first,
                array $second
            ): int {
                $dateComparison =
                    strcmp(
                        (string) (
                            $first['event_date']
                            ?? ''
                        ),
                        (string) (
                            $second['event_date']
                            ?? ''
                        )
                    );

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                return (int) (
                    $first['event_id']
                    ?? 0
                )
                    <=>
                    (int) (
                        $second['event_id']
                        ?? 0
                    );
            }
        );

        foreach ($eventMap as $row) {
            $eventTimestamp =
                strtotime(
                    (string) (
                        $row['event_date']
                        ?? ''
                    )
                );

            if ($eventTimestamp === false) {
                continue;
            }

            $date =
                date(
                    'Y-m-d',
                    $eventTimestamp
                );

            $events[$date][] = [
                'event_id' =>
                (int) (
                    $row['event_id']
                    ?? 0
                ),

                'title' =>
                $row['title']
                    ?? 'Untitled Event',

                'type' =>
                'School Event',

                'description' =>
                $row['description']
                    ?? '',

                'location' =>
                $row['location']
                    ?? '',

                'image_path' =>
                $row['image_path']
                    ?? null,

                'event_date' =>
                $row['event_date']
                    ?? null,

                'end_date' =>
                $row['end_date']
                    ?? null
            ];
        }

        return $events;
    }
}
