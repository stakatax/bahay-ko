<?php

$isLoggedIn =
    !empty($_SESSION['user_id']);

$currentRole =
    $_SESSION['role']
    ?? 'Guest';

$firstName =
    trim(
        (string) (
            $_SESSION['first_name']
            ?? ''
        )
    );

$displayName =
    $firstName !== ''
    ? $firstName
    : 'User';

$departmentName =
    trim(
        (string) (
            $_SESSION['department_name']
            ?? ''
        )
    );

$programCode =
    trim(
        (string) (
            $_SESSION['academic_program_code']
            ?? ''
        )
    );

$gradeLevelName =
    trim(
        (string) (
            $_SESSION['grade_level_name']
            ?? ''
        )
    );

$sectionName =
    trim(
        (string) (
            $_SESSION['section_name']
            ?? ''
        )
    );

$priorityAnnouncement =
    isset(
        $viewData['priority_announcement']
    ) &&
    is_array(
        $viewData['priority_announcement']
    )
    ? $viewData['priority_announcement']
    : null;

$nextEvent =
    isset(
        $viewData['next_event']
    ) &&
    is_array(
        $viewData['next_event']
    )
    ? $viewData['next_event']
    : null;

$unreadNotificationCount =
    max(
        0,
        (int) (
            $globalUnreadNotificationCount
            ?? 0
        )
    );

$pendingRegistrationCount =
    max(
        0,
        (int) (
            $globalPendingRegistrationCount
            ?? 0
        )
    );

$priorityAnnouncementId =
    (int) (
        $priorityAnnouncement['announcement_id']
        ?? 0
    );

$priorityAnnouncementTitle =
    trim(
        (string) (
            $priorityAnnouncement['title']
            ?? ''
        )
    );

$priorityAnnouncementLevel =
    trim(
        (string) (
            $priorityAnnouncement['priority']
            ?? 'Normal'
        )
    );

$isPriorityAnnouncement =
    in_array(
        strtolower(
            $priorityAnnouncementLevel
        ),
        [
            'emergency',
            'urgent',
            'important',
            'high'
        ],
        true
    );

$nextEventId =
    (int) (
        $nextEvent['event_id']
        ?? 0
    );

$nextEventTitle =
    trim(
        (string) (
            $nextEvent['title']
            ?? ''
        )
    );

$nextEventDate =
    trim(
        (string) (
            $nextEvent['event_date']
            ?? ''
        )
    );

$nextEventDateLabel =
    $nextEventDate !== '' &&
    strtotime($nextEventDate) !== false
    ? date(
        'M j, Y · g:i A',
        strtotime($nextEventDate)
    )
    : 'No upcoming event';

$roleHomeAction =
    match ($currentRole) {
        'Admin' => [
            'eyebrow' =>
            'Pending Registrations',

            'title' =>
            $pendingRegistrationCount > 0
                ? number_format(
                    $pendingRegistrationCount
                ) . ' awaiting review'
                : 'Registration queue clear',

            'description' =>
            $pendingRegistrationCount > 0
                ? 'Review submitted Student and Parent accounts.'
                : 'No registration requests currently require action.',

            'href' =>
            'index.php?page=account_approvals',

            'icon' =>
            'fa-solid fa-user-check'
        ],

        'Faculty' => [
            'eyebrow' =>
            'Faculty Workspace',

            'title' =>
            'Manage your content',

            'description' =>
            'Review drafts, submissions, and publishing feedback.',

            'href' =>
            'index.php?page=content_workspace',

            'icon' =>
            'fa-solid fa-layer-group'
        ],

        'Parent' => [
            'eyebrow' =>
            'Parent Access',

            'title' =>
            'Linked-Student updates',

            'description' =>
            'Review notices and activities relevant to your linked Student.',

            'href' =>
            'index.php?page=news',

            'icon' =>
            'fa-solid fa-people-roof'
        ],

        default => [
            'eyebrow' =>
            'Student Profile',

            'title' =>
            'Manage your interests',

            'description' =>
            'Keep your interests updated for more relevant information.',

            'href' =>
            'index.php?page=student_profile',

            'icon' =>
            'fa-solid fa-wand-magic-sparkles'
        ]
    };

?>

