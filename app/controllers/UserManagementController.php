<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/UserManagementService.php';

class UserManagementController extends BaseController
{
    private UserManagementService $service;

    public function __construct()
    {
        $this->service =
            new UserManagementService();
    }

    /* ==========================================
       MANAGE USERS PAGE
    ========================================== */

    public function index(): array
    {
        $this->requireRole(
            'Admin'
        );

        $selectedUserId =
            filter_input(
                INPUT_GET,
                'user_id',
                FILTER_VALIDATE_INT
            );

        $selectedUserId =
            $selectedUserId &&
            $selectedUserId > 0
            ? (int) $selectedUserId
            : 0;

        $filters = [
            'search' =>
            $_GET['search']
                ?? '',

            'role_id' =>
            $_GET['role_id']
                ?? 0,

            'status' =>
            $_GET['status']
                ?? '',

            'department_id' =>
            $_GET['department_id']
                ?? 0,

            'education_level_id' =>
            $_GET['education_level_id']
                ?? 0
        ];

        $facultyProvisioningFlash =
            $_SESSION['faculty_provisioning_flash']
            ?? null;

        unset(
            $_SESSION['faculty_provisioning_flash']
        );

        try {
            $directory =
                $this->service
                ->getDirectory(
                    $filters,
                    $selectedUserId
                );

            $directory['faculty_provisioning_flash'] =
                $facultyProvisioningFlash;

            return $directory;
        } catch (Throwable $exception) {
            error_log(
                'Manage Users directory error: '
                    . publicErrorMessage($exception)
            );

            return [
                'users' =>
                [],

                'selected_user' =>
                null,

                'selected_user_history' =>
                [],

                'selected_user_id' =>
                0,

                'filters' =>
                $filters,

                'counts' => [
                    'total' => 0,
                    'Pending' => 0,
                    'Active' => 0,
                    'Inactive' => 0,
                    'Rejected' => 0,
                    'Admin' => 0,
                    'Faculty' => 0,
                    'Student' => 0,
                    'Parent' => 0
                ],

                'roles' =>
                [],

                'departments' =>
                [],

                'education_levels' =>
                [],

                'academic_programs' =>
                [],

                'grade_levels' =>
                [],

                'sections' =>
                [],

                'statuses' => [
                    'Pending',
                    'Active',
                    'Inactive',
                    'Rejected'
                ],

                'faculty_provisioning_flash' =>
                $facultyProvisioningFlash,

                'directory_error' =>
                'The user directory could not be loaded.'
            ];
        }
    }

    /* ==========================================
   PROVISION FACULTY ACCOUNT
========================================== */

