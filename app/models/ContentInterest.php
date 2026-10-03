<?php

require_once __DIR__
    . '/BaseModel.php';

class ContentInterest extends BaseModel
{
    private const CONTENT_MAP = [
        'announcement' => [
            'table' =>
            'announcements',

            'primary_key' =>
            'announcement_id'
        ],

        'event' => [
            'table' =>
            'events',

            'primary_key' =>
            'event_id'
        ],

        'document' => [
            'table' =>
            'documents',

            'primary_key' =>
            'document_id'
        ],

        'survey' => [
            'table' =>
            'survey',

            'primary_key' =>
            'survey_id'
        ]
    ];

    /* ==========================================
       ACTIVE TOPIC DIRECTORY
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
                'Unable to load content topics: '
                    . $this->conn->error
            );
        }

        $interests =
            $result->fetch_all(
                MYSQLI_ASSOC
            );

        foreach (
            $interests as &$interest
        ) {
            $interest['interest_id'] =
                (int) (
                    $interest['interest_id']
                    ?? 0
                );

            $interest['sort_order'] =
                (int) (
                    $interest['sort_order']
                    ?? 0
                );
        }

        unset($interest);

        return $interests;
    }

    /* ==========================================
       CONTENT TOPIC LOOKUP
    ========================================== */

