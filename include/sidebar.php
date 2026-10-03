<?php

$currentPage =
    $page
    ?? ($_GET['page'] ?? 'home');

if ($currentPage === 'news') {
    $currentPage = 'home';
}

$currentRole =
    $_SESSION['role']
    ?? 'Guest';

$profilePhoto =
    trim(
        (string) (
            $_SESSION['profile_photo']
            ?? ''
        )
    );



$displayName =
    trim(
        (string) (
            $_SESSION['name']
            ?? 'User'
        )
    );

$displayNameParts =
    preg_split(
        '/\s+/u',
        $displayName,
        -1,
        PREG_SPLIT_NO_EMPTY
    )
    ?: [];

$sidebarInitials =
    mb_strtoupper(
        mb_substr(
            (string) (
                $displayNameParts[0]
                ?? 'U'
            ),
            0,
            1
        )
            .
            (
                count($displayNameParts) > 1
                ? mb_substr(
                    (string) end(
                        $displayNameParts
                    ),
                    0,
                    1
                )
                : ''
            )
    );

$navigationGroups = [
    [
        'label' => 'Main',
        'items' => [
            [
                'page' => 'home',
                'icon' => 'fa-solid fa-house',
                'label' => 'Home',
                'roles' => [
                    'Guest',
                    'Admin',
                    'Faculty',
                    'Student',
                    'Parent'
                ]
            ],

            [
                'page' =>
                'student_profile',

                'icon' =>
                'fa-solid fa-wand-magic-sparkles',

                'label' =>
                'My Interests',

                'roles' => [
                    'Student'
                ]
            ],

            [
                'page' => 'dashboard',
                'icon' => 'fa-solid fa-chart-line',
                'label' => 'Dashboard',
                'roles' => [
                    'Admin'
                ]
            ],
            [
                'page' => 'calendar',
                'icon' => 'fa-solid fa-calendar-days',
                'label' => 'Events',
                'roles' => [
                    'Admin',
                    'Faculty',
                    'Student',
                    'Parent'
                ]
            ],

            [
                'page' =>
                'notifications',

                'icon' =>
                'fa-regular fa-bell',

                'label' =>
                'Notifications',

                'badge' =>
                (int) (
                    $globalUnreadNotificationCount
                    ?? 0
                ),

                'roles' => [
                    'Admin',
                    'Faculty',
                    'Student',
                    'Parent'
                ]
            ],
        ]
    ],
    [
        'label' => 'School',
        'items' => [
            [
                'page' => 'academic',
                'icon' => 'fa-solid fa-graduation-cap',
                'label' => 'Academics',
                'roles' => [
                    'Guest',
                    'Admin',
                    'Faculty',
                    'Student',
                    'Parent'
                ]
            ],
            [
                'page' => 'about',
                'icon' => 'fa-solid fa-school',
                'label' => 'About School',
                'roles' => [
                    'Guest',
                    'Admin',
                    'Faculty',
                    'Student',
                    'Parent'
                ]
            ],
            [
                'page' => 'contact',
                'icon' => 'fa-solid fa-address-book',
                'label' => 'Contact',
                'roles' => [
                    'Guest',
                    'Admin',
                    'Faculty',
                    'Student',
                    'Parent'
                ]
            ]
        ]
    ],
    [
        'label' =>
        'Content Management',

        'items' => [
            [
                'page' =>
                'postings',

                'icon' =>
                'fa-solid fa-pen-to-square',

                'label' =>
                'Create Post',

                'roles' => [
                    'Admin',
                    'Faculty'
                ]
            ],
            [
                'page' =>
                'content_workspace',

                'icon' =>
                'fa-solid fa-layer-group',

                'label' =>
                'Content Workspace',

                'roles' => [
                    'Admin',
                    'Faculty'
                ]
            ],
            [
                'page' =>
                'department_analytics',

                'icon' =>
                'fa-solid fa-chart-column',

                'label' =>
                'Department Analytics',

                'roles' => [
                    'Faculty'
                ]
            ]
        ]
    ],
    [
        'label' =>
        'Administration',

        'items' => [

            [
                'page' =>
                'manage_users',

                'icon' =>
                'fa-solid fa-users-gear',

                'label' =>
                'Manage Users',

                'roles' => [
                    'Admin'
                ]
            ],


            [
                'page' =>
                'academic_management',

                'icon' =>
                'fa-solid fa-sitemap',

                'label' =>
                'Academic Structure',

                'roles' => [
                    'Admin'
                ]
            ],

            [
                'page' =>
                'student_profile_management',

                'icon' =>
                'fa-solid fa-clipboard-question',

                'label' =>
                'Student Profiles',

                'roles' => [
                    'Admin'
                ]
            ],








            [
                'page' =>
                'account_approvals',

                'icon' =>
                'fa-solid fa-user-check',

                'label' =>
                'Account Approvals',

                'badge' =>
                (int) (
                    $globalPendingRegistrationCount
                    ?? 0
                ),

                'roles' => [
                    'Admin'
                ]
            ]
        ]
    ]
];



