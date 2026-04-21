<?php
$page = $_GET['page'] ?? 'home';

$role = $role ?? 'Faculty';

// whitelist (important, don't skip this)
$allowedPages = ['home', 'about', 'contact', 'academic', 'news', 'calendar'];

if (!in_array($page, $allowedPages)) {
  $page = 'home';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>OLSCO Landing</title>
  <link rel="stylesheet" href="Assets/css/index.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

  <?php if ($page === 'about'): ?>
    <link rel="stylesheet" href="Assets/css/about.css" />
  <?php endif; ?>
  <?php if ($page === 'academic'): ?>
    <link rel="stylesheet" href="Assets/css/academic.css" />
  <?php endif; ?>
  <?php if ($page === 'news'): ?>
    <link rel="stylesheet" href="Assets/css/news.css" />
  <?php endif; ?>
  <?php if ($page === 'contact'): ?>
    <link rel="stylesheet" href="Assets/css/contact.css" />
  <?php endif; ?>
  <?php if ($page === 'calendar'): ?>
    <link rel="stylesheet" href="Assets/css/calendar.css" />
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

  <?php if ($page === 'academic'): ?>
    <script src="Assets/js/academic.js"></script>
  <?php endif; ?>
  <?php if ($page === 'calendar'): ?>
    <script src="Assets/js/calendar.js"></script>
  <?php endif; ?>
  <?php if ($page === 'news'): ?>
    <script src="Assets/js/news.js"></script>
  <?php endif; ?>

</body>

</html>