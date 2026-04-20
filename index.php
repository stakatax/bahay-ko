<?php
$page = $_GET['page'] ?? 'home';

$role = $role ?? 'Student';

// whitelist (important, don't skip this)
$allowedPages = ['home', 'about', 'contact', 'academic'];

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

  <link rel="stylesheet" href="Assets/css/academic.css">

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

  <script src="Assets/js/navbar.js">
    nice
  </script>

</body>

</html>