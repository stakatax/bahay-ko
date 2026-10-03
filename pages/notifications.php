<?php

$notifications =
    $viewData['notifications']
    ?? [];

$unreadCount =
    (int) (
        $viewData['unread_count']
        ?? 0
    );

$preferences =
    $viewData['preferences']
    ?? [];

$systemNotificationsEnabled =
    !array_key_exists(
        'system_enabled',
        $preferences
    )
    || !empty($preferences['system_enabled']);


$emailNotificationsEnabled =
    !empty($preferences['email_enabled']);

$browserPushEnabled =
    !empty($preferences['browser_push_enabled']);

$categoryPreferences =
    is_array(
        $preferences['categories']
            ?? null
    )
    ? $preferences['categories']
    : [];

$notificationCategories = [
    'content_updates' => [
        'label' =>
        'Content updates',

        'description' =>
        'Published announcements, events, documents, and surveys.',

        'icon' =>
        'fa-solid fa-bullhorn'
    ],

    'discussion' => [
        'label' =>
        'Discussion',

        'description' =>
        'New comments and replies in content discussions.',

        'icon' =>
        'fa-regular fa-comments'
    ],

    'engagement' => [
        'label' =>
        'Engagement',

        'description' =>
        'Reactions, acknowledgments, and survey responses.',

        'icon' =>
        'fa-regular fa-heart'
    ],

    'reminders' => [
        'label' =>
        'Reminders',

        'description' =>
        'Upcoming events and time-sensitive content reminders.',

        'icon' =>
        'fa-regular fa-clock'
    ],

    'workflow' => [
        'label' =>
        'Workflow',

        'description' =>
        'Review, approval, rejection, publishing, and draft updates.',

        'icon' =>
        'fa-solid fa-list-check'
    ],

    'account_system' => [
        'label' =>
        'Account and system',

        'description' =>
        'Account status, security, and important system information.',

        'icon' =>
        'fa-solid fa-shield-halved'
    ]
];

if (!in_array($_SESSION['role'] ?? '', ['Admin', 'Faculty'], true)) {
    unset($notificationCategories['workflow']);
}

$totalNotifications =
    count($notifications);

$notificationIcons = [
    'announcement' =>
    'fa-solid fa-bullhorn',

    'event' =>
    'fa-regular fa-calendar',

    'document' =>
    'fa-regular fa-file-lines',

    'survey' =>
    'fa-solid fa-square-poll-horizontal',

    'system' =>
    'fa-regular fa-bell',

    'reminder' =>
    'fa-regular fa-clock',

    'workflow' =>
    'fa-solid fa-list-check'
];

?>

<section class="app-page notifications-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->

    <header class="page-header notifications-page-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                Information Center
            </span>

            <h1>
                Notifications
            </h1>

            <p>
                Review school updates, published content,
                reminders, and workflow information selected
                for your account.
            </p>

        </div>

        <div class="page-actions">

            <div class="notifications-summary">

                <span>

                    <i class="fa-regular fa-bell"></i>

                </span>

                <div>

                    <strong>
                        <?= number_format(
                            $unreadCount
                        ) ?>
                    </strong>

                    <small>
                        <?= $unreadCount === 1
                            ? 'Unread notification'
                            : 'Unread notifications'
                        ?>
                    </small>

                </div>

            </div>

            <?php if (
                $unreadCount > 0
            ): ?>

                <form
                    method="post"
                    action="index.php?page=notification_mark_all_read">

                    <?= csrfInput() ?>

                    <button
                        type="submit"
                        class="app-button secondary">

                        <i class="fa-solid fa-check-double"></i>

                        Mark All as Read
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </header>

    <!-- ======================================
     NOTIFICATION PREFERENCES
