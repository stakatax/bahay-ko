<?php

require_once 'BaseController.php';
require_once __DIR__ . '/../services/AuthService.php';

class AuthController extends BaseController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService(null, (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }

    /* ==========================================
       LOGIN
    ========================================== */

    public function login(): void
    {
        $identifier = '';

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            $identifier = trim(
                $_POST['identifier']
                    ?? $_POST['studentID']
                    ?? ''
            );

            $password = (string) (
                $_POST['password']
                ?? ''
            );

            $user = $this->service->login(
                $identifier,
                $password
            );

            session_regenerate_id(true);

            $this->storeUserSession(
                $user
            );

            $_SESSION['last_activity_at'] =
                time();

            $_SESSION['session_regenerated_at'] =
                time();

            $loginIdentifier =
                $user['studID']
                ?? $user['email']
                ?? 'Unknown identifier';

            $fullName =
                $_SESSION['name']
                ?? 'Unknown user';

            $this->log(
                'LOGIN',
                "User {$loginIdentifier} ({$fullName}) logged in successfully."
            );

            $this->redirectAfterLogin(
                $user['role_prefix']
                    ?? ''
            );
        } catch (Throwable $exception) {
            $message =
                trim(
                    publicErrorMessage($exception)
                );

            $errorField = null;

            if (
                stripos(
                    $message,
                    'Student ID'
                ) !== false ||
                stripos(
                    $message,
                    'email address'
                ) !== false ||
                stripos(
                    $message,
                    'No account was found'
                ) !== false
            ) {
                $errorField =
                    'identifier';
            } elseif (
                stripos(
                    $message,
                    'password'
                ) !== false
            ) {
                $errorField =
                    'password';
            }

            /*
     * Preserve only safe form values.
     * Passwords must never be stored in session.
     */
            $_SESSION['login_flash'] = [
                'error' =>
                $message,

                'error_field' =>
                $errorField,

                'old_identifier' =>
                $identifier,

                'remember' =>
                !empty($_POST['remember'])
            ];

            if ($exception instanceof RequestRateLimitException) {
                renderRequestFailure($exception);
                return;
            }

            $this->redirect(
                'index.php?page=login'
            );
        }
    }

    private function storeUserSession(
        array $user
    ): void {
        $_SESSION['auth_credential_version'] = hash('sha256', (string) $user['password']);

        $_SESSION['user_id'] =
            (int) $user['user_id'];

        $_SESSION['studID'] =
            $user['studID']
            ?? null;

        $_SESSION['role_id'] =
            isset($user['role_id'])
            ? (int) $user['role_id']
            : null;

        $_SESSION['role'] =
            $user['role_prefix']
            ?? null;

        $_SESSION['department_id'] =
            isset($user['department_id'])
            ? (int) $user['department_id']
            : null;

        $_SESSION['education_level_id'] =
            isset($user['education_level_id'])
            ? (int) $user['education_level_id']
            : null;

        $_SESSION['academic_program_id'] =
            isset($user['academic_program_id'])
            ? (int) $user['academic_program_id']
            : null;

        $_SESSION['grade_level_id'] =
            isset($user['grade_level_id'])
            ? (int) $user['grade_level_id']
            : null;

        $_SESSION['section_id'] =
            isset($user['section_id'])
            ? (int) $user['section_id']
            : null;

        $_SESSION['first_name'] =
            $user['first_name']
            ?? '';

        $_SESSION['middle_name'] =
            $user['middle_name']
            ?? null;

        $_SESSION['last_name'] =
            $user['last_name']
            ?? '';

        $_SESSION['name_suffix'] =
            $user['name_suffix']
            ?? null;

        $_SESSION['name'] =
            implode(
                ' ',
                array_filter(
                    [
                        trim(
                            (string) (
                                $_SESSION['first_name']
                                ?? ''
                            )
                        ),

                        trim(
                            (string) (
                                $_SESSION['middle_name']
                                ?? ''
                            )
                        ),

                        trim(
                            (string) (
                                $_SESSION['last_name']
                                ?? ''
                            )
                        ),

                        trim(
                            (string) (
                                $_SESSION['name_suffix']
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

        $_SESSION['email'] =
            $user['email']
            ?? null;

        $_SESSION['profile_photo'] =
            $user['profile_photo']
            ?? null;

        $_SESSION['account_status'] =
            $user['status']
            ?? null;

        $_SESSION['must_change_password'] =
            !empty($user['must_change_password']);

        $_SESSION['faculty_profile_required'] = ($user['role_prefix'] ?? '') === 'Faculty'
            && (empty($user['gender']) || empty($user['birthdate']));

        $pendingLegalDocuments =
            $this->service
            ->getPendingLegalDocuments(
                (int) (
                    $user['user_id']
                    ?? 0
                )
            );

        $_SESSION['legal_reconsent_required'] =
            !empty($pendingLegalDocuments);

        $_SESSION['pending_legal_document_types'] =
            array_values(
                array_keys(
                    $pendingLegalDocuments
                )
            );

        $_SESSION['department_name'] =
            $user['department_name']
            ?? null;

        $_SESSION['education_level_name'] =
            $user['education_level_name']
            ?? null;

        $_SESSION['academic_program_name'] =
            $user['academic_program_name']
            ?? null;

        $_SESSION['academic_program_code'] =
            $user['academic_program_code']
            ?? null;

        $_SESSION['academic_program_type'] =
            $user['academic_program_type']
            ?? null;

        $_SESSION['grade_level_name'] =
            $user['grade_level_name']
            ?? null;

        $_SESSION['section_name'] =
            $user['section_name']
            ?? null;
    }

    private function redirectAfterLogin(
        string $role
    ): void {

        if (
            !empty($_SESSION['must_change_password'])
        ) {
            $this->redirect(
                'index.php?page=required_password_change'
            );

            return;
        }


        if (
            !empty($_SESSION['legal_reconsent_required'])
        ) {
            $this->redirect(
                'index.php?page=legal_reconsent'
            );

            return;
        }



        if (!empty($_SESSION['faculty_profile_required'])) {
            $this->redirect('index.php?page=account_profile');
            return;
        }

        switch ($role) {
            case 'Admin':
            case 'Faculty':
            case 'Student':
            case 'Parent':
                $this->redirect(
                    'index.php?page=home'
                );
                return;

            default:
                $this->clearSession();

                $this->redirect(
                    'index.php?page=login&error='
                        . urlencode(
                            'Your account role is not recognized.'
                        )
                );
        }
    }

    /* ==========================================
       REGISTRATION
    ========================================== */

    public function register(): void
    {
        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            $roleType = trim(
                $_POST['role_type']
                    ?? 'Student'
            );

            $registrationData = [
                'role_type' =>
                $roleType,

                'student_id' =>
                $_POST['student_id']
                    ?? $_POST['studentID']
                    ?? null,

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

                'birthdate' =>
                $_POST['birthdate']
                    ?? '',

                'gender' =>
                $_POST['gender']
                    ?? '',

                'password' =>
                $_POST['password']
                    ?? '',

                'password_confirmation' =>
                $_POST['password_confirmation']
                    ?? '',

                'department_id' =>
                $_POST['department_id']
                    ?? $_POST['department']
                    ?? null,

                'education_level_id' =>
                $_POST['education_level_id']
                    ?? null,

                'academic_program_id' =>
                $_POST['academic_program_id']
                    ?? null,

                'grade_level_id' =>
                $_POST['grade_level_id']
                    ?? null,

                'section_id' =>
                $_POST['section_id']
                    ?? null,

                'child_student_id' =>
                $_POST['child_student_id']
                    ?? '',
                'child_no_account' => is_scalar($_POST['child_no_account'] ?? '') ? (string)($_POST['child_no_account'] ?? '') : '',
                'child_name' => is_scalar($_POST['child_name'] ?? '') ? (string)($_POST['child_name'] ?? '') : '',
                'child_section_id' => is_scalar($_POST['child_section_id'] ?? '') ? (string)($_POST['child_section_id'] ?? '') : '',
                'child_reason' => is_scalar($_POST['child_reason'] ?? '') ? (string)($_POST['child_reason'] ?? '') : '',
                'child_reason_details' => is_scalar($_POST['child_reason_details'] ?? '') ? (string)($_POST['child_reason_details'] ?? '') : '',

                'relationship' =>
                $_POST['relationship']
                    ?? '',

                'accept_terms' =>
                $_POST['accept_terms']
                    ?? null,

                'accept_privacy' =>
                $_POST['accept_privacy']
                    ?? null
            ];

            $registeredUserId =
                $this->service->register(
                    $registrationData
                );

            $fullName =
                implode(
                    ' ',
                    array_filter(
                        [
                            trim(
                                (string) (
                                    $_POST['first_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $_POST['middle_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $_POST['last_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $_POST['name_suffix']
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

            try {
                $this->log(
                    'REGISTER_ACCOUNT',
                    "New {$roleType} registration submitted by {$fullName}. User ID: {$registeredUserId}."
                );
            } catch (Throwable $loggingException) {
                /*
                 * Registration stays successful even if
                 * public activity logging fails.
                 */
            }

            $message =
                'Registration submitted successfully. '
                . 'Your account is pending Administrator approval.';

            if (
                strcasecmp(
                    $roleType,
                    'Parent'
                ) === 0
            ) {
                $message =
                    'Parent registration submitted successfully. '
                    . 'Your account and Student relationship '
                    . 'must be verified by an Administrator.';
            }

            $successUrl = 'index.php?page=login&success=' . urlencode($message);
            if (requestErrorExpectsJson()) {
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-store');
                echo json_encode(['success'=>true, 'redirect'=>$successUrl]);
                return;
            }
            $this->redirect($successUrl);
        } catch (Throwable $exception) {
            $message =
                trim(
                    publicErrorMessage($exception)
                );

            $errorField = null;

            $fieldPatterns = [
                'first_name' => [
                    'First name'
                ],

                'middle_name' => [
                    'Middle name'
                ],

                'last_name' => [
                    'Last name'
                ],


                'name_suffix' => [
                    'name suffix'
                ],

                'email' => [
                    'email address',
                    'email is already'
                ],

                'birthdate' => [
                    'birthdate',
                    'age'
                ],

                'gender' => [
                    'gender'
                ],

                'password_confirmation' => [
                    'Password confirmation',
                    'Passwords do not match'
                ],

                'password' => [
                    'password'
                ],

                'department_id' => [
                    'School Division',
                    'department'
                ],


                'education_level_id' => [
                    'Education Level'
                ],

                'academic_program_id' => [
                    'Program or Strand'
                ],

                'grade_level_id' => [
                    'Grade or Year Level'
                ],

                'section_id' => [
                    'Section'
                ],

                'child_student_id' => [
                    'Child Student ID',
                    'active Student account'
                ],

                'child_name' => ['Child full name', 'child name'],
                'child_section_id' => ['child grade', 'child section'],
                'child_reason_details' => ['reason explanation', 'reason details'],
                'child_reason' => ['child registration reason'],

                'relationship' => [
                    'relationship'
                ],

                'student_id' => [
                    'Student ID'
                ],

                'role_type' => [
                    'Student and Parent accounts',
                    'registration role'
                ]
            ];

            // Child section errors must not be mistaken for the Student's section field.
            if (stripos($message, 'child grade') !== false || stripos($message, 'child section') !== false) {
                $errorField = 'child_section_id';
            }

            foreach (
                $fieldPatterns
                as $field => $patterns
            ) {
                if ($errorField !== null) break;
                foreach (
                    $patterns
                    as $pattern
                ) {
                    if (
                        stripos(
                            $message,
                            $pattern
                        ) !== false
                    ) {
                        $errorField =
                            $field;

                        break 2;
                    }
                }
            }

            /*
     * Preserve only non-sensitive values.
     * Passwords are intentionally excluded.
     */
            if (requestErrorExpectsJson()) {
                if ($exception instanceof RequestRateLimitException) {
                    renderRequestFailure($exception);
                    return;
                }
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-store');
                echo json_encode(['success'=>false, 'message'=>$message, 'error_field'=>$errorField]);
                return;
            }

            $_SESSION['registration_flash'] = [
                'error' =>
                $message,

                'error_field' =>
                $errorField,

                'old_input' => [
                    'role_type' =>
                    $_POST['role_type']
                        ?? 'Student',

                    'student_id' =>
                    $_POST['student_id']
                        ?? $_POST['studentID']
                        ?? '',

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

                    'birthdate' =>
                    $_POST['birthdate']
                        ?? '',

                    'gender' =>
                    $_POST['gender']
                        ?? '',

                    'department_id' =>
                    $_POST['department_id']
                        ?? $_POST['department']
                        ?? '',

                    'education_level_id' =>
                    $_POST['education_level_id']
                        ?? '',

                    'academic_program_id' =>
                    $_POST['academic_program_id']
                        ?? '',

                    'grade_level_id' =>
                    $_POST['grade_level_id']
                        ?? '',

                    'section_id' =>
                    $_POST['section_id']
                        ?? '',

                    'child_student_id' =>
                    $_POST['child_student_id']
                        ?? '',
                'child_no_account' => is_scalar($_POST['child_no_account'] ?? '') ? (string)($_POST['child_no_account'] ?? '') : '',
                'child_name' => is_scalar($_POST['child_name'] ?? '') ? (string)($_POST['child_name'] ?? '') : '',
                'child_section_id' => is_scalar($_POST['child_section_id'] ?? '') ? (string)($_POST['child_section_id'] ?? '') : '',
                'child_reason' => is_scalar($_POST['child_reason'] ?? '') ? (string)($_POST['child_reason'] ?? '') : '',
                'child_reason_details' => is_scalar($_POST['child_reason_details'] ?? '') ? (string)($_POST['child_reason_details'] ?? '') : '',

                    'relationship' =>
                    $_POST['relationship']
                        ?? ''
                ]
            ];

            $_SESSION['registration_flash']['old_input'] = array_map(
                static fn(mixed $value): string => is_scalar($value) ? (string) $value : '',
                $_SESSION['registration_flash']['old_input']
            );

            if ($errorField !== null && array_key_exists($errorField, $_SESSION['registration_flash']['old_input'])) {
                $_SESSION['registration_flash']['old_input'][$errorField] = '';
            }

            if ($exception instanceof RequestRateLimitException) {
                renderRequestFailure($exception);
                return;
            }

            $this->redirect(
                'index.php?page=register'
            );
        }
    }

    /* ==========================================
       REGISTRATION FORM OPTIONS
    ========================================== */

    public function registrationOptions(): array
    {
        try {
            return $this->service
                ->getRegistrationOptions();
        } catch (Throwable $exception) {
            return [
                'departments' => [],
                'education_levels' => [],
                'academic_programs' => [],
                'grade_levels' => [],
                'sections' => [],
                'legal_documents' => [],
                'relationships' => [
                    'Mother',
                    'Father',
                    'Guardian',
                    'Grandparent',
                    'Relative',
                    'Other'
                ],
                'load_error' =>
                publicErrorMessage($exception)
            ];
        }
    }

    /* ==========================================
   EXPIRE AUTHENTICATED SESSION
========================================== */


    /* ==========================================
   KEEP AUTHENTICATED SESSION ACTIVE
========================================== */

    public function keepAlive(): void
    {
        header(
            'Content-Type: application/json; charset=UTF-8'
        );

        try {
            $this->requirePostMethod();

            if (
                empty($_SESSION['user_id'])
            ) {
                throw new RuntimeException(
                    'Your session has already expired.'
                );
            }

            $this->requireCsrfToken();

            $currentTime =
                time();

            $_SESSION['last_activity_at'] =
                $currentTime;

            $_SESSION['session_regenerated_at'] =
                $currentTime;

            echo json_encode([
                'success' =>
                true,

                'expires_in' =>
                1800
            ]);
        } catch (Throwable $exception) {
            http_response_code(
                publicErrorStatus($exception, empty($_SESSION['user_id']) ? 401 : 419)
            );

            echo json_encode([
                'success' =>
                false,

                'message' =>
                publicErrorMessage($exception)
            ]);
        }

        exit;
    }

    /* ==========================================
       REQUIRED LEGAL RE-CONSENT
    ========================================== */

    public function legalReconsentPage(): array
    {
        $this->requireLogin();

        $userId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        $pendingDocuments =
            $this->service
            ->getPendingLegalDocuments(
                $userId
            );

        $_SESSION['legal_reconsent_required'] =
            !empty($pendingDocuments);

        $_SESSION['pending_legal_document_types'] =
            array_values(
                array_keys(
                    $pendingDocuments
                )
            );

        if (empty($pendingDocuments)) {
            $this->redirectAfterLogin(
                (string) (
                    $_SESSION['role']
                    ?? ''
                )
            );
        }

        $flash =
            $_SESSION['legal_reconsent_flash']
            ?? [];

        unset(
            $_SESSION['legal_reconsent_flash']
        );

        return [
            'pending_documents' =>
            $pendingDocuments,

            'error' =>
            (string) (
                $flash['error']
                ?? ''
            )
        ];
    }

    public function acceptLegalReconsent(): void
    {
        $this->requireLogin();

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $userId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $confirmed =
                (
                    $_POST['accept_current_legal_documents']
                    ?? null
                ) === '1';

            $result =
                $this->service
                ->acceptPendingLegalDocuments(
                    $userId,
                    $confirmed
                );

            $_SESSION['legal_reconsent_required'] =
                false;

            $_SESSION['pending_legal_document_types'] =
                [];

            try {
                $acceptedCount =
                    (int) (
                        $result['accepted']
                        ?? 0
                    );

                $this->log(
                    'ACCEPT_LEGAL_DOCUMENTS',
                    "User accepted {$acceptedCount} updated legal document(s).",
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
                 * Legal acceptance remains valid if
                 * general activity logging fails.
                 */
            }

            $this->redirectAfterLogin(
                (string) (
                    $_SESSION['role']
                    ?? ''
                )
            );
        } catch (Throwable $exception) {
            $_SESSION['legal_reconsent_flash'] = [
                'error' =>
                publicErrorMessage($exception)
            ];

            $this->redirect(
                'index.php?page=legal_reconsent'
            );
        }
    }

    /* ==========================================
   REQUIRED PASSWORD CHANGE
========================================== */

    public function requiredPasswordChange(): void
    {
        $this->requireLogin();

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            $userId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $updatedUser =
                $this->service
                ->changeRequiredPassword(
                    $userId,
                    (string) (
                        $_POST['new_password']
                        ?? ''
                    ),
                    (string) (
                        $_POST['new_password_confirmation']
                        ?? ''
                    ),
                    (string) (
                        $_SESSION['auth_credential_version']
                        ?? ''
                    )
                );

            /*
         * Refresh all account session information and
         * clear must_change_password from the session.
         */
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $this->storeUserSession(
                $updatedUser
            );

            $_SESSION['must_change_password'] =
                false;

            try {
                $this->log(
                    'COMPLETE_REQUIRED_PASSWORD_CHANGE',
                    'User completed the required first-login password change.',
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
             * Password change remains successful if
             * general activity logging fails.
             */
            }

            $this->redirectAfterLogin(
                $updatedUser['role_prefix']
                    ?? ''
            );
        } catch (Throwable $exception) {
            $_SESSION['required_password_flash'] = [
                'error' =>
                publicErrorMessage($exception)
            ];

            $this->redirect(
                'index.php?page=required_password_change'
            );
        }
    }

    /* ==========================================
       LOGOUT
    ========================================== */

    public function logout(): void
    {
        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
        } catch (Throwable $exception) {
            $this->redirect(
                'index.php?page=news&error='
                    . urlencode(
                        'The logout request expired. Please try again.'
                    )
            );
        }

        $expiredByInactivity =
            (
                $_POST['session_expired']
                ?? '0'
            ) === '1';

        $name =
            $_SESSION['name']
            ?? 'Unknown user';

        try {
            $this->log(
                'LOGOUT',
                "User {$name} logged out."
            );
        } catch (Throwable $loggingException) {
            /*
         * Logout continues even if activity
         * logging encounters a problem.
         */
        }

        $this->clearSession();

        if ($expiredByInactivity) {
            $this->redirect(
                'index.php?page=login&error='
                    . urlencode(
                        'Your session expired due to inactivity. Please sign in again.'
                    )
            );
        }

        $this->redirect(
            'index.php?page=home'
        );
    }

    private function clearSession(): void
    {
        $_SESSION = [];

        if (
            ini_get(
                'session.use_cookies'
            )
        ) {
            $params =
                session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' =>
                    time() - 42000,

                    'path' =>
                    $params['path']
                        ?? '/',

                    'domain' =>
                    $params['domain']
                        ?? '',

                    'secure' =>
                    !empty($params['secure']),

                    'httponly' =>
                    !empty($params['httponly']),

                    'samesite' =>
                    $params['samesite']
                        ?? 'Lax'
                ]
            );
        }

        session_destroy();
    }
}
