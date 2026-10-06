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
        return $this->getTargetSets([$contentType => $ids])[$contentType];
    }

    /** Live target reads for this batch only; never retain authorization state. */
    public function getTargetSets(array $sets): array
    {
        $map = []; $pairs = [];
        foreach ($sets as $type => $ids) {
            if (!isset(self::TARGETS[$type])) { throw new InvalidArgumentException('Invalid content type.'); }
            $map[$type] = [];
            foreach (array_unique(array_map('intval', $ids)) as $id) {
                if ($id > 0) { $pairs[] = [$type, $id]; }
            }
        }
        foreach (array_chunk($pairs, 500) as $chunk) {
            $groups = [];
            foreach ($chunk as [$type, $id]) { $groups[$type][] = $id; }
            $queries = []; $parameters = []; $types = '';
            foreach ($groups as $type => $ids) {
                [$table, $key] = self::TARGETS[$type];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $queries[] = "SELECT ? AS content_type, {$key} AS content_id, role_id, department_id,
                    education_level_id, academic_program_id, grade_level_id, section_id
                    FROM {$table} WHERE {$key} IN ({$placeholders})";
                $parameters = [...$parameters, $type, ...$ids];
                $types .= 's' . str_repeat('i', count($ids));
            }
            foreach ($this->select(implode(' UNION ALL ', $queries), $types, $parameters) as $row) {
                $type = $row['content_type']; unset($row['content_type']);
                $map[$type][(int) $row['content_id']][] = $row;
            }
        }
        return $map;
    }

    /** Display metadata only; this does not grant content access. */
    public function getLabelSets(array $sets): array
    {
        $map = []; $pairs = [];
        foreach ($sets as $type => $ids) {
            if (!isset(self::TARGETS[$type])) { throw new InvalidArgumentException('Invalid content type for target tags.'); }
            $map[$type] = [];
            foreach (array_unique(array_map('intval', $ids)) as $id) {
                if ($id > 0) { $pairs[] = [$type, $id]; }
            }
        }
        foreach (array_chunk($pairs, 500) as $chunk) {
            $groups = [];
            foreach ($chunk as [$type, $id]) { $groups[$type][] = $id; }
            $queries = []; $parameters = []; $types = '';
            foreach ($groups as $type => $ids) {
                [$table, $key] = self::TARGETS[$type];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $queries[] = "SELECT ? AS content_type, t.{$key} AS content_id,
                    r.role_prefix, d.department_name, el.education_level_name, ap.program_name, gl.grade_level_name, s.section_name
                    FROM {$table} t
                    LEFT JOIN role r ON r.role_id = t.role_id
                    LEFT JOIN department d ON d.department_id = t.department_id
                    LEFT JOIN education_level el ON el.education_level_id = t.education_level_id
                    LEFT JOIN academic_program ap ON ap.academic_program_id = t.academic_program_id
                    LEFT JOIN grade_level gl ON gl.grade_level_id = t.grade_level_id
                    LEFT JOIN section s ON s.section_id = t.section_id
                    WHERE t.{$key} IN ({$placeholders})";
                $parameters = [...$parameters, $type, ...$ids];
                $types .= 's' . str_repeat('i', count($ids));
            }
            $rows = $this->select(implode(' UNION ALL ', $queries), $types, $parameters);
            foreach ($rows as $row) {
                foreach (['role_prefix', 'department_name', 'education_level_name', 'program_name', 'grade_level_name', 'section_name'] as $field) {
                    $label = trim((string) ($row[$field] ?? ''));
                    if ($label !== '') { $map[$row['content_type']][(int) $row['content_id']][$label] = true; }
                }
            }
        }
        foreach ($map as &$items) {
            foreach ($items as &$labels) { $labels = array_keys($labels); }
            unset($labels);
        }
        unset($items);
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
