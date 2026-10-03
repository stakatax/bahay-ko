<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/ParentChildRecord.php';

class ContentAudience extends BaseModel
{
    private const TARGETS = [
        'announcement' => ['announcement_target', 'announcement_id'],
        'event' => ['event_target', 'event_id'],
        'document' => ['document_target', 'document_id'],
        'survey' => ['survey_target', 'survey_id']
    ];

    public function __construct(?mysqli $connection = null)
    {
        if ($connection !== null) {
            $this->conn = $connection;
        } else {
            parent::__construct();
        }
    }

    public function findActor(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $rows = $this->select(
            "SELECT u.user_id, u.role_id, r.role_prefix, u.status,
                    u.department_id, u.education_level_id,
                    u.academic_program_id, u.grade_level_id, u.section_id
             FROM user u
             INNER JOIN role r ON r.role_id = u.role_id
             WHERE u.user_id = ? LIMIT 1",
            'i',
            [$userId]
        );

        return $rows[0] ?? null;
    }

    public function getVerifiedStudentProfiles(int $parentId): array
    {
        $profiles = $this->select(
            "SELECT child.department_id, child.education_level_id,
                    child.academic_program_id, child.grade_level_id, child.section_id
             FROM parent_student ps
             INNER JOIN user child ON child.user_id = ps.student_user_id
                 AND child.status = 'Active'
             INNER JOIN role r ON r.role_id = child.role_id
                 AND r.role_prefix = 'Student'
             WHERE ps.parent_user_id = ? AND ps.status = 'Verified'
             ORDER BY child.user_id",
            'i',
            [$parentId]
        );
        return array_merge($profiles, (new ParentChildRecord($this->conn))->profiles($parentId));
    }

    public function getTargetMap(string $contentType, array $ids): array
    {
        if (!isset(self::TARGETS[$contentType])) {
            throw new InvalidArgumentException('Invalid content type.');
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));
        if ($ids === []) {
            return [];
        }

        [$table, $key] = self::TARGETS[$contentType];
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $rows = $this->select(
            "SELECT {$key} AS content_id, role_id, department_id,
                    education_level_id, academic_program_id, grade_level_id, section_id
             FROM {$table} WHERE {$key} IN ({$placeholders})",
            str_repeat('i', count($ids)),
            $ids
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['content_id']][] = $row;
        }

        return $map;
    }

    private function select(string $sql, string $types, array $values): array
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Unable to verify content access.');
        }

        try {
            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) {
                throw new RuntimeException('Unable to verify content access.');
            }
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } finally {
            $stmt->close();
        }
    }
}
