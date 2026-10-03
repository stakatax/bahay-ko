<?php

$users =
    $viewData['users']
    ?? [];

$selectedUser =
    $viewData['selected_user']
    ?? null;

$selectedUserHistory =
    $viewData['selected_user_history']
    ?? [];

$filters =
    $viewData['filters']
    ?? [];

$counts =
    $viewData['counts']
    ?? [];

$roles =
    $viewData['roles']
    ?? [];

$departments =
    $viewData['departments']
    ?? [];

$educationLevels =
    $viewData['education_levels']
    ?? [];


$academicPrograms =
    $viewData['academic_programs']
    ?? [];

$gradeLevels =
    $viewData['grade_levels']
    ?? [];

$sections =
    $viewData['sections']
    ?? [];

$successMessage =
    trim(
        (string) (
            $_GET['success']
            ?? ''
        )
    );

$errorMessage =
    trim(
        (string) (
            $_GET['error']
            ?? ''
        )
    );

$statuses =
    $viewData['statuses']
    ?? [];

$directoryError =
    trim(
        (string) (
            $viewData['directory_error']
            ?? ''
        )
    );


$facultyProvisioningFlash =
    $viewData['faculty_provisioning_flash']
    ?? null;

$facultyProvisioningType =
    trim(
        (string) (
            $facultyProvisioningFlash['type']
            ?? ''
        )
    );

$facultyOldInput =
    $facultyProvisioningFlash['old_input']
    ?? [];

$escape =
    static fn(
        mixed $value
    ): string =>
    htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

