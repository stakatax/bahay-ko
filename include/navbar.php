<?php
// Optional: detect current page for active state
$current = basename($_SERVER['PHP_SELF']);
?>

<div class="nav-wrapper">
    <div class="navbar">

        <img src="Assets/Images/ulsco.png" class="logo">

        <ul class="nav-links">
            <li class="<?= ($current == 'index.php') ? 'active' : '' ?>">HOME</li>
            <li class="<?= ($current == 'about.php') ? 'active' : '' ?>">OUR SCHOOL</li>
            <li>ACADEMIC OFFERINGS</li>
            <li>EVENTS</li>
            <li>NEWS</li>
            <li>CONTACT</li>
            <li>LOGIN</li>
        </ul>

        <button id="menuBtn" class="menu-btn">☰</button>
    </div>

    <!-- MOBILE MENU -->
    <div id="mobileMenu" class="mobile-menu">
        <div>HOME</div>
        <div>OUR SCHOOL</div>
        <div>ACADEMIC OFFERINGS</div>
        <div>EVENTS</div>
        <div>NEWS</div>
        <div>CONTACT</div>
        <div>LOGIN</div>
    </div>
</div>