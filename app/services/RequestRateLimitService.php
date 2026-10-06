<?php
require_once __DIR__ . '/../models/RequestRateLimit.php';
require_once __DIR__ . '/../../config/security.php';

class RequestRateLimitException extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('Too many requests. Please try again in ' . max(1, $retryAfter) . ' second(s).');
    }
}

class RequestRateLimitService
{
    public function __construct(private ?RequestRateLimit $model = null, private ?string $key = null) {}

    public function login(string $identifier, string $ip): void
    {
        $this->enforce('login_ip', self::normalizeIp($ip), 120, 900);
        $this->enforce('login_identifier', mb_strtolower(trim($identifier)), 20, 900);
    }

    public function register(string $ip): void
    {
        $this->enforce('registration_ip', self::normalizeIp($ip), 30, 3600);
    }

    public function contact(string $email, string $ip): void
    {
        $this->enforce('contact_ip', self::normalizeIp($ip), 5, 3600);
        $this->enforce('contact_email', mb_strtolower(trim($email)), 3, 3600);
        $this->enforce('contact_global', 'inquiry', 30, 3600);
    }

    public function engagement(string $action, int $userId): void
    {
        $limits = ['open'=>120, 'react'=>60, 'comment'=>10, 'acknowledge'=>60];
        if (!isset($limits[$action]) || $userId <= 0) { throw new InvalidArgumentException('Invalid engagement rate-limit context.'); }
        $this->enforce('engagement_' . $action, (string) $userId, $limits[$action], 60);
    }

    private function enforce(string $scope, string $identity, int $limit, int $seconds): void
    {
        $key = $this->key ?? applicationSecurityKey();
        if (strlen($key) < 64) { throw new RuntimeException('Rate-limit key is unavailable.'); }
        $this->model ??= new RequestRateLimit();
        $retry = $this->model->consume($scope, hash_hmac('sha256', $scope . "\0" . $identity, $key), $limit, $seconds);
        if ($retry > 0) { throw new RequestRateLimitException($retry); }
    }

    private static function normalizeIp(string $ip): string
    {
        // Callers use REMOTE_ADDR only; never trust client-supplied forwarding headers.
        if (filter_var(trim($ip), FILTER_VALIDATE_IP) === false) { return 'unknown'; }
        $packed = inet_pton(trim($ip));
        return $packed === false ? 'unknown' : bin2hex($packed);
    }
}
