<?php

/* ==========================================
   SECURE SESSION START
========================================== */

// HTTPS is supplied by the trusted web server. Forwarded headers and APP_URL
// are not proof of transport security. A TLS proxy must be integrated at the server.
function requestUsesHttps(): bool
{
    return !empty($_SERVER['HTTPS'])
        && strtolower((string) $_SERVER['HTTPS']) !== 'off';
}

function startSecureSession(): void
{
    if (
        session_status() !==
        PHP_SESSION_NONE
    ) {
        return;
    }

    ini_set(
        'session.use_only_cookies',
        '1'
    );

    ini_set(
        'session.use_strict_mode',
        '1'
    );

    ini_set(
        'session.use_trans_sid',
        '0'
    );

    $isHttps = requestUsesHttps();

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/* ==========================================
   AUTHENTICATED SESSION MAINTENANCE
========================================== */

function maintainAuthenticatedSession(
    int $inactivityLimit = 1800,
    int $regenerationInterval = 900
): bool {
    if (
        empty($_SESSION['user_id'])
    ) {
        unset(
            $_SESSION['last_activity_at'],
            $_SESSION['session_regenerated_at']
        );

        return false;
    }

    $currentTime =
        time();

    $lastActivityAt =
        (int) (
            $_SESSION['last_activity_at']
            ?? $currentTime
        );

    if (
        $lastActivityAt > 0 &&
        (
            $currentTime -
            $lastActivityAt
        ) > $inactivityLimit
    ) {
        /*
         * Remove all authenticated state and rotate
         * the session ID after inactivity expiry.
         */
        $_SESSION = [];

        session_regenerate_id(
            true
        );

        $_SESSION['session_expired'] =
            true;

        return true;
    }

    $lastRegeneratedAt =
        (int) (
            $_SESSION['session_regenerated_at']
            ?? 0
        );

    if (
        $lastRegeneratedAt <= 0 ||
        (
            $currentTime -
            $lastRegeneratedAt
        ) >= $regenerationInterval
    ) {
        session_regenerate_id(
            true
        );

        $_SESSION['session_regenerated_at'] =
            $currentTime;
    }

    $_SESSION['last_activity_at'] =
        $currentTime;

    return false;
}

/* ==========================================
   CSRF TOKEN
========================================== */

function csrfToken(): string
{
    if (
        empty($_SESSION['csrf_token']) ||
        !is_string(
            $_SESSION['csrf_token']
        )
    ) {
        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );
    }

    return $_SESSION['csrf_token'];
}

function csrfInput(): string
{
    return '<input type="hidden"'
        . ' name="csrf_token"'
        . ' value="'
        . htmlspecialchars(
            csrfToken(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '">';
}

/* ==========================================
   CSRF VALIDATION
========================================== */

function validateCsrfToken(
    ?string $submittedToken
): bool {
    $sessionToken =
        $_SESSION['csrf_token']
        ?? null;

    if (
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !is_string($submittedToken) ||
        $submittedToken === ''
    ) {
        return false;
    }

    return hash_equals(
        $sessionToken,
        $submittedToken
    );
}

function requireValidCsrfToken(): void
{
    $submittedToken =
        $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? null;

    if (
        !validateCsrfToken(
            is_string($submittedToken)
                ? $submittedToken
                : null
        )
    ) {
        throw new RuntimeException(
            'Your form session expired. Refresh the page and try again.'
        );
    }
}




/* ==========================================
   APPLICATION SECURITY KEY
========================================== */

function applicationSecurityKey(): string
{
    static $applicationKey =
    null;

    if (
        is_string(
            $applicationKey
        )
    ) {
        return $applicationKey;
    }

    $environmentKey =
        getenv(
            'OLSHCO_APP_KEY'
        );

    $candidateKey =
        is_string(
            $environmentKey
        )
        ? trim(
            $environmentKey
        )
        : '';

    /*
     * Local XAMPP fallback.
     * Production should provide OLSHCO_APP_KEY
     * through the server environment.
     */
    if ($candidateKey === '') {
        $localPath =
            __DIR__
            . '/security.local.php';

        if (is_file($localPath)) {
            $localConfiguration =
                require $localPath;

            if (
                is_array(
                    $localConfiguration
                )
            ) {
                $candidateKey =
                    trim(
                        (string) (
                            $localConfiguration['application_key']
                            ?? ''
                        )
                    );
            }
        }
    }

    if (
        strlen(
            $candidateKey
        ) < 64
    ) {
        throw new RuntimeException(
            'A secure application key of at least 64 characters is required.'
        );
    }

    $applicationKey =
        $candidateKey;

    return $applicationKey;
}




/* ==========================================
   TRUSTED APPLICATION URL
========================================== */

function applicationBaseUrl(): string
{
    static $applicationUrl =
    null;

    if (
        is_string(
            $applicationUrl
        )
    ) {
        return $applicationUrl;
    }

    $environmentUrl =
        getenv(
            'OLSHCO_APP_URL'
        );

    $candidateUrl =
        is_string(
            $environmentUrl
        )
        ? trim(
            $environmentUrl
        )
        : '';

    if ($candidateUrl === '') {
        $localPath =
            __DIR__
            . '/security.local.php';

        if (is_file($localPath)) {
            $localConfiguration =
                require $localPath;

            if (
                is_array(
                    $localConfiguration
                )
            ) {
                $candidateUrl =
                    trim(
                        (string) (
                            $localConfiguration['application_url']
                            ?? ''
                        )
                    );
            }
        }
    }

    $parts =
        parse_url(
            $candidateUrl
        );

    if (
        !is_array(
            $parts
        ) ||
        !in_array(
            strtolower(
                (string) (
                    $parts['scheme']
                    ?? ''
                )
            ),
            [
                'http',
                'https'
            ],
            true
        ) ||
        empty($parts['host'])
    ) {
        throw new RuntimeException(
            'A valid trusted application URL is required.'
        );
    }

    $applicationUrl =
        rtrim(
            $candidateUrl,
            '/'
        );

    return $applicationUrl;
}
