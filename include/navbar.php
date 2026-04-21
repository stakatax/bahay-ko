<?php
// Optional: detect current page for active state
$current = basename($_SERVER['PHP_SELF']);

$currentPage = $page ?? 'dashboard';
$nav = [
    'home' => [
        'label' => 'Home',
        'roles' => ['Student', 'Faculty', 'Admin']
    ],
    'about' => [
        'label' => 'About School',
        'roles' => ['Student', 'Faculty', 'Admin']
    ],
    'calendar' => [
        'label' => 'Calendar & Events',
        'roles' => ['Student', 'Faculty', 'Admin']
    ],
    'academic' => [
        'label' => 'Academic Offerings',
        'roles' => ['Student', 'Faculty', 'Admin']
    ],
    'news' => [
        'label' => 'News',
        'roles' => ['Student', 'Faculty', 'Admin']
    ],
    'contact' => [
        'label' => 'Contacts',
        'roles' => ['Student', 'Faculty', 'Admin'] // For global view
    ],
    'postings' => [
        'label' => 'Postings',
        'roles' => ['Faculty', 'Admin'] // Only for faculty and admin
    ],

];
?>

<div class="nav-wrapper">
    <div class="navbar">

        <img src="Assets/Images/ulsco.png" class="logo">

        <ul class="nav-links">
            <?php foreach ($nav as $key => $item): ?>
                <?php if (in_array($role, $item['roles'])): ?>
                    <li>
                        <a href="?page=<?php echo $key; ?>"
                            class="nav-link<?php echo $currentPage === $key ? ' active' : ''; ?>">
                            <span><?php echo $item['label']; ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

        <button id="menuBtn" class="menu-btn">☰</button>
    </div>

    <!-- MOBILE MENU -->
    <div id="mobileMenu" class="mobile-menu">
        <?php foreach ($nav as $key => $item): ?>
            <?php if (in_array($role, $item['roles'])): ?>
                <div>
                    <a style="text-decoration: none; color: #000;" href="?page=<?php echo $key; ?>"
                        class="nav-link<?php echo $currentPage === $key ? ' active' : ''; ?>">
                        <span><?php echo $item['label']; ?></span>
                    </a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>