<?php
    require_once 'BaseModel.php';

    class Event extends BaseModel {

        public function create($title, $date, $user_id)
            {
                $stmt = $this->conn->prepare("
                    INSERT INTO events
                    (title, event_date, created_at, status, user_id)
                    VALUES (?, ?, NOW(), 'active', ?)
                ");
            
                $stmt->bind_param("ssi", $title, $date, $user_id);
            
                return $stmt->execute();
            }

        // Used by Calendar
        public function getByYear($year) {
            $startDate = "$year-01-01";
            $endDate   = "$year-12-31";

            $stmt = $this->conn->prepare("
                SELECT event_id, title, event_date
                FROM events
                WHERE status = 'active'
                AND event_date BETWEEN ? AND ?
                ORDER BY event_date ASC
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();

            return $stmt->get_result();
        }

        // Recent Events
        public function getRecent($limit = 10) {

            $stmt = $this->conn->prepare("
                SELECT event_id, title, event_date
                FROM events
                WHERE status = 'active'
                ORDER BY event_date DESC
                LIMIT ?
            ");

            $stmt->bind_param("i", $limit);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }