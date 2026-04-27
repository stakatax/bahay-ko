<?php
    require_once 'BaseController.php';
    require_once __DIR__ . '/../services/AuthService.php';

    class AuthController extends BaseController {

        private $service;

        public function __construct() {
            $this->service = new AuthService();
        }

        public function login() {
            try {
                $user = $this->service->login(
                    $_POST['studentID'],
                    $_POST['password']
                );

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role_prefix'];
                $_SESSION['name'] = $user['first_name'] . " " . $user['last_name'];

                $this->log(
                    "LOGIN", 
                    "User {$user['first_name']} {$user['last_name']} logged in successfully"
                );

                if ($user['role_prefix'] === 'Admin') {
                    $this->redirect('index.php?page=dashboard');
                } else {
                    $this->redirect('index.php?page=home');
                }
            } catch (Exception $e) {
                $this->redirect("index.php?page=login&error=" . urlencode($e->getMessage()));
            }
        }

        public function register() {

            try {

                $this->service->register([
                    'studID' => $_POST['studentID'],
                    'first_name' => $_POST['first_name'],
                    'middle_name' => $_POST['middle_name'],
                    'last_name' => $_POST['last_name'],
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'gender' => $_POST['gender'],
                    'age' => $_POST['age'],
                    'department_id' => $_POST['department']
                ]);
                
                $this->log(
                    "REGISTER_ACCOUNT",
                    "New user Registered: {$_POST['first_name']} {$_POST['last_name']}"
                );

                $this->redirect("index.php?page=login&success=Registration successful!");

            } catch (Exception $e) {
                $this->redirect("index.php?page=register&error=" . urlencode($e->getMessage()));
            }
        }

        public function logout() {
            $name = $_SESSION['name'] ?? 'Unknown user';

            $this->log(
                "LOGOUT", 
                "User {$name} logged out"
            );

            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            $this->redirect('index.php?page=home');
        }
    }