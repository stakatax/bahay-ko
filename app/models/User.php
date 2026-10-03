<?php

require_once 'BaseModel.php';
require_once __DIR__ . '/ParentChildRecord.php';

class User extends BaseModel
{
    /* ==========================================
       USER LOOKUPS
    ========================================== */

    public function findByIdentifier(
        string $identifier
    ) {
        $identifier = trim($identifier);

        $stmt = $this->conn->prepare("
            SELECT
                u.*,
                r.role_prefix,

                d.department_name,

                el.education_level_name,

                ap.program_name
                    AS academic_program_name,

                ap.program_code
                    AS academic_program_code,

                ap.program_type
                    AS academic_program_type,

                gl.grade_level_name,

                s.section_name

            FROM user u

            INNER JOIN role r
                ON r.role_id = u.role_id

            LEFT JOIN department d
                ON d.department_id =
                   u.department_id

            LEFT JOIN education_level el
                ON el.education_level_id =
                   u.education_level_id

            LEFT JOIN academic_program ap
                ON ap.academic_program_id =
                   u.academic_program_id

            LEFT JOIN grade_level gl
                ON gl.grade_level_id =
                   u.grade_level_id

            LEFT JOIN section s
                ON s.section_id =
                   u.section_id

            WHERE u.studID = ?
               OR u.email = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare account lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            'ss',
            $identifier,
            $identifier
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    public function findByStudentID(
        string $studentId
    ) {
        $studentId = strtoupper(
            trim($studentId)
        );

        $stmt = $this->conn->prepare("
            SELECT
                u.*,
                r.role_prefix

            FROM user u

            INNER JOIN role r
                ON r.role_id = u.role_id

            WHERE u.studID = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare Student ID lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            's',
            $studentId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    public function findByEmail(
        string $email
    ) {
        $email = strtolower(
            trim($email)
        );

        $stmt = $this->conn->prepare("
            SELECT
                u.*,
                r.role_prefix

            FROM user u

            INNER JOIN role r
                ON r.role_id = u.role_id

            WHERE u.email = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare email lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            's',
            $email
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    public function findById(
        int $userId
    ) {
        $stmt = $this->conn->prepare("
            SELECT
                u.*,
                r.role_prefix,

                d.department_name,

                el.education_level_name,

                ap.program_name
                    AS academic_program_name,

                ap.program_code
                    AS academic_program_code,

                ap.program_type
                    AS academic_program_type,

                gl.grade_level_name,

                s.section_name

            FROM user u

            INNER JOIN role r
                ON r.role_id = u.role_id

            LEFT JOIN department d
                ON d.department_id =
                   u.department_id

            LEFT JOIN education_level el
                ON el.education_level_id =
                   u.education_level_id

            LEFT JOIN academic_program ap
                ON ap.academic_program_id =
                   u.academic_program_id

            LEFT JOIN grade_level gl
                ON gl.grade_level_id =
                   u.grade_level_id

            LEFT JOIN section s
                ON s.section_id =
                   u.section_id

            WHERE u.user_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare user lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    /* ==========================================
       ROLE LOOKUPS
    ========================================== */

    public function findRoleByPrefix(
        string $rolePrefix
    ) {
        $stmt = $this->conn->prepare("
            SELECT
                role_id,
                role_prefix

            FROM role

            WHERE role_prefix = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare role lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            's',
            $rolePrefix
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    /* ==========================================
       ACADEMIC LOOKUPS
    ========================================== */

    public function findDepartmentById(
        int $departmentId
    ) {
        $stmt = $this->conn->prepare("
        SELECT
            d.department_id,
            d.department_name,
            d.department_code,
            d.description,
            d.status

        FROM department d

        WHERE d.department_id = ?
          AND d.status = 'Active'

        LIMIT 1
    ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare School Division lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $departmentId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    public function findEducationLevelById(
        int $educationLevelId
    ) {
        $stmt = $this->conn->prepare("
        SELECT
            el.education_level_id,
            el.department_id,
            el.education_level_name,
            el.status

        FROM education_level el

        WHERE el.education_level_id = ?
          AND el.status = 'Active'

        LIMIT 1
    ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare Education Level lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $educationLevelId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    public function findAcademicProgramById(
        int $academicProgramId
    ) {
        $stmt = $this->conn->prepare("
            SELECT
                academic_program_id,
                education_level_id,
                program_name,
                program_code,
                program_type,
                status

            FROM academic_program

            WHERE academic_program_id = ?
              AND status = 'Active'

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare Program or Strand lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $academicProgramId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    public function educationLevelHasPrograms(
        int $educationLevelId
    ): bool {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) AS total

            FROM academic_program

            WHERE education_level_id = ?
              AND status = 'Active'
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to check available Programs or Strands.'
            );
        }

        $stmt->bind_param(
            'i',
            $educationLevelId
        );

        $stmt->execute();

        $row = $stmt
            ->get_result()
            ->fetch_assoc();

        return (int) (
            $row['total'] ?? 0
        ) > 0;
    }

    public function findGradeLevelById(
        int $gradeLevelId
    ) {
        $stmt = $this->conn->prepare("
            SELECT
                grade_level_id,
                education_level_id,
                grade_level_name,
                status

            FROM grade_level

            WHERE grade_level_id = ?
              AND status = 'Active'

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare Grade or Year Level lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $gradeLevelId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    public function findSectionById(
        int $sectionId
    ) {
        $stmt = $this->conn->prepare("
            SELECT
                section_id,
                grade_level_id,
                academic_program_id,
                section_name,
                status

            FROM section

            WHERE section_id = ?
              AND status = 'Active'

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare Section lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $sectionId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    /* ==========================================
   MANAGED ACADEMIC ASSIGNMENT UPDATE
========================================== */

    public function updateManagedAcademicAssignment(
        int $userId,
        ?int $departmentId,
        ?int $educationLevelId,
        ?int $academicProgramId,
        ?int $gradeLevelId,
        ?int $sectionId
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid managed user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE user

            SET
                department_id = ?,
                education_level_id = ?,
                academic_program_id = ?,
                grade_level_id = ?,
                section_id = ?,
                updated_at = NOW()

            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare academic assignment update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iiiiii',
            $departmentId,
            $educationLevelId,
            $academicProgramId,
            $gradeLevelId,
            $sectionId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update the academic assignment: '
                    . $error
            );
        }

        /*
     * A successful execution is valid even when the
     * submitted assignment matches the existing one.
     */
        $stmt->close();

        return true;
    }

    /* ==========================================
       REGISTRATION OPTIONS
    ========================================== */

    public function getActiveDepartments(): array
    {
        $result = $this->conn->query("
        SELECT
            d.department_id,
            d.department_name,
            d.department_code,
            d.description

        FROM department d

        WHERE d.status = 'Active'

        ORDER BY
            d.department_name ASC
    ");

        if (!$result) {
            throw new Exception(
                'Unable to load School Divisions.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getActiveEducationLevels(): array
    {
        $result = $this->conn->query("
        SELECT
            el.education_level_id,
            el.department_id,
            el.education_level_name

        FROM education_level el

        INNER JOIN department d
            ON d.department_id =
               el.department_id
           AND d.status = 'Active'

        WHERE el.status = 'Active'

        ORDER BY
            el.department_id ASC,
            el.education_level_id ASC
    ");

        if (!$result) {
            throw new Exception(
                'Unable to load Education Levels.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getActiveAcademicPrograms(): array
    {
        $result = $this->conn->query("
            SELECT
                academic_program_id,
                education_level_id,
                program_name,
                program_code,
                program_type

            FROM academic_program

            WHERE status = 'Active'

            ORDER BY
                education_level_id ASC,
                program_type ASC,
                program_code ASC
        ");

        if (!$result) {
            throw new Exception(
                'Unable to load Programs or Strands.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getActiveGradeLevels(): array
    {
        $result = $this->conn->query("
            SELECT
                grade_level_id,
                education_level_id,
                grade_level_name

            FROM grade_level

            WHERE status = 'Active'

            ORDER BY
                education_level_id ASC,
                grade_level_id ASC
        ");

        if (!$result) {
            throw new Exception(
                'Unable to load Grade or Year Levels.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getActiveSections(): array
    {
        $result = $this->conn->query("
            SELECT
                section_id,
                grade_level_id,
                academic_program_id,
                section_name

            FROM section

            WHERE status = 'Active'

            ORDER BY
                grade_level_id ASC,
                academic_program_id ASC,
                section_name ASC
        ");

        if (!$result) {
            throw new Exception(
                'Unable to load Sections.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    /* ==========================================
       STUDENT LOOKUP FOR PARENT LINKING
    ========================================== */

    public function findActiveStudent(
        string $studentId
    ) {
        $studentId = strtoupper(
            trim($studentId)
        );

        $stmt = $this->conn->prepare("
            SELECT
                u.user_id,
                u.studID,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.status,

                u.department_id,
                u.education_level_id,
                u.academic_program_id,
                u.grade_level_id,
                u.section_id,

                r.role_prefix

            FROM user u

            INNER JOIN role r
                ON r.role_id = u.role_id

            WHERE u.studID = ?
              AND r.role_prefix = 'Student'
              AND u.status = 'Active'

            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare Student lookup: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            's',
            $studentId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc();
    }

    /* ==========================================
   ADMIN USER DIRECTORY
========================================== */

    public function getManagedUsers(
        array $filters = [],
        int $limit = 200
    ): array {
        $search =
            trim(
                (string) (
                    $filters['search']
                    ?? ''
                )
            );

        $searchLike =
            '%'
            . $search
            . '%';

        $roleId =
            max(
                0,
                (int) (
                    $filters['role_id']
                    ?? 0
                )
            );

        $status =
            trim(
                (string) (
                    $filters['status']
                    ?? ''
                )
            );

        $departmentId =
            max(
                0,
                (int) (
                    $filters['department_id']
                    ?? 0
                )
            );

        $educationLevelId =
            max(
                0,
                (int) (
                    $filters['education_level_id']
                    ?? 0
                )
            );

        $limit =
            max(
                1,
                min(
                    $limit,
                    500
                )
            );

        $allowedStatuses = [
            '',
            'Pending',
            'Active',
            'Inactive',
            'Rejected'
        ];

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            $status = '';
        }

        $stmt =
            $this->conn->prepare("
        SELECT
            u.user_id,
            u.studID,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.name_suffix,
            u.email,
            u.gender,
            u.age,
            u.birthdate,
            u.profile_photo,
            u.status,
            u.created_at,
            u.updated_at,
u.last_login,
u.failed_attempts,
u.lock_until,
u.must_change_password,
u.password_changed_at,
u.provisioned_by,
u.provisioned_at,

            u.role_id,
            r.role_prefix,

            u.department_id,
            d.department_name,

            u.education_level_id,
            el.education_level_name,

            u.academic_program_id,
            ap.program_name
                AS academic_program_name,
            ap.program_code
                AS academic_program_code,

            u.grade_level_id,
            gl.grade_level_name,

            u.section_id,
            s.section_name,

            ps.parent_student_id,
            ps.relationship,
            ps.status
                AS relationship_status,

            child.user_id
                AS child_user_id,
            child.studID
                AS child_student_id,

            TRIM(
                CONCAT_WS(
                    ' ',
                    child.first_name,
                    child.middle_name,
                    child.last_name,
                    child.name_suffix
                )
            ) AS child_name

        FROM user u

        INNER JOIN role r
            ON r.role_id =
               u.role_id

        LEFT JOIN department d
            ON d.department_id =
               u.department_id

        LEFT JOIN education_level el
            ON el.education_level_id =
               u.education_level_id

        LEFT JOIN academic_program ap
            ON ap.academic_program_id =
               u.academic_program_id

        LEFT JOIN grade_level gl
            ON gl.grade_level_id =
               u.grade_level_id

        LEFT JOIN section s
            ON s.section_id =
               u.section_id

        LEFT JOIN parent_student ps
            ON ps.parent_user_id =
               u.user_id

        LEFT JOIN user child
            ON child.user_id =
               ps.student_user_id

        WHERE (
                ? = ''

                OR CONCAT_WS(
                    ' ',
                    u.studID,
                    u.first_name,
                    u.middle_name,
                    u.last_name,
                    u.name_suffix,
                    u.email
                ) LIKE ?
              )

          AND (
                ? = 0
                OR u.role_id = ?
              )

          AND (
                ? = ''
                OR u.status = ?
              )

          AND (
                ? = 0
                OR u.department_id = ?
              )

          AND (
                ? = 0
                OR u.education_level_id = ?
              )

        ORDER BY
            FIELD(
                u.status,
                'Pending',
                'Active',
                'Inactive',
                'Rejected'
            ),
            u.last_name ASC,
            u.first_name ASC,
            u.user_id ASC

        LIMIT ?
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the managed user directory: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssiissiiiii',
            $search,
            $searchLike,
            $roleId,
            $roleId,
            $status,
            $status,
            $departmentId,
            $departmentId,
            $educationLevelId,
            $educationLevelId,
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load the managed user directory: '
                    . $error
            );
        }

        $users =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $users;
    }

    public function getRoles(): array
    {
        $result =
            $this->conn->query("
        SELECT
            role_id,
            role_prefix

        FROM role

        WHERE role_prefix IS NOT NULL
          AND TRIM(role_prefix) <> ''

        ORDER BY
            FIELD(
                role_prefix,
                'Admin',
                'Faculty',
                'Student',
                'Parent'
            ),
            role_prefix ASC
    ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load account roles: '
                    . $this->conn->error
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }


    /* ==========================================
   MANAGED USER ACTIVITY HISTORY
========================================== */

    public function getManagedUserActivityHistory(
        int $userId,
        int $limit = 100
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $limit =
            max(
                1,
                min(
                    $limit,
                    200
                )
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                timeline.event_source,
                timeline.event_type,
                timeline.title,
                timeline.description,
                timeline.previous_value,
                timeline.new_value,
                timeline.reason,
                timeline.actor_user_id,
                timeline.actor_name,
                timeline.occurred_at

            FROM
            (
                /* Account status transitions */
                SELECT
                    'status_history'
                        AS event_source,

                    'status'
                        AS event_type,

                    'Account status changed'
                        AS title,

                    NULL
                        AS description,

                    ush.previous_status
                        AS previous_value,

                    ush.new_status
                        AS new_value,

                    ush.reason,

                    ush.changed_by
                        AS actor_user_id,

                    TRIM(
                        CONCAT_WS(
                            ' ',
                            actor.first_name,
                            NULLIF(
                                actor.middle_name,
                                ''
                            ),
                            actor.last_name,
                            NULLIF(
                                actor.name_suffix,
                                ''
                            )
                        )
                    ) AS actor_name,

                    ush.created_at
                        AS occurred_at

                FROM user_status_history ush

                INNER JOIN user actor
                    ON actor.user_id =
                       ush.changed_by

                WHERE ush.user_id = ?

                UNION ALL

                /* Staff role transitions */
                SELECT
                    'role_history'
                        AS event_source,

                    'role'
                        AS event_type,

                    'Staff role changed'
                        AS title,

                    NULL
                        AS description,

                    previous_role.role_prefix
                        AS previous_value,

                    new_role.role_prefix
                        AS new_value,

                    urh.reason,

                    urh.changed_by
                        AS actor_user_id,

                    TRIM(
                        CONCAT_WS(
                            ' ',
                            actor.first_name,
                            NULLIF(
                                actor.middle_name,
                                ''
                            ),
                            actor.last_name,
                            NULLIF(
                                actor.name_suffix,
                                ''
                            )
                        )
                    ) AS actor_name,

                    urh.created_at
                        AS occurred_at

                FROM user_role_history urh

                INNER JOIN role previous_role
                    ON previous_role.role_id =
                       urh.previous_role_id

                INNER JOIN role new_role
                    ON new_role.role_id =
                       urh.new_role_id

                INNER JOIN user actor
                    ON actor.user_id =
                       urh.changed_by

                WHERE urh.user_id = ?

                UNION ALL

                /* Structured security activity */
                SELECT
                    'activity_log'
                        AS event_source,

                    'security'
                        AS event_type,

                    REPLACE(
                        a.action_name,
                        '_',
                        ' '
                    ) AS title,

                    al.description,

                    NULL
                        AS previous_value,

                    NULL
                        AS new_value,

                    NULL
                        AS reason,

                    al.user_id
                        AS actor_user_id,

                    TRIM(
                        CONCAT_WS(
                            ' ',
                            actor.first_name,
                            NULLIF(
                                actor.middle_name,
                                ''
                            ),
                            actor.last_name,
                            NULLIF(
                                actor.name_suffix,
                                ''
                            )
                        )
                    ) AS actor_name,

                    al.timestamp
                        AS occurred_at

                FROM activity_log al

                INNER JOIN actions a
                    ON a.action_id =
                       al.action_id

                LEFT JOIN user actor
                    ON actor.user_id =
                       al.user_id

                WHERE al.target_user_id = ?

                  AND a.action_name NOT IN
                  (
                      'CHANGE_USER_ACCOUNT_STATUS',
                      'CHANGE_USER_STAFF_ROLE',
                      'PROVISION_FACULTY_ACCOUNT'
                  )

                UNION ALL

                /* Original account creation */
                SELECT
                    'account'
                        AS event_source,

                    'creation'
                        AS event_type,

                    CASE
                        WHEN account.provisioned_by
                             IS NOT NULL
                        THEN 'Staff account created'
                        ELSE 'Registration submitted'
                    END AS title,

                    CASE
                        WHEN account.provisioned_by
                             IS NOT NULL
                        THEN 'The account was created through Staff Provisioning.'
                        ELSE 'The account was submitted through public registration.'
                    END AS description,

                    NULL
                        AS previous_value,

                    account.status
                        AS new_value,

                    NULL
                        AS reason,

                    NULL
                        AS actor_user_id,

                    NULL
                        AS actor_name,

                    account.created_at
                        AS occurred_at

                FROM user account

                WHERE account.user_id = ?
  AND account.provisioned_by IS NULL

                UNION ALL

                /* Faculty provisioning event */
                SELECT
                    'provisioning'
                        AS event_source,

                    'provisioning'
                        AS event_type,

                    'Faculty account provisioned'
                        AS title,

                    'An Administrator created this Faculty account.'
                        AS description,

                    NULL
                        AS previous_value,

                    'Faculty'
                        AS new_value,

                    NULL
                        AS reason,

                    account.provisioned_by
                        AS actor_user_id,

                    TRIM(
                        CONCAT_WS(
                            ' ',
                            actor.first_name,
                            NULLIF(
                                actor.middle_name,
                                ''
                            ),
                            actor.last_name,
                            NULLIF(
                                actor.name_suffix,
                                ''
                            )
                        )
                    ) AS actor_name,

                    account.provisioned_at
                        AS occurred_at

                FROM user account

                LEFT JOIN user actor
                    ON actor.user_id =
                       account.provisioned_by

                WHERE account.user_id = ?
                  AND account.provisioned_at
                      IS NOT NULL

                UNION ALL

                /* Registration review decision */
                SELECT
                    'registration_review'
                        AS event_source,

                    'review'
                        AS event_type,

                    CASE
                        WHEN account.status =
                             'Rejected'
                        THEN 'Registration rejected'
                        ELSE 'Registration approved'
                    END AS title,

                    NULL
                        AS description,

                    'Pending'
                        AS previous_value,

                    account.status
                        AS new_value,

                    account.account_review_notes
                        AS reason,

                    account.account_reviewed_by
                        AS actor_user_id,

                    TRIM(
                        CONCAT_WS(
                            ' ',
                            actor.first_name,
                            NULLIF(
                                actor.middle_name,
                                ''
                            ),
                            actor.last_name,
                            NULLIF(
                                actor.name_suffix,
                                ''
                            )
                        )
                    ) AS actor_name,

                    account.account_reviewed_at
                        AS occurred_at

                FROM user account

                LEFT JOIN user actor
                    ON actor.user_id =
                       account.account_reviewed_by

                WHERE account.user_id = ?
                  AND account.account_reviewed_at
                      IS NOT NULL
            ) timeline

            WHERE timeline.occurred_at
                  IS NOT NULL

            ORDER BY
                timeline.occurred_at DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare managed-user activity history: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iiiiiii',
            $userId,
            $userId,
            $userId,
            $userId,
            $userId,
            $userId,
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load managed-user activity history: '
                    . $error
            );
        }

        $history =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $history;
    }

    /* ==========================================
   ACCOUNT REGISTRATION REVIEW
========================================== */

    /** Count the same capped queue rows, including existing Parent-link multiplicity. */
    public function countPendingRegistrations(int $limit = 100): int
    {
        $limit = max(1, min($limit, 200));
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total FROM (
                SELECT 1 FROM user u
                INNER JOIN role r ON r.role_id = u.role_id
                LEFT JOIN parent_student ps ON ps.parent_user_id = u.user_id
                WHERE u.status = 'Pending' AND r.role_prefix IN ('Student', 'Parent')
                LIMIT ?
            ) AS pending_rows
        ");
        if (!$stmt) {
            throw new RuntimeException('Unable to count pending registrations.');
        }
        try {
            $stmt->bind_param('i', $limit);
            if (!$stmt->execute()) {
                throw new RuntimeException('Unable to count pending registrations.');
            }
            return (int) $stmt->get_result()->fetch_assoc()['total'];
        } finally {
            $stmt->close();
        }
    }

    public function getPendingRegistrations(
        int $limit = 100
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    200
                )
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                u.user_id,
                u.studID,
                u.first_name,
u.middle_name,
u.last_name,
u.name_suffix,
u.email,
                u.gender,
                u.age,
                u.birthdate,
                u.created_at,
                u.status,

                r.role_id,
                r.role_prefix,

                d.department_id,
                d.department_name,

                el.education_level_id,
                el.education_level_name,

                ap.academic_program_id,
                ap.program_name,
                ap.program_code,

                gl.grade_level_id,
                gl.grade_level_name,

                s.section_id,
                s.section_name,

                ps.parent_student_id,
                ps.relationship,
                ps.status
                    AS relationship_status,

                child.user_id
                    AS child_user_id,

                child.studID
                    AS child_student_id,

                TRIM(
                    CONCAT(
                        COALESCE(
                            child.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            child.last_name,
                            ''
                        )
                    )
                ) AS child_name

            FROM user u

            INNER JOIN role r
                ON r.role_id =
                   u.role_id

            LEFT JOIN department d
                ON d.department_id =
                   u.department_id

            LEFT JOIN education_level el
                ON el.education_level_id =
                   u.education_level_id

            LEFT JOIN academic_program ap
                ON ap.academic_program_id =
                   u.academic_program_id

            LEFT JOIN grade_level gl
                ON gl.grade_level_id =
                   u.grade_level_id

            LEFT JOIN section s
                ON s.section_id =
                   u.section_id

            LEFT JOIN parent_student ps
                ON ps.parent_user_id =
                   u.user_id

            LEFT JOIN user child
                ON child.user_id =
                   ps.student_user_id

            WHERE u.status =
                    'Pending'

              AND r.role_prefix IN (
                    'Student',
                    'Parent'
              )

            ORDER BY
                u.created_at ASC,
                u.user_id ASC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare pending registration list: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load pending registrations: '
                    . $error
            );
        }

        $registrations =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $registrations;
    }

    public function findRegistrationForReview(
        int $userId
    ): ?array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid registration user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                u.user_id,
                u.studID,
                u.first_name,
u.middle_name,
u.last_name,
u.name_suffix,
u.email,
                u.gender,
                u.age,
                u.birthdate,
                u.profile_photo,
                u.created_at,
                u.updated_at,
                u.status,
                u.approved_by,
                u.approved_at,
                u.account_review_notes,
                u.account_reviewed_at,

                r.role_id,
                r.role_prefix,

                d.department_id,
                d.department_name,

                el.education_level_id,
                el.education_level_name,

                ap.academic_program_id,
                ap.program_name,
                ap.program_code,
                ap.program_type,

                gl.grade_level_id,
                gl.grade_level_name,

                s.section_id,
                s.section_name,

                ps.parent_student_id,
                ps.relationship,
                ps.status
                    AS relationship_status,

                ps.verified_by,
                ps.verified_at,

                child.user_id
                    AS child_user_id,

                child.studID
                    AS child_student_id,

                child.status
                    AS child_account_status,

                TRIM(
                    CONCAT(
                        COALESCE(
                            child.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            child.middle_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            child.last_name,
                            ''
                        )
                    )
                ) AS child_name

            FROM user u

            INNER JOIN role r
                ON r.role_id =
                   u.role_id

            LEFT JOIN department d
                ON d.department_id =
                   u.department_id

            LEFT JOIN education_level el
                ON el.education_level_id =
                   u.education_level_id

            LEFT JOIN academic_program ap
                ON ap.academic_program_id =
                   u.academic_program_id

            LEFT JOIN grade_level gl
                ON gl.grade_level_id =
                   u.grade_level_id

            LEFT JOIN section s
                ON s.section_id =
                   u.section_id

            LEFT JOIN parent_student ps
                ON ps.parent_user_id =
                   u.user_id

            LEFT JOIN user child
                ON child.user_id =
                   ps.student_user_id

            WHERE u.user_id = ?

              AND r.role_prefix IN (
                    'Student',
                    'Parent'
              )

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare registration review lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load registration review details: '
                    . $error
            );
        }

        $registration =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $registration
            ?: null;
    }

    public function approveRegistration(
        int $userId,
        int $adminId,
        ?string $reviewNotes = null
    ): bool {
        if (
            $userId <= 0 ||
            $adminId <= 0 ||
            $userId === $adminId
        ) {
            throw new InvalidArgumentException(
                'Invalid applicant or Administrator ID.'
            );
        }

        $reviewNotes =
            trim(
                (string) $reviewNotes
            );

        if ($reviewNotes === '') {
            $reviewNotes = null;
        } elseif (
            mb_strlen(
                $reviewNotes
            ) > 1000
        ) {
            throw new InvalidArgumentException(
                'Account review notes must not exceed 1000 characters.'
            );
        }

        $this->conn->begin_transaction();

        try {
            /*
         * Verify that the reviewer is currently
         * an active Administrator.
         */
            $adminStmt =
                $this->conn->prepare("
                SELECT
                    u.user_id

                FROM user u

                INNER JOIN role r
                    ON r.role_id =
                       u.role_id

                WHERE u.user_id = ?
                  AND u.status =
                        'Active'
                  AND r.role_prefix =
                        'Admin'

                LIMIT 1

                FOR UPDATE
            ");

            if (!$adminStmt) {
                throw new RuntimeException(
                    'Unable to prepare Administrator verification: '
                        . $this->conn->error
                );
            }

            $adminStmt->bind_param(
                'i',
                $adminId
            );

            if (!$adminStmt->execute()) {
                $error =
                    $adminStmt->error;

                $adminStmt->close();

                throw new RuntimeException(
                    'Unable to verify the Administrator: '
                        . $error
                );
            }

            $admin =
                $adminStmt
                ->get_result()
                ->fetch_assoc();

            $adminStmt->close();

            if (!$admin) {
                throw new RuntimeException(
                    'Only an active Administrator may approve registrations.'
                );
            }

            /*
         * Lock the applicant and verify that only
         * public Student or Parent registrations
         * can enter this approval workflow.
         */
            $applicantStmt =
                $this->conn->prepare("
                SELECT
                    u.user_id,
                    u.status,
                    r.role_prefix

                FROM user u

                INNER JOIN role r
                    ON r.role_id =
                       u.role_id

                WHERE u.user_id = ?
                  AND r.role_prefix IN (
                        'Student',
                        'Parent'
                  )

                LIMIT 1

                FOR UPDATE
            ");

            if (!$applicantStmt) {
                throw new RuntimeException(
                    'Unable to prepare applicant verification: '
                        . $this->conn->error
                );
            }

            $applicantStmt->bind_param(
                'i',
                $userId
            );

            if (!$applicantStmt->execute()) {
                $error =
                    $applicantStmt->error;

                $applicantStmt->close();

                throw new RuntimeException(
                    'Unable to verify the applicant: '
                        . $error
                );
            }

            $applicant =
                $applicantStmt
                ->get_result()
                ->fetch_assoc();

            $applicantStmt->close();

            if (!$applicant) {
                throw new RuntimeException(
                    'The registration could not be found.'
                );
            }

            if (
                (
                    $applicant['status']
                    ?? ''
                ) !== 'Pending'
            ) {
                throw new RuntimeException(
                    'The registration is no longer pending.'
                );
            }

            $isParent =
                (
                    $applicant['role_prefix']
                    ?? ''
                ) === 'Parent';

            /*
         * A Parent account cannot be activated unless
         * its child record is verified or its pending relationship
         * points to an active Student account.
         */
            $childRecordApproved = $isParent && (new ParentChildRecord($this->conn))->approve($userId, $adminId, $reviewNotes);
            if ($isParent && !$childRecordApproved) {
                $relationshipStmt =
                    $this->conn->prepare("
                    SELECT
                        ps.parent_student_id

                    FROM parent_student ps

                    INNER JOIN user student
                        ON student.user_id =
                           ps.student_user_id

                    WHERE ps.parent_user_id = ?
                      AND ps.status =
                            'Pending'
                      AND student.status =
                            'Active'

                    LIMIT 1

                    FOR UPDATE
                ");

                if (!$relationshipStmt) {
                    throw new RuntimeException(
                        'Unable to prepare Parent relationship verification: '
                            . $this->conn->error
                    );
                }

                $relationshipStmt->bind_param(
                    'i',
                    $userId
                );

                if (!$relationshipStmt->execute()) {
                    $error =
                        $relationshipStmt->error;

                    $relationshipStmt->close();

                    throw new RuntimeException(
                        'Unable to verify the Parent relationship: '
                            . $error
                    );
                }

                $relationship =
                    $relationshipStmt
                    ->get_result()
                    ->fetch_assoc();

                $relationshipStmt->close();

                if (!$relationship) {
                    throw new RuntimeException(
                        'The Parent relationship is missing, no longer pending, or linked to an inactive Student.'
                    );
                }
            }

            $updateStmt =
                $this->conn->prepare("
                UPDATE user

                SET
                    status =
                        'Active',

                    approved_by = ?,
                    approved_at = NOW(),

                    account_review_notes = ?,
                    account_reviewed_at = NOW(),
                    account_reviewed_by = ?,

                    failed_attempts = 0,
                    lock_until = NULL,
                    updated_at = NOW()

                WHERE user_id = ?
                  AND status =
                        'Pending'
            ");

            if (!$updateStmt) {
                throw new RuntimeException(
                    'Unable to prepare registration approval: '
                        . $this->conn->error
                );
            }

            $updateStmt->bind_param(
                'isii',
                $adminId,
                $reviewNotes,
                $adminId,
                $userId
            );

            if (!$updateStmt->execute()) {
                $error =
                    $updateStmt->error;

                $updateStmt->close();

                throw new RuntimeException(
                    'Unable to approve the registration: '
                        . $error
                );
            }

            $updated =
                $updateStmt->affected_rows === 1;

            $updateStmt->close();

            if (!$updated) {
                throw new RuntimeException(
                    'The registration approval did not update an account.'
                );
            }

            if ($isParent && !$childRecordApproved) {
                $parentStmt =
                    $this->conn->prepare("
                    UPDATE parent_student

                    SET
                        status =
                            'Verified',

                        verified_by = ?,
                        verified_at = NOW(),
                        updated_at = NOW()

                    WHERE parent_user_id = ?
                      AND status =
                            'Pending'
                ");

                if (!$parentStmt) {
                    throw new RuntimeException(
                        'Unable to prepare Parent relationship approval: '
                            . $this->conn->error
                    );
                }

                $parentStmt->bind_param(
                    'ii',
                    $adminId,
                    $userId
                );

                if (!$parentStmt->execute()) {
                    $error =
                        $parentStmt->error;

                    $parentStmt->close();

                    throw new RuntimeException(
                        'Unable to approve the Parent relationship: '
                            . $error
                    );
                }

                $relationshipUpdated =
                    $parentStmt->affected_rows === 1;

                $parentStmt->close();

                if (!$relationshipUpdated) {
                    throw new RuntimeException(
                        'The Parent relationship was not approved.'
                    );
                }
            }

            $this->conn->commit();

            return true;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }


    public function rejectRegistration(
        int $userId,
        int $adminId,
        string $reviewNotes
    ): bool {
        if (
            $userId <= 0 ||
            $adminId <= 0 ||
            $userId === $adminId
        ) {
            throw new InvalidArgumentException(
                'Invalid applicant or Administrator ID.'
            );
        }

        $reviewNotes =
            trim(
                $reviewNotes
            );

        if ($reviewNotes === '') {
            throw new InvalidArgumentException(
                'A rejection reason is required.'
            );
        }

        if (
            mb_strlen(
                $reviewNotes
            ) > 1000
        ) {
            throw new InvalidArgumentException(
                'The rejection reason must not exceed 1000 characters.'
            );
        }

        $this->conn->begin_transaction();

        try {
            /*
         * Only an active Administrator may reject
         * a public account registration.
         */
            $adminStmt =
                $this->conn->prepare("
                SELECT
                    u.user_id

                FROM user u

                INNER JOIN role r
                    ON r.role_id =
                       u.role_id

                WHERE u.user_id = ?
                  AND u.status =
                        'Active'
                  AND r.role_prefix =
                        'Admin'

                LIMIT 1

                FOR UPDATE
            ");

            if (!$adminStmt) {
                throw new RuntimeException(
                    'Unable to prepare Administrator verification: '
                        . $this->conn->error
                );
            }

            $adminStmt->bind_param(
                'i',
                $adminId
            );

            if (!$adminStmt->execute()) {
                $error =
                    $adminStmt->error;

                $adminStmt->close();

                throw new RuntimeException(
                    'Unable to verify the Administrator: '
                        . $error
                );
            }

            $admin =
                $adminStmt
                ->get_result()
                ->fetch_assoc();

            $adminStmt->close();

            if (!$admin) {
                throw new RuntimeException(
                    'Only an active Administrator may reject registrations.'
                );
            }

            /*
         * Lock the applicant and restrict this workflow
         * to Pending Student and Parent registrations.
         */
            $applicantStmt =
                $this->conn->prepare("
                SELECT
                    u.user_id,
                    u.status,
                    r.role_prefix

                FROM user u

                INNER JOIN role r
                    ON r.role_id =
                       u.role_id

                WHERE u.user_id = ?
                  AND r.role_prefix IN (
                        'Student',
                        'Parent'
                  )

                LIMIT 1

                FOR UPDATE
            ");

            if (!$applicantStmt) {
                throw new RuntimeException(
                    'Unable to prepare applicant verification: '
                        . $this->conn->error
                );
            }

            $applicantStmt->bind_param(
                'i',
                $userId
            );

            if (!$applicantStmt->execute()) {
                $error =
                    $applicantStmt->error;

                $applicantStmt->close();

                throw new RuntimeException(
                    'Unable to verify the applicant: '
                        . $error
                );
            }

            $applicant =
                $applicantStmt
                ->get_result()
                ->fetch_assoc();

            $applicantStmt->close();

            if (!$applicant) {
                throw new RuntimeException(
                    'The registration could not be found.'
                );
            }

            if (
                (
                    $applicant['status']
                    ?? ''
                ) !== 'Pending'
            ) {
                throw new RuntimeException(
                    'The registration is no longer pending.'
                );
            }

            $isParent =
                (
                    $applicant['role_prefix']
                    ?? ''
                ) === 'Parent';

            $updateStmt =
                $this->conn->prepare("
                UPDATE user

                SET
                    status =
                        'Rejected',

                    approved_by = NULL,
                    approved_at = NULL,

                    account_review_notes = ?,
                    account_reviewed_at = NOW(),
                    account_reviewed_by = ?,

                    failed_attempts = 0,
                    lock_until = NULL,
                    updated_at = NOW()

                WHERE user_id = ?
                  AND status =
                        'Pending'
            ");

            if (!$updateStmt) {
                throw new RuntimeException(
                    'Unable to prepare registration rejection: '
                        . $this->conn->error
                );
            }

            $updateStmt->bind_param(
                'sii',
                $reviewNotes,
                $adminId,
                $userId
            );

            if (!$updateStmt->execute()) {
                $error =
                    $updateStmt->error;

                $updateStmt->close();

                throw new RuntimeException(
                    'Unable to reject the registration: '
                        . $error
                );
            }

            $updated =
                $updateStmt->affected_rows === 1;

            $updateStmt->close();

            if (!$updated) {
                throw new RuntimeException(
                    'The registration rejection did not update an account.'
                );
            }

            /*
         * Parent relationship rejection occurs within
         * the same transaction as account rejection.
         */
            $childRecordRejected = $isParent && (new ParentChildRecord($this->conn))->reject($userId, $adminId, $reviewNotes);
            if ($isParent && !$childRecordRejected) {
                $parentStmt =
                    $this->conn->prepare("
                    UPDATE parent_student

                    SET
                        status =
                            'Rejected',

                        verified_by = ?,
                        verified_at = NOW(),
                        updated_at = NOW()

                    WHERE parent_user_id = ?
                      AND status =
                            'Pending'
                ");

                if (!$parentStmt) {
                    throw new RuntimeException(
                        'Unable to prepare Parent relationship rejection: '
                            . $this->conn->error
                    );
                }

                $parentStmt->bind_param(
                    'ii',
                    $adminId,
                    $userId
                );

                if (!$parentStmt->execute()) {
                    $error =
                        $parentStmt->error;

                    $parentStmt->close();

                    throw new RuntimeException(
                        'Unable to reject the Parent relationship: '
                            . $error
                    );
                }

                $relationshipUpdated =
                    $parentStmt->affected_rows === 1;

                $parentStmt->close();

                if (!$relationshipUpdated) {
                    throw new RuntimeException(
                        'The Parent relationship was not rejected.'
                    );
                }
            }

            $this->conn->commit();

            return true;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
   PROVISION FACULTY ACCOUNT
========================================== */

    public function createProvisionedFaculty(
        array $data
    ): int {
        $stmt =
            $this->conn->prepare("
            INSERT INTO user
            (
                studID,
                first_name,
                middle_name,
                last_name,
                name_suffix,
                email,
                password,
                must_change_password,
                gender,
                age,
                birthdate,
                status,
                role_id,
                department_id,
                education_level_id,
                academic_program_id,
                grade_level_id,
                section_id,
                provisioned_by,
                provisioned_at,
                created_at
            )

            VALUES
            (
                NULL,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                1,
                ?,
                ?,
                ?,
                'Active',
                ?,
                ?,
                ?,
                ?,
                NULL,
                NULL,
                ?,
                NOW(),
                NOW()
            )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Faculty account provisioning: '
                    . $this->conn->error
            );
        }

        $middleName =
            !empty($data['middle_name'])
            ? $data['middle_name']
            : null;

        $nameSuffix =
            !empty($data['name_suffix'])
            ? $data['name_suffix']
            : null;

        $stmt->bind_param(
            'sssssssisiiiii',
            $data['first_name'],
            $middleName,
            $data['last_name'],
            $nameSuffix,
            $data['email'],
            $data['password'],
            $data['gender'],
            $data['age'],
            $data['birthdate'],
            $data['role_id'],
            $data['department_id'],
            $data['education_level_id'],
            $data['academic_program_id'],
            $data['provisioned_by']
        );

        try {
            if (!$stmt->execute()) {
                $error =
                    $stmt->error;

                $stmt->close();

                throw new RuntimeException(
                    'Unable to provision the Faculty account: '
                        . $error
                );
            }
        } catch (
            mysqli_sql_exception $exception
        ) {
            $stmt->close();

            if (
                $exception->getCode() === 1062
            ) {
                throw new RuntimeException(
                    'That Faculty email address is already registered.'
                );
            }

            throw new RuntimeException(
                'Unable to provision the Faculty account: '
                    . $exception->getMessage()
            );
        }

        $facultyUserId =
            (int) $this->conn->insert_id;

        $stmt->close();

        if ($facultyUserId <= 0) {
            throw new RuntimeException(
                'The provisioned Faculty account ID was unavailable.'
            );
        }

        return $facultyUserId;
    }



    public function updateFacultyPersonalDetails(int $userId, array $data): bool
    {
        $stmt = $this->conn->prepare("UPDATE user u INNER JOIN role r ON r.role_id=u.role_id
            SET u.middle_name=?, u.name_suffix=?, u.gender=?, u.age=?, u.birthdate=?, u.updated_at=NOW()
            WHERE u.user_id=? AND u.status='Active' AND r.role_prefix='Faculty'
              AND u.must_change_password=0");
        try {
            $stmt->bind_param('sssisi', $data['middle_name'], $data['name_suffix'],
                $data['gender'], $data['age'], $data['birthdate'], $userId);
            return $stmt->execute();
        } finally { $stmt->close(); }
    }

    public function getActiveLegalDocumentVersions(): array
    {
        $result = $this->conn->query("
        SELECT
            legal_document_version_id,
            document_type,
            version,
                        title,
            content,
            effective_at
        FROM legal_document_version
        WHERE status = 'Active'
          AND effective_at <= NOW()
        ORDER BY
            document_type ASC,
            effective_at DESC,
            legal_document_version_id DESC
    ");

        $documents = [];

        while ($row = $result->fetch_assoc()) {
            $documentType =
                (string) $row['document_type'];

            if (
                !isset(
                    $documents[$documentType]
                )
            ) {
                $documents[$documentType] = $row;
            }
        }

        return $documents;
    }


    public function getPendingLegalDocumentVersions(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid legal-consent user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                current_version
                    .legal_document_version_id,

                current_version
                    .document_type,

                current_version
                    .version,

                               current_version
                    .title,

                current_version
                    .content,

                current_version
                    .effective_at

            FROM legal_document_version
                current_version

            WHERE current_version.status =
                    'Active'

              AND current_version.effective_at <=
                    NOW()

              /*
               * Keep only the newest effective
               * Active version for each document.
               */
              AND NOT EXISTS (
                    SELECT 1

                    FROM legal_document_version
                        newer_version

                    WHERE newer_version.document_type =
                            current_version.document_type

                      AND newer_version.status =
                            'Active'

                      AND newer_version.effective_at <=
                            NOW()

                      AND (
                            newer_version.effective_at >
                                current_version.effective_at

                            OR (
                                newer_version.effective_at =
                                    current_version.effective_at

                                AND newer_version
                                    .legal_document_version_id >
                                    current_version
                                        .legal_document_version_id
                            )
                          )
                  )

              /*
               * A version remains pending until this
               * specific user has accepted it.
               */
              AND NOT EXISTS (
                    SELECT 1

                    FROM user_legal_acceptance
                        acceptance

                    WHERE acceptance.user_id = ?

                      AND acceptance
                            .legal_document_version_id =
                          current_version
                            .legal_document_version_id
                  )

            ORDER BY
                current_version.document_type ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare pending legal-document lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load pending legal documents: '
                    . $error
            );
        }

        $rows =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        $documents = [];

        foreach ($rows as $row) {
            $documentType =
                (string) (
                    $row['document_type']
                    ?? ''
                );

            if ($documentType !== '') {
                $documents[$documentType] =
                    $row;
            }
        }

        return $documents;
    }

    public function createLegalAcceptance(
        int $userId,
        int $legalDocumentVersionId,
        string $acceptanceSource = 'Registration',
        ?string $userAgent = null
    ): void {
        $stmt = $this->conn->prepare("
        INSERT INTO user_legal_acceptance
        (
            user_id,
            legal_document_version_id,
            accepted_at,
            acceptance_source,
            user_agent,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            NOW(),
            ?,
            ?,
            NOW()
        )
    ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare legal acceptance record: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iiss',
            $userId,
            $legalDocumentVersionId,
            $acceptanceSource,
            $userAgent
        );

        $stmt->execute();
    }

    /* ==========================================
       USER CREATION
    ========================================== */

    public function create(
        array $data
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO user
            (
                studID,
                first_name,
                            middle_name,
            last_name,
            name_suffix,
            email,
                password,
                gender,
                age,
                birthdate,
                status,
                role_id,
                department_id,
                education_level_id,
                academic_program_id,
                grade_level_id,
                section_id,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'Pending',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare account registration: ' .
                    $this->conn->error
            );
        }

        $studentId =
            $data['studID'] ?? null;

        $middleName =
            $data['middle_name'] ?? null;

        $nameSuffix =
            $data['name_suffix']
            ?? null;

        $departmentId =
            $data['department_id'] ?? null;

        $educationLevelId =
            $data['education_level_id'] ?? null;

        $academicProgramId =
            $data['academic_program_id'] ?? null;

        $gradeLevelId =
            $data['grade_level_id'] ?? null;

        $sectionId =
            $data['section_id'] ?? null;

        /*
 * 16 parameters:
 *
 * 8 strings:
 * Student ID, first name, middle name,
 * last name, suffix, email, password,
 * and gender
 *
 * 1 integer:
 * age
 *
 * 1 string:
 * birthdate
 *
 * 6 integers:
 * role, department, education level,
 * academic program, grade, and section
 */
        $stmt->bind_param(
            'ssssssssisiiiiii',
            $studentId,
            $data['first_name'],
            $middleName,
            $data['last_name'],
            $nameSuffix,
            $data['email'],
            $data['password'],
            $data['gender'],
            $data['age'],
            $data['birthdate'],
            $data['role_id'],
            $departmentId,
            $educationLevelId,
            $academicProgramId,
            $gradeLevelId,
            $sectionId
        );

        try {
            $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                throw new Exception(
                    'The Student ID or email is already registered.'
                );
            }

            throw new Exception(
                'Unable to create the account: ' .
                    $exception->getMessage()
            );
        }

        return (int) $this->conn->insert_id;
    }

    /* ==========================================
       PARENT-STUDENT LINK
    ========================================== */

    public function createParentStudentLink(
        int $parentUserId,
        int $studentUserId,
        string $relationship
    ): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO parent_student
            (
                parent_user_id,
                student_user_id,
                relationship,
                status,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'Pending',
                NOW()
            )
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare Parent–Student link: ' .
                    $this->conn->error
            );
        }

        $stmt->bind_param(
            'iis',
            $parentUserId,
            $studentUserId,
            $relationship
        );

        try {
            return $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                throw new Exception(
                    'This Parent and Student link already exists.'
                );
            }

            throw new Exception(
                'Unable to create the Parent–Student link: ' .
                    $exception->getMessage()
            );
        }
    }

    /* ==========================================
   ACTIVE ADMINISTRATOR COUNT
========================================== */

    public function countActiveAdministrators(): int
    {
        $stmt =
            $this->conn->prepare("
            SELECT
                COUNT(*) AS total

            FROM user u

            INNER JOIN role r
                ON r.role_id =
                   u.role_id

            WHERE u.status = 'Active'
              AND r.role_prefix = 'Admin'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare active Administrator count: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to count active Administrators: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return (int) (
            $row['total']
            ?? 0
        );
    }

    /* ==========================================
   MANAGED ACCOUNT STATUS CHANGE
========================================== */

    public function changeManagedAccountStatus(
        int $userId,
        string $newStatus,
        string $reason,
        int $changedBy
    ): bool {
        if (
            $userId <= 0 ||
            $changedBy <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid managed user or Administrator ID.'
            );
        }

        if (
            !in_array(
                $newStatus,
                [
                    'Active',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid managed account status.'
            );
        }

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new InvalidArgumentException(
                'An account-status reason is required.'
            );
        }

        $this->conn->begin_transaction();

        try {
            /*
         * Lock the target account so concurrent status
         * actions cannot use an outdated status.
         */
            $lookupStmt =
                $this->conn->prepare("
                SELECT
                    status

                FROM user

                WHERE user_id = ?

                LIMIT 1

                FOR UPDATE
            ");

            if (!$lookupStmt) {
                throw new RuntimeException(
                    'Unable to prepare managed account lookup: '
                        . $this->conn->error
                );
            }

            $lookupStmt->bind_param(
                'i',
                $userId
            );

            if (!$lookupStmt->execute()) {
                $error =
                    $lookupStmt->error;

                $lookupStmt->close();

                throw new RuntimeException(
                    'Unable to lock the managed account: '
                        . $error
                );
            }

            $account =
                $lookupStmt
                ->get_result()
                ->fetch_assoc();

            $lookupStmt->close();

            if (!$account) {
                throw new RuntimeException(
                    'The managed user account could not be found.'
                );
            }

            $previousStatus =
                trim(
                    (string) (
                        $account['status']
                        ?? ''
                    )
                );

            if (
                !in_array(
                    $previousStatus,
                    [
                        'Active',
                        'Inactive'
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Only Active or Inactive accounts may be changed through User Management.'
                );
            }

            if ($previousStatus === $newStatus) {
                throw new RuntimeException(
                    'The account already has the selected status.'
                );
            }

            $updateStmt =
                $this->conn->prepare("
                UPDATE user

                SET
                    status = ?,

                    failed_attempts =
                        CASE
                            WHEN ? = 'Active'
                            THEN 0
                            ELSE failed_attempts
                        END,

                    lock_until =
                        CASE
                            WHEN ? = 'Active'
                            THEN NULL
                            ELSE lock_until
                        END,

                    updated_at = NOW()

                WHERE user_id = ?
            ");

            if (!$updateStmt) {
                throw new RuntimeException(
                    'Unable to prepare account-status update: '
                        . $this->conn->error
                );
            }

            $updateStmt->bind_param(
                'sssi',
                $newStatus,
                $newStatus,
                $newStatus,
                $userId
            );

            if (!$updateStmt->execute()) {
                $error =
                    $updateStmt->error;

                $updateStmt->close();

                throw new RuntimeException(
                    'Unable to update the account status: '
                        . $error
                );
            }

            if (
                $updateStmt->affected_rows !== 1
            ) {
                $updateStmt->close();

                throw new RuntimeException(
                    'The account-status update did not affect one account.'
                );
            }

            $updateStmt->close();

            $historyStmt =
                $this->conn->prepare("
                INSERT INTO user_status_history
                (
                    user_id,
                    previous_status,
                    new_status,
                    reason,
                    changed_by,
                    created_at
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");

            if (!$historyStmt) {
                throw new RuntimeException(
                    'Unable to prepare account-status history: '
                        . $this->conn->error
                );
            }

            $historyStmt->bind_param(
                'isssi',
                $userId,
                $previousStatus,
                $newStatus,
                $reason,
                $changedBy
            );

            if (!$historyStmt->execute()) {
                $error =
                    $historyStmt->error;

                $historyStmt->close();

                throw new RuntimeException(
                    'Unable to record the account-status history: '
                        . $error
                );
            }

            $historyStmt->close();

            $this->conn->commit();

            return true;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
   MANAGED STAFF ROLE CHANGE
========================================== */

    public function changeManagedStaffRole(
        int $userId,
        int $newRoleId,
        string $reason,
        int $changedBy
    ): bool {
        if (
            $userId <= 0 ||
            $newRoleId <= 0 ||
            $changedBy <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid user, role, or Administrator ID.'
            );
        }

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new InvalidArgumentException(
                'A role-change reason is required.'
            );
        }

        $this->conn->begin_transaction();

        try {
            $targetStmt =
                $this->conn->prepare("
                SELECT
                    u.role_id,
                    u.status,
                    r.role_prefix

                FROM user u

                INNER JOIN role r
                    ON r.role_id =
                       u.role_id

                WHERE u.user_id = ?

                LIMIT 1

                FOR UPDATE
            ");

            if (!$targetStmt) {
                throw new RuntimeException(
                    'Unable to prepare managed role lookup: '
                        . $this->conn->error
                );
            }

            $targetStmt->bind_param(
                'i',
                $userId
            );

            if (!$targetStmt->execute()) {
                $error =
                    $targetStmt->error;

                $targetStmt->close();

                throw new RuntimeException(
                    'Unable to lock the managed user role: '
                        . $error
                );
            }

            $targetUser =
                $targetStmt
                ->get_result()
                ->fetch_assoc();

            $targetStmt->close();

            if (!$targetUser) {
                throw new RuntimeException(
                    'The managed user account could not be found.'
                );
            }

            $previousRoleId =
                (int) (
                    $targetUser['role_id']
                    ?? 0
                );

            $previousRole =
                trim(
                    (string) (
                        $targetUser['role_prefix']
                        ?? ''
                    )
                );

            $targetStatus =
                trim(
                    (string) (
                        $targetUser['status']
                        ?? ''
                    )
                );

            $newRoleStmt =
                $this->conn->prepare("
                SELECT
                    role_id,
                    role_prefix

                FROM role

                WHERE role_id = ?

                LIMIT 1
            ");

            if (!$newRoleStmt) {
                throw new RuntimeException(
                    'Unable to prepare new role lookup: '
                        . $this->conn->error
                );
            }

            $newRoleStmt->bind_param(
                'i',
                $newRoleId
            );

            if (!$newRoleStmt->execute()) {
                $error =
                    $newRoleStmt->error;

                $newRoleStmt->close();

                throw new RuntimeException(
                    'Unable to load the selected role: '
                        . $error
                );
            }

            $newRole =
                $newRoleStmt
                ->get_result()
                ->fetch_assoc();

            $newRoleStmt->close();

            if (!$newRole) {
                throw new RuntimeException(
                    'The selected system role could not be found.'
                );
            }

            $newRolePrefix =
                trim(
                    (string) (
                        $newRole['role_prefix']
                        ?? ''
                    )
                );

            if ($previousRoleId === $newRoleId) {
                throw new RuntimeException(
                    'The account already has the selected role.'
                );
            }

            /*
         * A logged-in Administrator cannot remove
         * their own Administrator permissions.
         */
            if (
                $userId === $changedBy &&
                $previousRole === 'Admin' &&
                $newRolePrefix !== 'Admin'
            ) {
                throw new RuntimeException(
                    'You cannot remove your own Administrator role.'
                );
            }

            /*
         * Lock every active Administrator row before
         * checking the last-Administrator invariant.
         */
            if (
                $previousRole === 'Admin' &&
                $newRolePrefix !== 'Admin' &&
                $targetStatus === 'Active'
            ) {
                $adminLockResult =
                    $this->conn->query("
                    SELECT
                        u.user_id

                    FROM user u

                    INNER JOIN role r
                        ON r.role_id =
                           u.role_id

                    WHERE u.status = 'Active'
                      AND r.role_prefix = 'Admin'

                    FOR UPDATE
                ");

                if (!$adminLockResult) {
                    throw new RuntimeException(
                        'Unable to verify active Administrators: '
                            . $this->conn->error
                    );
                }

                if (
                    $adminLockResult->num_rows <= 1
                ) {
                    throw new RuntimeException(
                        'The last active Administrator role cannot be removed.'
                    );
                }
            }

            $updateStmt =
                $this->conn->prepare("
                UPDATE user

                SET
                    role_id = ?,
                    updated_at = NOW()

                WHERE user_id = ?
            ");

            if (!$updateStmt) {
                throw new RuntimeException(
                    'Unable to prepare staff-role update: '
                        . $this->conn->error
                );
            }

            $updateStmt->bind_param(
                'ii',
                $newRoleId,
                $userId
            );

            if (!$updateStmt->execute()) {
                $error =
                    $updateStmt->error;

                $updateStmt->close();

                throw new RuntimeException(
                    'Unable to update the staff role: '
                        . $error
                );
            }

            if (
                $updateStmt->affected_rows !== 1
            ) {
                $updateStmt->close();

                throw new RuntimeException(
                    'The staff-role update did not affect one account.'
                );
            }

            $updateStmt->close();

            $historyStmt =
                $this->conn->prepare("
                INSERT INTO user_role_history
                (
                    user_id,
                    previous_role_id,
                    new_role_id,
                    reason,
                    changed_by,
                    created_at
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");

            if (!$historyStmt) {
                throw new RuntimeException(
                    'Unable to prepare role-change history: '
                        . $this->conn->error
                );
            }

            $historyStmt->bind_param(
                'iiisi',
                $userId,
                $previousRoleId,
                $newRoleId,
                $reason,
                $changedBy
            );

            if (!$historyStmt->execute()) {
                $error =
                    $historyStmt->error;

                $historyStmt->close();

                throw new RuntimeException(
                    'Unable to record role-change history: '
                        . $error
                );
            }

            $historyStmt->close();

            $this->conn->commit();

            return true;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       TRANSACTIONS
    ========================================== */

    public function beginTransaction(): void
    {
        $this->conn->begin_transaction();
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollback(): void
    {
        $this->conn->rollback();
    }

    /* ==========================================
       LOGIN SECURITY
    ========================================== */


    /* ==========================================
   COMPLETE REQUIRED PASSWORD CHANGE
========================================== */

    public function completeRequiredPasswordChange(
        int $userId,
        string $passwordHash
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid password-change user ID.'
            );
        }

        if (
            trim(
                $passwordHash
            ) === ''
        ) {
            throw new InvalidArgumentException(
                'A secured password hash is required.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE user

            SET
                password = ?,
                must_change_password = 0,
                password_changed_at = NOW(),
                failed_attempts = 0,
                lock_until = NULL,
                updated_at = NOW()

            WHERE user_id = ?
              AND status = 'Active'
              AND must_change_password = 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare required password change: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'si',
            $passwordHash,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update the required password: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows === 1;

        $stmt->close();

        if (!$updated) {
            throw new RuntimeException(
                'The password-change requirement is no longer active or the account is unavailable.'
            );
        }

        return true;
    }

    public function incrementFailedAttempts(
        int $userId
    ): bool {
        $stmt = $this->conn->prepare("
            UPDATE user

            SET failed_attempts =
                failed_attempts + 1

            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to update login attempts.'
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        return $stmt->execute();
    }

    public function resetFailedAttempts(
        int $userId
    ): bool {
        $stmt = $this->conn->prepare("
            UPDATE user

            SET
                failed_attempts = 0,
                lock_until = NULL

            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to reset login attempts.'
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        return $stmt->execute();
    }

    public function lockAccount(
        int $userId,
        int $minutes = 15
    ): bool {
        $stmt = $this->conn->prepare("
            UPDATE user

            SET lock_until =
                DATE_ADD(
                    NOW(),
                    INTERVAL ? MINUTE
                )

            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to lock the account.'
            );
        }

        $stmt->bind_param(
            'ii',
            $minutes,
            $userId
        );

        return $stmt->execute();
    }

    /* ==========================================
       UPDATE PERSONAL PROFILE PHOTO
    ========================================== */

    public function updateProfilePhoto(
        int $userId,
        ?string $profilePhoto
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid profile photo user ID.'
            );
        }

        if ($profilePhoto !== null) {
            $profilePhoto =
                trim($profilePhoto);

            if ($profilePhoto === '') {
                $profilePhoto = null;
            } elseif (
                mb_strlen(
                    $profilePhoto
                ) > 255
            ) {
                throw new InvalidArgumentException(
                    'The profile photo path is too long.'
                );
            }
        }

        $stmt =
            $this->conn->prepare("
                UPDATE user

                SET
                    profile_photo = ?,
                    updated_at = NOW()

                WHERE user_id = ?
                  AND status = 'Active'
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare profile photo update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'si',
            $profilePhoto,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update the profile photo: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    public function updateLastLogin(
        int $userId
    ): bool {
        $stmt = $this->conn->prepare("
            UPDATE user

            SET last_login = NOW()

            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to update the last login.'
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        return $stmt->execute();
    }
}