<?php if ($isLoggedIn): ?>

    <!-- ==========================================
         AUTHENTICATED HOME
    =========================================== -->

    <section class="app-page home-app-page">

        <header class="page-header">

            <div class="page-header-copy">

                <span class="page-eyebrow">
                    OLSHCO Digital Hub
                </span>

                <h1>
                    Welcome back,
                    <?= htmlspecialchars(
                        $displayName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h1>

                <p>
                    Access official school announcements,
                    scheduled activities, academic information,
                    shared documents, and school services.
                </p>

            </div>

            <div class="page-actions">

                <a
                    href="index.php?page=news"
                    class="app-button primary">
                    <i class="fa-solid fa-bullhorn"></i>

                    Information Hub
                </a>

                <a
                    href="index.php?page=calendar"
                    class="app-button secondary">
                    <i class="fa-regular fa-calendar"></i>

                    View Calendar
                </a>

            </div>

        </header>

        <!-- ======================================
             ACCOUNT SUMMARY
        ======================================= -->

        <section class="home-welcome-card">

            <div class="home-welcome-content">

                <span class="home-welcome-icon">

                    <?php if ($currentRole === 'Admin'): ?>

                        <i class="fa-solid fa-user-shield"></i>

                    <?php elseif ($currentRole === 'Faculty'): ?>

                        <i class="fa-solid fa-chalkboard-user"></i>

                    <?php elseif ($currentRole === 'Parent'): ?>

                        <i class="fa-solid fa-people-roof"></i>

                    <?php else: ?>

                        <i class="fa-solid fa-user-graduate"></i>

                    <?php endif; ?>

                </span>

                <div>

                    <span class="home-role-label">
                        <?= htmlspecialchars(
                            $currentRole,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?> Account
                    </span>

                    <h2>

                        <?php if ($currentRole === 'Admin'): ?>

                            Manage and oversee the Digital Hub

                        <?php elseif ($currentRole === 'Faculty'): ?>

                            Share verified information with the school community

                        <?php elseif ($currentRole === 'Parent'): ?>

                            Stay informed about school updates and activities

                        <?php else: ?>

                            Stay connected with official school information

                        <?php endif; ?>

                    </h2>

                    <p>

                        <?php if ($currentRole === 'Admin'): ?>

                            Monitor content, system activity, user accounts,
                            engagement, and school-wide information.

                        <?php elseif ($currentRole === 'Faculty'): ?>

                            Access department information, publish content,
                            and keep students and parents updated.

                        <?php elseif ($currentRole === 'Parent'): ?>

                            Review announcements, activities, and notices
                            relevant to your linked Student.

                        <?php else: ?>

                            View announcements, events, documents,
                            and academic information intended for
                            your role and classification.

                        <?php endif; ?>

                    </p>

                </div>

            </div>

            <?php if (
                $departmentName !== ''
                || $programCode !== ''
                || $gradeLevelName !== ''
                || $sectionName !== ''
            ): ?>

                <div class="home-profile-tags">

                    <?php if ($departmentName !== ''): ?>

                        <span>

                            <i class="fa-solid fa-school"></i>

                            <?= htmlspecialchars(
                                $departmentName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    <?php endif; ?>

                    <?php if ($programCode !== ''): ?>

                        <span>

                            <i class="fa-solid fa-graduation-cap"></i>

                            <?= htmlspecialchars(
                                $programCode,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    <?php endif; ?>

                    <?php if ($gradeLevelName !== ''): ?>

                        <span>

                            <i class="fa-solid fa-layer-group"></i>

                            <?= htmlspecialchars(
                                $gradeLevelName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    <?php endif; ?>

                    <?php if ($sectionName !== ''): ?>

                        <span>

                            <i class="fa-solid fa-users"></i>

                            <?= htmlspecialchars(
                                $sectionName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

        <!-- ======================================
     AT A GLANCE
======================================= -->

        <section class="home-overview">

            <div class="home-overview-heading">

                <div>

                    <span class="page-eyebrow">
                        At a Glance
                    </span>

                    <h2>
                        What needs your attention
                    </h2>

                </div>

                <span class="home-overview-date">

                    <i
                        class="fa-regular fa-calendar"
                        aria-hidden="true"></i>

                    <?= htmlspecialchars(
                        date('F j, Y'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>

            <div class="home-overview-grid">

                <a
                    href="index.php?page=notifications"
                    class="home-overview-card">

                    <span class="home-overview-icon">

                        <i
                            class="fa-regular fa-bell"
                            aria-hidden="true"></i>

                    </span>

                    <div>

                        <small>
                            Notifications
                        </small>

                        <strong>
                            <?= $unreadNotificationCount > 0
                                ? number_format(
                                    $unreadNotificationCount
                                ) . ' unread'
                                : 'You are all caught up'
                            ?>
                        </strong>

                        <p>
                            <?= $unreadNotificationCount > 0
                                ? 'Open your Notification Center to review recent activity.'
                                : 'No unread notifications require your attention.'
                            ?>
                        </p>

                    </div>

                    <i
                        class="fa-solid fa-arrow-right"
                        aria-hidden="true"></i>

                </a>

                <a
                    href="<?= $priorityAnnouncementId > 0
                                ? 'index.php?page=news&amp;open_type=announcement&amp;open_id='
                                . $priorityAnnouncementId
                                : 'index.php?page=news'
                            ?>"
                    class="home-overview-card">

                    <span class="home-overview-icon">

                        <i
                            class="fa-solid fa-bullhorn"
                            aria-hidden="true"></i>

                    </span>

                    <div>

                        <small>
                            <?= $priorityAnnouncementTitle !== ''
                                ? htmlspecialchars(
                                    $priorityAnnouncementLevel
                                        . ' Announcement',
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : 'Announcements'
                            ?>
                        </small>

                        <strong>
                            <?= htmlspecialchars(
                                $priorityAnnouncementTitle !== ''
                                    ? $priorityAnnouncementTitle
                                    : 'No current announcement',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <p>
                            <?= $priorityAnnouncementTitle !== ''
                                ? 'Open this authorized announcement in the Information Hub.'
                                : 'There are no published announcements available to your account.'
                            ?>
                        </p>

                    </div>

                    <i
                        class="fa-solid fa-arrow-right"
                        aria-hidden="true"></i>

                </a>

                <a
                    href="<?= $nextEventId > 0
                                ? 'index.php?page=news&amp;open_type=event&amp;open_id='
                                . $nextEventId
                                : 'index.php?page=calendar'
                            ?>"
                    class="home-overview-card">

                    <span class="home-overview-icon">

                        <i
                            class="fa-regular fa-calendar-check"
                            aria-hidden="true"></i>

                    </span>

                    <div>

                        <small>
                            Next Event
                        </small>

                        <strong>
                            <?= htmlspecialchars(
                                $nextEventTitle !== ''
                                    ? $nextEventTitle
                                    : 'No upcoming event',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <p>
                            <?= htmlspecialchars(
                                $nextEventDateLabel,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    </div>

                    <i
                        class="fa-solid fa-arrow-right"
                        aria-hidden="true"></i>

                </a>

                <a
                    href="<?= htmlspecialchars(
                                $roleHomeAction['href'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    class="home-overview-card">

                    <span class="home-overview-icon">

                        <i
                            class="<?= htmlspecialchars(
                                        $roleHomeAction['icon'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            aria-hidden="true"></i>

                    </span>

                    <div>

                        <small>
                            <?= htmlspecialchars(
                                $roleHomeAction['eyebrow'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </small>

                        <strong>
                            <?= htmlspecialchars(
                                $roleHomeAction['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <p>
                            <?= htmlspecialchars(
                                $roleHomeAction['description'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    </div>

                    <i
                        class="fa-solid fa-arrow-right"
                        aria-hidden="true"></i>

                </a>

            </div>

        </section>



        <!-- ======================================
             ROLE-BASED CONTENT
        ======================================= -->

        <section class="content-grid two-columns">

            <article class="page-card">

                <div class="card-header">

                    <div class="card-header-copy">

                        <span class="page-eyebrow">
                            Recommended
                        </span>

                        <h2>
                            What you can do
                        </h2>

                        <p>
                            Actions available based on your account role.
                        </p>

                    </div>

                </div>

                <div class="home-action-list">

                    <?php if ($currentRole === 'Admin'): ?>

                        <a href="index.php?page=dashboard">

                            <span>

                                <i class="fa-solid fa-chart-line"></i>

                            </span>

                            <div>

                                <strong>
                                    Review system analytics
                                </strong>

                                <small>
                                    Monitor users, content,
                                    engagement, and activity.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                        <a href="index.php?page=postings">

                            <span>

                                <i class="fa-solid fa-pen-to-square"></i>

                            </span>

                            <div>

                                <strong>
                                    Publish school content
                                </strong>

                                <small>
                                    Create announcements, events,
                                    documents, and surveys.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php elseif ($currentRole === 'Faculty'): ?>

                        <a href="index.php?page=postings">

                            <span>

                                <i class="fa-solid fa-pen-to-square"></i>

                            </span>

                            <div>

                                <strong>
                                    Create department content
                                </strong>

                                <small>
                                    Publish verified updates
                                    for assigned audiences.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                        <a href="index.php?page=calendar">

                            <span>

                                <i class="fa-solid fa-calendar-plus"></i>

                            </span>

                            <div>

                                <strong>
                                    Review upcoming activities
                                </strong>

                                <small>
                                    Check school and department events.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php elseif ($currentRole === 'Parent'): ?>

                        <a href="index.php?page=news">

                            <span>

                                <i class="fa-solid fa-bell"></i>

                            </span>

                            <div>

                                <strong>
                                    Review Parent notices
                                </strong>

                                <small>
                                    View information relevant
                                    to your linked Student.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                        <a href="index.php?page=calendar">

                            <span>

                                <i class="fa-solid fa-calendar-check"></i>

                            </span>

                            <div>

                                <strong>
                                    Check upcoming events
                                </strong>

                                <small>
                                    Stay informed about important dates.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php else: ?>

                        <a href="index.php?page=news">

                            <span>

                                <i class="fa-solid fa-bullhorn"></i>

                            </span>

                            <div>

                                <strong>
                                    Read official announcements
                                </strong>

                                <small>
                                    View updates intended for
                                    your role and classification.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                        <a href="index.php?page=calendar">

                            <span>

                                <i class="fa-solid fa-calendar-days"></i>

                            </span>

                            <div>

                                <strong>
                                    View school events
                                </strong>

                                <small>
                                    Check activities and important dates.
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php endif; ?>

                </div>

            </article>

            <article class="page-card">

                <div class="card-header">

                    <div class="card-header-copy">

                        <span class="page-eyebrow">
                            School Information
                        </span>

                        <h2>
                            Explore OLSHCO
                        </h2>

                        <p>
                            Learn about the institution and its services.
                        </p>

                    </div>

                </div>

                <div class="home-action-list">

                    <a href="index.php?page=about">

                        <span>

                            <i class="fa-solid fa-school-flag"></i>

                        </span>

                        <div>

                            <strong>
                                About the school
                            </strong>

                            <small>
                                Mission, vision, history,
                                identity, and core values.
                            </small>

                        </div>

                        <i class="fa-solid fa-chevron-right"></i>

                    </a>

                    <a href="index.php?page=academic">

                        <span>

                            <i class="fa-solid fa-book-open-reader"></i>

                        </span>

                        <div>

                            <strong>
                                Academic offerings
                            </strong>

                            <small>
                                Review available school divisions,
                                programs, and learning pathways.
                            </small>

                        </div>

                        <i class="fa-solid fa-chevron-right"></i>

                    </a>

                    <a href="index.php?page=contact">

                        <span>

                            <i class="fa-solid fa-headset"></i>

                        </span>

                        <div>

                            <strong>
                                Contact the school
                            </strong>

                            <small>
                                Find the correct office
                                for your concern.
                            </small>

                        </div>

                        <i class="fa-solid fa-chevron-right"></i>

                    </a>

                </div>

            </article>

        </section>

    </section>

<?php else: ?>

    <!-- ==========================================
         PUBLIC DIGITAL HUB HOME
    =========================================== -->

    <main class="public-hub-page">

        <section class="public-home-intro">

            <div class="public-home-intro-copy">

                <span class="public-home-eyebrow">
                    Official School Information Platform
                </span>

                <h1>
                    Welcome to the
                    OLSHCO Digital Hub
                </h1>

                <p>
                    A centralized platform for official announcements,
                    scheduled activities, academic information,
                    school documents, and services from
                    Our Lady of the Sacred Heart College of Guimba, Inc.
                </p>

                <div class="public-home-actions">

                    <a
                        href="index.php?page=login"
                        class="public-home-button primary">
                        <i class="fa-solid fa-right-to-bracket"></i>

                        Sign In
                    </a>

                    <a
                        href="index.php?page=register"
                        class="public-home-button secondary">
                        <i class="fa-solid fa-user-plus"></i>

                        Create Account
                    </a>

                </div>

            </div>

            <div class="public-home-brand-card">

                <img
                    src="Assets/Images/ulsco.png"
                    alt="OLSHCO logo">

                <div>

                    <span>
                        Our Lady of the Sacred Heart
                        College of Guimba, Inc.
                    </span>

                    <strong>
                        Rooted in Faith,<br>
                        Grounded in Excellence
                    </strong>

                    <p>
                        Access trusted and verified school information
                        through one official digital platform.
                    </p>

                </div>

            </div>

        </section>

        <!-- ======================================
             PUBLIC QUICK ACCESS
        ======================================= -->

        <section class="public-home-section">

            <div class="public-section-heading">

                <div>

                    <span>
                        Explore the Digital Hub
                    </span>

                    <h2>
                        School information in one place
                    </h2>

                    <p>
                        Browse public school information or sign in
                        to access role-based content.
                    </p>

                </div>

            </div>

            <div class="public-home-grid three-columns">

                <a
                    href="index.php?page=about"
                    class="public-service-card">

                    <span class="public-service-icon">

                        <i class="fa-solid fa-school"></i>

                    </span>

                    <h3>
                        About OLSHCO
                    </h3>

                    <p>
                        Learn about the school,
                        its mission, vision, history,
                        and institutional identity.
                    </p>

                    <span class="public-card-link">
                        View School Profile

                        <i class="fa-solid fa-arrow-right"></i>
                    </span>

                </a>

                <a
                    href="index.php?page=academic"
                    class="public-service-card">

                    <span class="public-service-icon">

                        <i class="fa-solid fa-graduation-cap"></i>

                    </span>

                    <h3>
                        Academic Offerings
                    </h3>

                    <p>
                        Explore Elementary, Junior High,
                        Senior High, and College offerings.
                    </p>

                    <span class="public-card-link">
                        Explore Academics

                        <i class="fa-solid fa-arrow-right"></i>
                    </span>

                </a>



                <a
                    href="index.php?page=contact"
                    class="public-service-card">

                    <span class="public-service-icon">

                        <i class="fa-solid fa-address-book"></i>

                    </span>

                    <h3>
                        Contact Directory
                    </h3>

                    <p>
                        Find school offices,
                        contact information,
                        and inquiry channels.
                    </p>

                    <span class="public-card-link">
                        Contact the School

                        <i class="fa-solid fa-arrow-right"></i>
                    </span>

                </a>

            </div>

        </section>

        <!-- ======================================
             ACCOUNT BENEFITS
        ======================================= -->

        <section class="public-home-section">

            <div class="public-home-grid two-columns">

                <article class="public-info-panel">

                    <div class="public-panel-heading">

                        <span class="public-panel-icon">

                            <i class="fa-solid fa-shield-halved"></i>

                        </span>

                        <div>

                            <span>
                                Trusted Information
                            </span>

                            <h2>
                                Official and verified school updates
                            </h2>

                        </div>

                    </div>

                    <p>
                        The Digital Hub centralizes announcements,
                        events, documents, surveys, and school information
                        to reduce confusion caused by scattered posts
                        and communication channels.
                    </p>

                    <ul class="public-feature-list">

                        <li>

                            <i class="fa-solid fa-circle-check"></i>

                            Official announcements and notices

                        </li>

                        <li>

                            <i class="fa-solid fa-circle-check"></i>

                            Organized school and department events

                        </li>

                        <li>

                            <i class="fa-solid fa-circle-check"></i>

                            Searchable information and document access

                        </li>

                    </ul>

                </article>

                <article class="public-info-panel accent-panel">

                    <div class="public-panel-heading">

                        <span class="public-panel-icon">

                            <i class="fa-solid fa-users"></i>

                        </span>

                        <div>

                            <span>
                                Role-Based Access
                            </span>

                            <h2>
                                Information designed for your account
                            </h2>

                        </div>

                    </div>

                    <p>
                        Students and Parents may submit an account registration.
                        Faculty and Administrator accounts are securely provisioned
                        by authorized school personnel.
                    </p>

                    <div class="public-role-list">

                        <span>

                            <i class="fa-solid fa-user-graduate"></i>

                            Student

                        </span>

                        <span>

                            <i class="fa-solid fa-people-roof"></i>

                            Parent

                        </span>

                        <span>

                            <i class="fa-solid fa-chalkboard-user"></i>

                            Faculty

                        </span>

                        <span>

                            <i class="fa-solid fa-user-shield"></i>

                            Administrator

                        </span>

                    </div>

                    <a
                        href="index.php?page=register"
                        class="public-inline-action">
                        Student or Parent Registration

                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                </article>

            </div>

        </section>


    </main>

<?php endif; ?>