$fullName =
    static function (
        array $user
    ): string {
        return implode(
            ' ',
            array_filter(
                [
                    trim(
                        (string) (
                            $user['first_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $user['middle_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $user['last_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $user['name_suffix']
                            ?? ''
                        )
                    )
                ],
                static fn(
                    string $part
                ): bool =>
                $part !== ''
            )
        );
    };

$filterQuery = [
    'page' =>
    'manage_users',

    'search' =>
    $filters['search']
        ?? '',

    'role_id' =>
    (int) (
        $filters['role_id']
        ?? 0
    ),

    'status' =>
    $filters['status']
        ?? '',

    'department_id' =>
    (int) (
        $filters['department_id']
        ?? 0
    ),
];

$activeFilters = [];

$searchFilter =
    trim(
        (string) (
            $filters['search']
            ?? ''
        )
    );

if ($searchFilter !== '') {
    $activeFilters[] = [
        'key' => 'search',
        'label' => 'Search',
        'value' => $searchFilter
    ];
}

$statusFilter =
    trim(
        (string) (
            $filters['status']
            ?? ''
        )
    );

if ($statusFilter !== '') {
    $activeFilters[] = [
        'key' => 'status',
        'label' => 'Status',
        'value' => $statusFilter
    ];
}

$roleFilterId =
    (int) (
        $filters['role_id']
        ?? 0
    );

foreach ($roles as $role) {
    if (
        (int) (
            $role['role_id']
            ?? 0
        ) === $roleFilterId
    ) {
        $activeFilters[] = [
            'key' => 'role_id',
            'label' => 'Role',
            'value' =>
            $role['role_prefix']
                ?? 'Unknown'
        ];

        break;
    }
}

$departmentFilterId =
    (int) (
        $filters['department_id']
        ?? 0
    );

foreach ($departments as $department) {
    if (
        (int) (
            $department['department_id']
            ?? 0
        ) === $departmentFilterId
    ) {
        $activeFilters[] = [
            'key' => 'department_id',
            'label' => 'Department',
            'value' =>
            $department['department_name']
                ?? 'Unknown'
        ];

        break;
    }
}

?>

<section class="app-page manage-users-page">

    <header class="page-header manage-users-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                User Administration
            </span>

            <h1>
                Manage Users
            </h1>

            <p>
                Review accounts, roles, statuses, academic
                assignments, and Parentâ€“Student relationships.
            </p>

        </div>

        <div class="manage-users-header-actions">

            <a
                href="index.php?page=account_approvals"
                class="app-button secondary">

                <i class="fa-solid fa-user-clock"></i>

                Pending Approvals

            </a>

            <button
                type="button"
                id="openFacultyProvisionModal"
                class="app-button primary">

                <i class="fa-solid fa-user-plus"></i>

                Create Faculty

            </button>

        </div>

    </header>

    <?php if ($directoryError !== ''): ?>

        <div class="manage-users-alert error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= $escape(
                    $directoryError
                ) ?>
            </span>

        </div>



    <?php endif; ?>


    <?php if ($successMessage !== ''): ?>

        <div class="manage-users-alert success">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                <?= $escape(
                    $successMessage
                ) ?>
            </span>

        </div>

    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>

        <div class="manage-users-alert error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= $escape(
                    $errorMessage
                ) ?>
            </span>

        </div>

    <?php endif; ?>

    <section class="manage-users-stats">

        <?php

        $statCards = [
            [
                'label' => 'Total Accounts',
                'value' => $counts['total'] ?? 0,
                'icon' => 'fa-solid fa-users',
                'type' => 'total'
            ],
            [
                'label' => 'Active',
                'value' => $counts['Active'] ?? 0,
                'icon' => 'fa-solid fa-circle-check',
                'type' => 'active'
            ],
            [
                'label' => 'Inactive',
                'value' => $counts['Inactive'] ?? 0,
                'icon' => 'fa-solid fa-user-slash',
                'type' => 'inactive'
            ],
            [
                'label' => 'Rejected',
                'value' => $counts['Rejected'] ?? 0,
                'icon' => 'fa-solid fa-circle-xmark',
                'type' => 'rejected'
            ]
        ];

        ?>

        <?php foreach (
            $statCards
            as $statCard
        ): ?>

            <article class="manage-users-stat <?= $escape(
                                                    $statCard['type']
                                                ) ?>">

                <span>

                    <i class="<?= $escape(
                                    $statCard['icon']
                                ) ?>"></i>

                </span>

                <div>

                    <strong>
                        <?= number_format(
                            (int) $statCard['value']
                        ) ?>
                    </strong>

                    <small>
                        <?= $escape(
                            $statCard['label']
                        ) ?>
                    </small>

                </div>

            </article>

        <?php endforeach; ?>

    </section>

    <section class="page-card manage-users-filter-card">

        <form
            method="get"
            action="index.php"
            class="manage-users-filters">

            <input
                type="hidden"
                name="page"
                value="manage_users">

            <label class="manage-users-search">

                <span>Search</span>

                <div>

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="manageUsersLiveSearch"
                        name="search"
                        maxlength="150"
                        value="<?= $escape(
                                    $filters['search']
                                        ?? ''
                                ) ?>"
                        placeholder="Name, account ID, or email"
                        autocomplete="off">

                </div>

            </label>

            <label>

                <span>Role</span>

                <select name="role_id">

                    <option value="0">
                        All roles
                    </option>

                    <?php foreach (
                        $roles
                        as $role
                    ): ?>

                        <option
                            value="<?= (int) (
                                        $role['role_id']
                                        ?? 0
                                    ) ?>"
                            <?= (int) (
                                $filters['role_id']
                                ?? 0
                            ) === (int) (
                                $role['role_id']
                                ?? 0
                            )
                                ? 'selected'
                                : ''
                            ?>>

                            <?= $escape(
                                $role['role_prefix']
                                    ?? ''
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>

                <span>Status</span>

                <select name="status">

                    <option value="">
                        All statuses
                    </option>

                    <?php foreach (
                        $statuses
                        as $status
                    ): ?>

                        <option
                            value="<?= $escape(
                                        $status
                                    ) ?>"
                            <?= (
                                $filters['status']
                                ?? ''
                            ) === $status
                                ? 'selected'
                                : ''
                            ?>>

                            <?= $escape(
                                $status
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>

                <span>Department</span>

                <select name="department_id">

                    <option value="0">
                        All departments
                    </option>

                    <?php foreach (
                        $departments
                        as $department
                    ): ?>

                        <option
                            value="<?= (int) (
                                        $department['department_id']
                                        ?? 0
                                    ) ?>"
                            <?= (int) (
                                $filters['department_id']
                                ?? 0
                            ) === (int) (
                                $department['department_id']
                                ?? 0
                            )
                                ? 'selected'
                                : ''
                            ?>>

                            <?= $escape(
                                $department['department_name']
                                    ?? ''
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </label>


            <div class="manage-users-filter-actions">

                <button
                    type="submit"
                    class="app-button primary">

                    Apply Filters
                </button>

                <a
                    href="index.php?page=manage_users"
                    class="app-button secondary">

                    Reset
                </a>

            </div>

        </form>


        <?php if (!empty($activeFilters)): ?>

            <div class="manage-users-active-filters">

                <div class="manage-users-active-filter-heading">

                    <span>

                        <i class="fa-solid fa-filter"></i>

                        Active Filters

                    </span>

                    <small>

                        <?= number_format(
                            count($users)
                        ) ?>

                        matching
                        <?= count($users) === 1
                            ? 'account'
                            : 'accounts'
                        ?>

                    </small>

                </div>

                <div class="manage-users-filter-chips">

                    <?php foreach (
                        $activeFilters
                        as $activeFilter
                    ): ?>

                        <?php

                        $removeFilterQuery =
                            $filterQuery;

                        $removeFilterQuery[$activeFilter['key']] =
                            in_array(
                                $activeFilter['key'],
                                [
                                    'role_id',
                                    'department_id',
                                    'education_level_id'
                                ],
                                true
                            )
                            ? 0
                            : '';

                        $removeFilterUrl =
                            'index.php?'
                            . http_build_query(
                                $removeFilterQuery
                            );

                        ?>

                        <a
                            href="<?= $escape(
                                        $removeFilterUrl
                                    ) ?>"
                            class="manage-users-filter-chip"
                            title="Remove this filter">

                            <span>

                                <?= $escape(
                                    $activeFilter['label']
                                ) ?>:

                                <strong>
                                    <?= $escape(
                                        $activeFilter['value']
                                    ) ?>
                                </strong>

                            </span>

                            <i class="fa-solid fa-xmark"></i>

                        </a>

                    <?php endforeach; ?>

                    <a
                        href="index.php?page=manage_users"
                        class="manage-users-clear-filters">

                        Clear all

                    </a>

                </div>

            </div>

        <?php endif; ?>

    </section>

    <section class="manage-users-workspace">

        <article class="page-card manage-users-directory">

            <header>

                <div>

                    <span class="page-eyebrow">
                        User Directory
                    </span>

                    <h2 data-manage-user-count>

                        <span>
                            <?= number_format(
                                count($users)
                            ) ?>
                        </span>

                        matching
                        <?= count($users) === 1
                            ? 'account'
                            : 'accounts'
                        ?>

                    </h2>

                </div>

            </header>

            <?php if (empty($users)): ?>

                <div class="manage-users-empty">

                    <i class="fa-solid fa-users-slash"></i>

                    <h3>No accounts found</h3>

                    <p>
                        Change or reset the filters to view
                        other user accounts.
                    </p>

                </div>

            <?php else: ?>

                <div class="manage-users-list">

                    <div
                        class="manage-users-live-empty"
                        data-manage-user-live-empty
                        hidden>

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <strong>No matching accounts</strong>

                        <span>
                            Try a different name, account ID, or email.
                        </span>

                    </div>

                    <?php foreach (
                        $users
                        as $managedUser
                    ): ?>

                        <?php

                        $userId =
                            (int) (
                                $managedUser['user_id']
                                ?? 0
                            );

                        $userUrl =
                            'index.php?'
                            . http_build_query(
                                array_merge(
                                    $filterQuery,
                                    [
                                        'user_id' =>
                                        $userId
                                    ]
                                )
                            );

                        $isSelected =
                            $selectedUser &&
                            (int) (
                                $selectedUser['user_id']
                                ?? 0
                            ) === $userId;


                        $lockUntil =
                            trim(
                                (string) (
                                    $managedUser['lock_until']
                                    ?? ''
                                )
                            );

                        $lockTimestamp =
                            $lockUntil !== ''
                            ? strtotime($lockUntil)
                            : false;

                        $isLocked =
                            $lockTimestamp !== false &&
                            $lockTimestamp > time();

                        $requiresPasswordChange =
                            !empty($managedUser['must_change_password']);

                        $hasNeverLoggedIn =
                            empty($managedUser['last_login']);

                        ?>



                        <a
                            href="<?= $escape(
                                        $userUrl
                                    ) ?>"
                            class="manage-user-row<?= $isSelected
                                                        ? ' active'
                                                        : ''
                                                    ?>">

                            <span class="manage-user-avatar">

                                <?= $escape(
                                    strtoupper(
                                        mb_substr(
                                            $managedUser['first_name']
                                                ?? 'U',
                                            0,
                                            1
                                        )
                                    )
                                ) ?>

                            </span>

                            <span class="manage-user-copy">

                                <strong>
                                    <?= $escape(
                                        $fullName(
                                            $managedUser
                                        )
                                    ) ?>
                                </strong>

                                <small>
                                    <?= $escape(
                                        $managedUser['role_prefix']
                                            ?? 'Unassigned'
                                    ) ?>

                                    Â·

                                    <?= $escape(
                                        $managedUser['studID']
                                            ?? $managedUser['email']
                                            ?? 'No identifier'
                                    ) ?>
                                </small>

                            </span>

                            <span class="manage-user-row-badges">

                                <?php if ($isLocked): ?>

                                    <span
                                        class="manage-user-security-badge locked"
                                        title="This account is temporarily locked"
                                        aria-label="Account temporarily locked">

                                        <i class="fa-solid fa-lock"></i>

                                    </span>

                                <?php elseif ($requiresPasswordChange): ?>

                                    <span
                                        class="manage-user-security-badge password"
                                        title="Password change required"
                                        aria-label="Password change required">

                                        <i class="fa-solid fa-key"></i>

                                    </span>

                                <?php elseif ($hasNeverLoggedIn): ?>

                                    <span
                                        class="manage-user-security-badge new"
                                        title="This user has never logged in"
                                        aria-label="Never logged in">

                                        <i class="fa-regular fa-clock"></i>

                                    </span>

                                <?php endif; ?>

                                <span class="manage-user-status <?= $escape(
                                                                    strtolower(
                                                                        $managedUser['status']
                                                                            ?? ''
                                                                    )
                                                                ) ?>">

                                    <?= $escape(
                                        $managedUser['status']
                                            ?? 'Unknown'
                                    ) ?>

                                </span>

                            </span>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </article>

        <article class="page-card manage-user-details">

            <?php if (!$selectedUser): ?>

                <div class="manage-users-empty">

                    <i class="fa-regular fa-address-card"></i>

                    <h3>Select a user</h3>

                    <p>
                        Choose an account from the directory
                        to inspect its information.
                    </p>

                </div>

            <?php else: ?>

                <?php

                $selectedLockUntil =
                    trim(
                        (string) (
                            $selectedUser['lock_until']
                            ?? ''
                        )
                    );

                $selectedLockTimestamp =
                    $selectedLockUntil !== ''
                    ? strtotime($selectedLockUntil)
                    : false;

                $selectedIsLocked =
                    $selectedLockTimestamp !== false &&
                    $selectedLockTimestamp > time();

                $selectedRequiresPasswordChange =
                    !empty($selectedUser['must_change_password']);

                $selectedHasNeverLoggedIn =
                    empty($selectedUser['last_login']);

                ?>

                <header class="manage-user-profile-header">

                    <span class="manage-user-profile-avatar">

                        <?= $escape(
                            strtoupper(
                                mb_substr(
                                    $selectedUser['first_name']
                                        ?? 'U',
                                    0,
                                    1
                                )
                            )
                        ) ?>

                    </span>

                    <div>

                        <span class="page-eyebrow">
                            Account Details

                        </span>

                        <h2>
                            <?= $escape(
                                $fullName(
                                    $selectedUser
                                )
                            ) ?>
                        </h2>

                        <p>
                            <?= $escape(
                                $selectedUser['email']
                                    ?? 'No email address'
                            ) ?>
                        </p>

                    </div>

                    <div class="manage-user-profile-badges">

                        <?php if ($selectedIsLocked): ?>

                            <span class="manage-user-profile-flag locked">

                                <i class="fa-solid fa-lock"></i>

                                Temporarily Locked

                            </span>

                        <?php endif; ?>

                        <?php if ($selectedRequiresPasswordChange): ?>

                            <span class="manage-user-profile-flag password">

                                <i class="fa-solid fa-key"></i>

                                Password Change Required

                            </span>

                        <?php endif; ?>

                        <?php if ($selectedHasNeverLoggedIn): ?>

                            <span class="manage-user-profile-flag new">

                                <i class="fa-regular fa-clock"></i>

                                Never Logged In

                            </span>

                        <?php endif; ?>

                        <span class="manage-user-status <?= $escape(
                                                            strtolower(
                                                                $selectedUser['status']
                                                                    ?? ''
                                                            )
                                                        ) ?>">

                            <?= $escape(
                                $selectedUser['status']
                                    ?? 'Unknown'
                            ) ?>

                        </span>

                    </div>

                            <?php if (($selectedUser['role_prefix'] ?? '') === 'Parent'): ?>
                                <a class="app-button secondary manage-user-verification-link" href="index.php?page=account_approvals&amp;user_id=<?= (int)$selectedUser['user_id'] ?>">Review child verification</a>
                            <?php endif; ?>

                </header>

                <nav
                    class="manage-user-tabs"
                    aria-label="Selected user information">

                    <button
                        type="button"
                        class="active"
                        data-user-tab="overview"
                        aria-selected="true">

                        <i class="fa-regular fa-address-card"></i>

                        Overview

                    </button>

                    <button
                        type="button"
                        data-user-tab="access"
                        aria-selected="false">

                        <i class="fa-solid fa-shield-halved"></i>

                        Access & Security

                    </button>

                    <button
                        type="button"
                        data-user-tab="history"
                        aria-selected="false">

                        <i class="fa-solid fa-clock-rotate-left"></i>

                        Activity History

                    </button>

                </nav>

                <section
                    class="manage-user-tab-panel active"
                    data-user-tab-panel="overview">

                    <?php

                    $detailItems = [
                        'Role' =>
                        $selectedUser['role_prefix']
                            ?? 'Unassigned',

                        (($selectedUser['role_prefix'] ?? '') === 'Student'
                            ? 'Student ID'
                            : 'Account ID') =>
                        $selectedUser['studID']
                            ?? 'Not applicable',

                        'Department' =>
                        $selectedUser['department_name']
                            ?? 'Not assigned',

                        'Education Level' =>
                        $selectedUser['education_level_name']
                            ?? 'Not assigned',

                        'Program or Strand' =>
                        $selectedUser['academic_program_name']
                            ?? 'Not assigned',

                        'Grade or Year Level' =>
                        $selectedUser['grade_level_name']
                            ?? 'Not assigned',

                        'Section' =>
                        $selectedUser['section_name']
                            ?? 'Not assigned',

                        'Last Login' =>
                        !empty($selectedUser['last_login'])
                            ? date(
                                'M d, Y Â· h:i A',
                                strtotime(
                                    $selectedUser['last_login']
                                )
                            )
                            : 'Never',

                        'Registered' =>
                        !empty($selectedUser['created_at'])
                            ? date(
                                'M d, Y Â· h:i A',
                                strtotime(
                                    $selectedUser['created_at']
                                )
                            )
                            : 'Unknown'
                    ];

                    ?>

                    <dl class="manage-user-detail-grid">

                        <?php foreach (
                            $detailItems
                            as $label => $value
                        ): ?>

                            <div>

                                <dt>
                                    <?= $escape(
                                        $label
                                    ) ?>
                                </dt>

                                <dd>
                                    <?= $escape(
                                        $value
                                    ) ?>
                                </dd>

                            </div>

                        <?php endforeach; ?>

                    </dl>

                    <?php if (
                        (
                            $selectedUser['role_prefix']
                            ?? ''
                        ) === 'Parent'
                    ): ?>

                        <section class="manage-user-relationship">

                            <span>

                                <i class="fa-solid fa-people-roof"></i>

                            </span>

                            <div>

                                <small>
                                    Linked Student
                                </small>

                                <strong>
                                    <?= $escape(
                                        $selectedUser['child_name']
                                            ?? 'Not linked'
                                    ) ?>
                                </strong>

                                <p>
                                    <?= $escape(
                                        $selectedUser['relationship']
                                            ?? 'Relationship unavailable'
                                    ) ?>

                                    Â·

                                    <?= $escape(
                                        $selectedUser['relationship_status']
                                            ?? 'Unknown'
                                    ) ?>
                                </p>

                            </div>

                        </section>

                    <?php endif; ?>

                    <?php if (($selectedUser['role_prefix'] ?? '') === 'Faculty'
                        && in_array($selectedUser['status'] ?? '', ['Active', 'Inactive'], true)): ?>
                        <form id="facultyAssignmentForm" class="faculty-assignment-form" method="post"
                            action="index.php?page=manage_user_update_faculty_assignment">
                            <header class="faculty-assignment-heading">
                                <h3>Faculty Posting Assignment</h3>
                                <p>College Faculty can post within their assigned program. IBED Faculty can post within their assigned education level.</p>
                            </header>
                            <?= csrfInput() ?>
                            <input type="hidden" name="user_id" value="<?= (int) $selectedUser['user_id'] ?>">
                            <input type="hidden" name="confirm_faculty_assignment" value="0">
                            <?php
                            $facultyAssignmentValues = $selectedUser;
                            require __DIR__ . '/../include/components/faculty-assignment-fields.php';
                            ?>
                            <footer class="faculty-assignment-actions">
                                <button type="submit" class="app-button primary">Update Faculty Assignment</button>
                            </footer>
                        </form>
                    <?php else: ?>
                        <div class="manage-user-readonly-notice">
                            <i class="fa-solid fa-shield-halved"></i>
                            <p>Personal identity and Student academic records are read-only here. Student placement changes belong to the authorized Enrollment and Academic Management workflow.</p>
                        </div>
                    <?php endif; ?>

                </section>

                <section
                    class="manage-user-tab-panel"
                    data-user-tab-panel="access"
                    hidden>

                    <?php

                    $managedStatus =
                        trim(
                            (string) (
                                $selectedUser['status']
                                ?? ''
                            )
                        );

                    $managedRole =
                        trim(
                            (string) (
                                $selectedUser['role_prefix']
                                ?? ''
                            )
                        );

                    $managedUserId =
                        (int) (
                            $selectedUser['user_id']
                            ?? 0
                        );

                    $currentAdminId =
                        (int) (
                            $_SESSION['user_id']
                            ?? 0
                        );

                    $canChangeManagedStatus =
                        in_array(
                            $managedStatus,
                            [
                                'Active',
                                'Inactive'
                            ],
                            true
                        );

                    $isOwnActiveAdminAccount =
                        $managedUserId === $currentAdminId &&
                        $managedRole === 'Admin' &&
                        $managedStatus === 'Active';

                    $newManagedStatus =
                        $managedStatus === 'Active'
                        ? 'Inactive'
                        : 'Active';

                    ?>



                    <?php if (
                        $canChangeManagedStatus &&
                        !$isOwnActiveAdminAccount
                    ): ?>

                        <section
                            class="manage-user-access-panel <?= $escape(
                                                                strtolower(
                                                                    $newManagedStatus
                                                                )
                                                            ) ?>">

                            <header>

                                <span class="manage-user-access-icon">

                                    <i class="<?= $newManagedStatus === 'Active'
                                                    ? 'fa-solid fa-user-check'
                                                    : 'fa-solid fa-user-slash'
                                                ?>"></i>

                                </span>

                                <div>

                                    <span class="page-eyebrow">
                                        Account Access
                                    </span>

                                    <h3>
                                        <?= $newManagedStatus === 'Active'
                                            ? 'Activate Account'
                                            : 'Deactivate Account'
                                        ?>
                                    </h3>

                                    <p>
                                        <?= $newManagedStatus === 'Active'
                                            ? 'Restore the userâ€™s access to the Digital Hub.'
                                            : 'Temporarily prevent this user from signing in.'
                                        ?>
                                    </p>

                                </div>

                            </header>

                            <form
                                id="manageStatusForm"
                                action="index.php?page=manage_user_change_status"
                                method="post"
                                novalidate
                                data-user-name="<?= $escape(
                                                    $fullName(
                                                        $selectedUser
                                                    )
                                                ) ?>"
                                data-new-status="<?= $escape(
                                                        $newManagedStatus
                                                    ) ?>">

                                <?= csrfInput() ?>

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= $managedUserId ?>">

                                <input
                                    type="hidden"
                                    name="return_tab"
                                    value="access">

                                <input
                                    type="hidden"
                                    name="new_status"
                                    value="<?= $escape(
                                                $newManagedStatus
                                            ) ?>">

                                <input
                                    type="hidden"
                                    id="confirmStatusChange"
                                    name="confirm_status_change"
                                    value="0">

                                <?php foreach (
                                    $filters
                                    as $filterName => $filterValue
                                ): ?>

                                    <input
                                        type="hidden"
                                        name="return_<?= $escape(
                                                            $filterName
                                                        ) ?>"
                                        value="<?= $escape(
                                                    $filterValue
                                                ) ?>">

                                <?php endforeach; ?>

                                <label for="managedStatusReason">

                                    <span>
                                        Reason for
                                        <?= strtolower(
                                            $newManagedStatus
                                        ) ?>
                                        *
                                    </span>

                                    <textarea
                                        id="managedStatusReason"
                                        name="status_reason"
                                        maxlength="1000"
                                        rows="4"
                                        placeholder="<?= $newManagedStatus === 'Active'
                                                            ? 'Explain why account access is being restored.'
                                                            : 'Explain why account access is being suspended.'
                                                        ?>"
                                        required></textarea>

                                    <small>
                                        This reason will be retained in the permanent
                                        account-status history.
                                    </small>

                                </label>

                                <footer>

                                    <p>

                                        <i class="fa-solid fa-clock-rotate-left"></i>

                                        This action is reversible and does not delete
                                        the user or their records.

                                    </p>

                                    <button
                                        type="submit"
                                        class="app-button <?= $newManagedStatus === 'Active'
                                                                ? 'success'
                                                                : 'danger'
                                                            ?>">

                                        <i class="<?= $newManagedStatus === 'Active'
                                                        ? 'fa-solid fa-user-check'
                                                        : 'fa-solid fa-user-slash'
                                                    ?>"></i>

                                        Review
                                        <?= $newManagedStatus === 'Active'
                                            ? 'Activation'
                                            : 'Deactivation'
                                        ?>

                                    </button>

                                </footer>

                            </form>

                        </section>

                    <?php elseif ($isOwnActiveAdminAccount): ?>

                        <div class="manage-user-protected-notice">

                            <i class="fa-solid fa-lock"></i>

                            <p>
                                Your current Administrator account cannot be
                                deactivated from its own active session.
                            </p>

                        </div>

                    <?php endif; ?>

                    <?php

                    $managedFailedAttempts =
                        max(
                            0,
                            (int) (
                                $selectedUser['failed_attempts']
                                ?? 0
                            )
                        );

                    $managedLockUntil =
                        trim(
                            (string) (
                                $selectedUser['lock_until']
                                ?? ''
                            )
                        );

                    $managedLockTimestamp =
                        $managedLockUntil !== ''
                        ? strtotime(
                            $managedLockUntil
                        )
                        : false;

                    $hasFutureAccountLock =
                        $managedLockTimestamp !== false &&
                        $managedLockTimestamp > time();

                    $canUnlockManagedAccount =
                        (
                            $selectedUser['status']
                            ?? ''
                        ) === 'Active' &&
                        (
                            $managedFailedAttempts > 0 ||
                            $hasFutureAccountLock
                        );

                    ?>

                    <?php if ($canUnlockManagedAccount): ?>

                        <section class="manage-user-lock-panel">

                            <header>

                                <span class="manage-user-lock-icon">

                                    <i class="fa-solid fa-lock"></i>

                                </span>

                                <div>

                                    <span class="page-eyebrow">
                                        Login Security
                                    </span>

                                    <h3>
                                        Account Lock Detected
                                    </h3>

                                    <p>
                                        This account has failed login activity or
                                        an active temporary login lock.
                                    </p>

                                </div>

                            </header>

                            <dl class="manage-user-lock-details">

                                <div>

                                    <dt>
                                        Failed Attempts
                                    </dt>

                                    <dd>
                                        <?= number_format(
                                            $managedFailedAttempts
                                        ) ?>
                                    </dd>

                                </div>

                                <div>

                                    <dt>
                                        Locked Until
                                    </dt>

                                    <dd>
                                        <?= $hasFutureAccountLock
                                            ? $escape(
                                                date(
                                                    'M d, Y Â· h:i A',
                                                    $managedLockTimestamp
                                                )
                                            )
                                            : 'No active timed lock'
                                        ?>
                                    </dd>

                                </div>

                            </dl>

                            <form
                                id="manageUnlockForm"
                                action="index.php?page=manage_user_unlock"
                                method="post"
                                data-user-name="<?= $escape(
                                                    $fullName(
                                                        $selectedUser
                                                    )
                                                ) ?>">

                                <?= csrfInput() ?>

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int) (
                                                $selectedUser['user_id']
                                                ?? 0
                                            ) ?>">

                                <input
                                    type="hidden"
                                    name="return_tab"
                                    value="access">

                                <input
                                    type="hidden"
                                    id="confirmAccountUnlock"
                                    name="confirm_unlock"
                                    value="0">

                                <?php foreach (
                                    $filters
                                    as $filterName => $filterValue
                                ): ?>

                                    <input
                                        type="hidden"
                                        name="return_<?= $escape(
                                                            $filterName
                                                        ) ?>"
                                        value="<?= $escape(
                                                    $filterValue
                                                ) ?>">

                                <?php endforeach; ?>

                                <footer>

                                    <p>

                                        <i class="fa-solid fa-shield-halved"></i>

                                        Unlocking clears failed attempts and the
                                        temporary lock. It does not change the password.

                                    </p>

                                    <button
                                        type="submit"
                                        class="app-button warning">

                                        <i class="fa-solid fa-lock-open"></i>

                                        Unlock Account

                                    </button>

                                </footer>

                            </form>

                        </section>

                    <?php endif; ?>

                    <?php

                    $staffRole =
                        trim(
                            (string) (
                                $selectedUser['role_prefix']
                                ?? ''
                            )
                        );

                    $staffStatus =
                        trim(
                            (string) (
                                $selectedUser['status']
                                ?? ''
                            )
                        );

                    $staffUserId =
                        (int) (
                            $selectedUser['user_id']
                            ?? 0
                        );

                    $isManagedStaff =
                        in_array(
                            $staffRole,
                            [
                                'Admin',
                                'Faculty'
                            ],
                            true
                        ) &&
                        in_array(
                            $staffStatus,
                            [
                                'Active',
                                'Inactive'
                            ],
                            true
                        );

                    $isCurrentAdministrator =
                        $staffUserId ===
                        (int) (
                            $_SESSION['user_id']
                            ?? 0
                        ) &&
                        $staffRole === 'Admin';

                    ?>

                    <?php if (
                        $isManagedStaff &&
                        !$isCurrentAdministrator
                    ): ?>

                        <section class="manage-user-role-panel">

                            <header>

                                <span class="manage-user-role-icon">

                                    <i class="fa-solid fa-user-shield"></i>

                                </span>

                                <div>

                                    <span class="page-eyebrow">
                                        System Authorization
                                    </span>

                                    <h3>
                                        Change Staff Role
                                    </h3>

                                    <p>
                                        Change access between the Faculty and
                                        Administrator staff roles.
                                    </p>

                                </div>

                            </header>

                            <form
                                id="manageRoleForm"
                                action="index.php?page=manage_user_change_role"
                                method="post"
                                novalidate
                                data-user-name="<?= $escape(
                                                    $fullName(
                                                        $selectedUser
                                                    )
                                                ) ?>"
                                data-current-role="<?= $escape(
                                                        $staffRole
                                                    ) ?>">

                                <?= csrfInput() ?>

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= $staffUserId ?>">



                                <input
                                    type="hidden"
                                    name="return_tab"
                                    value="access">

                                <input
                                    type="hidden"
                                    id="confirmRoleChange"
                                    name="confirm_role_change"
                                    value="0">

                                <?php foreach (
                                    $filters
                                    as $filterName => $filterValue
                                ): ?>

                                    <input
                                        type="hidden"
                                        name="return_<?= $escape(
                                                            $filterName
                                                        ) ?>"
                                        value="<?= $escape(
                                                    $filterValue
                                                ) ?>">

                                <?php endforeach; ?>

                                <div class="manage-user-role-grid">

                                    <label>

                                        <span>
                                            Current Role
                                        </span>

                                        <input
                                            type="text"
                                            value="<?= $escape(
                                                        $staffRole
                                                    ) ?>"
                                            readonly>

                                    </label>

                                    <label>

                                        <span>
                                            New Staff Role *
                                        </span>

                                        <select
                                            id="managedNewRole"
                                            name="new_role_id"
                                            required>

                                            <option value="">
                                                Select new role
                                            </option>

                                            <?php foreach (
                                                $roles
                                                as $role
                                            ): ?>

                                                <?php

                                                $rolePrefix =
                                                    trim(
                                                        (string) (
                                                            $role['role_prefix']
                                                            ?? ''
                                                        )
                                                    );

                                                $isAllowedNewRole =
                                                    in_array(
                                                        $rolePrefix,
                                                        [
                                                            'Admin',
                                                            'Faculty'
                                                        ],
                                                        true
                                                    ) &&
                                                    $rolePrefix !==
                                                    $staffRole;

                                                ?>

                                                <?php if (
                                                    $isAllowedNewRole
                                                ): ?>

                                                    <option
                                                        value="<?= (int) (
                                                                    $role['role_id']
                                                                    ?? 0
                                                                ) ?>"
                                                        data-role-prefix="<?= $escape(
                                                                                $rolePrefix
                                                                            ) ?>">

                                                        <?= $escape(
                                                            $rolePrefix
                                                        ) ?>

                                                    </option>

                                                <?php endif; ?>

                                            <?php endforeach; ?>

                                        </select>

                                    </label>

                                </div>

                                <label
                                    class="manage-user-role-reason"
                                    for="managedRoleReason">

                                    <span>
                                        Reason for role change *
                                    </span>

                                    <textarea
                                        id="managedRoleReason"
                                        name="role_change_reason"
                                        maxlength="1000"
                                        rows="4"
                                        placeholder="Explain why this staff member requires a different system role."
                                        required></textarea>

                                    <small>
                                        This reason and both roles will be retained
                                        in the permanent role-change history.
                                    </small>

                                </label>

                                <footer>

                                    <p>

                                        <i class="fa-solid fa-triangle-exclamation"></i>

                                        Administrator access includes sensitive
                                        user-management and approval permissions.

                                    </p>

                                    <button
                                        type="submit"
                                        class="app-button role">

                                        <i class="fa-solid fa-user-gear"></i>

                                        Review Role Change

                                    </button>

                                </footer>

                            </form>

                        </section>

                    <?php elseif ($isCurrentAdministrator): ?>

                        <div class="manage-user-protected-notice">

                            <i class="fa-solid fa-user-shield"></i>

                            <p>
                                You cannot remove the Administrator role from
                                the account currently operating this session.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>

                <section
                    class="manage-user-tab-panel"
                    data-user-tab-panel="history"
                    hidden>

                    <?php if (
                        empty($selectedUserHistory)
                    ): ?>

                        <div class="manage-user-history-empty">

                            <i class="fa-solid fa-clock-rotate-left"></i>

                            <h3>No account activity yet</h3>

                            <p>
                                Structured account, access, role, and security
                                events will appear here.
                            </p>

                        </div>

                    <?php else: ?>

                        <header class="manage-user-history-header">

                            <div>

                                <span class="page-eyebrow">
                                    Audit Timeline
                                </span>

                                <h3>Account Activity</h3>

                                <p>
                                    Chronological account and security changes
                                    associated with this user.
                                </p>

                            </div>

                            <span>
                                <?= number_format(
                                    count(
                                        $selectedUserHistory
                                    )
                                ) ?>
                                <?= count(
                                    $selectedUserHistory
                                ) === 1
                                    ? 'event'
                                    : 'events'
                                ?>
                            </span>

                        </header>

                        <ol class="manage-user-timeline">

                            <?php foreach (
                                $selectedUserHistory
                                as $historyItem
                            ): ?>

                                <?php

                                $eventType =
                                    trim(
                                        (string) (
                                            $historyItem['event_type']
                                            ?? 'info'
                                        )
                                    );

                                $eventIcon =
                                    match ($eventType) {
                                        'status' =>
                                        'fa-solid fa-toggle-on',

                                        'role' =>
                                        'fa-solid fa-user-shield',

                                        'security' =>
                                        'fa-solid fa-lock',

                                        'provisioning' =>
                                        'fa-solid fa-user-plus',

                                        'creation' =>
                                        'fa-solid fa-user-clock',

                                        'review' =>
                                        'fa-solid fa-user-check',

                                        default =>
                                        'fa-solid fa-circle-info'
                                    };

                                $eventTitle =
                                    trim(
                                        (string) (
                                            $historyItem['title']
                                            ?? 'Account activity'
                                        )
                                    );

                                if (
                                    (
                                        $historyItem['event_source']
                                        ?? ''
                                    ) === 'activity_log'
                                ) {
                                    $eventTitle =
                                        ucwords(
                                            strtolower(
                                                $eventTitle
                                            )
                                        );
                                }

                                $previousValue =
                                    trim(
                                        (string) (
                                            $historyItem['previous_value']
                                            ?? ''
                                        )
                                    );

                                $newValue =
                                    trim(
                                        (string) (
                                            $historyItem['new_value']
                                            ?? ''
                                        )
                                    );

                                $reason =
                                    trim(
                                        (string) (
                                            $historyItem['reason']
                                            ?? ''
                                        )
                                    );

                                $description =
                                    trim(
                                        (string) (
                                            $historyItem['description']
                                            ?? ''
                                        )
                                    );

                                $actorName =
                                    trim(
                                        (string) (
                                            $historyItem['actor_name']
                                            ?? ''
                                        )
                                    );

                                $occurredAt =
                                    !empty($historyItem['occurred_at'])
                                    ? date(
                                        'M d, Y Â· h:i A',
                                        strtotime(
                                            $historyItem['occurred_at']
                                        )
                                    )
                                    : 'Unknown time';

                                ?>

                                <li class="<?= $escape(
                                                $eventType
                                            ) ?>">

                                    <span class="manage-user-timeline-icon">

                                        <i class="<?= $escape(
                                                        $eventIcon
                                                    ) ?>"></i>

                                    </span>

                                    <article>

                                        <header>

                                            <div>

                                                <h4>
                                                    <?= $escape(
                                                        $eventTitle
                                                    ) ?>
                                                </h4>

                                                <time>
                                                    <?= $escape(
                                                        $occurredAt
                                                    ) ?>
                                                </time>

                                            </div>

                                            <?php if (
                                                $previousValue !== '' ||
                                                $newValue !== ''
                                            ): ?>

                                                <span class="manage-user-transition">

                                                    <?= $escape(
                                                        $previousValue !== ''
                                                            ? $previousValue
                                                            : 'Created'
                                                    ) ?>

                                                    <i class="fa-solid fa-arrow-right"></i>

                                                    <?= $escape(
                                                        $newValue !== ''
                                                            ? $newValue
                                                            : 'Updated'
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>

                                        </header>

                                        <?php if (
                                            $description !== ''
                                        ): ?>

                                            <p>
                                                <?= $escape(
                                                    $description
                                                ) ?>
                                            </p>

                                        <?php endif; ?>

                                        <?php if (
                                            $reason !== ''
                                        ): ?>

                                            <div class="manage-user-history-reason">

                                                <strong>Reason</strong>

                                                <span>
                                                    <?= $escape(
                                                        $reason
                                                    ) ?>
                                                </span>

                                            </div>

                                        <?php endif; ?>

                                        <footer>

                                            <i class="fa-regular fa-user"></i>

                                            <?= $escape(
                                                $actorName !== ''
                                                    ? $actorName
                                                    : 'System'
                                            ) ?>

                                        </footer>

                                    </article>

                                </li>

                            <?php endforeach; ?>

                        </ol>

                    <?php endif; ?>

                </section>

        </article>

    <?php endif; ?>


    </section>
    <div
        id="facultyProvisionModal"
        class="manage-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="facultyProvisionTitle"
        hidden>

        <div
            class="manage-modal-backdrop"
            data-close-faculty-modal></div>

        <section class="manage-modal-card">

            <header>

                <div>

                    <span class="page-eyebrow">
                        Staff Provisioning
                    </span>

                    <h2 id="facultyProvisionTitle">
                        Create Faculty Account
                    </h2>

                    <p>
                        Create an active Faculty account with a
                        one-time temporary password.
                    </p>

                </div>

                <button
                    type="button"
                    class="manage-modal-close"
                    data-close-faculty-modal
                    aria-label="Close Faculty account form">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </header>

            <form
                id="facultyProvisionForm"
                action="index.php?page=manage_user_provision_faculty"
                method="post"
                novalidate>

                <?= csrfInput() ?>

                <input
                    type="hidden"
                    id="confirmFacultyProvisioning"
                    name="confirm_faculty_provisioning"
                    value="0">

                <div class="faculty-provision-grid">

                    <label>

                        <span>First Name *</span>

                        <input
                            type="text"
                            name="first_name"
                            maxlength="100"
                            autocomplete="off"
                            value="<?= $escape(
                                        $facultyOldInput['first_name']
                                            ?? ''
                                    ) ?>"
                            required>

                    </label>



                    <label>

                        <span>Last Name *</span>

                        <input
                            type="text"
                            name="last_name"
                            maxlength="100"
                            autocomplete="off"
                            value="<?= $escape(
                                        $facultyOldInput['last_name']
                                            ?? ''
                                    ) ?>"
                            required>

                    </label>



                    <label class="faculty-field-wide">

                        <span>Email Address *</span>

                        <input
                            type="email"
                            name="email"
                            maxlength="100"
                            autocomplete="off"
                            value="<?= $escape(
                                        $facultyOldInput['email']
                                            ?? ''
                                    ) ?>"
                            required>

                    </label>





                    <?php
                    $facultyAssignmentValues = $facultyOldInput;
                    require __DIR__ . '/../include/components/faculty-assignment-fields.php';
                    ?>

                </div>

                <div class="faculty-provision-notice">

                    <i class="fa-solid fa-key"></i>

                    <p>
                        A temporary password will appear after creation. Share it privately with the Faculty member. They must change it and complete their profile at first sign-in.
                    </p>

                </div>

                <footer>

                    <button
                        type="button"
                        class="app-button secondary"
                        data-close-faculty-modal>

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="app-button primary">

                        <i class="fa-solid fa-user-plus"></i>

                        Review Account

                    </button>

                </footer>

            </form>

        </section>

    </div>

    <?php if (
        $facultyProvisioningType === 'success'
    ): ?>

        <div
            id="facultyProvisioningResult"
            hidden
            data-name="<?= $escape(
                            $facultyProvisioningFlash['name']
                                ?? ''
                        ) ?>"
            data-email="<?= $escape(
                            $facultyProvisioningFlash['email']
                                ?? ''
                        ) ?>"
            data-temporary-password="<?= $escape(
                                            $facultyProvisioningFlash['temporary_password']
                                                ?? ''
                                        ) ?>">
        </div>

    <?php endif; ?>

    <div
        id="facultyProvisioningState"
        hidden
        data-open-form="<?= $facultyProvisioningType === 'error'
                            ? '1'
                            : '0'
                        ?>">
    </div>


</section>
