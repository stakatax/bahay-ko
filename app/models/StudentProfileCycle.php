<?php

require_once __DIR__
    . '/BaseModel.php';

class StudentProfileCycle extends BaseModel
{
    /** Committed assignments are the durable source for missing reminders. */
    public function getMissingAssignmentNotifications(int $limit = 100, ?int $cycleId = null): array
    {
        $limit = max(1, min(500, $limit));
        $id = $cycleId ?? 0;
        $stmt = $this->conn->prepare("
            SELECT u.user_id, c.student_profile_cycle_id, c.cycle_name, c.opens_at, c.due_at
            FROM student_profile_cycle_assignment a
            INNER JOIN student_profile p ON p.student_profile_id = a.student_profile_id
            INNER JOIN user u ON u.user_id = p.user_id
            INNER JOIN role r ON r.role_id = u.role_id
            INNER JOIN student_profile_cycle c ON c.student_profile_cycle_id = a.student_profile_cycle_id
            WHERE c.status = 'Active' AND c.is_default = 0
              AND a.assignment_status IN ('Assigned', 'InProgress')
              AND u.status = 'Active' AND r.role_prefix = 'Student'
              AND (? = 0 OR c.student_profile_cycle_id = ?)
              AND NOT EXISTS (
                  SELECT 1 FROM notification n WHERE n.user_id = u.user_id
                    AND n.deduplication_key = CONCAT('student-profile-cycle-assigned:cycle:', c.student_profile_cycle_id)
              )
            ORDER BY a.student_profile_cycle_assignment_id ASC LIMIT ?
        ");
        if (!$stmt) throw new RuntimeException('Unable to prepare profile assignment reminders.');
        $stmt->bind_param('iii', $id, $id, $limit);
        if (!$stmt->execute()) throw new RuntimeException('Unable to load profile assignment reminders.');
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /* ==========================================
       CYCLE LOOKUP
    ========================================== */

    public function findCycle(
        int $cycleId
    ): ?array {
        if ($cycleId <= 0) {
            return null;
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    cycle.student_profile_cycle_id,
                    cycle.cycle_name,
                    cycle.academic_year,
                    cycle.cycle_type,
                    cycle.academic_term,
                    cycle.survey_version,
                    cycle.status,
                    cycle.is_default,
                    cycle.opens_at,
                    cycle.due_at,
                    cycle.created_by,
                    cycle.activated_by,
                    cycle.closed_by,
                    cycle.created_at,
                    cycle.activated_at,
                    cycle.closed_at,
                    cycle.updated_at,

                    version.version_name,
                    version.status AS version_status,

                    (
                        SELECT COUNT(*)

                        FROM student_profile_cycle_scope
                            scope

                        WHERE scope.student_profile_cycle_id =
                              cycle.student_profile_cycle_id
                    ) AS scope_count,

                    (
                        SELECT COUNT(*)

                        FROM student_profile_cycle_assignment
                            assignment

                        WHERE assignment.student_profile_cycle_id =
                              cycle.student_profile_cycle_id
                    ) AS assignment_count

                FROM student_profile_cycle cycle

                INNER JOIN student_profile_survey_version
                    version

                    ON version.survey_version =
                       cycle.survey_version

                WHERE cycle.student_profile_cycle_id = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the Student profile cycle lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $cycleId
        );

        $stmt->execute();

        $cycle =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$cycle) {
            return null;
        }

        $cycle['student_profile_cycle_id'] =
            (int) $cycle['student_profile_cycle_id'];

        $cycle['survey_version'] =
            (int) $cycle['survey_version'];

        $cycle['is_default'] =
            !empty($cycle['is_default']);

        $cycle['scope_count'] =
            (int) ($cycle['scope_count'] ?? 0);

        $cycle['assignment_count'] =
            (int) ($cycle['assignment_count'] ?? 0);

        return $cycle;
    }

    public function getCycleScopes(
        int $cycleId
    ): array {
        if ($cycleId <= 0) {
            return [];
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    scope.student_profile_cycle_scope_id,
                    scope.student_profile_cycle_id,
                    scope.scope_type,
                    scope.department_id,
                    scope.education_level_id,
                    scope.academic_program_id,
                    scope.grade_level_id,
                    scope.section_id,
                    department.department_name,
                    department.department_code,
                    level.education_level_name,
                    program.program_name,
                    program.program_code,
                    grade.grade_level_name,
                    section.section_name

                FROM student_profile_cycle_scope scope

                LEFT JOIN department
                    ON department.department_id =
                       scope.department_id

                LEFT JOIN education_level level
                    ON level.education_level_id =
                       scope.education_level_id

                LEFT JOIN academic_program program
                    ON program.academic_program_id =
                       scope.academic_program_id

                LEFT JOIN grade_level grade
                    ON grade.grade_level_id =
                       scope.grade_level_id

                LEFT JOIN section
                    ON section.section_id =
                       scope.section_id

                WHERE scope.student_profile_cycle_id = ?

                ORDER BY
                    scope.student_profile_cycle_scope_id ASC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student profile cycle scopes: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $cycleId
        );

        $stmt->execute();

        $scopes =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $scopes;
    }

    /* ==========================================
       CREATE DRAFT CYCLE WITH SCOPES
    ========================================== */

    public function createDraftCycle(
        array $cycleData,
        array $scopes,
        int $adminId
    ): array {
        $this->conn->begin_transaction();

        try {
            $cycleStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile_cycle
                    (
                        cycle_name,
                        academic_year,
                        cycle_type,
                        academic_term,
                        survey_version,
                        status,
                        is_default,
                        opens_at,
                        due_at,
                        created_by,
                        created_at
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?,
                        'Draft', 0, ?, ?, ?, NOW()
                    )
                ");

            if (!$cycleStmt) {
                throw new RuntimeException(
                    'Unable to prepare the Student profile cycle: '
                        . $this->conn->error
                );
            }

            $cycleStmt->bind_param(
                'ssssissi',
                $cycleData['cycle_name'],
                $cycleData['academic_year'],
                $cycleData['cycle_type'],
                $cycleData['academic_term'],
                $cycleData['survey_version'],
                $cycleData['opens_at'],
                $cycleData['due_at'],
                $adminId
            );

            $cycleStmt->execute();

            $cycleId =
                (int) $this->conn->insert_id;

            $cycleStmt->close();

            $scopeStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile_cycle_scope
                    (
                        student_profile_cycle_id,
                        scope_type,
                        department_id,
                        education_level_id,
                        academic_program_id,
                        grade_level_id,
                        section_id,
                        created_at
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, NOW()
                    )
                ");

            if (!$scopeStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student profile cycle targeting: '
                        . $this->conn->error
                );
            }

            foreach ($scopes as $scope) {
                $departmentId =
                    $scope['department_id'];

                $educationLevelId =
                    $scope['education_level_id'];

                $academicProgramId =
                    $scope['academic_program_id'];

                $gradeLevelId =
                    $scope['grade_level_id'];

                $sectionId =
                    $scope['section_id'];

                $scopeStmt->bind_param(
                    'isiiiii',
                    $cycleId,
                    $scope['scope_type'],
                    $departmentId,
                    $educationLevelId,
                    $academicProgramId,
                    $gradeLevelId,
                    $sectionId
                );

                $scopeStmt->execute();
            }

            $scopeStmt->close();

            $this->conn->commit();

            $cycle =
                $this->findCycle(
                    $cycleId
                );

            if (!$cycle) {
                throw new RuntimeException(
                    'The created Student profile cycle could not be reloaded.'
                );
            }

            $cycle['scopes'] =
                $this->getCycleScopes(
                    $cycleId
                );

            return $cycle;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       TARGET PREVIEW
    ========================================== */

    public function previewCycleTargets(
        int $cycleId
    ): array {
        if ($cycleId <= 0) {
            return [];
        }

        $stmt =
            $this->conn->prepare("
                SELECT DISTINCT
                    user.user_id,
                    user.studID,
                    user.first_name,
                    user.last_name,
                    user.department_id,
                    department.department_code,
                    user.education_level_id,
                    level.education_level_name,
                    user.academic_program_id,
                    program.program_code,
                    user.grade_level_id,
                    grade.grade_level_name,
                    user.section_id,
                    section.section_name,
                    profile.student_profile_id

                FROM user

                INNER JOIN role
                    ON role.role_id =
                       user.role_id

                LEFT JOIN department
                    ON department.department_id =
                       user.department_id

                LEFT JOIN education_level level
                    ON level.education_level_id =
                       user.education_level_id

                LEFT JOIN academic_program program
                    ON program.academic_program_id =
                       user.academic_program_id

                LEFT JOIN grade_level grade
                    ON grade.grade_level_id =
                       user.grade_level_id

                LEFT JOIN section
                    ON section.section_id =
                       user.section_id

                LEFT JOIN student_profile profile
                    ON profile.user_id =
                       user.user_id

                WHERE role.role_prefix = 'Student'
                  AND user.status = 'Active'

                  AND EXISTS (
                        SELECT 1

                        FROM student_profile_cycle_scope
                            scope

                        WHERE scope.student_profile_cycle_id = ?

                          AND (
                                scope.scope_type =
                                    'AllStudents'

                                OR (
                                    scope.scope_type =
                                        'Department'
                                    AND user.department_id =
                                        scope.department_id
                                )

                                OR (
                                    scope.scope_type =
                                        'EducationLevel'
                                    AND user.education_level_id =
                                        scope.education_level_id
                                )

                                OR (
                                    scope.scope_type =
                                        'AcademicProgram'
                                    AND user.academic_program_id =
                                        scope.academic_program_id
                                )

                                OR (
                                    scope.scope_type =
                                        'GradeLevel'
                                    AND user.grade_level_id =
                                        scope.grade_level_id
                                )

                                OR (
                                    scope.scope_type =
                                        'Section'
                                    AND user.section_id =
                                        scope.section_id
                                )
                              )
                  )

                ORDER BY
                    user.last_name ASC,
                    user.first_name ASC,
                    user.user_id ASC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the Student profile target preview: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $cycleId
        );

        $stmt->execute();

        $targets =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach ($targets as &$target) {
            $target['user_id'] =
                (int) $target['user_id'];

            $target['student_profile_id'] =
                $target['student_profile_id'] === null
                ? null
                : (int) $target['student_profile_id'];
        }

        unset($target);

        return $targets;
    }

    /* ==========================================
       ADMINISTRATOR CYCLE MONITORING
    ========================================== */

    public function getCycles(): array
    {
        $result =
            $this->conn->query("
                SELECT
                    cycle.student_profile_cycle_id,
                    cycle.cycle_name,
                    cycle.academic_year,
                    cycle.cycle_type,
                    cycle.academic_term,
                    cycle.survey_version,
                    cycle.status,
                    cycle.is_default,
                    cycle.opens_at,
                    cycle.due_at,
                    cycle.created_at,
                    cycle.activated_at,
                    cycle.closed_at,

                    version.version_name,

                    COUNT(
                        assignment.student_profile_cycle_assignment_id
                    ) AS assignment_count,

                    COALESCE(
                        SUM(
                            assignment.assignment_status = 'Assigned'
                        ),
                        0
                    ) AS assigned_count,

                    COALESCE(
                        SUM(
                            assignment.assignment_status = 'InProgress'
                        ),
                        0
                    ) AS in_progress_count,

                    COALESCE(
                        SUM(
                            assignment.assignment_status = 'Completed'
                        ),
                        0
                    ) AS completed_count,

                    COALESCE(
                        SUM(
                            assignment.assignment_status = 'Exempt'
                        ),
                        0
                    ) AS exempt_count

                FROM student_profile_cycle cycle

                INNER JOIN student_profile_survey_version version
                    ON version.survey_version =
                       cycle.survey_version

                LEFT JOIN student_profile_cycle_assignment assignment
                    ON assignment.student_profile_cycle_id =
                       cycle.student_profile_cycle_id

                GROUP BY
                    cycle.student_profile_cycle_id,
                    cycle.cycle_name,
                    cycle.academic_year,
                    cycle.cycle_type,
                    cycle.academic_term,
                    cycle.survey_version,
                    cycle.status,
                    cycle.is_default,
                    cycle.opens_at,
                    cycle.due_at,
                    cycle.created_at,
                    cycle.activated_at,
                    cycle.closed_at,
                    version.version_name

                ORDER BY
                    CASE cycle.status
                        WHEN 'Active' THEN 1
                        WHEN 'Scheduled' THEN 2
                        WHEN 'Draft' THEN 3
                        WHEN 'Closed' THEN 4
                        ELSE 5
                    END,
                    cycle.student_profile_cycle_id DESC
            ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load Student profile cycles: '
                    . $this->conn->error
            );
        }

        $cycles =
            $result->fetch_all(
                MYSQLI_ASSOC
            );

        $integerFields = [
            'student_profile_cycle_id',
            'survey_version',
            'assignment_count',
            'assigned_count',
            'in_progress_count',
            'completed_count',
            'exempt_count'
        ];

        foreach ($cycles as &$cycle) {
            foreach (
                $integerFields
                as $field
            ) {
                $cycle[$field] =
                    (int) (
                        $cycle[$field]
                        ?? 0
                    );
            }

            $cycle['is_default'] =
                !empty($cycle['is_default']);
        }

        unset($cycle);

        return $cycles;
    }

    public function getCycleAssignments(
        int $cycleId
    ): array {
        if ($cycleId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student profile cycle ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    assignment.student_profile_cycle_assignment_id,
                    assignment.student_profile_cycle_id,
                    assignment.assignment_status,
                    assignment.assigned_at,
                    assignment.started_at,
                    assignment.last_saved_at,
                    assignment.completed_at,
                    assignment.exempted_at,
                    assignment.exemption_reason,

                    profile.student_profile_id,

                    user.user_id,
                    user.studID,
                    user.first_name,
                    user.middle_name,
                    user.last_name,
                    user.name_suffix,
                    user.email,

                    department.department_name,
                    department.department_code,
                    level.education_level_name,
                    program.program_name,
                    program.program_code,
                    grade.grade_level_name,
                    section.section_name

                FROM student_profile_cycle_assignment assignment

                INNER JOIN student_profile profile
                    ON profile.student_profile_id =
                       assignment.student_profile_id

                INNER JOIN user
                    ON user.user_id =
                       profile.user_id

                LEFT JOIN department
                    ON department.department_id =
                       user.department_id

                LEFT JOIN education_level level
                    ON level.education_level_id =
                       user.education_level_id

                LEFT JOIN academic_program program
                    ON program.academic_program_id =
                       user.academic_program_id

                LEFT JOIN grade_level grade
                    ON grade.grade_level_id =
                       user.grade_level_id

                LEFT JOIN section
                    ON section.section_id =
                       user.section_id

                WHERE assignment.student_profile_cycle_id = ?

                ORDER BY
                    CASE assignment.assignment_status
                        WHEN 'InProgress' THEN 1
                        WHEN 'Assigned' THEN 2
                        WHEN 'Completed' THEN 3
                        WHEN 'Exempt' THEN 4
                        ELSE 5
                    END,
                    user.last_name ASC,
                    user.first_name ASC,
                    user.user_id ASC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare cycle assignment monitoring: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $cycleId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load cycle assignments: '
                    . $error
            );
        }

        $assignments =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach ($assignments as &$assignment) {
            $assignment['student_profile_cycle_assignment_id'] =
                (int) $assignment['student_profile_cycle_assignment_id'];

            $assignment['student_profile_cycle_id'] =
                (int) $assignment['student_profile_cycle_id'];

            $assignment['student_profile_id'] =
                (int) $assignment['student_profile_id'];

            $assignment['user_id'] =
                (int) $assignment['user_id'];
        }

        unset($assignment);

        return $assignments;
    }

