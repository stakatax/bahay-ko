<?php
require_once __DIR__ . '/BaseModel.php';

class ContentWorkspace extends BaseModel
{
    /** Owner scope is supplied by the authorized workspace service. */
    public function countByWorkflowStatus(?int $ownerId = null): array
    {
        if ($ownerId !== null && $ownerId <= 0) {
            throw new InvalidArgumentException('Invalid workspace owner.');
        }
        $parts = [];
        foreach (['announcements', 'events', 'documents', 'survey'] as $table) {
            $where = $ownerId === null ? '' : ' WHERE user_id = ?';
            $parts[] = "SELECT workflow_status, COUNT(*) AS total FROM {$table}{$where} GROUP BY workflow_status";
        }
        $stmt = $this->conn->prepare('SELECT workflow_status, SUM(total) AS total FROM ('
            . implode(' UNION ALL ', $parts) . ') AS counts GROUP BY workflow_status');
        if (!$stmt) { throw new RuntimeException('Unable to load workspace totals.'); }
        try {
            if ($ownerId !== null) { $stmt->bind_param('iiii', $ownerId, $ownerId, $ownerId, $ownerId); }
            if (!$stmt->execute()) { throw new RuntimeException('Unable to load workspace totals.'); }
            $counts = [];
            foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                $counts[$row['workflow_status']] = (int) $row['total'];
            }
            return $counts;
        } finally { $stmt->close(); }
    }
}
