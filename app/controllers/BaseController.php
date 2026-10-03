<?php

require_once __DIR__ . '/../../config/request-errors.php';

require_once __DIR__ . '/../../config/dbconnect.php';
require_once __DIR__ . '/../../config/logging.php';
require_once __DIR__
    . '/../../config/security.php';

class BaseController
{
    protected function view(
        string $view,
        array $data = []
    ): void {
        if (!empty($data)) {
            extract($data);
        }

        require __DIR__
            . '/../../pages/'
            . $view
            . '.php';
    }

    protected function log(
        string $action,
        string $description,
        ?int $targetUserId = null
    ): void {
        global $conn;

        logActivity(
            $conn,
            $action,
            $description,
            $targetUserId
        );
    }

    protected function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    protected function getCurrentRole(): string
    {
        return $_SESSION['role']
            ?? 'Guest';
    }

    protected function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect(
                'index.php?page=login&error='
                    . urlencode(
                        'Please log in to continue.'
                    )
            );
        }
    }

    protected function hasRole(
        string $role
    ): bool {
        return $this->getCurrentRole()
            === $role;
    }

    protected function hasAnyRole(
        array $allowedRoles
    ): bool {
        return in_array(
            $this->getCurrentRole(),
            $allowedRoles,
            true
        );
    }

    protected function requireRole(
        string $requiredRole
    ): void {
        $this->requireLogin();

        if (!$this->hasRole($requiredRole)) {
            $this->redirectUnauthorized();
        }
    }

    protected function requireAnyRole(
        array $allowedRoles
    ): void {
        $this->requireLogin();

        if (
            !$this->hasAnyRole(
                $allowedRoles
            )
        ) {
            $this->redirectUnauthorized();
        }
    }

    protected function redirectUnauthorized(
        string $message =
        'You are not authorized to access that feature.'
    ): void {
        $this->redirect(
            'index.php?page=news&error='
                . urlencode($message)
        );
    }

    protected function redirect(
        string $url
    ): void {
        header(
            'Location: ' . $url
        );

        exit;
    }


    /* ==========================================
   REQUEST SECURITY
========================================== */

    protected function requirePostMethod(): void
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

    protected function requireScalarFormValues(): void
    {
        foreach ($_POST as $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    'Invalid form values. Please review your entries and try again.'
                );
            }
        }
    }

    protected function requireCsrfToken(): void
    {
        requireValidCsrfToken();
    }
}
