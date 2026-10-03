<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/PasswordRecoveryService.php';

class PasswordRecoveryController extends BaseController
{
    private PasswordRecoveryService $service;

    public function __construct()
    {
        $this->service =
            new PasswordRecoveryService();
    }

    /* ==========================================
       REQUEST RESET LINK
    ========================================== */

    public function request(): void
    {
        $identifier = '';

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            $identifier =
                trim(
                    (string) (
                        $_POST['identifier']
                        ?? ''
                    )
                );


            $ipAddress =
                trim(
                    (string) (
                        $_SERVER['REMOTE_ADDR']
                        ?? ''
                    )
                );

            $userAgent =
                trim(
                    (string) (
                        $_SERVER['HTTP_USER_AGENT']
                        ?? ''
                    )
                );

            $this->service
                ->request(
                    $identifier,
                    $ipAddress,
                    $userAgent
                );

            $_SESSION['password_recovery_flash'] = [
                'success' =>
                'If an eligible account matches that information, password-reset instructions have been sent to its registered email address.'
            ];
        } catch (
            InvalidArgumentException $exception
        ) {
            $_SESSION['password_recovery_flash'] = [
                'error' =>
                publicErrorMessage($exception),

                'old_identifier' =>
                $identifier
            ];
        } catch (Throwable $exception) {
            /*
             * Do not reveal whether the account
             * exists or whether SMTP failed.
             */
            error_log(
                'Password recovery request error: '
                    . publicErrorMessage($exception)
            );

            $_SESSION['password_recovery_flash'] = [
                'success' =>
                'If an eligible account matches that information, password-reset instructions have been sent to its registered email address.'
            ];
        }

        $this->redirect(
            'index.php?page=forgot_password'
        );
    }

    /* ==========================================
       RESET PAGE DATA
    ========================================== */

    public function resetPage(): array
    {
        $rawToken =
            trim(
                (string) (
                    is_string($_GET['token'] ?? null)
                        ? $_GET['token']
                        : ''
                )
            );

        try {
            $token =
                $this->service
                ->validateToken(
                    $rawToken
                );

            if (!$token) {
                throw new RuntimeException(
                    'This password-reset link is invalid or has expired.'
                );
            }

            return [
                'valid_token' =>
                true,

                'token' =>
                $rawToken,

                'expires_at' =>
                $token['expires_at']
                    ?? null
            ];
        } catch (Throwable $exception) {
            return [
                'valid_token' =>
                false,

                'token' =>
                '',

                'error' =>
                'This password-reset link is invalid or has expired.'
            ];
        }
    }

    /* ==========================================
       COMPLETE RESET
    ========================================== */

    public function reset(): void
    {
        $rawToken = '';

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            $rawToken =
                trim(
                    (string) (
                        $_POST['token']
                        ?? ''
                    )
                );


            $password =
                (string) (
                    $_POST['password']
                    ?? ''
                );

            $passwordConfirmation =
                (string) (
                    $_POST['password_confirmation']
                    ?? ''
                );

            $this->service
                ->reset(
                    $rawToken,
                    $password,
                    $passwordConfirmation
                );

            /*
             * Clear any authenticated state in
             * this browser after password reset.
             */
            $_SESSION = [];

            session_regenerate_id(
                true
            );

            $_SESSION['password_reset_success'] =
                'Your password has been reset successfully. You can now log in using your new password.';

            $this->redirect(
                'index.php?page=login'
            );
        } catch (Throwable $exception) {
            $_SESSION['password_reset_flash'] = [
                'error' =>
                publicErrorMessage($exception)
            ];

            $this->redirect(
                'index.php?page=password_reset&token='
                    . rawurlencode(
                        $rawToken
                    )
            );
        }
    }
}