======================================= -->

    <section class="page-card notifications-preference-card">

        <div class="notifications-preference-copy">

            <span class="notifications-preference-icon">

                <i class="fa-solid fa-sliders"></i>

            </span>

            <div>

                <span class="page-eyebrow">
                    Delivery Preference
                </span>

                <h2>
                    In-system notifications
                </h2>

                <p>
                    Control whether new published-content
                    notifications are delivered to your account.
                    Existing notifications will remain available.
                </p>

            </div>

        </div>

        <form
            method="post"
            action="index.php?page=notification_update_preferences"
            class="notifications-preference-form">

            <?= csrfInput() ?>

            <input
                type="hidden"
                name="system_enabled"
                value="0">

            <label class="notifications-toggle">

                <input
                    type="checkbox"
                    name="system_enabled"
                    value="1"
                    <?= $systemNotificationsEnabled
                        ? 'checked'
                        : ''
                    ?>>

                <span
                    class="notifications-toggle-control"
                    aria-hidden="true">
                </span>

                <span class="notifications-toggle-copy">

                    <strong>
                        <?= $systemNotificationsEnabled
                            ? 'Enabled'
                            : 'Disabled'
                        ?>
                    </strong>

                    <small>
                        Receive targeted updates in the Digital Hub
                    </small>

                </span>

            </label>

            <button
                type="submit"
                class="app-button primary">

                <i class="fa-solid fa-floppy-disk"></i>

                Save Preference
            </button>

        </form>

    </section>

    <!-- ======================================
         EMAIL NOTIFICATIONS
    ======================================= -->

    <section class="page-card notifications-preference-card">

        <div class="notifications-preference-copy">

            <span class="notifications-preference-icon">

                <i class="fa-regular fa-envelope"></i>

            </span>

            <div>

                <span class="page-eyebrow">
                    Email Delivery
                </span>

                <h2>
                    Email notifications
                </h2>

                <p>
                    Receive targeted announcements, events,
                    documents, surveys, and reminders through
                    the email address registered to your account.
                </p>

            </div>

        </div>

        <form
            method="post"
            action="index.php?page=notification_update_email_preference"
            class="notifications-preference-form">

            <?= csrfInput() ?>

            <input
                type="hidden"
                name="email_enabled"
                value="0">

            <label class="notifications-toggle">

                <input
                    type="checkbox"
                    name="email_enabled"
                    value="1"
                    <?= $emailNotificationsEnabled
                        ? 'checked'
                        : ''
                    ?>>

                <span
                    class="notifications-toggle-control"
                    aria-hidden="true">
                </span>

                <span class="notifications-toggle-copy">

                    <strong>
                        <?= $emailNotificationsEnabled
                            ? 'Enabled'
                            : 'Disabled'
                        ?>
                    </strong>

                    <small>
                        Receive school updates in your email inbox
                    </small>

                </span>

            </label>

            <button
                type="submit"
                class="app-button primary">

                <i class="fa-solid fa-floppy-disk"></i>

                Save Preference
            </button>

        </form>

    </section>


    <!-- ======================================
     BROWSER PUSH NOTIFICATIONS