function sidebarUserCanAccess(
    string $role,
    array $allowedRoles
): bool {
    return in_array(
        $role,
        $allowedRoles,
        true
    );
}

?>

<header class="app-mobile-header">
    <button
        type="button"
        id="sidebarMobileToggle"
        class="sidebar-mobile-toggle"
        aria-controls="appSidebar"
        aria-expanded="false"
        aria-label="Open navigation menu">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>
    <a class="app-mobile-brand" href="index.php?page=home" aria-label="OLSHCO Digital Hub home">
        <img src="Assets/Images/ulsco.png" alt="" width="36" height="36">
        <span><strong>OLSHCO</strong><small>Digital Hub</small></span>
    </a>

</header>

<aside
    class="sidebar"
    id="appSidebar"
    aria-label="Application sidebar">

    <script>
        (() => {
            'use strict';

            const sidebar =
                document.getElementById(
                    'appSidebar'
                );

            if (!sidebar) {
                return;
            }

            const isTablet =
                window.matchMedia(
                    '(min-width: 851px) and (max-width: 1100px)'
                ).matches;

            const isDesktop =
                window.matchMedia(
                    '(min-width: 1101px)'
                ).matches;

            let savedCollapsed =
                false;

            if (isDesktop) {
                try {
                    savedCollapsed =
                        window.localStorage.getItem(
                            'olshco-sidebar-collapsed'
                        ) ===
                        '1';
                } catch (storageError) {
                    savedCollapsed =
                        false;
                }
            }

            if (
                isTablet ||
                savedCollapsed
            ) {
                sidebar.classList.add(
                    'is-collapsed'
                );
            }
        })();
    </script>

    <button
        type="button"
        id="sidebarMobileClose"
        class="sidebar-mobile-close"
        aria-label="Close navigation menu">

        <i
            class="fa-solid fa-xmark"
            aria-hidden="true"></i>

    </button>

    <!-- ======================================
         BRAND
    ======================================= -->

    <div class="sidebar-logo">

        <img
            src="Assets/Images/ulsco.png"
            alt="OLSHCO logo">

        <div class="sidebar-brand-text">

            <strong>OLSHCO</strong>

            <span>Digital Hub</span>

        </div>

        <button
            type="button"
            id="sidebarCollapseToggle"
            class="sidebar-collapse-toggle"
            aria-controls="appSidebar"
            aria-expanded="true"
            aria-label="Collapse navigation sidebar"
            title="Collapse sidebar">

            <i
                class="fa-solid fa-angles-left"
                aria-hidden="true"></i>

        </button>

    </div>

    <!-- ======================================
         NAVIGATION
    ======================================= -->

    <nav
        class="sidebar-menu"
        aria-label="Application navigation">

        <?php foreach (
            $navigationGroups as $group
        ): ?>

            <?php
            $visibleItems = array_filter(
                $group['items'],
                function (
                    array $item
                ) use (
                    $currentRole
                ): bool {
                    return sidebarUserCanAccess(
                        $currentRole,
                        $item['roles']
                    );
                }
            );
            ?>

            <?php if (
                !empty($visibleItems)
            ): ?>

                <section
                    class="sidebar-menu-group"
                    aria-label="<?= htmlspecialchars(
                                    $group['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                    <span class="sidebar-group-label">
                        <?= htmlspecialchars(
                            $group['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <?php foreach (
                        $visibleItems as $item
                    ): ?>

                        <?php
                        $isActive =
                            $currentPage ===
                            $item['page'];
                        ?>

                        <a
                            href="index.php?page=<?= urlencode(
                                                        $item['page']
                                                    ) ?>"
                            class="sidebar-link<?=
                                                $isActive
                                                    ? ' active'
                                                    : ''
                                                ?>"
                            title="<?= htmlspecialchars(
                                        $item['label'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            <?php if ($isActive): ?>
                            aria-current="page"
                            <?php endif; ?>>

                            <i
                                class="<?= htmlspecialchars(
                                            $item['icon'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"></i>

                            <span>
                                <?= htmlspecialchars(
                                    $item['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <?php if (
                                !empty($item['badge'])
                            ): ?>

                                <strong
                                    class="sidebar-unread-badge"
                                    aria-label="<?= number_format(
                                                    (int) $item['badge']
                                                ) ?> unread notifications">

                                    <?= (int) $item['badge'] > 99
                                        ? '99+'
                                        : number_format(
                                            (int) $item['badge']
                                        )
                                    ?>

                                </strong>

                            <?php endif; ?>

                        </a>

                    <?php endforeach; ?>

                </section>

            <?php endif; ?>

        <?php endforeach; ?>

    </nav>

    <!-- ======================================
         USER PROFILE AND LOGOUT
    ======================================= -->

    <div class="sidebar-footer">

        <?php if ($currentRole === 'Guest'): ?>

            <div class="sidebar-guest-summary">

                <span class="sidebar-profile-initials">
                    <i
                        class="fa-solid fa-user"
                        aria-hidden="true"></i>
                </span>

                <div class="sidebar-profile-details">

                    <strong>Guest Access</strong>

                    <span>
                        Public school information
                    </span>

                </div>

            </div>

            <div class="sidebar-footer-divider"></div>

            <div class="sidebar-guest-actions">

                <a
                    href="index.php?page=login"
                    class="logout-btn sidebar-sign-in"
                    aria-label="Sign In"
                    title="Sign In">

                    <i
                        class="fa-solid fa-right-to-bracket"
                        aria-hidden="true"></i>

                    <span>Sign In</span>

                </a>

                <a
                    href="index.php?page=register"
                    class="sidebar-register-btn"
                    aria-label="Create Account"
                    title="Create Account">

                    <i
                        class="fa-solid fa-user-plus"
                        aria-hidden="true"></i>

                    <span>Create Account</span>

                </a>

            </div>

        <?php else: ?>

            <a
                href="index.php?page=account_profile"
                class="sidebar-profile"
                title="Open My Account">

                <?php if ($profilePhoto !== ''): ?>

                    <img
                        src="<?= htmlspecialchars(
                                    $profilePhoto,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        alt="<?= htmlspecialchars(
                                    $displayName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?> profile photo">

                <?php else: ?>

                    <span class="sidebar-profile-initials">

                        <?= htmlspecialchars(
                            $sidebarInitials,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                <?php endif; ?>

                <div class="sidebar-profile-details">

                    <strong>
                        <?= htmlspecialchars(
                            $displayName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            $currentRole,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <small>

                        <i
                            class="fa-solid fa-circle"
                            aria-hidden="true"></i>

                        Logged In

                    </small>

                </div>

                <i
                    class="fa-solid fa-chevron-right sidebar-profile-arrow"
                    aria-hidden="true">
                </i>

            </a>

            <div class="sidebar-footer-divider"></div>

            <form
                method="post"
                action="index.php?page=logout"
                class="sidebar-logout-form">

                <?= csrfInput() ?>

                <button
                    type="submit"
                    class="logout-btn">

                    <i
                        class="fa-solid fa-right-from-bracket"
                        aria-hidden="true"></i>

                    <span>Logout</span>

                </button>

            </form>

        <?php endif; ?>

    </div>
</aside>


<div
    id="sidebarMobileOverlay"
    class="sidebar-mobile-overlay"
    hidden>
</div>