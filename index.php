<?php

  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  require_once __DIR__ . '/app/controllers/EventController.php';
  require_once __DIR__ . '/app/controllers/PostController.php';
  require_once __DIR__ . '/app/controllers/AuthController.php';

  $page = $_GET['page'] ?? 'home';

  $allowedPages = ['home', 'about', 'contact', 'academic', 'news', 'calendar', 'postings', 'login', 'register', 'dashboard'];

  $role = $_SESSION['role'] ?? 'Guest';
  $data = [];
  $title = 'OLSCO Landing';
  $pageCSS = null;
  $pageJS = null;

  switch ($page) {
      case 'login_action':
        (new AuthController())->login();
        exit;

      case 'register_action':
        (new AuthController())->register();
        exit;

      case 'logout':
        (new AuthController())->logout();
        exit;

      case 'calendar':
          $data = (new EventController())->calendar();
          $title = 'Event Calendar';
          $pageCSS = 'calendar.css';
          $pageJS = 'calendar.js';
          break;

      case 'news':
          $data = (new PostController())->news();
          $title = 'News & Updates';
          $pageCSS = 'news.css';
          $pageJS = 'news.js';
          break;

      case 'postings':
          $title = 'Create Post';
          $pageCSS = 'posting.css';
          $pageJS = 'posting.js';
          break;
      
      case 'post_store':
        (new PostController())->store();
        exit;

      case 'event_store':
        (new EventController())->store();
        exit;

      case 'academic':
          $title = 'Academics';
          $pageCSS = 'academic.css';
          $pageJS = 'academic.js';
          break;

      case 'about':
          $title = 'About Us';
          $pageCSS = 'about.css';
          break;

      case 'contact':
          $title = 'Contact';
          $pageCSS = 'contact.css';
          break;

      case 'login':
          $title = 'Login';
          $pageCSS = 'login.css';
          break;

      case 'register':
          $title = 'Register';
          $pageCSS = 'register.css';
          break;

      case 'dashboard':
          $title = 'Dashboard';
          $pageCSS = 'dashboard.css';
          break;

      default:
          // home
          break;
  }

  if (!in_array($page, $allowedPages)) {
      $page = 'home';
  }

  if (!empty($data)) {
      extract($data);
  }
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $title ?? 'OLSCO Landing' ?></title>

  <link rel="stylesheet" href="Assets/css/index.css" />

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <?php if (!empty($pageCSS)): ?>
      <link rel="stylesheet" href="Assets/css/<?= $pageCSS ?>">
  <?php endif; ?>

</head>

<body>

  <div class="fullscreen">
    <div class="gradient-frame">
      <div class="main-card">

        <?php include 'include/navbar.php'; ?>

        <?php include __DIR__ . '/pages/' . $page . '.php'; ?>

      </div>
    </div>
  </div>

  <script src="Assets/js/navbar.js"></script>

  <!-- PAGE JS -->
  <?php if ($pageJS): ?>
      <script src="Assets/js/<?= $pageJS ?>"></script>
  <?php endif; ?>

</body>

</html>