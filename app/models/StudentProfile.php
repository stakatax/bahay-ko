<?php

require_once __DIR__
    . '/BaseModel.php';

class StudentProfile extends BaseModel
{
    /* ==========================================
       STUDENT VALIDATION
    ========================================== */

    public function isActiveStudent(
        int $userId
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) AS total

            FROM user u

            INNER JOIN role r
                ON r.role_id =
                    u.role_id

            WHERE u.user_id = ?
              AND u.status = 'Active'
              AND r.role_prefix = 'Student'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student validation: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        $stmt->execute();

        $row = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return (int) (
            $row['total']
            ?? 0
        ) > 0;
    }

    /* ==========================================
       INTEREST DIRECTORY
    ========================================== */

    public function getActiveInterests(): array
    {
        $result = $this->conn->query("
            SELECT
                interest_id,
                interest_name,
                interest_slug,
                description,
                sort_order

            FROM content_interest

            WHERE status = 'Active'

            ORDER BY
                sort_order ASC,
                interest_name ASC
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load Student interests: '
                    . $this->conn->error
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    /* ==========================================
       ACTIVE SURVEY QUESTION CATALOG
    ========================================== */

    public function getActiveSurveyQuestions(
        int $surveyVersion = 1
    ): array {
        if ($surveyVersion <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student profile survey version.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                student_profile_question_id,
                question_key,
                section_key,
                section_label,
                section_sort_order,
                question_text,
                help_text,
                response_type,
                options_json,
                is_required,
                is_sensitive,
                consent_key,
                analytics_enabled,
                survey_version,
                sort_order

            FROM student_profile_question

            WHERE survey_version = ?
              AND status = 'Active'

            ORDER BY
                section_sort_order ASC,
                sort_order ASC,
                student_profile_question_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student survey questions: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyVersion
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load Student survey questions: '
                    . $error
            );
        }

        $questions =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach (
            $questions
            as &$question
        ) {
            $question['student_profile_question_id'] =
                (int) (
                    $question['student_profile_question_id']
                    ?? 0
                );

            $question['is_required'] =
                !empty($question['is_required']);

            $question['is_sensitive'] =
                !empty($question['is_sensitive']);

            $question['analytics_enabled'] =
                !empty($question['analytics_enabled']);

            $question['survey_version'] =
                (int) (
                    $question['survey_version']
                    ?? $surveyVersion
                );

            $question['sort_order'] =
                (int) (
                    $question['sort_order']
                    ?? 0
                );

            $optionsJson =
                trim(
                    (string) (
                        $question['options_json']
                        ?? ''
                    )
                );

            $question['options'] =
                $optionsJson === ''
                ? []
                : (
                    json_decode(
                        $optionsJson,
                        true
                    )
                    ?: []
                );

            unset(
                $question['options_json']
            );
        }

        unset($question);

        return $questions;
    }

    public function getActiveSurveyConsentDefinitions(): array
    {
        $result =
            $this->conn->query("
            SELECT
                definition
                    .student_profile_consent_definition_id,

                definition
                    .consent_key,

                definition
                    .consent_version,

                definition
                    .title,

                definition
                    .consent_statement,

                definition
                    .effective_at

            FROM student_profile_consent_definition
                definition

            WHERE definition.status =
                    'Active'

              AND definition.effective_at <=
                    NOW()

              AND NOT EXISTS (
                    SELECT 1

                    FROM student_profile_consent_definition
                        newer_definition

                    WHERE newer_definition.consent_key =
                            definition.consent_key

                      AND newer_definition.status =
                            'Active'

                      AND newer_definition.effective_at <=
                            NOW()

                      AND (
                            newer_definition.effective_at >
                                definition.effective_at

                            OR (
                                newer_definition.effective_at =
                                    definition.effective_at

                                AND newer_definition
                                    .student_profile_consent_definition_id >
                                    definition
                                        .student_profile_consent_definition_id
                            )
                          )
                  )

            ORDER BY
                definition
                    .student_profile_consent_definition_id
                    ASC
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load Student survey consent definitions: '
                    . $this->conn->error
            );
        }

        $definitions = [];

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $consentKey =
                (string) (
                    $row['consent_key']
                    ?? ''
                );

            if ($consentKey !== '') {
                $definitions[$consentKey] =
                    $row;
            }
        }

        return $definitions;
    }

    /* ==========================================
       PROFILE LOOKUP
    ========================================== */

    public function findByUserId(
        int $userId
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare("
            SELECT
                student_profile_id,
                user_id,
                completion_status,
                survey_completion_status,
                survey_version,
                survey_current_step,
                survey_completed_at,
                survey_last_saved_at,
                personalization_enabled,
                profile_version,
                completed_at,
                created_at,
                updated_at

            FROM student_profile

            WHERE user_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student profile lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        $stmt->execute();

        $profile = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$profile) {
            return null;
        }

        $profile['student_profile_id'] =
            (int) $profile['student_profile_id'];

        $profile['user_id'] =
            (int) $profile['user_id'];

        $profile['personalization_enabled'] =
            (bool) $profile['personalization_enabled'];

        $profile['profile_version'] =
            (int) $profile['profile_version'];


        $profile['survey_version'] =
            (int) (
                $profile['survey_version']
                ?? 1
            );

        $profile['survey_current_step'] =
            $profile['survey_current_step']
            ?? null;

        $profile['interests'] =
            $this->getProfileInterests(
                $profile['student_profile_id']
            );

        return $profile;
    }

    public function ensureProfile(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student profile user ID.'
            );
        }

        $stmt =
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
                user_id =
                    VALUES(user_id)
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student profile initialization: '
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
                'Unable to initialize the Student profile: '
                    . $error
            );
        }

        $stmt->close();

        $profile =
            $this->findByUserId(
                $userId
            );

        if (!$profile) {
            throw new RuntimeException(
                'The initialized Student profile could not be loaded.'
            );
        }

        return $profile;
    }

    /* ==========================================
       CURRENT SURVEY CYCLE ASSIGNMENT
    ========================================== */

    public function getCurrentSurveyAssignment(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student survey assignment user ID.'
            );
        }

        // Existing profiles need no initialization write on every Student request.
        $profile = $this->findByUserId($userId)
            ?? $this->ensureProfile($userId);

        $studentProfileId =
            (int) (
                $profile['student_profile_id']
                ?? 0
            );

        if ($studentProfileId <= 0) {
            throw new RuntimeException(
                'The Student profile is unavailable.'
            );
        }

        $assignment =
            $this->findCurrentSurveyAssignment(
                $studentProfileId
            );

        if ($assignment !== null) {
            return $assignment;
        }

        /*
         * Students created after the migration
         * receive the active default cycle when
         * they first access the profile survey.
         */
        $cycleResult =
            $this->conn->query("
                SELECT
                    student_profile_cycle_id

                FROM student_profile_cycle

                WHERE is_default = 1
                  AND status = 'Active'

                  AND (
                        opens_at IS NULL
                        OR opens_at <= NOW()
                      )

                ORDER BY
                    activated_at DESC,
                    student_profile_cycle_id DESC

                LIMIT 1
            ");

        if (!$cycleResult) {
            throw new RuntimeException(
                'Unable to load the default Student profile cycle: '
                    . $this->conn->error
            );
        }

        $cycleRow =
            $cycleResult->fetch_assoc();

        $cycleId =
            (int) (
                $cycleRow['student_profile_cycle_id']
                ?? 0
            );

        if ($cycleId <= 0) {
            throw new RuntimeException(
                'No active Student profile survey cycle is available.'
            );
        }

        $assignmentStatus =
            match ((string) (
                $profile['survey_completion_status']
                ?? 'NotStarted'
            )) {
                'Completed' =>
                'Completed',

                'InProgress' =>
                'InProgress',

                default =>
                'Assigned'
            };

        $assignedAt =
            (string) (
                $profile['created_at']
                ?? ''
            );

        $startedAt =
            in_array(
                $assignmentStatus,
                [
                    'InProgress',
                    'Completed'
                ],
                true
            )
            ? (
                $profile['survey_last_saved_at']
                ?? $assignedAt
            )
            : null;

        $completedAt =
            $assignmentStatus === 'Completed'
            ? (
                $profile['survey_completed_at']
                ?? null
            )
            : null;

        $lastSavedAt =
            $profile['survey_last_saved_at']
            ?? null;

        $insertStmt =
            $this->conn->prepare("
                INSERT IGNORE INTO
                student_profile_cycle_assignment
                (
                    student_profile_cycle_id,
                    student_profile_id,
                    assignment_status,
                    assigned_at,
                    started_at,
                    completed_at,
                    last_saved_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    COALESCE(
                        NULLIF(?, ''),
                        NOW()
                    ),
                    ?,
                    ?,
                    ?
                )
            ");

        if (!$insertStmt) {
            throw new RuntimeException(
                'Unable to prepare the default Student survey assignment: '
                    . $this->conn->error
            );
        }

        $insertStmt->bind_param(
            'iisssss',
            $cycleId,
            $studentProfileId,
            $assignmentStatus,
            $assignedAt,
            $startedAt,
            $completedAt,
            $lastSavedAt
        );

        if (!$insertStmt->execute()) {
            $error =
                $insertStmt->error;

            $insertStmt->close();

            throw new RuntimeException(
                'Unable to create the default Student survey assignment: '
                    . $error
            );
        }

        $insertStmt->close();

        $assignment =
            $this->findCurrentSurveyAssignment(
                $studentProfileId
            );

        if ($assignment === null) {
            throw new RuntimeException(
                'The Student survey assignment could not be loaded.'
            );
        }

        return $assignment;
    }

    private function findCurrentSurveyAssignment(
        int $studentProfileId
    ): ?array {
        if ($studentProfileId <= 0) {
            return null;
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    assignment
                        .student_profile_cycle_assignment_id,

                    assignment
                        .student_profile_cycle_id,

                    assignment
                        .student_profile_id,

                    assignment
                        .assignment_status,

                    assignment
                        .assigned_at,

                    assignment
                        .started_at,

                    assignment
                        .completed_at,

                    assignment
                        .last_saved_at,

                    cycle.cycle_name,
                    cycle.academic_year,
                    cycle.cycle_type,
                    cycle.academic_term,
                    cycle.survey_version,
                    cycle.status AS cycle_status,
                    cycle.is_default,
                    cycle.opens_at,
                    cycle.due_at,
                    cycle.activated_at,

                    version.version_name AS survey_version_name,
                    version.status AS survey_version_status

                FROM student_profile_cycle_assignment
                    assignment

                INNER JOIN student_profile_cycle
                    cycle

                    ON cycle.student_profile_cycle_id =
                       assignment.student_profile_cycle_id

                INNER JOIN student_profile_survey_version
                    version

                    ON version.survey_version =
                       cycle.survey_version

                WHERE assignment.student_profile_id = ?

                  AND cycle.status = 'Active'

                  AND (
                        cycle.opens_at IS NULL
                        OR cycle.opens_at <= NOW()
                      )

                ORDER BY
                    cycle.is_default ASC,
                    cycle.activated_at DESC,
                    cycle.student_profile_cycle_id DESC

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the Student survey assignment lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $studentProfileId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load the Student survey assignment: '
                    . $error
            );
        }

        $assignment =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$assignment) {
            return null;
        }

        $assignment['student_profile_cycle_assignment_id'] =
            (int) $assignment['student_profile_cycle_assignment_id'];

        $assignment['student_profile_cycle_id'] =
            (int) $assignment['student_profile_cycle_id'];

        $assignment['student_profile_id'] =
            (int) $assignment['student_profile_id'];

        $assignment['survey_version'] =
            (int) $assignment['survey_version'];

        $assignment['is_default'] =
            !empty($assignment['is_default']);

        return $assignment;
    }

    public function getSurveyResponses(
        int $studentProfileId,
        int $surveyVersion
    ): array {
        if (
            $studentProfileId <= 0 ||
            $surveyVersion <= 0
        ) {
            return [];
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                response
                    .student_profile_response_id,

                response
                    .student_profile_question_id,

                question
                    .question_key,

                question
                    .survey_version,

                response
                    .response_json,

                response
                    .responded_at,

                response
                    .updated_at

            FROM student_profile_response
                response

            INNER JOIN student_profile_question
                question

                ON question
                    .student_profile_question_id =
                   response
                    .student_profile_question_id

            WHERE response.student_profile_id = ?
              AND question.survey_version = ?

            ORDER BY
                question.section_sort_order ASC,
                question.sort_order ASC,
                question.student_profile_question_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student survey responses: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $studentProfileId,
            $surveyVersion
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load Student survey responses: '
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

        $responses = [];

        foreach ($rows as $row) {
            $questionKey =
                (string) (
                    $row['question_key']
                    ?? ''
                );

            if ($questionKey === '') {
                continue;
            }

            $row['student_profile_response_id'] =
                (int) (
                    $row['student_profile_response_id']
                    ?? 0
                );

            $row['student_profile_question_id'] =
                (int) (
                    $row['student_profile_question_id']
                    ?? 0
                );

            $row['survey_version'] =
                (int) (
                    $row['survey_version']
                    ?? $surveyVersion
                );

            $row['value'] =
                json_decode(
                    (string) (
                        $row['response_json']
                        ?? 'null'
                    ),
                    true
                );

            unset(
                $row['response_json']
            );

            $responses[$questionKey] =
                $row;
        }

        return $responses;
    }

    public function getSurveyConsents(
        int $studentProfileId
    ): array {
        if ($studentProfileId <= 0) {
            return [];
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                student_profile_consent_id,
                consent_key,
                consent_version,
                consent_granted,
                granted_at,
                withdrawn_at,
                acceptance_source,
                updated_at

            FROM student_profile_consent

            WHERE student_profile_id = ?

            ORDER BY
                student_profile_consent_id DESC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Student survey consents: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $studentProfileId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load Student survey consents: '
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

        $consents = [];

        foreach ($rows as $row) {
            $consentKey =
                (string) (
                    $row['consent_key']
                    ?? ''
                );

            if (
                $consentKey === '' ||
                isset($consents[$consentKey])
            ) {
                continue;
            }

            $row['consent_granted'] =
                !empty($row['consent_granted']);

            $consents[$consentKey] =
                $row;
        }

        return $consents;
    }

    /* ==========================================
       SAVE SURVEY PROGRESS
    ========================================== */

    public function saveSurveyProgress(
        int $userId,
        int $surveyVersion,
        int $cycleAssignmentId,
        array $responses,
        array $consents,
        array $clearedQuestionIds,
        string $currentStep,
        bool $completed
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student survey user ID.'
            );
        }

        if ($surveyVersion <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student survey version.'
            );
        }

        if ($cycleAssignmentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student survey cycle assignment.'
            );
        }

        $profile =
            $this->ensureProfile(
                $userId
            );

        $studentProfileId =
            (int) (
                $profile['student_profile_id']
                ?? 0
            );

        if ($studentProfileId <= 0) {
            throw new RuntimeException(
                'The Student profile is unavailable.'
            );
        }

        $currentStep =
            trim($currentStep);

        $completionStatus =
            $completed
            ? 'Completed'
            : 'InProgress';

        $this->conn->begin_transaction();

        try {
            $assignmentStmt =
                $this->conn->prepare("
                    SELECT
                        assignment.assignment_status,
                        assignment.completed_at

                    FROM student_profile_cycle_assignment
                        assignment

                    INNER JOIN student_profile_cycle
                        cycle

                        ON cycle.student_profile_cycle_id =
                           assignment.student_profile_cycle_id

                    WHERE assignment
                            .student_profile_cycle_assignment_id = ?

                      AND assignment.student_profile_id = ?

                      AND cycle.survey_version = ?

                      AND cycle.status = 'Active'

                      AND (
                            cycle.opens_at IS NULL
                            OR cycle.opens_at <= NOW()
                          )

                    LIMIT 1

                    FOR UPDATE
                ");

            if (!$assignmentStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student survey assignment validation: '
                        . $this->conn->error
                );
            }

            $assignmentStmt->bind_param(
                'iii',
                $cycleAssignmentId,
                $studentProfileId,
                $surveyVersion
            );

            if (!$assignmentStmt->execute()) {
                throw new RuntimeException(
                    'Unable to validate the Student survey assignment: '
                        . $assignmentStmt->error
                );
            }

            $assignment =
                $assignmentStmt
                ->get_result()
                ->fetch_assoc();

            $assignmentStmt->close();

            if (!$assignment) {
                throw new RuntimeException(
                    'The Student survey assignment is no longer active.'
                );
            }

            if (
                (
                    $assignment['assignment_status']
                    ?? ''
                ) === 'Exempt'
            ) {
                throw new RuntimeException(
                    'This Student is exempt from the assigned profile cycle.'
                );
            }

            $responseStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile_response
                    (
                        student_profile_id,
                        student_profile_question_id,
                        response_json,
                        responded_at,
                        updated_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        NOW(),
                        NOW()
                    )

                    ON DUPLICATE KEY UPDATE
                        response_json =
                            VALUES(response_json),

                        updated_at =
                            NOW()
                ");

            if (!$responseStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student survey responses: '
                        . $this->conn->error
                );
            }

            foreach (
                $responses
                as $questionId => $responseValue
            ) {
                $questionId =
                    (int) $questionId;

                if ($questionId <= 0) {
                    continue;
                }

                $responseJson =
                    json_encode(
                        $responseValue,
                        JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR
                    );

                $responseStmt->bind_param(
                    'iis',
                    $studentProfileId,
                    $questionId,
                    $responseJson
                );

                if (!$responseStmt->execute()) {
                    throw new RuntimeException(
                        'Unable to save a Student survey response: '
                            . $responseStmt->error
                    );
                }
            }

            $responseStmt->close();

            $clearedQuestionIds =
                array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'intval',
                                $clearedQuestionIds
                            ),
                            static fn(
                                int $questionId
                            ): bool =>
                            $questionId > 0
                        )
                    )
                );

            if ($clearedQuestionIds !== []) {
                $deleteStmt =
                    $this->conn->prepare("
                        DELETE FROM
                            student_profile_response

                        WHERE student_profile_id = ?
                          AND student_profile_question_id = ?
                    ");

                if (!$deleteStmt) {
                    throw new RuntimeException(
                        'Unable to prepare Student survey response removal: '
                            . $this->conn->error
                    );
                }

                foreach (
                    $clearedQuestionIds
                    as $questionId
                ) {
                    $deleteStmt->bind_param(
                        'ii',
                        $studentProfileId,
                        $questionId
                    );

                    if (!$deleteStmt->execute()) {
                        throw new RuntimeException(
                            'Unable to remove a Student survey response: '
                                . $deleteStmt->error
                        );
                    }
                }

                $deleteStmt->close();
            }

            $consentStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile_consent
                    (
                        student_profile_id,
                        consent_key,
                        consent_version,
                        consent_granted,
                        granted_at,
                        withdrawn_at,
                        acceptance_source,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        IF(? = 1, NOW(), NULL),
                        IF(? = 0, NOW(), NULL),
                        ?,
                        NOW(),
                        NOW()
                    )

                    ON DUPLICATE KEY UPDATE
                        consent_granted =
                            VALUES(consent_granted),

                        granted_at =
                            IF(
                                VALUES(consent_granted) = 1,
                                COALESCE(
                                    granted_at,
                                    NOW()
                                ),
                                NULL
                            ),

                        withdrawn_at =
                            IF(
                                VALUES(consent_granted) = 0,
                                NOW(),
                                NULL
                            ),

                        acceptance_source =
                            VALUES(acceptance_source),

                        updated_at =
                            NOW()
                ");

            if (!$consentStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student survey consent: '
                        . $this->conn->error
                );
            }

            foreach (
                $consents
                as $consent
            ) {
                $consentKey =
                    trim(
                        (string) (
                            $consent['consent_key']
                            ?? ''
                        )
                    );

                $consentVersion =
                    trim(
                        (string) (
                            $consent['consent_version']
                            ?? ''
                        )
                    );

                $consentGranted =
                    !empty($consent['consent_granted'])
                    ? 1
                    : 0;

                $acceptanceSource =
                    (
                        $consent['acceptance_source']
                        ?? ''
                    ) === 'ProfileUpdate'
                    ? 'ProfileUpdate'
                    : 'Survey';

                if (
                    $consentKey === '' ||
                    $consentVersion === ''
                ) {
                    continue;
                }

                $consentStmt->bind_param(
                    'issiiis',
                    $studentProfileId,
                    $consentKey,
                    $consentVersion,
                    $consentGranted,
                    $consentGranted,
                    $consentGranted,
                    $acceptanceSource
                );

                if (!$consentStmt->execute()) {
                    throw new RuntimeException(
                        'Unable to save Student survey consent: '
                            . $consentStmt->error
                    );
                }
            }

            $consentStmt->close();

            $completedFlag =
                $completed
                ? 1
                : 0;

            $requestedAssignmentStatus =
                $completed
                ? 'Completed'
                : 'InProgress';

            $assignmentUpdateStmt =
                $this->conn->prepare("
                    UPDATE student_profile_cycle_assignment

                    SET
                        started_at =
                            COALESCE(
                                started_at,
                                NOW()
                            ),

                        last_saved_at =
                            NOW(),

                        completed_at =
                            CASE
                                WHEN assignment_status =
                                    'Completed'
                                THEN completed_at

                                WHEN ? = 1
                                THEN NOW()

                                ELSE NULL
                            END,

                        assignment_status =
                            CASE
                                WHEN assignment_status =
                                    'Completed'
                                THEN 'Completed'

                                ELSE ?
                            END,

                        updated_at =
                            NOW()

                    WHERE student_profile_cycle_assignment_id = ?
                ");

            if (!$assignmentUpdateStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student survey assignment progress: '
                        . $this->conn->error
                );
            }

            $assignmentUpdateStmt->bind_param(
                'isi',
                $completedFlag,
                $requestedAssignmentStatus,
                $cycleAssignmentId
            );

            if (!$assignmentUpdateStmt->execute()) {
                throw new RuntimeException(
                    'Unable to update Student survey assignment progress: '
                        . $assignmentUpdateStmt->error
                );
            }

            $assignmentUpdateStmt->close();

            $assignmentStatusStmt =
                $this->conn->prepare("
                    SELECT
                        assignment_status,
                        completed_at

                    FROM student_profile_cycle_assignment

                    WHERE student_profile_cycle_assignment_id = ?

                    LIMIT 1
                ");

            if (!$assignmentStatusStmt) {
                throw new RuntimeException(
                    'Unable to prepare the saved assignment status lookup: '
                        . $this->conn->error
                );
            }

            $assignmentStatusStmt->bind_param(
                'i',
                $cycleAssignmentId
            );

            $assignmentStatusStmt->execute();

            $savedAssignment =
                $assignmentStatusStmt
                ->get_result()
                ->fetch_assoc();

            $assignmentStatusStmt->close();

            if (!$savedAssignment) {
                throw new RuntimeException(
                    'The saved Student survey assignment could not be reloaded.'
                );
            }

            $completionStatus =
                (
                    $savedAssignment['assignment_status']
                    ?? ''
                ) === 'Completed'
                ? 'Completed'
                : 'InProgress';

            $surveyCompletedAt =
                $completionStatus === 'Completed'
                ? (
                    $savedAssignment['completed_at']
                    ?? null
                )
                : null;

            $savedCurrentStep =

                $savedCurrentStep =
                $completed
                ? null
                : (
                    $currentStep !== ''
                    ? $currentStep
                    : null
                );



            $profileStmt =
                $this->conn->prepare("
                    UPDATE student_profile

                    SET
                        survey_completion_status = ?,
                        survey_version = ?,
                        survey_current_step = ?,

                        survey_completed_at = ?,

                        survey_last_saved_at = NOW(),
                        updated_at = NOW()

                    WHERE student_profile_id = ?
                ");

            if (!$profileStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student survey progress: '
                        . $this->conn->error
                );
            }

            $profileStmt->bind_param(
                'sissi',
                $completionStatus,
                $surveyVersion,
                $savedCurrentStep,
                $surveyCompletedAt,
                $studentProfileId
            );

            if (!$profileStmt->execute()) {
                throw new RuntimeException(
                    'Unable to save Student survey progress: '
                        . $profileStmt->error
                );
            }

            $profileStmt->close();

            $this->conn->commit();
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }

        $savedProfile =
            $this->findByUserId(
                $userId
            );

        if (!$savedProfile) {
            throw new RuntimeException(
                'The saved Student survey profile could not be reloaded.'
            );
        }

        return $savedProfile;
    }

    /** Minimal feed projection; profile editing continues to use the full lookup. */
    public function getPersonalizationInterests(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $stmt = $this->conn->prepare("
            SELECT spi.interest_id, spi.preference_weight
            FROM student_profile sp
            INNER JOIN student_profile_interest spi
                ON spi.student_profile_id = sp.student_profile_id
            INNER JOIN content_interest ci ON ci.interest_id = spi.interest_id
            WHERE sp.user_id = ? AND sp.completion_status = 'Completed'
              AND sp.personalization_enabled <> 0 AND ci.status = 'Active'
            ORDER BY spi.preference_weight DESC, ci.sort_order ASC, ci.interest_name ASC
        ");
        if (!$stmt) {
            throw new RuntimeException('Unable to load personalization interests.');
        }
        try {
            $stmt->bind_param('i', $userId);
            if (!$stmt->execute()) {
                throw new RuntimeException('Unable to load personalization interests.');
            }
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } finally {
            $stmt->close();
        }
    }

    public function getProfileInterests(
        int $studentProfileId
    ): array {
        if ($studentProfileId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare("
            SELECT
                spi.student_profile_interest_id,
                spi.interest_id,
                ci.interest_name,
                ci.interest_slug,
                ci.description,
                spi.preference_weight,
                spi.selected_at,
                spi.updated_at

            FROM student_profile_interest spi

            INNER JOIN content_interest ci
                ON ci.interest_id =
                    spi.interest_id

            WHERE spi.student_profile_id = ?
              AND ci.status = 'Active'

            ORDER BY
                spi.preference_weight DESC,
                ci.sort_order ASC,
                ci.interest_name ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare profile interests: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $studentProfileId
        );

        $stmt->execute();

        $interests = $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach (
            $interests as &$interest
        ) {
            $interest['student_profile_interest_id'] = (int) $interest['student_profile_interest_id'];

            $interest['interest_id'] =
                (int) $interest['interest_id'];

            $interest['preference_weight'] =
                (int) $interest['preference_weight'];
        }

        unset($interest);

        return $interests;
    }

    /* ==========================================
       SAVE PROFILE
    ========================================== */

    public function savePreferences(
        int $userId,
        array $preferences,
        bool $personalizationEnabled = true
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student user ID.'
            );
        }

        if ($preferences === []) {
            throw new InvalidArgumentException(
                'Select at least one interest.'
            );
        }

        $this->conn->begin_transaction();

        try {
            $enabled =
                $personalizationEnabled
                ? 1
                : 0;

            $profileStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile
                    (
                        user_id,
                        completion_status,
                        personalization_enabled,
                        profile_version,
                        completed_at,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        ?,
                        'Completed',
                        ?,
                        1,
                        NOW(),
                        NOW(),
                        NOW()
                    )

                    ON DUPLICATE KEY UPDATE
                        completion_status =
                            'Completed',

                        personalization_enabled =
                            VALUES(
                                personalization_enabled
                            ),

                        profile_version =
                            profile_version + 1,

                        completed_at =
                            NOW(),

                        updated_at =
                            NOW()
                ");

            if (!$profileStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student profile: '
                        . $this->conn->error
                );
            }

            $profileStmt->bind_param(
                'ii',
                $userId,
                $enabled
            );

            $profileStmt->execute();
            $profileStmt->close();

            $profileIdStmt =
                $this->conn->prepare("
                    SELECT student_profile_id

                    FROM student_profile

                    WHERE user_id = ?

                    LIMIT 1
                ");

            if (!$profileIdStmt) {
                throw new RuntimeException(
                    'Unable to reload the Student profile: '
                        . $this->conn->error
                );
            }

            $profileIdStmt->bind_param(
                'i',
                $userId
            );

            $profileIdStmt->execute();

            $profileRow =
                $profileIdStmt
                ->get_result()
                ->fetch_assoc();

            $profileIdStmt->close();

            $studentProfileId =
                (int) (
                    $profileRow['student_profile_id']
                    ?? 0
                );

            if ($studentProfileId <= 0) {
                throw new RuntimeException(
                    'The Student profile could not be reloaded.'
                );
            }

            $deleteStmt =
                $this->conn->prepare("
                    DELETE FROM
                        student_profile_interest

                    WHERE student_profile_id = ?
                ");

            if (!$deleteStmt) {
                throw new RuntimeException(
                    'Unable to prepare existing preference cleanup: '
                        . $this->conn->error
                );
            }

            $deleteStmt->bind_param(
                'i',
                $studentProfileId
            );

            $deleteStmt->execute();
            $deleteStmt->close();

            $interestStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile_interest
                    (
                        student_profile_id,
                        interest_id,
                        preference_weight,
                        selected_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        NOW()
                    )
                ");

            if (!$interestStmt) {
                throw new RuntimeException(
                    'Unable to prepare Student preferences: '
                        . $this->conn->error
                );
            }

            foreach (
                $preferences as $preference
            ) {
                $interestId =
                    (int) (
                        $preference['interest_id']
                        ?? 0
                    );

                $preferenceWeight =
                    (int) (
                        $preference['preference_weight']
                        ?? 3
                    );

                $interestStmt->bind_param(
                    'iii',
                    $studentProfileId,
                    $interestId,
                    $preferenceWeight
                );

                $interestStmt->execute();
            }

            $interestStmt->close();

            $this->conn->commit();

            $profile =
                $this->findByUserId(
                    $userId
                );

            if (!$profile) {
                throw new RuntimeException(
                    'The saved Student profile could not be loaded.'
                );
            }

            return $profile;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }
}
