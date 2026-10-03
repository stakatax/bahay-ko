<?php
require_once __DIR__.'/BaseModel.php';

class PublicationNotificationOutbox extends BaseModel
{
    private const SOURCES = [
        'announcement' => ['announcements', 'announcement_id'],
        'event' => ['events', 'event_id'],
        'document' => ['documents', 'document_id'],
        'survey' => ['survey', 'survey_id']
    ];

    public function isDeliverable(string $type, int $id): bool
    {
        if (!isset(self::SOURCES[$type]) || $id <= 0) {
            throw new InvalidArgumentException('Invalid publication notification source.');
        }
        [$table, $key] = self::SOURCES[$type];
        $stmt = $this->execute("SELECT workflow_status, status, send_notification FROM {$table} WHERE {$key} = ?", 'i', [$id]);
        try { $row = $stmt->get_result()->fetch_assoc(); } finally { $stmt->close(); }
        return $row && $row['workflow_status'] === 'published'
            && in_array(strtolower($row['status']), ['active', 'published'], true)
            && !empty($row['send_notification']);
    }

    // Caller must use the same transaction as the publication and audience writes.
    public function enqueue(string $type, int $id): void
    {
        if (!$this->isDeliverable($type, $id)) return;
        $stmt = $this->execute("INSERT INTO publication_notification_outbox (content_type, content_id)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE outbox_id = outbox_id", 'si', [$type, $id]);
        $stmt->close();
        $stmt = $this->execute("UPDATE publication_notification_outbox
            SET delivery_status = 'Pending', available_at = NOW(), completed_at = NULL
            WHERE content_type = ? AND content_id = ? AND delivery_status = 'Cancelled'", 'si', [$type, $id]);
        $stmt->close();
    }

    public function claim(?string $type = null, ?int $id = null): ?array
    {
        if (($type === null) !== ($id === null)
            || ($type !== null && (!isset(self::SOURCES[$type]) || $id <= 0))) {
            throw new InvalidArgumentException('Invalid publication claim filter.');
        }
        $token = bin2hex(random_bytes(32));
        $filter = $type !== null ? ' AND content_type = ? AND content_id = ?' : '';
        $values = $type !== null ? [$token, $type, $id] : [$token];
        // One atomic update, so concurrent dispatchers cannot claim the same live lease.
        $stmt = $this->execute("UPDATE publication_notification_outbox
            SET delivery_status = 'Processing', lock_token = ?, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                attempt_count = attempt_count + 1
            WHERE ((delivery_status = 'Pending' AND available_at <= NOW())
                OR (delivery_status = 'Processing' AND locked_until <= NOW())) {$filter}
            ORDER BY available_at, outbox_id LIMIT 1", $type !== null ? 'ssi' : 's', $values);
        $claimed = $stmt->affected_rows > 0;
        $stmt->close();
        if (!$claimed) return null;
        $stmt = $this->execute("SELECT outbox_id, content_type, content_id, attempt_count, lock_token
            FROM publication_notification_outbox WHERE lock_token = ? AND delivery_status = 'Processing'", 's', [$token]);
        try { return $stmt->get_result()->fetch_assoc() ?: null; } finally { $stmt->close(); }
    }

    public function finish(array $job, string $status): bool
    {
        if (!in_array($status, ['Completed', 'Cancelled'], true)) throw new InvalidArgumentException('Invalid completion state.');
        $stmt = $this->execute("UPDATE publication_notification_outbox
            SET delivery_status = ?, completed_at = NOW(), lock_token = NULL, locked_until = NULL, last_error = NULL
            WHERE outbox_id = ? AND lock_token = ? AND delivery_status = 'Processing'",
            'sis', [$status, (int)$job['outbox_id'], $job['lock_token']]);
        try { return $stmt->affected_rows > 0; } finally { $stmt->close(); }
    }

    public function retry(array $job, Throwable $exception): void
    {
        $delay = min(3600, 30 * (2 ** min(7, max(0, (int)$job['attempt_count'] - 1))));
        $error = get_class($exception).' (code '.(int)$exception->getCode().')';
        $stmt = $this->execute("UPDATE publication_notification_outbox
            SET delivery_status = 'Pending', available_at = DATE_ADD(NOW(), INTERVAL ? SECOND),
                lock_token = NULL, locked_until = NULL, last_error = ?
            WHERE outbox_id = ? AND lock_token = ? AND delivery_status = 'Processing'",
            'isis', [$delay, $error, (int)$job['outbox_id'], $job['lock_token']]);
        $stmt->close();
    }

    private function execute(string $sql, string $types, array $values): mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new RuntimeException('Unable to prepare publication notification operation.');
        try {
            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) throw new RuntimeException('Unable to persist publication notification operation.');
            return $stmt;
        } catch (Throwable $exception) {
            $stmt->close();
            throw $exception;
        }
    }
}
