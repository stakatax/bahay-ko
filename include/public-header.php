<?php

$publicNavigation = [
    [
        'page' => 'home',
        'label' => 'Home'
    ],
    [
        'page' => 'academic',
        'label' => 'Academics'
    ],
    [
        'page' => 'about',
        'label' => 'About'
    ],
    [
        'page' => 'contact',
        'label' => 'Contact'
    ]
];

$currentPublicPage =
    (string) (
        $page
        ?? 'home'
    );

?>

<header class="public-header">

    <div class="public-header-inner">

        <a
            href="index.php?page=home"
            class="public-brand"
            aria-label="OLSHCO Digital Hub home">

            <img
                src="Assets/Images/ulsco.png"
                alt="OLSHCO logo">

            <span>

                <strong>OLSHCO</strong>

                <small>Digital Hub</small>

            </span>

        </a>

        <button
            type="button"
            class="public-menu-toggle"
            data-public-menu-toggle
            aria-controls="publicNavigation"
            aria-expanded="false"
            aria-label="Open navigation menu">

            <i
                class="fa-solid fa-bars"
                aria-hidden="true"></i>

            <span>Menu</span>

        </button>

        <div
            class="public-navigation-panel"
            id="publicNavigation"
            data-public-navigation>

            <nav
                class="public-navigation"
                aria-label="Public navigation">

                <?php foreach (
                    $publicNavigation
                    as $navigationItem
                ): ?>

                    <?php
                    $isActive =
                        $currentPublicPage ===
                        $navigationItem['page'];
                    ?>

                    <a
                        href="index.php?page=<?= urlencode(
                                                    $navigationItem['page']
                                                ) ?>"
                        class="<?= $isActive
                                    ? 'active'
                                    : ''
                                ?>"
                        <?= $isActive
                            ? 'aria-current="page"'
                            : ''
                        ?>>

                        <?= htmlspecialchars(
                            $navigationItem['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                <?php endforeach; ?>

            </nav>

            <div class="public-header-actions">

                <a
                    href="index.php?page=login"
                    class="public-sign-in">

                    <i
                        class="fa-solid fa-right-to-bracket"
                        aria-hidden="true"></i>

                    <span>Sign In</span>

                </a>

                <a
                    href="index.php?page=register"
                    class="public-register">

                    <i
                        class="fa-solid fa-user-plus"
                        aria-hidden="true"></i>

                    <span>Create Account</span>

                </a>

            </div>

        </div>

    </div>

</header>