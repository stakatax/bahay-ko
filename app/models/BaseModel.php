<?php
    class BaseModel {

        protected $conn;

        public function __construct() {

            require __DIR__ . '/../../config/dbconnect.php';

            $this->conn = $conn;
        }
    }