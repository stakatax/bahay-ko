<?php
    require_once __DIR__ . '/../models/User.php';
    
    class AuthService {
    
        private $user;
    
        public function __construct() {
            $this->user = new User();
        }
    
        public function register($data) {
    
            if (empty($data['studID']) || empty($data['password'])) {
                throw new Exception("Missing required fields");
            }
    
            $role = $this->user->getStudentRole();
    
            if (!$role) {
                throw new Exception("System role not found");
            }
    
            $data['role_id'] = $role['role_id'];
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    
            $success = $this->user->create($data);
    
            if (!$success) {
                throw new Exception("Registration failed. ID might exist.");
            }
    
            return true;
        }
    
        public function login($studID, $password) {
    
            $user = $this->user->findByStudentID($studID);
    
            if (!$user) {
                throw new Exception("User ID not found.");
            }
    
            if (!password_verify($password, $user['password'])) {
                throw new Exception("Incorrect password.");
            }
    
            return $user;
        }
    }