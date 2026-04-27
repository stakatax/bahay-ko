<?php
    require_once __DIR__ . '/../../config/dbconnect.php';
    require_once __DIR__ . '/../../config/logging.php';

    class BaseController {

        protected function view($view, $data = []) {

            if (!empty($data)) {
                extract($data);
            }

            require __DIR__ . '/../../pages/' . $view . '.php';
        }

        protected function log($action, $description) {
            global $conn;

            logActivity($conn, $action, $description);
        }

        protected function redirect($url) {

            header("Location: $url");
            exit;
        }
    }