    /* ==========================================
       TRANSACTIONAL ACTIVATION
    ========================================== */

    public function closeCycle(
        int $cycleId,
        int $adminId
    ): array {
        if ($cycleId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student profile cycle ID.'
            );
        }

        if ($adminId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Administrator user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                UPDATE student_profile_cycle

                SET
                    status = 'Closed',
                    closed_by = ?,
                    closed_at = NOW()

                WHERE student_profile_cycle_id = ?
                  AND status IN ('Active', 'Scheduled')
                  AND is_default = 0
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student profile cycle closing: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $adminId,
            $cycleId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to close the Student profile cycle: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        if (!$updated) {
            $cycle =
                $this->findCycle(
                    $cycleId
                );

            if (!$cycle) {
                throw new RuntimeException(
                    'The Student profile cycle could not be found.'
                );
            }

            if (!empty($cycle['is_default'])) {
                throw new RuntimeException(
                    'The default Student profile cycle cannot be closed.'
                );
            }

            throw new RuntimeException(
                'Only Active or Scheduled Student profile cycles may be closed.'
            );
        }

        $cycle =
            $this->findCycle(
                $cycleId
            );

        if (!$cycle) {
            throw new RuntimeException(
                'The closed Student profile cycle could not be reloaded.'
            );
        }

        return $cycle;
    }

