<?php

require_once __DIR__
    . '/BaseModel.php';

class StudentProfileManagement extends BaseModel
{
    /* ==========================================
       QUESTIONNAIRE VERSION DIRECTORY
    ========================================== */

    public function getSurveyVersions(): array
    {
        $result =
            $this->conn->query("
                SELECT
                    version.survey_version,
                    version.version_name,
                    version.description,
                    version.status,
                    version.created_by,
                    version.activated_by,
                    version.created_at,
                    version.activated_at,
                    version.retired_at,
                    version.updated_at,

                    creator.first_name AS creator_first_name,
                    creator.last_name AS creator_last_name,

                    activator.first_name AS activator_first_name,
                    activator.last_name AS activator_last_name,

                    COUNT(question.student_profile_question_id)
                        AS question_count,

                    SUM(
                        CASE
                            WHEN question.status = 'Active'
                            THEN 1
                            ELSE 0
                        END
                    ) AS active_question_count

                FROM student_profile_survey_version
                    version

                LEFT JOIN user creator
                    ON creator.user_id =
                       version.created_by

                LEFT JOIN user activator
                    ON activator.user_id =
                       version.activated_by

                LEFT JOIN student_profile_question
                    question

                    ON question.survey_version =
                       version.survey_version

                GROUP BY
                    version.survey_version,
                    version.version_name,
                    version.description,
                    version.status,
                    version.created_by,
                    version.activated_by,
                    version.created_at,
                    version.activated_at,
                    version.retired_at,
                    version.updated_at,
                    creator.first_name,
                    creator.last_name,
                    activator.first_name,
                    activator.last_name

                ORDER BY
                    version.survey_version DESC
            ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load questionnaire versions: '
                    . $this->conn->error
            );
        }

        $versions =
            $result->fetch_all(
                MYSQLI_ASSOC
            );

        foreach ($versions as &$version) {
            $version['survey_version'] =
                (int) $version['survey_version'];

            $version['question_count'] =
                (int) (
                    $version['question_count']
                    ?? 0
                );

            $version['active_question_count'] =
                (int) (
                    $version['active_question_count']
                    ?? 0
                );
        }

        unset($version);

