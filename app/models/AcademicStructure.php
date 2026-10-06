<?php

require_once __DIR__
    . '/BaseModel.php';

class AcademicStructure extends BaseModel
{

    private ?PublicCatalogCache $catalogCache = null;
    private bool $catalogTransaction = false;
    private ?PublicCatalogCache $registrationCache = null;

    public function __construct(?mysqli $connection = null)
    {
        parent::__construct($connection);
        // Injected connections may use temporary tables; never share their cache.
        if ($connection === null) {
            require_once __DIR__ . '/PublicCatalogCache.php';
            $this->catalogCache = PublicCatalogCache::directory('public-catalog');
            $this->registrationCache = PublicCatalogCache::directory('registration-academics');
        }
    }

    private const ENTITY_MAP = [
        'department' => [
            'table' => 'department',
            'primary_key' => 'department_id'
        ],

        'education_level' => [
            'table' => 'education_level',
            'primary_key' => 'education_level_id'
        ],

        'academic_program' => [
            'table' => 'academic_program',
            'primary_key' => 'academic_program_id'
        ],

        'grade_level' => [
            'table' => 'grade_level',
            'primary_key' => 'grade_level_id'
        ],

        'section' => [
            'table' => 'section',
            'primary_key' => 'section_id'
        ]
    ];
    /* ==========================================
       COMPLETE DIRECTORY
    ========================================== */

    public function getDirectory(): array
    {
        return [
            'departments' =>
            $this->getDepartments(),

            'education_levels' =>
            $this->getEducationLevels(),

            'academic_programs' =>
            $this->getAcademicPrograms(),

            'grade_levels' =>
            $this->getGradeLevels(),

            'sections' =>
            $this->getSections()
        ];
    }

    /* ==========================================
   PUBLIC ACADEMIC CATALOG
========================================== */

    public function getPublicCatalog(): array
    {
        if ($this->catalogCache !== null && !$this->catalogTransaction) {
            return $this->catalogCache->remember(fn(): array => $this->loadPublicCatalog());
        }
        return $this->loadPublicCatalog();
    }

