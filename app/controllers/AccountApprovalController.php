<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/AccountApprovalService.php';

require_once __DIR__ . '/../../config/account-review-statements.php';

class AccountApprovalController extends BaseController
{
    private AccountApprovalService $service;

    public function __construct()
    {
        $this->service =
            new AccountApprovalService();
    }

    /* ==========================================
       PENDING ACCOUNT REVIEW PAGE
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

        $selectedRegistration =
            null;

        $selectionError =
            null;

        if (
            $selectedUserId !== false &&
            $selectedUserId !== null &&
            $selectedUserId > 0
        ) {
            try {
                $selectedRegistration =
                    $this->service
                    ->getRegistrationForReview(
                        (int) $selectedUserId
                    );
            } catch (Throwable $exception) {
                $selectionError =
                    publicErrorMessage($exception);
            }
        }

        return [
            'child_sections' => $this->service->childSections(),
            'pending_registrations' =>
            $this->service
                ->getPendingRegistrations(
                    100
                ),

            'selected_registration' =>
            $selectedRegistration,

            'selected_user_id' => (
                $selectedUserId !== false &&
                $selectedUserId !== null
            )
                ? (int) $selectedUserId
                : 0,

            'selection_error' =>
            $selectionError
        ];
    }

    /* ==========================================
       APPROVE REGISTRATION
    ========================================== */

    public function updateChild(): void
    {
        $this->requireRole('Admin');
        try {
            $this->requirePostRequest();
            $this->requireCsrfToken();
            $id = $this->getRegistrationUserId();
            $this->service->updateChildRecord($id, $this->getCurrentUserId(), $_POST);
            $this->redirect('index.php?page=account_approvals&user_id=' . $id . '&success=' . urlencode('Child verification updated.'));
        } catch (Throwable $e) {
            $this->redirect('index.php?page=account_approvals&user_id=' . (int)($_POST['registration_user_id'] ?? 0) . '&error=' . urlencode(publicErrorMessage($e)));
        }
    }

    public function approve(): void
    {
        $this->requireRole(
            'Admin'
        );

        try {
            $this->requirePostRequest();

            $this->requireCsrfToken();

            $confirmation =
                trim(
                    (string) (
                        $_POST['confirm_approval']
                        ?? ''
                    )
                );

            if ($confirmation !== '1') {
                throw new RuntimeException(
                    'Confirm that the applicant information was verified before approval.'
                );
            }


            $userId =
                $this->getRegistrationUserId();

            $adminId =
                $this->getCurrentUserId();

            $reviewNotes = resolveAccountReviewStatement('approve', $_POST);

            $approvedRegistration =
                $this->service
                ->approveRegistration(
                    $userId,
                    $adminId,
                    $reviewNotes
                );

            $applicantName =
                implode(
                    ' ',
                    array_filter(
                        [
                            trim(
                                (string) (
                                    $registration['first_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $registration['middle_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $registration['last_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $registration['name_suffix']
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
                    'ACTIVATE_ACCOUNT',
                    'Administrator approved the '
                        . (
                            $approvedRegistration['role_prefix']
                            ?? 'user'
                        )
                        . ' registration for '
                        . (
                            $applicantName !== ''
                            ? $applicantName
                            : 'User #' . $userId
                        )
                        . '.'
                );
            } catch (Throwable $loggingException) {
                /*
                 * Approval remains successful when
                 * activity logging is unavailable.
                 */
            }

            $this->redirect(
                'index.php?page=account_approvals'
                    . '&success='
                    . urlencode(
                        'Registration approved successfully.'
                    )
            );
        } catch (Throwable $exception) {
            error_log(
                'Account approval error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=account_approvals'
                    . '&user_id='
                    . (
                        (int) (
                            $_POST['registration_user_id']
                            ?? 0
                        )
                    )
                    . '&error='
                    . urlencode(
                        publicErrorMessage($exception)
                    )
            );
        }
    }

    /* ==========================================
   REJECT REGISTRATION
========================================== */

    public function reject(): void
    {
        $this->requireRole(
            'Admin'
        );

        try {
            $this->requirePostRequest();

            $this->requireCsrfToken();

            $confirmation =
                trim(
                    (string) (
                        $_POST['confirm_rejection']
                        ?? ''
                    )
                );

            if ($confirmation !== '1') {
                throw new RuntimeException(
                    'Confirm the registration rejection before continuing.'
                );
            }

            $userId =
                $this->getRegistrationUserId();

            $adminId =
                $this->getCurrentUserId();

            $reviewNotes = resolveAccountReviewStatement('reject', $_POST);

            if ($reviewNotes === '') {
                throw new InvalidArgumentException(
                    'A rejection reason is required.'
                );
            }

            $registration =
                $this->service
                ->rejectRegistration(
                    $userId,
                    $adminId,
                    $reviewNotes
                );

            $applicantName =
                implode(
                    ' ',
                    array_filter(
                        [
                            trim(
                                (string) (
                                    $registration['first_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $registration['middle_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $registration['last_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $registration['name_suffix']
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
                    'DEACTIVATE_ACCOUNT',
                    'Administrator rejected the '
                        . (
                            $registration['role_prefix']
                            ?? 'user'
                        )
                        . ' registration for '
                        . (
                            $applicantName !== ''
                            ? $applicantName
                            : 'User #' . $userId
                        )
                        . '. Reason: '
                        . $reviewNotes
                );
            } catch (Throwable $loggingException) {
                /*
             * Account rejection remains successful
             * if activity logging fails.
             */
            }

            $this->redirect(
                'index.php?page=account_approvals'
                    . '&success='
                    . urlencode(
                        'Registration rejected successfully.'
                    )
            );
        } catch (Throwable $exception) {
            error_log(
                'Account rejection error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=account_approvals'
                    . '&user_id='
                    . (int) (
                        $_POST['registration_user_id']
                        ?? 0
                    )
                    . '&error='
                    . urlencode(
                        publicErrorMessage($exception)
                    )
            );
        }
    }

    /* ==========================================
       REQUEST VALUES
    ========================================== */

    private function getCurrentUserId(): int
    {
        $userId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid authenticated Administrator.'
            );
        }

        return $userId;
    }

    private function getRegistrationUserId(): int
    {
        $userId =
            filter_input(
                INPUT_POST,
                'registration_user_id',
                FILTER_VALIDATE_INT
            );

        if (
            !$userId ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid registration user ID.'
            );
        }

        return (int) $userId;
    }

    private function requirePostRequest(): void
    {
        $requestMethod =
            strtoupper(
                (string) (
                    $_SERVER['REQUEST_METHOD']
                    ?? ''
                )
            );

        if ($requestMethod !== 'POST') {
            throw new RuntimeException(
                'Invalid request method.'
            );
        }
    }
}