    public function activateCycle(
        int $cycleId,
        int $adminId
    ): array {
        $this->conn->begin_transaction();

        try {
            $lockStmt =
                $this->conn->prepare("
                    SELECT
                        cycle.survey_version,
                        cycle.status,
                        version.status AS version_status,

                        (
                            SELECT COUNT(*)

                            FROM student_profile_question
                                question

                            WHERE question.survey_version =
                                  cycle.survey_version

                              AND question.status = 'Active'
                        ) AS active_question_count

                    FROM student_profile_cycle cycle

                    INNER JOIN student_profile_survey_version
                        version

                        ON version.survey_version =
                           cycle.survey_version

                    WHERE cycle.student_profile_cycle_id = ?

                    LIMIT 1

                    FOR UPDATE
                ");

            $lockStmt->bind_param(
                'i',
                $cycleId
            );

            $lockStmt->execute();

            $lockedCycle =
                $lockStmt
                ->get_result()
                ->fetch_assoc();

            $lockStmt->close();

            if (!$lockedCycle) {
                throw new RuntimeException(
                    'The Student profile cycle could not be found.'
                );
            }

            if (
                (
                    $lockedCycle['status']
                    ?? ''
                ) !== 'Draft'
            ) {
                throw new RuntimeException(
                    'Only Draft Student profile cycles may be activated.'
                );
            }

            if (
                (int) (
                    $lockedCycle['active_question_count']
                    ?? 0
                ) <= 0
            ) {
                throw new RuntimeException(
                    'The selected questionnaire has no active questions.'
                );
            }

            $targets =
                $this->previewCycleTargets(
                    $cycleId
                );

            if ($targets === []) {
                throw new RuntimeException(
                    'The selected cycle scope does not contain any active Students.'
                );
            }

            $profileStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile
                    (
                        user_id,
                        completion_status,
                        survey_completion_status,
                        survey_version,
                        personalization_enabled,
                        profile_version,
                        created_at
                    )
                    VALUES
                    (
                        ?,
                        'NotStarted',
                        'NotStarted',
                        1,
                        1,
                        1,
                        NOW()
                    )

                    ON DUPLICATE KEY UPDATE
                        user_id = VALUES(user_id)
                ");

            $profileLookupStmt =
                $this->conn->prepare("
                    SELECT
                        student_profile_id,
                        survey_completion_status,
                        survey_completed_at,
                        survey_last_saved_at,
                        created_at

                    FROM student_profile

                    WHERE user_id = ?

                    LIMIT 1
                ");

            $overlapStmt =
                $this->conn->prepare("
                    SELECT
                        cycle.cycle_name

                    FROM student_profile_cycle_assignment
                        assignment

                    INNER JOIN student_profile_cycle cycle
                        ON cycle.student_profile_cycle_id =
                           assignment.student_profile_cycle_id

                    WHERE assignment.student_profile_id = ?
                      AND assignment.student_profile_cycle_id <> ?
                      AND assignment.assignment_status IN
                          ('Assigned', 'InProgress')
                      AND cycle.status = 'Active'
                      AND cycle.is_default = 0

                    LIMIT 1
                ");

            $defaultCycleResult =
                $this->conn->query("
                    SELECT student_profile_cycle_id

                    FROM student_profile_cycle

                    WHERE is_default = 1
                      AND status = 'Active'

                    ORDER BY
                        student_profile_cycle_id ASC

                    LIMIT 1
                ");

            $defaultCycleRow =
                $defaultCycleResult->fetch_assoc();

            $defaultCycleId =
                (int) (
                    $defaultCycleRow['student_profile_cycle_id']
                    ?? 0
                );

            $defaultAssignmentStmt =
                $this->conn->prepare("
                    INSERT IGNORE INTO
                    student_profile_cycle_assignment
                    (
                        student_profile_cycle_id,
                        student_profile_id,
                        assignment_status,
                        assigned_at
                    )
                    VALUES
                    (
                        ?, ?, 'Assigned', NOW()
                    )
                ");

            $supersedeDefaultStmt =
                $this->conn->prepare("
                    UPDATE student_profile_cycle_assignment

                    SET
                        assignment_status =
                            CASE
                                WHEN assignment_status =
                                    'Completed'
                                THEN 'Completed'
                                ELSE 'Exempt'
                            END,

                        exempted_at =
                            CASE
                                WHEN assignment_status =
                                    'Completed'
                                THEN exempted_at
                                ELSE NOW()
                            END,

                        exemption_reason =
                            CASE
                                WHEN assignment_status =
                                    'Completed'
                                THEN exemption_reason
                                ELSE 'Superseded by a newer Student profile cycle.'
                            END,

                        updated_at = NOW()

                    WHERE student_profile_cycle_id = ?
                      AND student_profile_id = ?
                ");

            $newAssignmentStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile_cycle_assignment
                    (
                        student_profile_cycle_id,
                        student_profile_id,
                        assignment_status,
                        assigned_by,
                        assigned_at
                    )
                    VALUES
                    (
                        ?, ?, 'Assigned', ?, NOW()
                    )
                ");

            foreach ($targets as $target) {
                $userId =
                    (int) $target['user_id'];

                $profileStmt->bind_param(
                    'i',
                    $userId
                );

                $profileStmt->execute();

                $profileLookupStmt->bind_param(
                    'i',
                    $userId
                );

                $profileLookupStmt->execute();

                $profile =
                    $profileLookupStmt
                    ->get_result()
                    ->fetch_assoc();

                $studentProfileId =
                    (int) (
                        $profile['student_profile_id']
                        ?? 0
                    );

                if ($studentProfileId <= 0) {
                    throw new RuntimeException(
                        'A targeted Student profile could not be initialized.'
                    );
                }

                $overlapStmt->bind_param(
                    'ii',
                    $studentProfileId,
                    $cycleId
                );

                $overlapStmt->execute();

                $overlap =
                    $overlapStmt
                    ->get_result()
                    ->fetch_assoc();

                if ($overlap) {
                    throw new RuntimeException(
                        'A targeted Student already has an unfinished active cycle: '
                            . (
                                $overlap['cycle_name']
                                ?? 'Unknown cycle'
                            )
                            . '.'
                    );
                }

                if ($defaultCycleId > 0) {
                    $defaultAssignmentStmt->bind_param(
                        'ii',
                        $defaultCycleId,
                        $studentProfileId
                    );

                    $defaultAssignmentStmt->execute();

                    $supersedeDefaultStmt->bind_param(
                        'ii',
                        $defaultCycleId,
                        $studentProfileId
                    );

                    $supersedeDefaultStmt->execute();
                }

                $newAssignmentStmt->bind_param(
                    'iii',
                    $cycleId,
                    $studentProfileId,
                    $adminId
                );

                $newAssignmentStmt->execute();
            }

            $profileStmt->close();
            $profileLookupStmt->close();
            $overlapStmt->close();
            $defaultAssignmentStmt->close();
            $supersedeDefaultStmt->close();
            $newAssignmentStmt->close();

            $surveyVersion =
                (int) $lockedCycle['survey_version'];

            $versionStmt =
                $this->conn->prepare("
                    UPDATE student_profile_survey_version

                    SET
                        status = 'Active',
                        activated_by = ?,
                        activated_at =
                            COALESCE(
                                activated_at,
                                NOW()
                            ),
                        updated_at = NOW()

                    WHERE survey_version = ?
                      AND status IN ('Draft', 'Active')
                ");

            $versionStmt->bind_param(
                'ii',
                $adminId,
                $surveyVersion
            );

            $versionStmt->execute();
            $versionStmt->close();

            $cycleStmt =
                $this->conn->prepare("
                    UPDATE student_profile_cycle

                    SET
                        status = 'Active',
                        activated_by = ?,
                        activated_at = NOW(),
                        updated_at = NOW()

                    WHERE student_profile_cycle_id = ?
                ");

            $cycleStmt->bind_param(
                'ii',
                $adminId,
                $cycleId
            );

            $cycleStmt->execute();
            $cycleStmt->close();

            $this->conn->commit();

            $cycle =
                $this->findCycle(
                    $cycleId
                );

            if (!$cycle) {
                throw new RuntimeException(
                    'The activated Student profile cycle could not be reloaded.'
                );
            }

            $cycle['target_count'] =
                count($targets);

            return $cycle;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }
}
