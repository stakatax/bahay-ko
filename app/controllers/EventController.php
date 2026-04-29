<?php
    require_once 'BaseController.php';
    require_once __DIR__ . '/../services/EventService.php';

    class EventController extends BaseController {

        private $service;

        public function __construct() {
            $this->service = new EventService();
        }

        /**
         * 📅 CALENDAR PAGE
         */
        public function calendar() {
            date_default_timezone_set('Asia/Manila');
            /* SAFE MONTH/YEAR */
            $month = isset($_GET['month']) 
                && $_GET['month'] >= 1 
                && $_GET['month'] <= 12
                ? (int)$_GET['month']
                : date('n');

            $year = isset($_GET['year']) 
                && $_GET['year'] >= 1970 
                && $_GET['year'] <= 2100
                ? (int)$_GET['year']
                : date('Y');

            $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
            $daysInMonth = date('t', $firstDayOfMonth);
            $dayOfWeek = date('w', $firstDayOfMonth);
            $monthName = date('F', $firstDayOfMonth);

            $currentDate = date('Y-m-d');

            $prev = strtotime("-1 month", $firstDayOfMonth);
            $next = strtotime("+1 month", $firstDayOfMonth);

            $prevMonth = date('n', $prev);
            $prevYear = date('Y', $prev);

            $nextMonth = date('n', $next);
            $nextYear = date('Y', $next);

            $events = $this->service->getCalendarEvents($year);

            $selectedDate = isset($_GET['date'])
                ? date('Y-m-d', strtotime($_GET['date']))
                : $currentDate;

            $selectedEvents = $events[$selectedDate] ?? [];

            $recentEvents = $this->service->getCalendarEvents($year);

            return [
                'events' => $events,
                'selectedEvents' => $selectedEvents,
                'month' => $month,
                'year' => $year,
                'monthName' => $monthName,
                'daysInMonth' => $daysInMonth,
                'dayOfWeek' => $dayOfWeek,
                'prevMonth' => $prevMonth,
                'prevYear' => $prevYear,
                'nextMonth' => $nextMonth,
                'nextYear' => $nextYear,
                'currentDate' => $currentDate,
                'selectedDate' => $selectedDate,
            ];
        }

        public function store() {
            header('Content-Type: application/json');

            $input = json_decode(file_get_contents("php://input"), true);
            $date = $input['date'] ?? null;
            $title = $input['title'] ?? null;
            if (!$date || !$title) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing data'
                ]);
                return;
            }

            $user_id = $_SESSION['user_id'] ?? null;
            $result = $this->service->storeEvent($title, $date, $user_id);
            if ($result) {
                $this->log("POST_EVENT", "User created event: {$title}");
                echo json_encode([
                    'status' => 'success'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to save event'
                ]);
            }
        }
}