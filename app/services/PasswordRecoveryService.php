<?php

require_once __DIR__
    . '/../models/PasswordRecovery.php';

require_once __DIR__
    . '/../models/User.php';

require_once __DIR__
    . '/AuthService.php';

require_once __DIR__
    . '/EmailSender.php';

require_once __DIR__
    . '/../../config/security.php';

class PasswordRecoveryService
{
    private PasswordRecovery $recovery;

    private User $user;

    private AuthService $auth;

    private EmailSender $email;

    private const TOKEN_LIFETIME_MINUTES =
    30;

    private const THROTTLE_WINDOW_MINUTES =
    15;

    private const IDENTIFIER_LIMIT =
    3;

    private const IP_LIMIT =
    10;

    public function __construct()
    {
        $this->recovery =
            new PasswordRecovery();

        $this->user =
            new User();

        $this->auth =
            new AuthService();

        $this->email =
            new EmailSender();
    }

    /* ==========================================
       REQUEST PASSWORD RESET
    ========================================== */

    public function request(
        string $identifier,
        string $ipAddress,
        string $userAgent
    ): bool {
        $normalizedIdentifier =
            $this->normalizeIdentifier(
                $identifier
            );

        $key =
            applicationSecurityKey();

        $identifierHash =
            hash_hmac(
                'sha256',
                $normalizedIdentifier,
                $key
            );

        $normalizedIp =
            trim(
                $ipAddress
            );

        if ($normalizedIp === '') {
            $normalizedIp =
                'unknown';
        }

        $ipHash =
            hash_hmac(
                'sha256',
                $normalizedIp,
                $key
            );

        $counts =
            $this->recovery
            ->getRecentRequestCounts(
                $identifierHash,
                $ipHash,
                self::THROTTLE_WINDOW_MINUTES
            );

        if (
            $counts['identifier_count'] >=
            self::IDENTIFIER_LIMIT ||
            $counts['ip_count'] >=
            self::IP_LIMIT
        ) {
            throw new InvalidArgumentException(
                'Too many password recovery requests. Wait 15 minutes before trying again.'
            );
        }

        $account =
            $this->user
            ->findByIdentifier(
                $normalizedIdentifier
            );

        $eligibleAccount =
            is_array(
                $account
            ) &&
            (
                $account['status']
                ?? ''
            ) === 'Active' &&
            filter_var(
                $account['email']
                    ?? '',
                FILTER_VALIDATE_EMAIL
            );

        $userId =
            $eligibleAccount
            ? (int) (
                $account['user_id']
                ?? 0
            )
            : null;

        $this->recovery
            ->recordRequest(
                $userId,
                $identifierHash,
                $ipHash
            );

        /*
         * Unknown, inactive, pending, rejected,
         * and email-less accounts receive the
         * same public response without a token.
         */
        if (
            !$eligibleAccount ||
            $userId === null ||
            $userId <= 0
        ) {
            return false;
        }

        $rawToken =
            bin2hex(
                random_bytes(32)
            );

        $tokenHash =
            hash_hmac(
                'sha256',
                $rawToken,
                $key
            );

        $normalizedUserAgent =
            trim(
                $userAgent
            );

        $userAgentHash =
            $normalizedUserAgent !== ''
            ? hash_hmac(
                'sha256',
                $normalizedUserAgent,
                $key
            )
            : null;

        $expiresAt =
            date(
                'Y-m-d H:i:s',
                time() +
                    (
                        self::TOKEN_LIFETIME_MINUTES *
                        60
                    )
            );

        $this->recovery
            ->replaceActiveToken(
                $userId,
                $tokenHash,
                $ipHash,
                $userAgentHash,
                $expiresAt
            );

        $resetUrl =
            applicationBaseUrl()
            . '/index.php?page=password_reset'
            . '&token='
            . rawurlencode(
                $rawToken
            );

        $recipientName =
            trim(
                (
                    $account['first_name']
                    ?? ''
                )
                    . ' '
                    . (
                        $account['last_name']
                        ?? ''
                    )
            );

        $message =
            "A password reset was requested for your "
            . "OLSHCO Digital Hub account."
            . PHP_EOL
            . PHP_EOL
            . "Use this secure link within "
            . self::TOKEN_LIFETIME_MINUTES
            . " minutes:"
            . PHP_EOL
            . $resetUrl
            . PHP_EOL
            . PHP_EOL
            . "If you did not request this reset, "
            . "you may ignore this message. "
            . "Your current password will remain unchanged.";

        try {
            $this->email->send(
                (string) $account['email'],
                $recipientName,
                'Reset your Digital Hub password',
                $message
            );
        } catch (Throwable $exception) {
            $this->recovery
                ->invalidateToken(
                    $tokenHash
                );

            throw $exception;
        }

        return true;
    }

    /* ==========================================
       VALIDATE RESET LINK
    ========================================== */

    public function validateToken(
        string $rawToken
    ): ?array {
        $rawToken =
            $this->normalizeToken(
                $rawToken
            );

        $tokenHash =
            hash_hmac(
                'sha256',
                $rawToken,
                applicationSecurityKey()
            );

        return $this->recovery
            ->findValidToken(
                $tokenHash
            );
    }

    /* ==========================================
       COMPLETE PASSWORD RESET
    ========================================== */

    public function reset(
        string $rawToken,
        string $password,
        string $passwordConfirmation
    ): int {
        $rawToken =
            $this->normalizeToken(
                $rawToken
            );

        $this->auth
            ->validatePassword(
                $password,
                $passwordConfirmation
            );

        $token =
            $this->validateToken(
                $rawToken
            );

        if (!$token) {
            throw new RuntimeException(
                'This password-reset link is invalid or has expired.'
            );
        }

        if (
            password_verify(
                $password,
                (string) (
                    $token['current_password_hash']
                    ?? ''
                )
            )
        ) {
            throw new InvalidArgumentException(
                'Choose a password different from your current password.'
            );
        }

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        if (
            !is_string(
                $passwordHash
            ) ||
            $passwordHash === ''
        ) {
            throw new RuntimeException(
                'Unable to secure the new password.'
            );
        }

        $tokenHash =
            hash_hmac(
                'sha256',
                $rawToken,
                applicationSecurityKey()
            );

        return $this->recovery
            ->completeReset(
                $tokenHash,
                $passwordHash
            );
    }

    /* ==========================================
       NORMALIZATION
    ========================================== */

    private function normalizeIdentifier(
        string $identifier
    ): string {
        $identifier =
            trim(
                $identifier
            );

        if ($identifier === '') {
            throw new InvalidArgumentException(
                'Enter your account ID or email address.'
            );
        }

        if (
            mb_strlen(
                $identifier
            ) > 190
        ) {
            throw new InvalidArgumentException(
                'The account identifier is too long.'
            );
        }

        return str_contains(
            $identifier,
            '@'
        )
            ? mb_strtolower(
                $identifier
            )
            : mb_strtoupper(
                $identifier
            );
    }

    private function normalizeToken(
        string $rawToken
    ): string {
        $rawToken =
            strtolower(
                trim(
                    $rawToken
                )
            );

        if (
            !preg_match(
                '/^[a-f0-9]{64}$/',
                $rawToken
            )
        ) {
            throw new InvalidArgumentException(
                'This password-reset link is invalid or has expired.'
            );
        }

        return $rawToken;
    }
}
