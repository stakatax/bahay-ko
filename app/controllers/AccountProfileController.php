<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/AccountProfileService.php';

class AccountProfileController extends BaseController
{
    private AccountProfileService $service;

    public function __construct()
    {
        $this->service =
            new AccountProfileService();
    }

    /* ==========================================
       ACCOUNT PROFILE PAGE
    ========================================== */

    public function index(): array
    {
        $this->requireLogin();

        $userId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        $profileData =
            $this->service
            ->getProfileData(
                $userId
            );

        $flash =
            $_SESSION['account_profile_flash']
            ?? [];

        unset(
            $_SESSION['account_profile_flash']
        );

        return array_merge(
            $profileData,
            [
                'flash_type' =>
                (string) (
                    $flash['type']
                    ?? ''
                ),

                'flash_message' =>
                (string) (
                    $flash['message']
                    ?? ''
                )
            ]
        );
    }

    /* ==========================================
       UPLOAD PROFILE PHOTO
    ========================================== */

    public function updateDetails(): void
    {
        $this->requireLogin();
        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $user = $this->service->updatePersonalDetails($userId, [
                'middle_name'=>$_POST['middle_name'] ?? '',
                'name_suffix'=>$_POST['name_suffix'] ?? '',
                'gender'=>$_POST['gender'] ?? '',
                'birthdate'=>$_POST['birthdate'] ?? ''
            ]);
            $_SESSION['faculty_profile_required'] = empty($user['gender']) || empty($user['birthdate']);
            try {
                $this->log('UPDATE_ACCOUNT_PROFILE', 'Faculty updated their own personal details.', $userId);
            } catch (Throwable $exception) {
                error_log('Faculty profile audit logging failed.');
            }
            $this->setFlash('success', 'Your personal details were saved.');
        } catch (Throwable $exception) {
            $this->setFlash('error', publicErrorMessage($exception));
        }
        $this->redirect('index.php?page=account_profile');
    }

    public function uploadPhoto(): void
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

            $uploadedFile =
                $_FILES['profile_photo']
                ?? [];

            if (!is_array($uploadedFile)) {
                $uploadedFile = [];
            }

            $user =
                $this->service
                ->updateProfilePhoto(
                    $userId,
                    $uploadedFile
                );

            $_SESSION['profile_photo'] =
                $user['profile_photo']
                ?? null;

            try {
                $this->log(
                    'UPDATE_ACCOUNT_PROFILE',
                    'Updated personal profile photo.',
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
                 * Successful photo updates remain
                 * successful if activity logging fails.
                 */
            }

            $this->setFlash(
                'success',
                'Your profile photo was updated successfully.'
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception)
            );
        }

        $this->redirect(
            'index.php?page=account_profile'
        );
    }

    /* ==========================================
       REMOVE PROFILE PHOTO
    ========================================== */

    public function removePhoto(): void
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

            $this->service
                ->removeProfilePhoto(
                    $userId
                );

            $_SESSION['profile_photo'] =
                null;

            try {
                $this->log(
                    'UPDATE_ACCOUNT_PROFILE',
                    'Removed personal profile photo.',
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
                 * Successful removal remains successful
                 * if activity logging fails.
                 */
            }

            $this->setFlash(
                'success',
                'Your profile photo was removed.'
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception)
            );
        }

        $this->redirect(
            'index.php?page=account_profile'
        );
    }

    private function setFlash(
        string $type,
        string $message
    ): void {
        $_SESSION['account_profile_flash'] = [
            'type' =>
            $type,

            'message' =>
            trim($message)
        ];
    }
}