        return $versions;
    }

    public function findSurveyVersion(
        int $surveyVersion
    ): ?array {
        if ($surveyVersion <= 0) {
            return null;
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    version.survey_version,
                    version.version_name,
                    version.description,
                    version.status,
                    version.created_by,
                    version.activated_by,
                    version.created_at,
                    version.activated_at,
                    version.retired_at,
                    version.updated_at,

                    (
                        SELECT COUNT(*)

                        FROM student_profile_question
                            question

                        WHERE question.survey_version =
                              version.survey_version
                    ) AS question_count,

                    (
                        SELECT COUNT(*)

                        FROM student_profile_question
                            question

                        WHERE question.survey_version =
                              version.survey_version

                          AND question.status = 'Active'
                    ) AS active_question_count

                FROM student_profile_survey_version
                    version

                WHERE version.survey_version = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the questionnaire version lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyVersion
        );

        $stmt->execute();

        $version =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$version) {
            return null;
        }

        $version['survey_version'] =
            (int) $version['survey_version'];

        $version['question_count'] =
            (int) (
                $version['question_count']
                ?? 0
            );

        $version['active_question_count'] =
            (int) (
                $version['active_question_count']
                ?? 0
            );

        return $version;
    }

    public function getSurveyQuestions(
        int $surveyVersion
    ): array {
        if ($surveyVersion <= 0) {
            return [];
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
                    status,
                    sort_order,
                    created_at,
                    updated_at

                FROM student_profile_question

                WHERE survey_version = ?

                ORDER BY
                    section_sort_order ASC,
                    sort_order ASC,
                    student_profile_question_id ASC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare questionnaire questions: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyVersion
        );

        $stmt->execute();

        $questions =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach ($questions as &$question) {
            $question['student_profile_question_id'] =
                (int) $question['student_profile_question_id'];

            $question['survey_version'] =
                (int) $question['survey_version'];

            $question['section_sort_order'] =
                (int) $question['section_sort_order'];

            $question['sort_order'] =
                (int) $question['sort_order'];

            $question['is_required'] =
                !empty($question['is_required']);

            $question['is_sensitive'] =
                !empty($question['is_sensitive']);

            $question['analytics_enabled'] =
                !empty($question['analytics_enabled']);

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
        }

        unset($question);

        return $questions;
    }

    public function getSurveySections(
        int $surveyVersion
    ): array {
        if ($surveyVersion <= 0) {
            return [];
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    section_key,
                    section_label,
                    section_sort_order,
                    COUNT(*) AS question_count

                FROM student_profile_question

                WHERE survey_version = ?

                GROUP BY
                    section_key,
                    section_label,
                    section_sort_order

                ORDER BY
                    section_sort_order ASC,
                    section_label ASC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare questionnaire sections: '
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
                'Unable to load questionnaire sections: '
                    . $error
            );
        }

        $sections =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach ($sections as &$section) {
            $section['section_sort_order'] =
                (int) (
                    $section['section_sort_order']
                    ?? 0
                );

            $section['question_count'] =
                (int) (
                    $section['question_count']
                    ?? 0
                );
        }

        unset($section);

        return $sections;
    }

    public function findSurveySection(
        int $surveyVersion,
        string $sectionKey
    ): ?array {
        $sectionKey =
            trim($sectionKey);

        if (
            $surveyVersion <= 0 ||
            $sectionKey === ''
        ) {
            return null;
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    section_key,
                    section_label,
                    section_sort_order

                FROM student_profile_question

                WHERE survey_version = ?
                  AND section_key = ?

                ORDER BY
                    student_profile_question_id ASC

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare questionnaire-section lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'is',
            $surveyVersion,
            $sectionKey
        );

        $stmt->execute();

        $section =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$section) {
            return null;
        }

        $section['section_sort_order'] =
            (int) (
                $section['section_sort_order']
                ?? 0
            );

        return $section;
    }

    /* ==========================================
       CREATE ISOLATED DRAFT VERSION
    ========================================== */

    public function createDraftSurveyVersion(
        int $sourceVersion,
        string $versionName,
        ?string $description,
        int $adminId
    ): array {
        if (
            $sourceVersion <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid questionnaire draft request.'
            );
        }

        $this->conn->begin_transaction();

        try {
            $source =
                $this->findSurveyVersion(
                    $sourceVersion
                );

            if (!$source) {
                throw new RuntimeException(
                    'The source questionnaire version could not be found.'
                );
            }

            if (
                (int) (
                    $source['question_count']
                    ?? 0
                ) <= 0
            ) {
                throw new RuntimeException(
                    'The source questionnaire has no questions to copy.'
                );
            }

            $versionResult =
                $this->conn->query("
                    SELECT
                        COALESCE(
                            MAX(survey_version),
                            0
                        ) + 1 AS next_version

                    FROM student_profile_survey_version
                ");

            if (!$versionResult) {
                throw new RuntimeException(
                    'Unable to determine the next questionnaire version.'
                );
            }

            $versionRow =
                $versionResult->fetch_assoc();

            $newVersion =
                (int) (
                    $versionRow['next_version']
                    ?? 0
                );

            if ($newVersion <= 0) {
                throw new RuntimeException(
                    'The next questionnaire version was unavailable.'
                );
            }

            $versionStmt =
                $this->conn->prepare("
                    INSERT INTO
                    student_profile_survey_version
                    (
                        survey_version,
                        version_name,
                        description,
                        status,
                        created_by,
                        created_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'Draft',
                        ?,
                        NOW()
                    )
                ");

            if (!$versionStmt) {
                throw new RuntimeException(
                    'Unable to prepare the questionnaire draft: '
                        . $this->conn->error
                );
            }

            $versionStmt->bind_param(
                'issi',
                $newVersion,
                $versionName,
                $description,
                $adminId
            );

            $versionStmt->execute();
            $versionStmt->close();

            $copyStmt =
                $this->conn->prepare("
                    INSERT INTO student_profile_question
                    (
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
                        status,
                        sort_order,
                        created_at
                    )

                    SELECT
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
                        ?,
                        status,
                        sort_order,
                        NOW()

                    FROM student_profile_question

                    WHERE survey_version = ?

                    ORDER BY
                        section_sort_order ASC,
                        sort_order ASC,
                        student_profile_question_id ASC
                ");

            if (!$copyStmt) {
                throw new RuntimeException(
                    'Unable to prepare the questionnaire question copy: '
                        . $this->conn->error
                );
            }

            $copyStmt->bind_param(
                'ii',
                $newVersion,
                $sourceVersion
            );

            $copyStmt->execute();

            $copiedQuestionCount =
                $copyStmt->affected_rows;

            $copyStmt->close();

            if ($copiedQuestionCount <= 0) {
                throw new RuntimeException(
                    'No questionnaire questions were copied.'
                );
            }

            $this->conn->commit();

            $draft =
                $this->findSurveyVersion(
                    $newVersion
                );

            if (!$draft) {
                throw new RuntimeException(
                    'The created questionnaire draft could not be reloaded.'
                );
            }

            return $draft;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       QUESTION CONFIGURATION
    ========================================== */

    public function findSurveyQuestion(
        int $questionId
    ): ?array {
        if ($questionId <= 0) {
            return null;
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
                    status,
                    sort_order,
                    created_at,
                    updated_at

                FROM student_profile_question

                WHERE student_profile_question_id = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the questionnaire question lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $questionId
        );

        $stmt->execute();

        $question =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$question) {
            return null;
        }

        return $this->normalizeQuestionRow(
            $question
        );
    }

    public function createSurveyQuestion(
        int $surveyVersion,
        array $data
    ): array {
        $optionsJson =
            $data['options'] === []
            ? null
            : json_encode(
                $data['options'],
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_THROW_ON_ERROR
            );

        $stmt =
            $this->conn->prepare("
                INSERT INTO student_profile_question
                (
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
                    status,
                    sort_order,
                    created_at
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 'Active', ?, NOW()
                )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the questionnaire question: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'sssissssiisiii',
            $data['question_key'],
            $data['section_key'],
            $data['section_label'],
            $data['section_sort_order'],
            $data['question_text'],
            $data['help_text'],
            $data['response_type'],
            $optionsJson,
            $data['is_required'],
            $data['is_sensitive'],
            $data['consent_key'],
            $data['analytics_enabled'],
            $surveyVersion,
            $data['sort_order']
        );

        $stmt->execute();

        $questionId =
            (int) $this->conn->insert_id;

        $stmt->close();

        $question =
            $this->findSurveyQuestion(
                $questionId
            );

        if (!$question) {
            throw new RuntimeException(
                'The created questionnaire question could not be reloaded.'
            );
        }

        return $question;
    }

    public function updateSurveyQuestion(
        int $questionId,
        array $data
    ): array {
        $optionsJson =
            $data['options'] === []
            ? null
            : json_encode(
                $data['options'],
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_THROW_ON_ERROR
            );

        $stmt =
            $this->conn->prepare("
                UPDATE student_profile_question

                SET
                    section_key = ?,
                    section_label = ?,
                    section_sort_order = ?,
                    question_text = ?,
                    help_text = ?,
                    response_type = ?,
                    options_json = ?,
                    is_required = ?,
                    is_sensitive = ?,
                    consent_key = ?,
                    analytics_enabled = ?,
                    sort_order = ?,
                    updated_at = NOW()

                WHERE student_profile_question_id = ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the questionnaire question update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssissssiisiii',
            $data['section_key'],
            $data['section_label'],
            $data['section_sort_order'],
            $data['question_text'],
            $data['help_text'],
            $data['response_type'],
            $optionsJson,
            $data['is_required'],
            $data['is_sensitive'],
            $data['consent_key'],
            $data['analytics_enabled'],
            $data['sort_order'],
            $questionId
        );

        $stmt->execute();
        $stmt->close();

        $question =
            $this->findSurveyQuestion(
                $questionId
            );

        if (!$question) {
            throw new RuntimeException(
                'The updated questionnaire question could not be reloaded.'
            );
        }

        return $question;
    }

    public function setSurveyQuestionStatus(
        int $questionId,
        string $status
    ): array {
        $stmt =
            $this->conn->prepare("
                UPDATE student_profile_question

                SET
                    status = ?,
                    updated_at = NOW()

                WHERE student_profile_question_id = ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the questionnaire question status: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'si',
            $status,
            $questionId
        );

        $stmt->execute();
        $stmt->close();

        $question =
            $this->findSurveyQuestion(
                $questionId
            );

        if (!$question) {
            throw new RuntimeException(
                'The questionnaire question could not be reloaded.'
            );
        }

        return $question;
    }

    public function getConsentDefinitions(): array
    {
        $result =
            $this->conn->query("
                SELECT
                    student_profile_consent_definition_id,
                    consent_key,
                    consent_version,
                    title,
                    status,
                    effective_at

                FROM student_profile_consent_definition

                WHERE status = 'Active'

                ORDER BY
                    title ASC,
                    student_profile_consent_definition_id ASC
            ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load Student profile consent definitions: '
                    . $this->conn->error
            );
        }

        $definitions =
            $result->fetch_all(
                MYSQLI_ASSOC
            );

        foreach ($definitions as &$definition) {
            $definition['student_profile_consent_definition_id'] =
                (int) $definition['student_profile_consent_definition_id'];
        }

        unset($definition);

        return $definitions;
    }

    public function hasConsentDefinition(
        string $consentKey
    ): bool {
        $stmt =
            $this->conn->prepare("
                SELECT 1

                FROM student_profile_consent_definition

                WHERE consent_key = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the consent-definition lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            's',
            $consentKey
        );

        $stmt->execute();

        $exists =
            $stmt
            ->get_result()
            ->num_rows > 0;

        $stmt->close();

        return $exists;
    }

    private function normalizeQuestionRow(
        array $question
    ): array {
        $question['student_profile_question_id'] =
            (int) $question['student_profile_question_id'];

        $question['survey_version'] =
            (int) $question['survey_version'];

        $question['section_sort_order'] =
            (int) $question['section_sort_order'];

        $question['sort_order'] =
            (int) $question['sort_order'];

        $question['is_required'] =
            !empty($question['is_required']);

        $question['is_sensitive'] =
            !empty($question['is_sensitive']);

        $question['analytics_enabled'] =
            !empty($question['analytics_enabled']);

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

        return $question;
    }
}