======================================= -->

    <section
        class="page-card notifications-preference-card browser-push-card"
        data-browser-push-card>

        <div class="notifications-preference-copy">

            <span class="notifications-preference-icon">

                <i class="fa-solid fa-display"></i>

            </span>

            <div>

                <span class="page-eyebrow">
                    Outside the Digital Hub
                </span>

                <h2>
                    Browser notifications
                </h2>

                <p>
                    Receive targeted school updates through this
                    browser even when the Digital Hub tab is closed.
                    Permission is requested only when you enable it.
                </p>

            </div>

        </div>

        <div class="notifications-preference-form">

            <div
                class="browser-push-status"
                id="browserPushStatus"
                aria-live="polite">

                <span
                    class="browser-push-status-indicator"
                    aria-hidden="true">
                </span>

                <span>

                    <strong id="browserPushStatusTitle">
                        Checking browser support
                    </strong>

                    <small id="browserPushStatusMessage">
                        Please wait while notification support is verified.
                    </small>

                </span>

            </div>

            <button
                type="button"
                class="app-button primary"
                id="browserPushToggleButton"
                data-server-enabled="<?= $browserPushEnabled
                                            ? '1'
                                            : '0'
                                        ?>"
                disabled>

                <i class="fa-regular fa-bell"></i>

                <span>
                    Checking
                </span>

            </button>

        </div>

        <div
            id="browserPushConfiguration"
            data-configuration-url="index.php?page=browser_push_configuration"
            data-subscribe-url="index.php?page=browser_push_subscribe"
            data-unsubscribe-url="index.php?page=browser_push_unsubscribe"
            data-service-worker-url="push-service-worker.js"
            data-csrf-token="<?= htmlspecialchars(
                                    csrfToken(),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
            hidden>
        </div>

    </section>

    <!-- ======================================
         CATEGORY DELIVERY PREFERENCES
    ======================================= -->

    <details class="page-card notification-category-card">

        <summary class="notification-category-header">

            <div class="notifications-preference-copy">

                <span class="notifications-preference-icon">

                    <i class="fa-solid fa-table-cells-large"></i>

                </span>

                <div>

                    <span class="page-eyebrow">
                        Notification Categories
                    </span>

                    <h2 id="notificationCategoryHeading">
                        Choose what you receive
                    </h2>

                    <p>
                        Configure each notification category separately
                        for the Digital Hub, email, and this browser.
                        The delivery controls above remain the master
                        switches for their respective channels.
                    </p>

                </div>

            </div>

            <div class="notification-master-summary">

                <span class="<?= $systemNotificationsEnabled
                                    ? 'is-enabled'
                                    : 'is-disabled'
                                ?>">

                    <i class="fa-solid fa-inbox"></i>

                    In-system
                    <?= $systemNotificationsEnabled
                        ? 'enabled'
                        : 'disabled'
                    ?>

                </span>

                <span class="<?= $emailNotificationsEnabled
                                    ? 'is-enabled'
                                    : 'is-disabled'
                                ?>">

                    <i class="fa-regular fa-envelope"></i>

                    Email
                    <?= $emailNotificationsEnabled
                        ? 'enabled'
                        : 'disabled'
                    ?>

                </span>

                <span class="<?= $browserPushEnabled
                                    ? 'is-enabled'
                                    : 'is-disabled'
                                ?>">

                    <i class="fa-solid fa-display"></i>

                    Browser
                    <?= $browserPushEnabled
                        ? 'enabled'
                        : 'disabled'
                    ?>

                </span>

            </div>

            <span
                class="notification-category-chevron"
                aria-hidden="true">

                <i class="fa-solid fa-chevron-down"></i>

            </span>

        </summary>

        <form
            method="post"
            action="index.php?page=notification_update_category_preferences"
            class="notification-category-form">

            <?= csrfInput() ?>

            <div class="notification-category-table">

                <div
                    class="notification-category-table-header"
                    aria-hidden="true">

                    <span>
                        Category
                    </span>

                    <span>
                        Digital Hub
                    </span>

                    <span>
                        Email
                    </span>

                    <span>
                        Browser
                    </span>

                </div>

                <?php foreach (
                    $notificationCategories
                    as $categoryKey => $category
                ): ?>

                    <?php

                    $categoryPreference =
                        is_array(
                            $categoryPreferences[$categoryKey]
                                ?? null
                        )
                        ? $categoryPreferences[$categoryKey]
                        : [];

                    $categorySystemEnabled =
                        !array_key_exists(
                            'system_enabled',
                            $categoryPreference
                        )
                        || !empty($categoryPreference['system_enabled']);

                    $categoryEmailEnabled =
                        !array_key_exists(
                            'email_enabled',
                            $categoryPreference
                        )
                        || !empty($categoryPreference['email_enabled']);

                    $categoryBrowserEnabled =
                        !array_key_exists(
                            'browser_push_enabled',
                            $categoryPreference
                        )
                        || !empty($categoryPreference['browser_push_enabled']);

                    ?>

                    <div class="notification-category-row">

                        <div class="notification-category-copy">

                            <span>

                                <i class="<?= htmlspecialchars(
                                                $category['icon'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $category['label'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        $category['description'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </div>

                        </div>

                        <label class="notification-category-channel">

                            <input
                                type="hidden"
                                name="categories[<?= htmlspecialchars(
                                                        $categoryKey,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>][system_enabled]"
                                value="0">

                            <input
                                type="checkbox"
                                name="categories[<?= htmlspecialchars(
                                                        $categoryKey,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>][system_enabled]"
                                value="1"
                                <?= $categorySystemEnabled
                                    ? 'checked'
                                    : ''
                                ?>>

                            <span aria-hidden="true"></span>

                            <small>
                                Digital Hub
                            </small>

                        </label>

                        <label class="notification-category-channel">

                            <input
                                type="hidden"
                                name="categories[<?= htmlspecialchars(
                                                        $categoryKey,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>][email_enabled]"
                                value="0">

                            <input
                                type="checkbox"
                                name="categories[<?= htmlspecialchars(
                                                        $categoryKey,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>][email_enabled]"
                                value="1"
                                <?= $categoryEmailEnabled
                                    ? 'checked'
                                    : ''
                                ?>>

                            <span aria-hidden="true"></span>

                            <small>
                                Email
                            </small>

                        </label>

                        <label class="notification-category-channel">

                            <input
                                type="hidden"
                                name="categories[<?= htmlspecialchars(
                                                        $categoryKey,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>][browser_push_enabled]"
                                value="0">

                            <input
                                type="checkbox"
                                name="categories[<?= htmlspecialchars(
                                                        $categoryKey,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>][browser_push_enabled]"
                                value="1"
                                <?= $categoryBrowserEnabled
                                    ? 'checked'
                                    : ''
                                ?>>

                            <span aria-hidden="true"></span>

                            <small>
                                Browser
                            </small>

                        </label>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="notification-category-actions">

                <p>

                    <i class="fa-solid fa-circle-info"></i>

                    Disabling a category does not delete notifications
                    you have already received.

                </p>

                <button
                    type="submit"
                    class="app-button primary">

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Category Preferences
                </button>

            </div>

        </form>

    </details>

    <!-- ======================================
         NOTIFICATION LIST
    ======================================= -->

    <section class="page-card notifications-card">

        <div class="card-header">

            <div class="card-header-copy">

                <span class="page-eyebrow">
                    Recent Activity
                </span>

                <h2>
                    Your notification feed
                </h2>

                <p>
                    Showing your latest
                    <?= number_format(
                        $totalNotifications
                    ) ?>
                    <?= $totalNotifications === 1
                        ? 'notification'
                        : 'notifications'
                    ?>.
                </p>

            </div>

            <?php if (
                $unreadCount > 0
            ): ?>

                <span class="notifications-unread-badge">

                    <?= number_format(
                        $unreadCount
                    ) ?>

                    unread

                </span>

            <?php endif; ?>

        </div>

        <?php if (
            empty($notifications)
        ): ?>

            <div class="notifications-empty">

                <span>

                    <i class="fa-regular fa-bell-slash"></i>

                </span>

                <h3>
                    No notifications yet
                </h3>

                <p>
                    New school updates and content selected
                    for your account will appear here.
                </p>

                <a
                    href="index.php?page=news"
                    class="app-button secondary">

                    <i class="fa-solid fa-bullhorn"></i>

                    Open Information Hub
                </a>

            </div>

        <?php else: ?>

            <div class="notifications-list">

                <?php foreach (
                    $notifications
                    as $notification
                ): ?>

                    <?php

                    $notificationId =
                        (int) (
                            $notification['notification_id']
                            ?? 0
                        );

                    $notificationType =
                        strtolower(
                            trim(
                                (string) (
                                    $notification['notification_type']
                                    ?? 'system'
                                )
                            )
                        );

                    $contentType =
                        strtolower(
                            trim(
                                (string) (
                                    $notification['content_type']
                                    ?? ''
                                )
                            )
                        );

                    $iconKey =
                        $contentType !== ''
                        ? $contentType
                        : $notificationType;

                    $iconClass =
                        $notificationIcons[$iconKey]
                        ?? 'fa-regular fa-bell';

                    $isRead =
                        !empty($notification['is_read']);

                    $createdAt =
                        $notification['created_at']
                        ?? null;

                    ?>

                    <article
                        class="notification-item <?= $isRead
                                                        ? 'is-read'
                                                        : 'is-unread'
                                                    ?>">

                        <a
                            href="index.php?page=notification_open&amp;notification_id=<?= $notificationId ?>"
                            class="notification-item-link">

                            <span
                                class="notification-item-icon type-<?= htmlspecialchars(
                                                                        $iconKey,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>">

                                <i class="<?= htmlspecialchars(
                                                $iconClass,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"></i>

                            </span>

                            <div class="notification-item-content">

                                <div class="notification-item-heading">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $notification['title']
                                                ?? 'Notification',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                    <?php if (!$isRead): ?>

                                        <span class="notification-new-badge">
                                            New
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <p>
                                    <?= htmlspecialchars(
                                        $notification['message']
                                            ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <div class="notification-item-meta">

                                    <span>

                                        <i class="fa-regular fa-clock"></i>

                                        <?= !empty($createdAt)
                                            ? htmlspecialchars(
                                                date(
                                                    'M d, Y g:i A',
                                                    strtotime(
                                                        $createdAt
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : 'Date unavailable'
                                        ?>

                                    </span>

                                    <span>

                                        <i class="fa-solid fa-tag"></i>

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $contentType !== ''
                                                    ? $contentType
                                                    : $notificationType
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </div>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</section>