    private function loadPublicCatalog(): array
    {
        /*
     * Public pages need only active academic
     * records. Avoid the dependency counts and
     * assigned-user queries used by the Admin
     * management directory.
     */
        $educationLevels =
            $this->fetchAll("
            SELECT
                el.education_level_id,
                el.department_id,
                el.education_level_name,
                d.department_name,
                d.department_code

            FROM education_level el

            INNER JOIN department d
                ON d.department_id =
                   el.department_id
               AND d.status = 'Active'

            WHERE el.status = 'Active'

            ORDER BY
                el.education_level_id ASC
        ");

        $academicPrograms =
            $this->fetchAll("
            SELECT
                ap.academic_program_id,
                ap.education_level_id,
                ap.program_name,
                ap.program_code,
                ap.program_type,
                ap.description,
                el.education_level_name,
                d.department_name

            FROM academic_program ap

            INNER JOIN education_level el
                ON el.education_level_id =
                   ap.education_level_id
               AND el.status = 'Active'

            INNER JOIN department d
                ON d.department_id =
                   el.department_id
               AND d.status = 'Active'

            WHERE ap.status = 'Active'

            ORDER BY
                el.education_level_id ASC,
                ap.program_type ASC,
                ap.program_name ASC
        ");

        $gradeLevels =
            $this->fetchAll("
            SELECT
                gl.grade_level_id,
                gl.education_level_id,
                gl.grade_level_name,
                el.education_level_name,
                d.department_name

            FROM grade_level gl

            INNER JOIN education_level el
                ON el.education_level_id =
                   gl.education_level_id
               AND el.status = 'Active'

            INNER JOIN department d
                ON d.department_id =
                   el.department_id
               AND d.status = 'Active'

            WHERE gl.status = 'Active'

            ORDER BY
                el.education_level_id ASC,
                gl.grade_level_id ASC
        ");

        return [
            'education_levels' =>
            $educationLevels,

            'academic_programs' =>
            $academicPrograms,

            'grade_levels' =>
            $gradeLevels
        ];
    }

    /* ==========================================
       SCHOOL DIVISIONS
    ========================================== */

    public function getDepartments(): array
    {
        return $this->fetchAll("
            SELECT
                d.department_id,
                d.department_name,
                d.department_code,
                d.description,
                d.status,

                (
                    SELECT COUNT(*)
                    FROM education_level el
                    WHERE el.department_id =
                          d.department_id
                ) AS education_level_count,

                (
                    SELECT COUNT(*)
                    FROM education_level el
                    WHERE el.department_id =
                          d.department_id
                      AND el.status = 'Active'
                ) AS active_education_level_count,

                (
                    SELECT COUNT(*)
                    FROM user u
                    WHERE u.department_id =
                          d.department_id
                ) AS assigned_user_count

            FROM department d

            ORDER BY
                CASE
                    WHEN d.status = 'Active'
                    THEN 0
                    ELSE 1
                END,
                d.department_name ASC
        ");
    }

    /* ==========================================
       EDUCATION LEVELS
    ========================================== */

    public function getEducationLevels(): array
    {
        return $this->fetchAll("
        SELECT
            el.education_level_id,
            el.department_id,
            el.education_level_name,
            el.status,

            d.department_name,
            d.department_code,

            (
                SELECT COUNT(*)
                FROM academic_program ap
                WHERE ap.education_level_id =
                      el.education_level_id
            ) AS program_count,

            (
                SELECT COUNT(*)
                FROM academic_program ap
                WHERE ap.education_level_id =
                      el.education_level_id
                  AND ap.status = 'Active'
            ) AS active_program_count,

            (
                SELECT COUNT(*)
                FROM grade_level gl
                WHERE gl.education_level_id =
                      el.education_level_id
            ) AS grade_level_count,

            (
                SELECT COUNT(*)
                FROM grade_level gl
                WHERE gl.education_level_id =
                      el.education_level_id
                  AND gl.status = 'Active'
            ) AS active_grade_level_count,

            (
                SELECT COUNT(*)
                FROM user u
                WHERE u.education_level_id =
                      el.education_level_id
            ) AS assigned_user_count

        FROM education_level el

        INNER JOIN department d
            ON d.department_id =
               el.department_id

        ORDER BY
            d.department_name ASC,
            el.education_level_id ASC
    ");
    }

    /* ==========================================
       PROGRAMS AND STRANDS
    ========================================== */

    public function getAcademicPrograms(): array
    {
        return $this->fetchAll("
        SELECT
            ap.academic_program_id,
            ap.education_level_id,
            ap.program_name,
            ap.program_code,
            ap.program_type,
            ap.description,
            ap.status,
            ap.created_at,
            ap.updated_at,

            el.education_level_name,
            el.department_id,

            d.department_name,

            (
                SELECT COUNT(*)
                FROM section s
                WHERE s.academic_program_id =
                      ap.academic_program_id
            ) AS section_count,

            (
                SELECT COUNT(*)
                FROM section s
                WHERE s.academic_program_id =
                      ap.academic_program_id
                  AND s.status = 'Active'
            ) AS active_section_count,

            (
                SELECT COUNT(*)
                FROM user u
                WHERE u.academic_program_id =
                      ap.academic_program_id
            ) AS assigned_user_count

        FROM academic_program ap

        INNER JOIN education_level el
            ON el.education_level_id =
               ap.education_level_id

        INNER JOIN department d
            ON d.department_id =
               el.department_id

        ORDER BY
            el.education_level_id ASC,
            ap.program_type ASC,
            ap.program_name ASC
    ");
    }

    /* ==========================================
       GRADES AND YEAR LEVELS
    ========================================== */

    public function getGradeLevels(): array
    {
        return $this->fetchAll("
        SELECT
            gl.grade_level_id,
            gl.education_level_id,
            gl.grade_level_name,
            gl.status,

            el.education_level_name,
            el.department_id,

            d.department_name,

            (
                SELECT COUNT(*)
                FROM section s
                WHERE s.grade_level_id =
                      gl.grade_level_id
            ) AS section_count,

            (
                SELECT COUNT(*)
                FROM section s
                WHERE s.grade_level_id =
                      gl.grade_level_id
                  AND s.status = 'Active'
            ) AS active_section_count,

            (
                SELECT COUNT(*)
                FROM user u
                WHERE u.grade_level_id =
                      gl.grade_level_id
            ) AS assigned_user_count

        FROM grade_level gl

        INNER JOIN education_level el
            ON el.education_level_id =
               gl.education_level_id

        INNER JOIN department d
            ON d.department_id =
               el.department_id

        ORDER BY
            el.education_level_id ASC,
            gl.grade_level_id ASC
    ");
    }

    /* ==========================================
       SECTIONS
    ========================================== */

    public function getSections(): array
    {
        return $this->fetchAll("
            SELECT
                s.section_id,
                s.grade_level_id,
                s.academic_program_id,
                s.section_name,
                s.status,

                gl.grade_level_name,
                gl.education_level_id,

                el.education_level_name,
                el.department_id,

                d.department_name,

                ap.program_name,
                ap.program_code,
                ap.program_type,

                (
                    SELECT COUNT(*)
                    FROM user u
                    WHERE u.section_id =
                          s.section_id
                ) AS assigned_user_count

            FROM section s

            INNER JOIN grade_level gl
                ON gl.grade_level_id =
                   s.grade_level_id

            INNER JOIN education_level el
                ON el.education_level_id =
                   gl.education_level_id

            INNER JOIN department d
                ON d.department_id =
                   el.department_id

            LEFT JOIN academic_program ap
                ON ap.academic_program_id =
                   s.academic_program_id

            ORDER BY
                el.education_level_id ASC,
                gl.grade_level_id ASC,
                ap.program_name ASC,
                s.section_name ASC
        ");
    }

    /* ==========================================
   ENTITY LOOKUP
========================================== */

    public function findEntity(
        string $entityType,
        int $entityId
    ): ?array {
        $definition =
            $this->getEntityDefinition(
                $entityType
            );

        $table =
            $definition['table'];

        $primaryKey =
            $definition['primary_key'];

        $stmt = $this->conn->prepare("
        SELECT *
        FROM {$table}
        WHERE {$primaryKey} = ?
        LIMIT 1
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare academic entity lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $entityId
        );

        $stmt->execute();

        $record =
            $stmt->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $record ?: null;
    }

    /* ==========================================
   DEPENDENCY COUNTS
========================================== */

    public function getDependencyCounts(
        string $entityType,
        int $entityId
    ): array {
        $queries = [
            'department' => "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM education_level
                    WHERE department_id = ?
                      AND status = 'Active'
                ) AS active_children,

                (
                    SELECT COUNT(*)
                    FROM user
                    WHERE department_id = ?
                ) AS assigned_users
        ",

            'education_level' => "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM academic_program
                    WHERE education_level_id = ?
                      AND status = 'Active'
                )
                +
                (
                    SELECT COUNT(*)
                    FROM grade_level
                    WHERE education_level_id = ?
                      AND status = 'Active'
                ) AS active_children,

                (
                    SELECT COUNT(*)
                    FROM user
                    WHERE education_level_id = ?
                ) AS assigned_users
        ",

            'academic_program' => "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM section
                    WHERE academic_program_id = ?
                      AND status = 'Active'
                ) AS active_children,

                (
                    SELECT COUNT(*)
                    FROM user
                    WHERE academic_program_id = ?
                ) AS assigned_users
        ",

            'grade_level' => "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM section
                    WHERE grade_level_id = ?
                      AND status = 'Active'
                ) AS active_children,

                (
                    SELECT COUNT(*)
                    FROM user
                    WHERE grade_level_id = ?
                ) AS assigned_users
        ",

            'section' => "
            SELECT
                0 AS active_children,

                (
                    SELECT COUNT(*)
                    FROM user
                    WHERE section_id = ?
                ) AS assigned_users
        "
        ];

        if (!isset($queries[$entityType])) {
            throw new InvalidArgumentException(
                'Unsupported academic entity type.'
            );
        }

        $stmt =
            $this->conn->prepare(
                $queries[$entityType]
            );

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare dependency lookup: '
                    . $this->conn->error
            );
        }

        switch ($entityType) {
            case 'education_level':
                $stmt->bind_param(
                    'iii',
                    $entityId,
                    $entityId,
                    $entityId
                );
                break;

            case 'department':
            case 'academic_program':
            case 'grade_level':
                $stmt->bind_param(
                    'ii',
                    $entityId,
                    $entityId
                );
                break;

            case 'section':
                $stmt->bind_param(
                    'i',
                    $entityId
                );
                break;
        }

        $stmt->execute();

        $counts =
            $stmt->get_result()
            ->fetch_assoc();

        $stmt->close();

        return [
            'active_children' =>
            (int) (
                $counts['active_children']
                ?? 0
            ),

            'assigned_users' =>
            (int) (
                $counts['assigned_users']
                ?? 0
            )
        ];
    }

    /* ==========================================
   CREATE ENTITY
========================================== */

    public function createEntity(
        string $entityType,
        array $data
    ): int {
        $this->getEntityDefinition(
            $entityType
        );

        switch ($entityType) {
            case 'department':
                $stmt = $this->conn->prepare("
                INSERT INTO department
                (
                    department_name,
                    department_code,
                    description,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'Active'
                )
            ");

                $stmt?->bind_param(
                    'sss',
                    $data['department_name'],
                    $data['department_code'],
                    $data['description']
                );
                break;

            case 'education_level':
                $stmt = $this->conn->prepare("
                INSERT INTO education_level
                (
                    department_id,
                    education_level_name,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    'Active'
                )
            ");

                $stmt?->bind_param(
                    'is',
                    $data['department_id'],
                    $data['education_level_name']
                );
                break;

            case 'academic_program':
                $stmt = $this->conn->prepare("
                INSERT INTO academic_program
                (
                    education_level_id,
                    program_name,
                    program_code,
                    program_type,
                    description,
                    status,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Active',
                    NOW()
                )
            ");

                $stmt?->bind_param(
                    'issss',
                    $data['education_level_id'],
                    $data['program_name'],
                    $data['program_code'],
                    $data['program_type'],
                    $data['description']
                );
                break;

            case 'grade_level':
                $stmt = $this->conn->prepare("
                INSERT INTO grade_level
                (
                    education_level_id,
                    grade_level_name,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    'Active'
                )
            ");

                $stmt?->bind_param(
                    'is',
                    $data['education_level_id'],
                    $data['grade_level_name']
                );
                break;

            case 'section':
                $stmt = $this->conn->prepare("
                INSERT INTO section
                (
                    grade_level_id,
                    academic_program_id,
                    section_name,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'Active'
                )
            ");

                $stmt?->bind_param(
                    'iis',
                    $data['grade_level_id'],
                    $data['academic_program_id'],
                    $data['section_name']
                );
                break;

            default:
                throw new InvalidArgumentException(
                    'Unsupported academic entity type.'
                );
        }

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare academic creation: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to create the academic record: '
                    . $error
            );
        }

        $entityId =
            (int) $this->conn->insert_id;

        $stmt->close();
        if (!$this->catalogTransaction) { $this->clearDisplayCaches(); }

        return $entityId;
    }

    /* ==========================================
   UPDATE ENTITY
========================================== */

    public function updateEntity(
        string $entityType,
        int $entityId,
        array $data
    ): void {
        $this->getEntityDefinition(
            $entityType
        );

        switch ($entityType) {
            case 'department':
                $stmt = $this->conn->prepare("
                UPDATE department
                SET
                    department_name = ?,
                    department_code = ?,
                    description = ?
                WHERE department_id = ?
            ");

                $stmt?->bind_param(
                    'sssi',
                    $data['department_name'],
                    $data['department_code'],
                    $data['description'],
                    $entityId
                );
                break;

            case 'education_level':
                $stmt = $this->conn->prepare("
                UPDATE education_level
                SET
                    department_id = ?,
                    education_level_name = ?
                WHERE education_level_id = ?
            ");

                $stmt?->bind_param(
                    'isi',
                    $data['department_id'],
                    $data['education_level_name'],
                    $entityId
                );
                break;

            case 'academic_program':
                $stmt = $this->conn->prepare("
                UPDATE academic_program
                SET
                    education_level_id = ?,
                    program_name = ?,
                    program_code = ?,
                    program_type = ?,
                    description = ?,
                    updated_at = NOW()
                WHERE academic_program_id = ?
            ");

                $stmt?->bind_param(
                    'issssi',
                    $data['education_level_id'],
                    $data['program_name'],
                    $data['program_code'],
                    $data['program_type'],
                    $data['description'],
                    $entityId
                );
                break;

            case 'grade_level':
                $stmt = $this->conn->prepare("
                UPDATE grade_level
                SET
                    education_level_id = ?,
                    grade_level_name = ?
                WHERE grade_level_id = ?
            ");

                $stmt?->bind_param(
                    'isi',
                    $data['education_level_id'],
                    $data['grade_level_name'],
                    $entityId
                );
                break;

            case 'section':
                $stmt = $this->conn->prepare("
                UPDATE section
                SET
                    grade_level_id = ?,
                    academic_program_id = ?,
                    section_name = ?
                WHERE section_id = ?
            ");

                $stmt?->bind_param(
                    'iisi',
                    $data['grade_level_id'],
                    $data['academic_program_id'],
                    $data['section_name'],
                    $entityId
                );
                break;

            default:
                throw new InvalidArgumentException(
                    'Unsupported academic entity type.'
                );
        }

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare academic update: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update the academic record: '
                    . $error
            );
        }

        $stmt->close();
        if (!$this->catalogTransaction) { $this->clearDisplayCaches(); }
    }

    /* ==========================================
   CHANGE STATUS
========================================== */

    public function changeStatus(
        string $entityType,
        int $entityId,
        string $status
    ): void {
        $definition =
            $this->getEntityDefinition(
                $entityType
            );

        if (
            !in_array(
                $status,
                [
                    'Active',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid academic record status.'
            );
        }

        $table =
            $definition['table'];

        $primaryKey =
            $definition['primary_key'];

        $stmt = $this->conn->prepare("
        UPDATE {$table}
        SET status = ?
        WHERE {$primaryKey} = ?
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare academic status update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'si',
            $status,
            $entityId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to change the academic record status: '
                    . $error
            );
        }

        $stmt->close();
        if (!$this->catalogTransaction) { $this->clearDisplayCaches(); }
    }

    public function getHistory(
        int $limit = 50
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    100
                )
            );

        $stmt = $this->conn->prepare("
        SELECT
            history.academic_history_id,
            history.entity_type,
            history.entity_id,
            history.change_type,
            history.previous_data,
            history.new_data,
            history.reason,
            history.changed_by,
            history.created_at,

            actor.studID
                AS changed_by_identifier,

            TRIM(
                CONCAT_WS(
                    ' ',
                    NULLIF(
                        actor.first_name,
                        ''
                    ),
                    NULLIF(
                        actor.middle_name,
                        ''
                    ),
                    NULLIF(
                        actor.last_name,
                        ''
                    ),
                    NULLIF(
                        actor.name_suffix,
                        ''
                    )
                )
            ) AS changed_by_name

        FROM academic_structure_history
            AS history

        LEFT JOIN user
            AS actor
            ON actor.user_id =
                history.changed_by

        ORDER BY
            history.academic_history_id DESC

        LIMIT ?
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare academic history retrieval: '
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
                'Unable to retrieve academic history: '
                    . $error
            );
        }

        $result =
            $stmt->get_result();

        if (!$result) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to read academic history: '
                    . $error
            );
        }

        $history = [];

        $nameFields = [
            'department' =>
            'department_name',

            'education_level' =>
            'education_level_name',

            'academic_program' =>
            'academic_program_name',

            'grade_level' =>
            'grade_level_name',

            'section' =>
            'section_name'
        ];

        $entityLabels = [
            'department' =>
            'School Division',

            'education_level' =>
            'Education Level',

            'academic_program' =>
            'Program or Strand',

            'grade_level' =>
            'Grade or Year Level',

            'section' =>
            'Section'
        ];

        while (
            $row =
            $result->fetch_assoc()
        ) {
            foreach (
                [
                    'previous_data',
                    'new_data'
                ]
                as $snapshotField
            ) {
                $snapshotJson =
                    $row[$snapshotField];

                if (
                    $snapshotJson === null ||
                    $snapshotJson === ''
                ) {
                    $row[$snapshotField] =
                        null;

                    continue;
                }

                $decodedSnapshot =
                    json_decode(
                        $snapshotJson,
                        true
                    );

                $row[$snapshotField] =
                    is_array(
                        $decodedSnapshot
                    )
                    ? $decodedSnapshot
                    : null;
            }

            $entityType =
                (string) (
                    $row['entity_type']
                    ?? ''
                );

            $entityId =
                (int) (
                    $row['entity_id']
                    ?? 0
                );

            $snapshot =
                is_array(
                    $row['new_data']
                )
                ? $row['new_data']
                : (
                    is_array(
                        $row['previous_data']
                    )
                    ? $row['previous_data']
                    : []
                );

            $nameField =
                $nameFields[$entityType] ?? '';

            $entityName =
                $nameField !== ''
                ? trim(
                    (string) (
                        $snapshot[$nameField]
                        ?? ''
                    )
                )
                : '';

            if ($entityName === '') {
                $entityName =
                    (
                        $entityLabels[$entityType] ??
                        'Academic Record'
                    )
                    . ' #'
                    . $entityId;
            }

            $actorName =
                trim(
                    (string) (
                        $row['changed_by_name']
                        ?? ''
                    )
                );

            if ($actorName === '') {
                $actorName =
                    'User #'
                    . (
                        (int) (
                            $row['changed_by']
                            ?? 0
                        )
                    );
            }

            $row['academic_history_id'] =
                (int) $row['academic_history_id'];

            $row['entity_id'] =
                $entityId;

            $row['changed_by'] =
                (int) $row['changed_by'];

            $row['entity_name'] =
                $entityName;

            $row['changed_by_name'] =
                $actorName;

            $history[] =
                $row;
        }

        $stmt->close();

        return $history;
    }

    /* ==========================================
   HISTORY
========================================== */

    public function recordHistory(
        string $entityType,
        int $entityId,
        string $changeType,
        ?array $previousData,
        ?array $newData,
        string $reason,
        int $changedBy
    ): void {
        $this->getEntityDefinition(
            $entityType
        );

        $allowedChanges = [
            'create',
            'update',
            'activate',
            'deactivate'
        ];

        if (
            !in_array(
                $changeType,
                $allowedChanges,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported academic change type.'
            );
        }

        $previousJson =
            $previousData !== null
            ? json_encode(
                $previousData,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            )
            : null;

        $newJson =
            $newData !== null
            ? json_encode(
                $newData,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            )
            : null;

        if (
            $previousJson === false ||
            $newJson === false
        ) {
            throw new RuntimeException(
                'Unable to encode the academic audit data.'
            );
        }

        $stmt = $this->conn->prepare("
        INSERT INTO academic_structure_history
        (
            entity_type,
            entity_id,
            change_type,
            previous_data,
            new_data,
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
            ?,
            ?,
            NOW()
        )
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare academic history: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'sissssi',
            $entityType,
            $entityId,
            $changeType,
            $previousJson,
            $newJson,
            $reason,
            $changedBy
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to record academic history: '
                    . $error
            );
        }

        $stmt->close();
    }

    /* ==========================================
   TRANSACTIONS
========================================== */

    public function beginTransaction(): void
    {
        $this->conn->begin_transaction();
        $this->catalogTransaction = true;
    }

    public function commit(): void
    {
        $this->conn->commit();
        $this->catalogTransaction = false;
        $this->clearDisplayCaches();
    }

    private function clearDisplayCaches(): void
    {
        $this->catalogCache?->clear();
        $this->registrationCache?->clear();
    }

    public function rollback(): void
    {
        $this->conn->rollback();
        $this->catalogTransaction = false;
    }

    /* ==========================================
   ENTITY DEFINITION
========================================== */

    private function getEntityDefinition(
        string $entityType
    ): array {
        if (
            !isset(
                self::ENTITY_MAP[$entityType]
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported academic entity type.'
            );
        }

        return self::ENTITY_MAP[$entityType];
    }

    /* ==========================================
       QUERY HELPER
    ========================================== */

    private function fetchAll(
        string $sql
    ): array {
        $result =
            $this->conn->query(
                $sql
            );

        if (!$result) {
            throw new RuntimeException(
                'Unable to load the academic structure: '
                    . $this->conn->error
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }
}
