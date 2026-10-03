<?php
require_once __DIR__ . '/BaseModel.php';

class SessionAccount extends BaseModel
{
    public function __construct(mysqli $connection) { $this->conn = $connection; }

    public function findForSession(int $userId): ?array
    {
        $stmt = $this->conn->prepare("SELECT u.user_id, u.status, u.role_id, r.role_prefix,
            SHA2(u.password, 256) AS credential_version, u.must_change_password, u.gender, u.birthdate,
            u.department_id, u.education_level_id, u.academic_program_id, u.grade_level_id, u.section_id,
            d.department_name, el.education_level_name, ap.program_name AS academic_program_name, ap.program_type AS academic_program_type,
            gl.grade_level_name, s.section_name
            FROM user u LEFT JOIN role r ON r.role_id=u.role_id
            LEFT JOIN department d ON d.department_id=u.department_id
            LEFT JOIN education_level el ON el.education_level_id=u.education_level_id
            LEFT JOIN academic_program ap ON ap.academic_program_id=u.academic_program_id
            LEFT JOIN grade_level gl ON gl.grade_level_id=u.grade_level_id
            LEFT JOIN section s ON s.section_id=u.section_id
            WHERE u.user_id=? LIMIT 1");
        if (!$stmt) throw new RuntimeException('Account verification unavailable.');
        try {
            $stmt->bind_param('i',$userId);
            if (!$stmt->execute()) throw new RuntimeException('Account verification unavailable.');
            return $stmt->get_result()->fetch_assoc() ?: null;
        } finally { $stmt->close(); }
    }
}