    public function updateFacultyAssignment(): void
    {
        $this->requireRole('Admin');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Method not allowed.';
            return;
        }
        try {
            $this->requireCsrfToken();
        } catch (RuntimeException $exception) {
            http_response_code(419);
            echo 'Your form session expired. Refresh the page and try again.';
            return;
        }
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $userId = $userId !== false && $userId > 0 ? (int) $userId : 0;
        try {
            if (($_POST['confirm_faculty_assignment'] ?? '') !== '1') {
                throw new InvalidArgumentException('Confirm the Faculty assignment change.');
            }
            $this->service->updateFacultyAssignment($userId, (int) ($_SESSION['user_id'] ?? 0), $_POST);
            try {
                $this->log('UPDATE_FACULTY_ASSIGNMENT', 'Administrator updated the academic posting assignment of Faculty #' . $userId, $userId);
            } catch (Throwable $exception) {
                error_log('Faculty assignment audit logging failed: ' . publicErrorMessage($exception));
            }
            $this->redirect($this->buildManageUsersRedirect($userId, 'success', 'Faculty assignment updated successfully.'));
        } catch (InvalidArgumentException | DomainException $exception) {
            $this->redirect($this->buildManageUsersRedirect($userId, 'error', publicErrorMessage($exception)));
        } catch (Throwable $exception) {
            error_log('Faculty assignment update failed: ' . publicErrorMessage($exception));
            $this->redirect($this->buildManageUsersRedirect($userId, 'error', 'Unable to update the Faculty assignment. Please try again.'));
        }
    }

    public function provisionFaculty(): void
    {
        $this->requireRole(
            'Admin'
        );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $confirmation =
                trim(
                    (string) (
                        $_POST['confirm_faculty_provisioning']
                        ?? ''
                    )
                );

            if ($confirmation !== '1') {
                throw new RuntimeException(
                    'Confirm the Faculty account creation before continuing.'
                );
            }

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $faculty =
                $this->service
                ->provisionFaculty(
                    $adminId,
                    [
                        'first_name' =>
                        $_POST['first_name']
                            ?? '',

                        'last_name' =>
                        $_POST['last_name']
                            ?? '',

                        'email' =>
                        $_POST['email']
                            ?? '',

                        'education_level_id' => $_POST['education_level_id'] ?? 0,
                        'academic_program_id' => $_POST['academic_program_id'] ?? null,

                        'department_id' =>
                        $_POST['department_id']
                            ?? 0
                    ]
                );

            $facultyUserId =
                (int) (
                    $faculty['user_id']
                    ?? 0
                );

            $facultyName =
                trim(
                    implode(
                        ' ',
                        array_filter(
                            [
                                $faculty['first_name']
                                    ?? '',

                                $faculty['middle_name']
                                    ?? '',

                                $faculty['last_name']
                                    ?? '',

                                $faculty['name_suffix']
                                    ?? ''
                            ],
                            static fn(
                                mixed $value
                            ): bool =>
                            trim(
                                (string) $value
                            ) !== ''
                        )
                    )
                );

            $_SESSION['faculty_provisioning_flash'] = [
                'type' =>
                'success',

                'user_id' =>
                $facultyUserId,

                'name' =>
                $facultyName,

                'email' =>
                $faculty['email']
                    ?? '',

                /*
             * Displayed exactly once on the next request.
             */
                'temporary_password' =>
                $faculty['temporary_password']
                    ?? ''
            ];

            try {
                $this->log(
                    'PROVISION_FACULTY_ACCOUNT',
                    'Administrator provisioned the Faculty account of '
                        . (
                            $facultyName !== ''
                            ? $facultyName
                            : 'user #' . $facultyUserId
                        )
                        . '. User ID: '
                        . $facultyUserId
                        . '.',
                    $facultyUserId
                );
            } catch (Throwable $loggingException) {
                /*
             * Never write the temporary password
             * to the general activity log.
             */
            }

            $this->redirect(
                'index.php?'
                    . http_build_query([
                        'page' =>
                        'manage_users',

                        'user_id' =>
                        $facultyUserId,

                        'success' =>
                        'Faculty account created successfully.'
                    ])
            );
        } catch (Throwable $exception) {
            $_SESSION['faculty_provisioning_flash'] = [
                'type' =>
                'error',

                'message' =>
                publicErrorMessage($exception),

                /*
             * Preserve only non-sensitive form values.
             */
                'old_input' => [
                    'first_name' =>
                    $_POST['first_name']
                        ?? '',

                    'middle_name' =>
                    $_POST['middle_name']
                        ?? '',

                    'last_name' =>
                    $_POST['last_name']
                        ?? '',

                    'name_suffix' =>
                    $_POST['name_suffix']
                        ?? '',

                    'email' =>
                    $_POST['email']
                        ?? '',

                    'gender' =>
                    $_POST['gender']
                        ?? '',

                    'birthdate' =>
                    $_POST['birthdate']
                        ?? '',

                    'education_level_id' => $_POST['education_level_id'] ?? '',
                    'academic_program_id' => $_POST['academic_program_id'] ?? '',

                    'department_id' =>
                    $_POST['department_id']
                        ?? ''
                ]
            ];

            $this->redirect(
                'index.php?'
                    . http_build_query([
                        'page' =>
                        'manage_users',

                        'error' =>
                        publicErrorMessage($exception),

                        'open_faculty_form' =>
                        1
                    ])
            );
        }
    }

    /* ==========================================
   CHANGE ACCOUNT STATUS
========================================== */

    public function changeAccountStatus(): void
    {
        $this->requireRole(
            'Admin'
        );

        $userId =
            filter_input(
                INPUT_POST,
                'user_id',
                FILTER_VALIDATE_INT
            );

        $userId =
            $userId && $userId > 0
            ? (int) $userId
            : 0;

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            if ($userId <= 0) {
                throw new InvalidArgumentException(
                    'Select a valid user account.'
                );
            }

            $confirmation =
                trim(
                    (string) (
                        $_POST['confirm_status_change']
                        ?? ''
                    )
                );

            if ($confirmation !== '1') {
                throw new RuntimeException(
                    'Confirm the account-status change before continuing.'
                );
            }

            $newStatus =
                trim(
                    (string) (
                        $_POST['new_status']
                        ?? ''
                    )
                );

            $reason =
                trim(
                    (string) (
                        $_POST['status_reason']
                        ?? ''
                    )
                );

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $updatedUser =
                $this->service
                ->changeAccountStatus(
                    $userId,
                    $adminId,
                    $newStatus,
                    $reason
                );

            $updatedName =
                trim(
                    implode(
                        ' ',
                        array_filter(
                            [
                                $updatedUser['first_name']
                                    ?? '',

                                $updatedUser['middle_name']
                                    ?? '',

                                $updatedUser['last_name']
                                    ?? '',

                                $updatedUser['name_suffix']
                                    ?? ''
                            ],
                            static fn(
                                mixed $value
                            ): bool =>
                            trim(
                                (string) $value
                            ) !== ''
                        )
                    )
                );

            try {
                $this->log(
                    'CHANGE_USER_ACCOUNT_STATUS',
                    'Administrator changed the account status of '
                        . (
                            $updatedName !== ''
                            ? $updatedName
                            : 'user #' . $userId
                        )
                        . ' to '
                        . (
                            $updatedUser['status']
                            ?? $newStatus
                        )
                        . '. Reason: '
                        . $reason
                        . '. User ID: '
                        . $userId
                        . '.',
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
             * The status transaction remains successful
             * if general activity logging fails.
             */
            }

            $this->redirect(
                $this->buildManageUsersRedirect(
                    $userId,
                    'success',
                    (
                        $updatedUser['status']
                        ?? $newStatus
                    ) === 'Active'
                        ? 'Account activated successfully.'
                        : 'Account deactivated successfully.'
                )
            );
        } catch (Throwable $exception) {
            $this->redirect(
                $this->buildManageUsersRedirect(
                    $userId,
                    'error',
                    publicErrorMessage($exception)
                )
            );
        }
    }

    /* ==========================================
   UNLOCK ACCOUNT
========================================== */

    public function unlockAccount(): void
    {
        $this->requireRole(
            'Admin'
        );

        $userId =
            filter_input(
                INPUT_POST,
                'user_id',
                FILTER_VALIDATE_INT
            );

        $userId =
            $userId && $userId > 0
            ? (int) $userId
            : 0;

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            if ($userId <= 0) {
                throw new InvalidArgumentException(
                    'Select a valid user account.'
                );
            }

            $confirmation =
                trim(
                    (string) (
                        $_POST['confirm_unlock']
                        ?? ''
                    )
                );

            if ($confirmation !== '1') {
                throw new RuntimeException(
                    'Confirm the account unlock before continuing.'
                );
            }

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $updatedUser =
                $this->service
                ->unlockAccount(
                    $userId,
                    $adminId
                );

            $updatedName =
                trim(
                    implode(
                        ' ',
                        array_filter(
                            [
                                $updatedUser['first_name']
                                    ?? '',

                                $updatedUser['middle_name']
                                    ?? '',

                                $updatedUser['last_name']
                                    ?? '',

                                $updatedUser['name_suffix']
                                    ?? ''
                            ],
                            static fn(
                                mixed $value
                            ): bool =>
                            trim(
                                (string) $value
                            ) !== ''
                        )
                    )
                );

            try {
                $this->log(
                    'UNLOCK_USER_ACCOUNT',
                    'Administrator unlocked the account of '
                        . (
                            $updatedName !== ''
                            ? $updatedName
                            : 'user #' . $userId
                        )
                        . '. User ID: '
                        . $userId
                        . '.',
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
             * Unlock remains successful if general
             * activity logging unexpectedly fails.
             */
            }

            $this->redirect(
                $this->buildManageUsersRedirect(
                    $userId,
                    'success',
                    'Account unlocked successfully. Failed login attempts were reset.'
                )
            );
        } catch (Throwable $exception) {
            $this->redirect(
                $this->buildManageUsersRedirect(
                    $userId,
                    'error',
                    publicErrorMessage($exception)
                )
            );
        }
    }

    /* ==========================================
   CHANGE STAFF ROLE
========================================== */

    public function changeStaffRole(): void
    {
        $this->requireRole(
            'Admin'
        );

        $userId =
            filter_input(
                INPUT_POST,
                'user_id',
                FILTER_VALIDATE_INT
            );

        $newRoleId =
            filter_input(
                INPUT_POST,
                'new_role_id',
                FILTER_VALIDATE_INT
            );

        $userId =
            $userId && $userId > 0
            ? (int) $userId
            : 0;

        $newRoleId =
            $newRoleId && $newRoleId > 0
            ? (int) $newRoleId
            : 0;

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            if (
                $userId <= 0 ||
                $newRoleId <= 0
            ) {
                throw new InvalidArgumentException(
                    'Select a valid user and staff role.'
                );
            }

            $confirmation =
                trim(
                    (string) (
                        $_POST['confirm_role_change']
                        ?? ''
                    )
                );

            if ($confirmation !== '1') {
                throw new RuntimeException(
                    'Confirm the staff-role change before continuing.'
                );
            }

            $reason =
                trim(
                    (string) (
                        $_POST['role_change_reason']
                        ?? ''
                    )
                );

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $updatedUser =
                $this->service
                ->changeStaffRole(
                    $userId,
                    $adminId,
                    $newRoleId,
                    $reason
                );

            $updatedName =
                trim(
                    implode(
                        ' ',
                        array_filter(
                            [
                                $updatedUser['first_name']
                                    ?? '',

                                $updatedUser['middle_name']
                                    ?? '',

                                $updatedUser['last_name']
                                    ?? '',

                                $updatedUser['name_suffix']
                                    ?? ''
                            ],
                            static fn(
                                mixed $value
                            ): bool =>
                            trim(
                                (string) $value
                            ) !== ''
                        )
                    )
                );

            $updatedRole =
                trim(
                    (string) (
                        $updatedUser['role_prefix']
                        ?? 'Unknown'
                    )
                );

            try {
                $this->log(
                    'CHANGE_USER_STAFF_ROLE',
                    'Administrator changed the staff role of '
                        . (
                            $updatedName !== ''
                            ? $updatedName
                            : 'user #' . $userId
                        )
                        . ' to '
                        . $updatedRole
                        . '. Reason: '
                        . $reason
                        . '. User ID: '
                        . $userId
                        . '.',
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
             * The role transaction remains successful
             * if general activity logging fails.
             */
            }

            $this->redirect(
                $this->buildManageUsersRedirect(
                    $userId,
                    'success',
                    'Staff role changed to '
                        . $updatedRole
                        . ' successfully.'
                )
            );
        } catch (Throwable $exception) {
            $this->redirect(
                $this->buildManageUsersRedirect(
                    $userId,
                    'error',
                    publicErrorMessage($exception)
                )
            );
        }
    }



    /* ==========================================
   MANAGE USERS REDIRECT
========================================== */

    private function buildManageUsersRedirect(
        int $userId,
        string $messageType,
        string $message
    ): string {
        $query = [
            'page' =>
            'manage_users'
        ];

        if ($userId > 0) {
            $query['user_id'] =
                $userId;
        }

        $preservedFilters = [
            'search',
            'role_id',
            'status',
            'department_id',
            'education_level_id'
        ];

        foreach (
            $preservedFilters
            as $filter
        ) {
            $value =
                trim(
                    (string) (
                        $_POST['return_' . $filter]
                        ?? ''
                    )
                );

            if ($value !== '') {
                $query[$filter] =
                    $value;
            }
        }

        $returnTab =
            trim(
                (string) (
                    $_POST['return_tab']
                    ?? ''
                )
            );

        if (
            in_array(
                $returnTab,
                [
                    'overview',
                    'access',
                    'history'
                ],
                true
            )
        ) {
            $query['tab'] =
                $returnTab;
        }

        if (
            !in_array(
                $messageType,
                [
                    'success',
                    'error'
                ],
                true
            )
        ) {
            $messageType =
                'error';
        }

        $query[$messageType] =
            $message;

        return 'index.php?'
            . http_build_query(
                $query
            );
    }
}
