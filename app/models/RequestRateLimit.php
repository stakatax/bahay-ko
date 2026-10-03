<?php
require_once __DIR__ . '/BaseModel.php';

class RequestRateLimit extends BaseModel
{
    /** Returns retry seconds; zero means this attempt is within the limit. */
    public function consume(string $scope, string $keyHash, int $limit, int $windowSeconds): int
    {
        if (!preg_match('/^[a-z_]{1,32}$/D', $scope) || !preg_match('/^[a-f0-9]{64}$/D', $keyHash)
            || $limit < 1 || $limit > 60000 || $windowSeconds < 1 || $windowSeconds > 86400) {
            throw new InvalidArgumentException('Invalid rate-limit policy.');
        }
        $transaction = $this->conn->query('SELECT @@in_transaction AS active');
        if (!$transaction) { throw new RuntimeException('Rate-limit storage is unavailable.'); }
        if ((int) $transaction->fetch_assoc()['active'] !== 0) {
            throw new RuntimeException('Rate limits must be checked before application transactions.');
        }
        // Bounded cleanup; never remove an active window or any application content.
        if (!$this->conn->query('DELETE FROM request_rate_limit WHERE expires_at < UNIX_TIMESTAMP() - 86400 LIMIT 100')) {
            throw new RuntimeException('Rate-limit storage is unavailable.');
        }
        if (!$this->conn->begin_transaction()) { throw new RuntimeException('Rate-limit storage is unavailable.'); }
        try {
            $stmt = $this->conn->prepare('INSERT INTO request_rate_limit (key_hash, scope, attempts, expires_at)
                VALUES (?, ?, 1, UNIX_TIMESTAMP() + ?)
                ON DUPLICATE KEY UPDATE
                    attempts = IF(expires_at <= UNIX_TIMESTAMP(), 1, LEAST(attempts + 1, ?)),
                    expires_at = IF(expires_at <= UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + ?, expires_at)');
            if (!$stmt) { throw new RuntimeException('Rate-limit storage is unavailable.'); }
            try {
                $ceiling = $limit + 1;
                $stmt->bind_param('ssiii', $keyHash, $scope, $windowSeconds, $ceiling, $windowSeconds);
                if (!$stmt->execute()) { throw new RuntimeException('Rate-limit storage is unavailable.'); }
            } finally { $stmt->close(); }
            $stmt = $this->conn->prepare('SELECT attempts, GREATEST(1, expires_at - UNIX_TIMESTAMP()) AS retry_after FROM request_rate_limit WHERE key_hash = ?');
            if (!$stmt) { throw new RuntimeException('Rate-limit storage is unavailable.'); }
            try {
                $stmt->bind_param('s', $keyHash);
                if (!$stmt->execute()) { throw new RuntimeException('Rate-limit storage is unavailable.'); }
                $row = $stmt->get_result()->fetch_assoc();
                if (!$row) { throw new RuntimeException('Rate-limit storage is unavailable.'); }
            } finally { $stmt->close(); }
            if (!$this->conn->commit()) { throw new RuntimeException('Unable to save rate-limit state.'); }
            return (int) $row['attempts'] > $limit ? (int) $row['retry_after'] : 0;
        } catch (Throwable $exception) {
            $this->conn->rollback();
            throw $exception;
        }
    }
}
