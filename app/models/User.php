<?php
    require_once 'BaseModel.php';
    
    class User extends BaseModel {
    
        public function findByStudentID($studID) {
    
            $stmt = $this->conn->prepare("
                SELECT u.*, r.role_prefix
                FROM user u
                JOIN role r ON u.role_id = r.role_id
                WHERE u.studID = ?
            ");
    
            $stmt->bind_param("s", $studID);
            $stmt->execute();
    
            return $stmt->get_result()->fetch_assoc();
        }
    
        public function create($data) {
    
            $stmt = $this->conn->prepare("
                INSERT INTO user
                (studID, first_name, middle_name, last_name,
                 email, password, gender, age,
                 role_id, department_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
    
            $stmt->bind_param(
                "sssssssiii",
                $data['studID'],
                $data['first_name'],
                $data['middle_name'],
                $data['last_name'],
                $data['email'],
                $data['password'],
                $data['gender'],
                $data['age'],
                $data['role_id'],
                $data['department_id']
            );
    
            return $stmt->execute();
        }
    
        public function getStudentRole() {
    
            $role_prefix = "Student";
    
            $stmt = $this->conn->prepare("
                SELECT role_id FROM role WHERE role_prefix = ?
            ");
    
            $stmt->bind_param("s", $role_prefix);
            $stmt->execute();
    
            return $stmt->get_result()->fetch_assoc();
        }
    }