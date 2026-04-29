<?php
    require_once __DIR__ . '/../models/Event.php';

    class EventService {

        private $model;

        public function __construct() {
            $this->model = new Event();
        }

        public function storeEvent($title, $date, $user_id) {
            return $this->model->create($title, $date, $user_id);
        }

        public function getCalendarEvents($year) {

            $events = [];

            for ($month = 1; $month <= 12; $month++) {

                $date = strtotime("$year-$month-01");

                while (date('N', $date) != 5) { // 5 = Friday
                    $date = strtotime("+1 day", $date);
                }

                $formatted = date('Y-m-d', $date);

                $events[$formatted][] = [
                    "title" => "FIRST FRIDAY MASS",
                    "type"  => "Church Event"
                ];
            }

            /* 🔹 DATABASE EVENTS */
            $result = $this->model->getByYear($year);

            while ($row = $result->fetch_assoc()) {
            
                $date = date(
                    'Y-m-d',
                    strtotime($row['event_date'])
                );
            
                $events[$date][] = [
                    "title" => $row['title'],
                    "type"  => "Event"
                ];
            }

            return $events;
        }
    }