    public function getAssignments(
        string $contentType,
        int $contentId
    ): array {
        $this->getContentDefinition(
            $contentType
        );

        if ($contentId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare("
            SELECT
                cia.content_interest_assignment_id,
                cia.content_type,
                cia.content_id,
                cia.interest_id,
                ci.interest_name,
                ci.interest_slug,
                ci.description,
                cia.assigned_by,
                cia.assigned_at

            FROM content_interest_assignment cia

            INNER JOIN content_interest ci
                ON ci.interest_id =
                    cia.interest_id

            WHERE cia.content_type = ?
              AND cia.content_id = ?

            ORDER BY
                ci.sort_order ASC,
                ci.interest_name ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare content topic lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'si',
            $contentType,
            $contentId
        );

        $stmt->execute();

        $assignments =
            $stmt->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach (
            $assignments as &$assignment
        ) {
            $assignment['content_interest_assignment_id'] = (int) (
                $assignment['content_interest_assignment_id']
                ?? 0
            );

            $assignment['content_id'] =
                (int) (
                    $assignment['content_id']
                    ?? 0
                );

            $assignment['interest_id'] =
                (int) (
                    $assignment['interest_id']
                    ?? 0
                );

            $assignment['assigned_by'] =
                isset(
                    $assignment['assigned_by']
                )
                ? (int) $assignment['assigned_by']
                : null;
        }

        unset($assignment);

        return $assignments;
    }

    public function getAssignedInterestIds(
        string $contentType,
        int $contentId
    ): array {
        return array_values(
            array_map(
                static fn(
                    array $assignment
                ): int =>
                (int) (
                    $assignment['interest_id']
                    ?? 0
                ),
                $this->getAssignments(
                    $contentType,
                    $contentId
                )
            )
        );
    }


    public function getAssignmentMap(
        string $contentType,
        array $contentIds
    ): array {
        $contentType =
            strtolower(
                trim(
                    $contentType
                )
            );

        $this->getContentDefinition(
            $contentType
        );

        $contentIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(
                                mixed $contentId
                            ): int =>
                            (int) $contentId,
                            $contentIds
                        ),
                        static fn(
                            int $contentId
                        ): bool =>
                        $contentId > 0
                    )
                )
            );

        if ($contentIds === []) {
            return [];
        }

        $placeholders =
            implode(
                ', ',
                array_fill(
                    0,
                    count($contentIds),
                    '?'
                )
            );

        $parameterTypes =
            's'
            . str_repeat(
                'i',
                count($contentIds)
            );

        $parameters = [
            $contentType,
            ...$contentIds
        ];

        $stmt =
            $this->conn->prepare("
            SELECT
                cia.content_id,
                cia.interest_id,
                ci.interest_name,
                ci.interest_slug

            FROM content_interest_assignment cia

            INNER JOIN content_interest ci
                ON ci.interest_id =
                    cia.interest_id
               AND ci.status = 'Active'

            WHERE cia.content_type = ?
              AND cia.content_id
                  IN ({$placeholders})

            ORDER BY
                cia.content_id ASC,
                ci.sort_order ASC,
                ci.interest_name ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare bulk content topic lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            $parameterTypes,
            ...$parameters
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load bulk content topics: '
                    . $error
            );
        }

        $rows =
            $stmt->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        $assignmentMap = [];

        foreach ($rows as $row) {
            $contentId =
                (int) (
                    $row['content_id']
                    ?? 0
                );

            $interestId =
                (int) (
                    $row['interest_id']
                    ?? 0
                );

            if (
                $contentId <= 0 ||
                $interestId <= 0
            ) {
                continue;
            }

            $assignmentMap[$contentId][] = [
                'interest_id' =>
                $interestId,

                'interest_name' =>
                trim(
                    (string) (
                        $row['interest_name']
                        ?? ''
                    )
                ),

                'interest_slug' =>
                trim(
                    (string) (
                        $row['interest_slug']
                        ?? ''
                    )
                )
            ];
        }

        return $assignmentMap;
    }

    /* ==========================================
       REPLACE CONTENT TOPICS
    ========================================== */

    public function replaceAssignments(
        string $contentType,
        int $contentId,
        array $interestIds,
        int $assignedBy
    ): array {
        $this->getContentDefinition(
            $contentType
        );

        if (
            $contentId <= 0 ||
            $assignedBy <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content or assigning user ID.'
            );
        }

        if (
            !$this->contentExists(
                $contentType,
                $contentId
            )
        ) {
            throw new RuntimeException(
                'The selected content could not be found.'
            );
        }

        $interestIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(
                                mixed $interestId
                            ): int =>
                            (int) $interestId,
                            $interestIds
                        ),
                        static fn(
                            int $interestId
                        ): bool =>
                        $interestId > 0
                    )
                )
            );

        $activeInterestIds = [];

        foreach (
            $this->getActiveInterests()
            as $interest
        ) {
            $activeInterestIds[(int) $interest['interest_id']] = true;
        }

        foreach (
            $interestIds as $interestId
        ) {
            if (
                !isset(
                    $activeInterestIds[$interestId]
                )
            ) {
                throw new InvalidArgumentException(
                    'One or more selected content topics are inactive or unavailable.'
                );
            }
        }

        $this->conn->begin_transaction();

        try {
            $deleteStmt =
                $this->conn->prepare("
                    DELETE FROM
                        content_interest_assignment

                    WHERE content_type = ?
                      AND content_id = ?
                ");

            if (!$deleteStmt) {
                throw new RuntimeException(
                    'Unable to prepare existing topic cleanup: '
                        . $this->conn->error
                );
            }

            $deleteStmt->bind_param(
                'si',
                $contentType,
                $contentId
            );

            $deleteStmt->execute();
            $deleteStmt->close();

            if ($interestIds !== []) {
                $insertStmt =
                    $this->conn->prepare("
                        INSERT INTO content_interest_assignment
                        (
                            content_type,
                            content_id,
                            interest_id,
                            assigned_by,
                            assigned_at
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            NOW()
                        )
                    ");

                if (!$insertStmt) {
                    throw new RuntimeException(
                        'Unable to prepare content topic assignment: '
                            . $this->conn->error
                    );
                }

                foreach (
                    $interestIds as $interestId
                ) {
                    $insertStmt->bind_param(
                        'siii',
                        $contentType,
                        $contentId,
                        $interestId,
                        $assignedBy
                    );

                    $insertStmt->execute();
                }

                $insertStmt->close();
            }

            $this->conn->commit();

            return $this->getAssignments(
                $contentType,
                $contentId
            );
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       CONTENT VALIDATION
    ========================================== */

    private function contentExists(
        string $contentType,
        int $contentId
    ): bool {
        $definition =
            $this->getContentDefinition(
                $contentType
            );

        $table =
            $definition['table'];

        $primaryKey =
            $definition['primary_key'];

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total

            FROM {$table}

            WHERE {$primaryKey} = ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare content validation: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $contentId
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

    private function getContentDefinition(
        string $contentType
    ): array {
        $contentType =
            strtolower(
                trim(
                    $contentType
                )
            );

        if (
            !isset(
                self::CONTENT_MAP[$contentType]
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported content topic type.'
            );
        }

        return self::CONTENT_MAP[$contentType];
    }
}
