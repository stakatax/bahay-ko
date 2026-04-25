<?php
// Optional: detect current page for active state
$current = basename($_SERVER['PHP_SELF']);

$currentPage = $page ?? 'dashboard';
$nav = [
    'dashboard' => [
        'label' => 'Dashboard',
        'roles' => ['Admin']
    ],
    'home' => [
        'label' => 'Home',
        'roles' => ['Guest', 'Student', 'Faculty']
    ],
    'about' => [
        'label' => 'About School',
        'roles' => ['Guest', 'Student', 'Faculty', 'Admin']
    ],
    'calendar' => [
        'label' => 'Calendar & Events',
        'roles' => ['Student', 'Faculty', 'Admin']
    ],
    'academic' => [
        'label' => 'Academic Offerings',
        'roles' => ['Guest', 'Student', 'Faculty', 'Admin']
    ],
    'news' => [
        'label' => 'News',
        'roles' => ['Student', 'Faculty', 'Admin']
    ],
    'contact' => [
        'label' => 'Contacts',
        'roles' => ['Guest', 'Student', 'Faculty', 'Admin']
    ],
    'postings' => [
        'label' => 'Postings',
        'roles' => ['Faculty', 'Admin'] 
    ],
    'login' => [
        'label' => 'Sign In',
        'roles' => ['Guest']
    ],
    'register' => [
        'label' => 'Sign Up',
        'roles' => ['Guest']
    ],
    'logout' => [
        'label' => 'Logout',
        'roles' => ['Student', 'Faculty', 'Admin'],
        'action' => 'logout'
    ]

];
?>

<div class="nav-wrapper">
    <div class="navbar">

        <img src="Assets/Images/ulsco.png" class="logo">

        <ul class="nav-links">
            <?php foreach ($nav as $key => $item): ?>
                <?php if (in_array($role, $item['roles'])): ?>
                    <li>
                        <?php if ($key === 'logout'): ?>
                            <a href="config/logout.php" class="nav-link">
                                <span><?php echo $item['label']; ?></span>
                            </a>
                        <?php else: ?>
                            <a href="?page=<?php echo $key; ?>"
                                class="nav-link<?php echo $currentPage === $key ? ' active' : ''; ?>">
                                <span><?php echo $item['label']; ?></span>
                            </a>
                        <?php endif; ?>
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