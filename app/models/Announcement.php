<?php
    require_once 'BaseModel.php';

    class Announcement extends BaseModel {

        public function create($title, $content, $user_id) {
    
            $stmt = $this->conn->prepare("
                INSERT INTO announcements
                (title, content, type, created_at, status, user_id)
                VALUES (?, ?, 'announcement', NOW(), 'active', ?)
            ");
    
            $stmt->bind_param("ssi", $title, $content, $user_id);

            return $stmt->execute();
        }
    
        public function getRecent($limit = 5) {
    
            $stmt = $this->conn->prepare("
                SELECT title, content, created_at
                FROM announcements
                WHERE status = 'active'
                ORDER BY created_at DESC
                LIMIT ?
            ");
    
            $stmt->bind_param("i", $limit);
            $stmt->execute();
    
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }