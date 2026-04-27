<?php
    require_once 'BaseModel.php';

    class Document extends BaseModel {

        public function create($fileName, $fileType, $user_id) {

            $stmt = $this->conn->prepare("
                INSERT INTO documents
                (file_name, file_type, created_at, status, user_id)
                VALUES (?, ?, NOW(), 'active', ?)
            ");

            $stmt->bind_param("ssi", $fileName, $fileType, $user_id);

            return $stmt->execute();
        }

        public function getRecent($limit) {

            $stmt = $this->conn->prepare("
                SELECT file_name, file_type, created_at
                FROM documents
                WHERE status = 'active'
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->bind_param("i", $limit);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
}