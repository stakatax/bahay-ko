<?php

$role = $_SESSION['role'] ?? 'Guest';

$topbarConfig = [
    'dashboard' => [
        'title' => 'Dashboard',
        'subtitle' => "Welcome back! Here's what's happening today.",
        'search' => false,
        'action' => false
    ],

    'news' => [
        'title' => 'Information Hub',
        'subtitle' => 'Announcements, events, and shared documents in one place.',
        'search' => true,
        'action' => in_array($role, ['Admin', 'Faculty'], true)
    ],

    'calendar' => [
        'title' => 'Events',
        'subtitle' => 'View upcoming activities and school schedules.',
        'search' => false,
        'action' => false
    ],

    'postings' => [
        'title' => 'Create Post',
        'subtitle' => 'Publish announcements, events, and documents.',
        'search' => false,
        'action' => false
    ]
];

$currentTopbar = $topbarConfig[$page] ?? [
    'title' => $title ?? ucfirst($page),
    'subtitle' => '',
    'search' => false,
    'action' => false
];
?>

<header class="topbar">

    <div class="topbar-left">

        <h1>
            <?= htmlspecialchars($currentTopbar['title']) ?>
        </h1>

        <?php if (!empty($currentTopbar['subtitle'])): ?>
            <p>
                <?= htmlspecialchars($currentTopbar['subtitle']) ?>
            </p>
        <?php endif; ?>

    </div>

    <div class="topbar-right">

        <?php if ($currentTopbar['search']): ?>

            <label class="topbar-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    id="hubSearch"
                    placeholder="Search updates..."
                    autocomplete="off">

                <button
                    type="button"
                    id="clearHubSearch"
                    class="topbar-search-clear"
                    aria-label="Clear search"
                    hidden>
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </label>

        <?php endif; ?>

        <?php if ($currentTopbar['action']): ?>

            <a
                href="index.php?page=postings"
                class="topbar-action">
                <i class="fa-solid fa-plus"></i>
                <span>New Post</span>
            </a>

        <?php endif; ?>

    </div>

</header>