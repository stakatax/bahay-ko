<?php

require_once 'BaseModel.php';

class Dashboard extends BaseModel
{
    private const STUDENT_SURVEY_MINIMUM_GROUP_SIZE = 5;

    public function getStatistics()
    {
        return [
            'users' => $this->countUsers(),
            'announcements' => $this->countAnnouncements(),
            'events' => $this->countEvents(),
            'documents' => $this->countDocuments(),
            'surveys' => $this->countSurveys()
        ];
    }

    private function countUsers()
    {
        $result = $this->conn->query("SELECT COUNT(*) total FROM user");
        return (int)$result->fetch_assoc()['total'];
    }

    private function countAnnouncements(): int
    {
        $result =
            $this->conn->query("
            SELECT COUNT(*) AS total
            FROM announcements
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to count announcements: '
                    . $this->conn->error
            );
        }

        return (int) (
            $result->fetch_assoc()['total']
            ?? 0
        );
    }

    private function countEvents(): int
    {
        $result =
            $this->conn->query("
            SELECT COUNT(*) AS total
            FROM events
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to count events: '
                    . $this->conn->error
            );
        }

        return (int) (
            $result->fetch_assoc()['total']
            ?? 0
        );
    }

    private function countDocuments(): int
    {
        $result =
            $this->conn->query("
            SELECT COUNT(*) AS total
            FROM documents
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to count documents: '
                    . $this->conn->error
            );
        }

        return (int) (
            $result->fetch_assoc()['total']
            ?? 0
        );
    }

    private function countSurveys(): int
    {
        $result =
            $this->conn->query("
            SELECT COUNT(*) AS total
            FROM survey
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to count surveys: '
                    . $this->conn->error
            );
        }

        return (int) (
            $result->fetch_assoc()['total']
            ?? 0
        );
    }

    /* ==========================================
   CONTENT WORKFLOW STATISTICS
========================================== */

    public function getWorkflowStatistics(): array
    {
        $result =
            $this->conn->query("
            SELECT
                workflow_status,
                COUNT(*) AS total

            FROM (
                SELECT workflow_status
                FROM announcements

                UNION ALL

                SELECT workflow_status
                FROM events

                UNION ALL

                SELECT workflow_status
                FROM documents

                UNION ALL

                SELECT workflow_status
                FROM survey
            ) AS content_workflow

            GROUP BY workflow_status
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load workflow statistics: '
                    . $this->conn->error
            );
        }

        $statistics = [
            'pending_review' => 0,
            'approved' => 0,
            'scheduled' => 0,
            'published' => 0
        ];

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $status =
                strtolower(
                    trim(
                        (string) (
                            $row['workflow_status']
                            ?? ''
                        )
                    )
                );

            if (
                array_key_exists(
                    $status,
                    $statistics
                )
            ) {
                $statistics[$status] =
                    (int) (
                        $row['total']
                        ?? 0
                    );
            }
        }

        return $statistics;
    }

    /* ==========================================
       ANALYTICS DATE RANGE
    ========================================== */

    private function normalizeAnalyticsRange(
        string $range
    ): string {
        $range =
            strtolower(
                trim(
                    $range
                )
            );

        $allowedRanges = [
            '7',
            '30',
            '90',
            'all'
        ];

        return in_array(
            $range,
            $allowedRanges,
            true
        )
            ? $range
            : '30';
    }

    private function buildDateRangeCondition(
        string $range,
        string $dateColumn
    ): string {
        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        if ($range === 'all') {
            return '';
        }

        $days =
            (int) $range;

        return "
            AND {$dateColumn} >=
                DATE_SUB(
                    NOW(),
                    INTERVAL {$days} DAY
                )
        ";
    }

    /* ==========================================
   SYSTEM ENGAGEMENT STATISTICS
========================================== */

    public function getEngagementStatistics(
        string $range = '30'
    ): array {
        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $viewCondition =
            $this->buildDateRangeCondition(
                $range,
                'viewed_at'
            );

        $reactionCondition =
            $this->buildDateRangeCondition(
                $range,
                'reacted_at'
            );

        $commentCondition =
            $this->buildDateRangeCondition(
                $range,
                'created_at'
            );

        $acknowledgmentCondition =
            $this->buildDateRangeCondition(
                $range,
                'acknowledged_at'
            );

        $result =
            $this->conn->query("
            SELECT
                (
                    SELECT COUNT(*)

                    FROM content_view

                    WHERE 1 = 1
                    {$viewCondition}
                ) AS views,

                (
                    SELECT COUNT(*)

                    FROM content_reaction

                    WHERE 1 = 1
                    {$reactionCondition}
                ) AS reactions,

                (
                    SELECT COUNT(*)

                    FROM content_comment

                    WHERE 1 = 1
                    {$commentCondition}
                ) AS comments,

                (
                    SELECT COUNT(*)

                    FROM content_acknowledgment

                    WHERE 1 = 1
                    {$acknowledgmentCondition}
                ) AS acknowledgments
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load engagement statistics: '
                    . $this->conn->error
            );
        }

        $row =
            $result->fetch_assoc()
            ?: [];

        return [
            'views' =>
            (int) (
                $row['views']
                ?? 0
            ),

            'reactions' =>
            (int) (
                $row['reactions']
                ?? 0
            ),

            'comments' =>
            (int) (
                $row['comments']
                ?? 0
            ),

            'acknowledgments' =>
            (int) (
                $row['acknowledgments']
                ?? 0
            )
        ];
    }


    /* ==========================================
   MOST AND LEAST VIEWED CONTENT
========================================== */

    public function getContentViewRankings(
        string $range = '30',
        int $limit = 5
    ): array {
        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $limit =
            max(
                1,
                min(
                    $limit,
                    20
                )
            );

        $announcementDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'COALESCE(
                    published_at,
                    created_at
                )'
            );

        $eventDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'created_at'
            );

        $documentDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'created_at'
            );

        $surveyDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'COALESCE(
                    published_at,
                    created_at
                )'
            );

        /*
         * This condition belongs inside the
         * LEFT JOIN. Keeping it there ensures
         * content with zero views remains part
         * of the Least Viewed ranking.
         */
        $viewDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'content_view.viewed_at'
            );

        $result =
            $this->conn->query("
            SELECT
                content.content_type,
                content.content_id,
                content.title,
                content.content_date,

                COUNT(
                    content_view.content_id
                ) AS view_count

            FROM (
                SELECT
                    'announcement'
                        AS content_type,

                    announcement_id
                        AS content_id,

                    title,

                    COALESCE(
                        published_at,
                        created_at
                    ) AS content_date

                FROM announcements

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                  {$announcementDateCondition}

                UNION ALL

                SELECT
                    'event'
                        AS content_type,

                    event_id
                        AS content_id,

                    title,

                    created_at
                        AS content_date

                FROM events

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                  {$eventDateCondition}

                UNION ALL

                SELECT
                    'document'
                        AS content_type,

                    document_id
                        AS content_id,

                    COALESCE(
                        NULLIF(
                            TRIM(title),
                            ''
                        ),
                        file_name
                    ) AS title,

                    created_at
                        AS content_date

                FROM documents

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                  {$documentDateCondition}

                UNION ALL

                SELECT
                    'survey'
                        AS content_type,

                    survey_id
                        AS content_id,

                    title,

                    COALESCE(
                        published_at,
                        created_at
                    ) AS content_date

                FROM survey

                WHERE status = 'Published'
                  AND workflow_status =
                        'published'

                  {$surveyDateCondition}
            ) AS content

            LEFT JOIN content_view

                ON content_view.content_type =
                   content.content_type

               AND content_view.content_id =
                   content.content_id

               {$viewDateCondition}

            GROUP BY
                content.content_type,
                content.content_id,
                content.title,
                content.content_date
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load content view rankings: '
                    . $this->conn->error
            );
        }

        $items =
            $result->fetch_all(
                MYSQLI_ASSOC
            );

        foreach ($items as &$item) {
            $item['content_id'] =
                (int) (
                    $item['content_id']
                    ?? 0
                );

            $item['view_count'] =
                (int) (
                    $item['view_count']
                    ?? 0
                );
        }

        unset($item);

        $mostViewed =
            $items;

        usort(
            $mostViewed,
            static function (
                array $first,
                array $second
            ): int {
                $viewComparison =
                    (
                        $second['view_count']
                        ?? 0
                    )
                    <=>
                    (
                        $first['view_count']
                        ?? 0
                    );

                if ($viewComparison !== 0) {
                    return $viewComparison;
                }

                return strtotime(
                    $second['content_date']
                        ?? '1970-01-01'
                )
                    <=>
                    strtotime(
                        $first['content_date']
                            ?? '1970-01-01'
                    );
            }
        );

        $leastViewed =
            $items;

        usort(
            $leastViewed,
            static function (
                array $first,
                array $second
            ): int {
                $viewComparison =
                    (
                        $first['view_count']
                        ?? 0
                    )
                    <=>
                    (
                        $second['view_count']
                        ?? 0
                    );

                if ($viewComparison !== 0) {
                    return $viewComparison;
                }

                return strtotime(
                    $second['content_date']
                        ?? '1970-01-01'
                )
                    <=>
                    strtotime(
                        $first['content_date']
                            ?? '1970-01-01'
                    );
            }
        );

        return [
            'most_viewed' =>
            array_slice(
                $mostViewed,
                0,
                $limit
            ),

            'least_viewed' =>
            array_slice(
                $leastViewed,
                0,
                $limit
            )
        ];
    }

    /* ==========================================
   POSTS PER DEPARTMENT
========================================== */

    public function getDepartmentPostingStatistics(
        string $range = '30'
    ): array {
        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $contentDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'content.content_date'
            );

        $departmentResult =
            $this->conn->query("
            SELECT
                department_id,
                department_name

            FROM department

            WHERE status = 'Active'

            ORDER BY
                department_name ASC
        ");

        if (!$departmentResult) {
            throw new RuntimeException(
                'Unable to load dashboard departments: '
                    . $this->conn->error
            );
        }

        $statistics = [];

        while (
            $department =
            $departmentResult->fetch_assoc()
        ) {
            $departmentId =
                (int) (
                    $department['department_id']
                    ?? 0
                );

            if ($departmentId <= 0) {
                continue;
            }

            $statistics[$departmentId] = [
                'department_id' =>
                $departmentId,

                'department_name' =>
                trim(
                    (string) (
                        $department['department_name']
                        ?? 'Unnamed Department'
                    )
                ),

                'total_posts' => 0,
                'published_posts' => 0,
                'announcement_count' => 0,
                'event_count' => 0,
                'document_count' => 0,
                'survey_count' => 0
            ];
        }

        $postingResult =
            $this->conn->query("
            SELECT
    CASE
        WHEN LOWER(
            COALESCE(
                r.role_prefix,
                ''
            )
        ) = 'admin'
        THEN NULL

        ELSE u.department_id
    END AS department_id,

                COUNT(*) AS total_posts,

                SUM(
                    content.workflow_status =
                    'published'
                ) AS published_posts,

                SUM(
                    content.content_type =
                    'announcement'
                ) AS announcement_count,

                SUM(
                    content.content_type =
                    'event'
                ) AS event_count,

                SUM(
                    content.content_type =
                    'document'
                ) AS document_count,

                SUM(
                    content.content_type =
                    'survey'
                ) AS survey_count

            FROM (
                SELECT
                    'announcement'
                        AS content_type,

                    user_id,
                    workflow_status,
                    created_at
                        AS content_date

                FROM announcements

                UNION ALL

                SELECT
                    'event'
                        AS content_type,

                    user_id,
                    workflow_status,
                    created_at
                        AS content_date

                FROM events

                UNION ALL

                SELECT
                    'document'
                        AS content_type,

                    user_id,
                    workflow_status,
                    created_at
                        AS content_date

                FROM documents

                UNION ALL

                SELECT
                    'survey'
                        AS content_type,

                    user_id,
                    workflow_status,
                    created_at
                        AS content_date

                FROM survey
            ) AS content

            LEFT JOIN user u
    ON u.user_id =
       content.user_id

LEFT JOIN role r
    ON r.role_id =
       u.role_id

WHERE 1 = 1

            {$contentDateCondition}

            GROUP BY
    CASE
        WHEN LOWER(
            COALESCE(
                r.role_prefix,
                ''
            )
        ) = 'admin'
        THEN NULL

        ELSE u.department_id
    END

        ");

        if (!$postingResult) {
            throw new RuntimeException(
                'Unable to load department posting statistics: '
                    . $this->conn->error
            );
        }

        $unassigned = null;

        while (
            $row =
            $postingResult->fetch_assoc()
        ) {
            $departmentId =
                (int) (
                    $row['department_id']
                    ?? 0
                );

            $postingStatistics = [
                'department_id' =>
                $departmentId > 0
                    ? $departmentId
                    : null,

                'department_name' =>
                $departmentId > 0
                    ? (
                        $statistics[$departmentId]['department_name']
                        ?? 'Inactive or Unknown Department'
                    )
                    : 'School-wide / Administration',

                'total_posts' =>
                (int) (
                    $row['total_posts']
                    ?? 0
                ),

                'published_posts' =>
                (int) (
                    $row['published_posts']
                    ?? 0
                ),

                'announcement_count' =>
                (int) (
                    $row['announcement_count']
                    ?? 0
                ),

                'event_count' =>
                (int) (
                    $row['event_count']
                    ?? 0
                ),

                'document_count' =>
                (int) (
                    $row['document_count']
                    ?? 0
                ),

                'survey_count' =>
                (int) (
                    $row['survey_count']
                    ?? 0
                )
            ];

            if ($departmentId > 0) {
                $statistics[$departmentId] =
                    $postingStatistics;
            } else {
                $unassigned =
                    $postingStatistics;
            }
        }

        $items =
            array_values(
                $statistics
            );

        if ($unassigned !== null) {
            $items[] =
                $unassigned;
        }

        usort(
            $items,
            static function (
                array $first,
                array $second
            ): int {
                $countComparison =
                    (
                        $second['total_posts']
                        ?? 0
                    )
                    <=>
                    (
                        $first['total_posts']
                        ?? 0
                    );

                if ($countComparison !== 0) {
                    return $countComparison;
                }

                return strcmp(
                    (string) (
                        $first['department_name']
                        ?? ''
                    ),
                    (string) (
                        $second['department_name']
                        ?? ''
                    )
                );
            }
        );

        return $items;
    }

    /* ==========================================
       FACULTY DEPARTMENT CONTENT RANKINGS
    ========================================== */

    public function getDepartmentContentViewRankings(
        int $departmentId,
        string $range = '30',
        int $limit = 5
    ): array {
        if ($departmentId <= 0) {
            return [
                'most_viewed' => [],
                'least_viewed' => []
            ];
        }

        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $limit =
            max(
                1,
                min(
                    $limit,
                    20
                )
            );

        $announcementDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'COALESCE(
                    published_at,
                    created_at
                )'
            );

        $eventDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'created_at'
            );

        $documentDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'created_at'
            );

        $surveyDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'COALESCE(
                    published_at,
                    created_at
                )'
            );

        /*
         * Keep this condition inside the
         * LEFT JOIN so zero-view content remains
         * eligible for Least Viewed.
         */
        $viewDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'content_view.viewed_at'
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                content.content_type,
                content.content_id,
                content.title,
                content.content_date,

                COUNT(
                    content_view.content_id
                ) AS view_count

            FROM (
                SELECT
                    'announcement'
                        AS content_type,

                    announcement_id
                        AS content_id,

                    title,

                    COALESCE(
                        published_at,
                        created_at
                    ) AS content_date,

                    user_id

                FROM announcements

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                  {$announcementDateCondition}

                UNION ALL

                SELECT
                    'event',

                    event_id,

                    title,

                    created_at,

                    user_id

                FROM events

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                  {$eventDateCondition}

                UNION ALL

                SELECT
                    'document',

                    document_id,

                    COALESCE(
                        NULLIF(
                            TRIM(title),
                            ''
                        ),
                        file_name
                    ),

                    created_at,

                    user_id

                FROM documents

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                  {$documentDateCondition}

                UNION ALL

                SELECT
                    'survey',

                    survey_id,

                    title,

                    COALESCE(
                        published_at,
                        created_at
                    ),

                    user_id

                FROM survey

                WHERE status = 'Published'
                  AND workflow_status =
                        'published'

                  {$surveyDateCondition}
            ) AS content

            INNER JOIN user u
                ON u.user_id =
                   content.user_id

            INNER JOIN role r
                ON r.role_id =
                   u.role_id

            LEFT JOIN content_view

                ON content_view.content_type =
                   content.content_type

               AND content_view.content_id =
                   content.content_id

               {$viewDateCondition}

            WHERE u.department_id = ?

              AND LOWER(
                    COALESCE(
                        r.role_prefix,
                        ''
                    )
                  ) = 'faculty'

            GROUP BY
                content.content_type,
                content.content_id,
                content.title,
                content.content_date
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare department content rankings: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $departmentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load department content rankings: '
                    . $error
            );
        }

        $items =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach ($items as &$item) {
            $item['content_id'] =
                (int) (
                    $item['content_id']
                    ?? 0
                );

            $item['view_count'] =
                (int) (
                    $item['view_count']
                    ?? 0
                );

            $item['content_type'] =
                strtolower(
                    trim(
                        (string) (
                            $item['content_type']
                            ?? ''
                        )
                    )
                );
        }

        unset($item);

        $mostViewed =
            $items;

        usort(
            $mostViewed,
            static function (
                array $first,
                array $second
            ): int {
                $viewComparison =
                    (
                        $second['view_count']
                        ?? 0
                    )
                    <=>
                    (
                        $first['view_count']
                        ?? 0
                    );

                if ($viewComparison !== 0) {
                    return $viewComparison;
                }

                return strtotime(
                    $second['content_date']
                        ?? '1970-01-01'
                )
                    <=>
                    strtotime(
                        $first['content_date']
                            ?? '1970-01-01'
                    );
            }
        );

        $leastViewed =
            $items;

        usort(
            $leastViewed,
            static function (
                array $first,
                array $second
            ): int {
                $viewComparison =
                    (
                        $first['view_count']
                        ?? 0
                    )
                    <=>
                    (
                        $second['view_count']
                        ?? 0
                    );

                if ($viewComparison !== 0) {
                    return $viewComparison;
                }

                return strtotime(
                    $second['content_date']
                        ?? '1970-01-01'
                )
                    <=>
                    strtotime(
                        $first['content_date']
                            ?? '1970-01-01'
                    );
            }
        );

        return [
            'most_viewed' =>
            array_slice(
                $mostViewed,
                0,
                $limit
            ),

            'least_viewed' =>
            array_slice(
                $leastViewed,
                0,
                $limit
            )
        ];
    }


    /* ==========================================
       VERIFY FACULTY DEPARTMENT CONTENT ACCESS
    ========================================== */

    public function canDepartmentAccessContent(
        string $contentType,
        int $contentId,
        int $departmentId
    ): bool {
        $contentType =
            strtolower(
                trim(
                    $contentType
                )
            );

        if (
            $contentId <= 0 ||
            $departmentId <= 0
        ) {
            return false;
        }

        $contentTables = [
            'announcement' => [
                'table' =>
                'announcements',

                'id_column' =>
                'announcement_id'
            ],

            'event' => [
                'table' =>
                'events',

                'id_column' =>
                'event_id'
            ],

            'document' => [
                'table' =>
                'documents',

                'id_column' =>
                'document_id'
            ],

            'survey' => [
                'table' =>
                'survey',

                'id_column' =>
                'survey_id'
            ]
        ];

        if (
            !isset(
                $contentTables[$contentType]
            )
        ) {
            return false;
        }

        /*
         * Table and column names come only from
         * the internal whitelist above.
         */
        $table =
            $contentTables[$contentType]['table'];

        $idColumn =
            $contentTables[$contentType]['id_column'];

        $stmt =
            $this->conn->prepare("
            SELECT 1

            FROM {$table} content

            INNER JOIN user u
                ON u.user_id =
                   content.user_id

            INNER JOIN role r
                ON r.role_id =
                   u.role_id

            WHERE content.{$idColumn} = ?
              AND u.department_id = ?

              AND LOWER(
                    COALESCE(
                        r.role_prefix,
                        ''
                    )
                  ) = 'faculty'

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare department content authorization: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $contentId,
            $departmentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to verify department content access: '
                    . $error
            );
        }

        $allowed =
            $stmt
            ->get_result()
            ->num_rows > 0;

        $stmt->close();

        return $allowed;
    }

    /* ==========================================
       FACULTY DEPARTMENT ENGAGEMENT
    ========================================== */

    public function getDepartmentEngagementStatistics(
        int $departmentId,
        string $range = '30'
    ): array {
        if ($departmentId <= 0) {
            return [
                'views' => 0,
                'reactions' => 0,
                'comments' => 0,
                'acknowledgments' => 0
            ];
        }

        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $engagementDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'engagement.action_date'
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                engagement.engagement_type,
                COUNT(*) AS total

            FROM (
                SELECT
                    'views'
                        AS engagement_type,

                    content_type,
                    content_id,

                    viewed_at
                        AS action_date

                FROM content_view

                UNION ALL

                SELECT
                    'reactions',

                    content_type,
                    content_id,

                    reacted_at
                        AS action_date

                FROM content_reaction

                UNION ALL

                SELECT
                    'comments',

                    content_type,
                    content_id,

                    created_at
                        AS action_date

                FROM content_comment

                WHERE status = 'Active'

                UNION ALL

                SELECT
                    'acknowledgments',

                    content_type,
                    content_id,

                    acknowledged_at
                        AS action_date

                FROM content_acknowledgment
            ) AS engagement

            INNER JOIN (
                SELECT
                    'announcement'
                        AS content_type,

                    announcement_id
                        AS content_id,

                    user_id

                FROM announcements

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                UNION ALL

                SELECT
                    'event',

                    event_id,

                    user_id

                FROM events

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                UNION ALL

                SELECT
                    'document',

                    document_id,

                    user_id

                FROM documents

                WHERE status = 'active'
                  AND workflow_status =
                        'published'

                UNION ALL

                SELECT
                    'survey',

                    survey_id,

                    user_id

                FROM survey

                WHERE status = 'Published'
                  AND workflow_status =
                        'published'
            ) AS department_content

                ON department_content.content_type =
                   engagement.content_type

               AND department_content.content_id =
                   engagement.content_id

            INNER JOIN user u
                ON u.user_id =
                   department_content.user_id

            INNER JOIN role r
                ON r.role_id =
                   u.role_id

            WHERE u.department_id = ?

              AND LOWER(
                    COALESCE(
                        r.role_prefix,
                        ''
                    )
                  ) = 'faculty'

              {$engagementDateCondition}

            GROUP BY
                engagement.engagement_type
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare department engagement statistics: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $departmentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load department engagement statistics: '
                    . $error
            );
        }

        $statistics = [
            'views' => 0,
            'reactions' => 0,
            'comments' => 0,
            'acknowledgments' => 0
        ];

        $result =
            $stmt->get_result();

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $engagementType =
                strtolower(
                    trim(
                        (string) (
                            $row['engagement_type']
                            ?? ''
                        )
                    )
                );

            if (
                array_key_exists(
                    $engagementType,
                    $statistics
                )
            ) {
                $statistics[$engagementType] =
                    (int) (
                        $row['total']
                        ?? 0
                    );
            }
        }

        $stmt->close();

        return $statistics;
    }

    /* ==========================================
       DEPARTMENT CONTENT BREAKDOWN
    ========================================== */

    public function getDepartmentContentBreakdown(
        int $departmentId,
        string $range = '30',
        int $limit = 50
    ): array {
        if ($departmentId <= 0) {
            return [];
        }

        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $limit =
            max(
                1,
                min(
                    $limit,
                    100
                )
            );

        /*
         * Validate that the requested department
         * exists and is currently active.
         */
        $departmentStmt =
            $this->conn->prepare("
            SELECT
                department_id,
                department_name

            FROM department

            WHERE department_id = ?
              AND status = 'Active'

            LIMIT 1
        ");

        if (!$departmentStmt) {
            throw new RuntimeException(
                'Unable to prepare department lookup: '
                    . $this->conn->error
            );
        }

        $departmentStmt->bind_param(
            'i',
            $departmentId
        );

        if (!$departmentStmt->execute()) {
            $error =
                $departmentStmt->error;

            $departmentStmt->close();

            throw new RuntimeException(
                'Unable to load department: '
                    . $error
            );
        }

        $department =
            $departmentStmt
            ->get_result()
            ->fetch_assoc();

        $departmentStmt->close();

        if (!$department) {
            return [];
        }

        $contentDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'department_content.content_date'
            );

        $contentStmt =
            $this->conn->prepare("
            SELECT
    department_content.content_type,
    department_content.content_id,
    department_content.user_id,
    department_content.title,
                department_content.workflow_status,
                department_content.content_status,
                department_content.content_date,

                TRIM(
                    CONCAT(
                        COALESCE(
                            u.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            u.last_name,
                            ''
                        )
                    )
                ) AS author_name

            FROM (
                SELECT
                    'announcement'
                        AS content_type,

                    announcement_id
                        AS content_id,

                    title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM announcements

                UNION ALL

                SELECT
                    'event'
                        AS content_type,

                    event_id
                        AS content_id,

                    title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM events

                UNION ALL

                SELECT
                    'document'
                        AS content_type,

                    document_id
                        AS content_id,

                    COALESCE(
                        NULLIF(
                            TRIM(title),
                            ''
                        ),
                        file_name
                    ) AS title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM documents

                UNION ALL

                SELECT
                    'survey'
                        AS content_type,

                    survey_id
                        AS content_id,

                    title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM survey
            ) AS department_content

           INNER JOIN user u
    ON u.user_id =
       department_content.user_id

LEFT JOIN role r
    ON r.role_id =
       u.role_id

WHERE u.department_id = ?

  AND LOWER(
        COALESCE(
            r.role_prefix,
            ''
        )
      ) <> 'admin'

            {$contentDateCondition}

            ORDER BY
                department_content.content_date DESC,
                department_content.content_id DESC

            LIMIT ?
        ");

        if (!$contentStmt) {
            throw new RuntimeException(
                'Unable to prepare department content breakdown: '
                    . $this->conn->error
            );
        }

        $contentStmt->bind_param(
            'ii',
            $departmentId,
            $limit
        );

        if (!$contentStmt->execute()) {
            $error =
                $contentStmt->error;

            $contentStmt->close();

            throw new RuntimeException(
                'Unable to load department content breakdown: '
                    . $error
            );
        }

        $items =
            $contentStmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $contentStmt->close();

        foreach ($items as &$item) {
            $item['content_id'] =
                (int) (
                    $item['content_id']
                    ?? 0
                );

            $item['user_id'] =
                (int) (
                    $item['user_id']
                    ?? 0
                );

            $item['content_type'] =
                strtolower(
                    trim(
                        (string) (
                            $item['content_type']
                            ?? ''
                        )
                    )
                );

            $item['workflow_status'] =
                strtolower(
                    trim(
                        (string) (
                            $item['workflow_status']
                            ?? ''
                        )
                    )
                );
        }

        unset($item);

        return [
            'department_id' =>
            (int) (
                $department['department_id']
                ?? 0
            ),

            'department_name' =>
            trim(
                (string) (
                    $department['department_name']
                    ?? 'Unknown Department'
                )
            ),

            'range' =>
            $range,

            'items' =>
            $items
        ];
    }

    /* ==========================================
       SCHOOL-WIDE CONTENT BREAKDOWN
    ========================================== */

    public function getSchoolwideContentBreakdown(
        string $range = '30',
        int $limit = 50
    ): array {
        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $limit =
            max(
                1,
                min(
                    $limit,
                    100
                )
            );

        $contentDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'schoolwide_content.content_date'
            );

        $stmt =
            $this->conn->prepare("
            SELECT
    schoolwide_content.content_type,
    schoolwide_content.content_id,
    schoolwide_content.user_id,
    schoolwide_content.title,
                schoolwide_content.workflow_status,
                schoolwide_content.content_status,
                schoolwide_content.content_date,

                COALESCE(
                    NULLIF(
                        TRIM(
                            CONCAT(
                                COALESCE(
                                    u.first_name,
                                    ''
                                ),
                                ' ',
                                COALESCE(
                                    u.last_name,
                                    ''
                                )
                            )
                        ),
                        ''
                    ),
                    'System Administration'
                ) AS author_name

            FROM (
                SELECT
                    'announcement'
                        AS content_type,

                    announcement_id
                        AS content_id,

                    title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM announcements

                UNION ALL

                SELECT
                    'event'
                        AS content_type,

                    event_id
                        AS content_id,

                    title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM events

                UNION ALL

                SELECT
                    'document'
                        AS content_type,

                    document_id
                        AS content_id,

                    COALESCE(
                        NULLIF(
                            TRIM(title),
                            ''
                        ),
                        file_name
                    ) AS title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM documents

                UNION ALL

                SELECT
                    'survey'
                        AS content_type,

                    survey_id
                        AS content_id,

                    title,

                    workflow_status,

                    status
                        AS content_status,

                    created_at
                        AS content_date,

                    user_id

                FROM survey
            ) AS schoolwide_content

            LEFT JOIN user u
                ON u.user_id =
                   schoolwide_content.user_id

            LEFT JOIN role r
                ON r.role_id =
                   u.role_id

            WHERE (
                LOWER(
                    COALESCE(
                        r.role_prefix,
                        ''
                    )
                ) = 'admin'

                OR u.department_id IS NULL
            )

            {$contentDateCondition}

            ORDER BY
                schoolwide_content.content_date DESC,
                schoolwide_content.content_id DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare school-wide content breakdown: '
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
                'Unable to load school-wide content breakdown: '
                    . $error
            );
        }

        $items =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach ($items as &$item) {
            $item['content_id'] =
                (int) (
                    $item['content_id']
                    ?? 0
                );

            $item['user_id'] =
                (int) (
                    $item['user_id']
                    ?? 0
                );

            $item['content_type'] =
                strtolower(
                    trim(
                        (string) (
                            $item['content_type']
                            ?? ''
                        )
                    )
                );

            $item['workflow_status'] =
                strtolower(
                    trim(
                        (string) (
                            $item['workflow_status']
                            ?? ''
                        )
                    )
                );
        }

        unset($item);

        return [
            'department_id' =>
            null,

            'scope' =>
            'schoolwide',

            'department_name' =>
            'School-wide / Administration',

            'range' =>
            $range,

            'items' =>
            $items
        ];
    }

    /* ==========================================
   RECENT SYSTEM ACTIVITY
========================================== */

    public function getRecentActivity(
        string $range = '30',
        int $limit = 8
    ): array {
        $range =
            $this->normalizeAnalyticsRange(
                $range
            );

        $limit =
            max(
                1,
                min(
                    $limit,
                    30
                )
            );

        $activityDateCondition =
            $this->buildDateRangeCondition(
                $range,
                'activity_log.timestamp'
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                activity_log.log_id,

                COALESCE(
                    actions.action_name,
                    'SYSTEM_ACTIVITY'
                ) AS action,

                activity_log.description,
                activity_log.timestamp

            FROM activity_log

            LEFT JOIN actions
                ON actions.action_id =
                   activity_log.action_id

            WHERE 1 = 1

            {$activityDateCondition}

            ORDER BY
                activity_log.timestamp DESC,
                activity_log.log_id DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare recent activity: '
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
                'Unable to load recent activity: '
                    . $error
            );
        }

        $activity =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $activity;
    }

    /* ==========================================
   RULE-BASED ADMIN ACTION CENTER
========================================== */

    public function getActionableInsights(): array
    {
        $insights = [];

        /*
     * Rule 1:
     * Pending reviews older than 24 hours.
     */
        $pendingResult =
            $this->conn->query("
            SELECT
                COUNT(*) AS total

            FROM (
                SELECT
                    created_at,
                    workflow_status
                FROM announcements

                UNION ALL

                SELECT
                    created_at,
                    workflow_status
                FROM events

                UNION ALL

                SELECT
                    created_at,
                    workflow_status
                FROM documents

                UNION ALL

                SELECT
                    created_at,
                    workflow_status
                FROM survey
            ) AS pending_content

            WHERE workflow_status =
                    'pending_review'

              AND created_at <=
                    DATE_SUB(
                        NOW(),
                        INTERVAL 24 HOUR
                    )
        ");

        if (!$pendingResult) {
            throw new RuntimeException(
                'Unable to evaluate pending-review insights: '
                    . $this->conn->error
            );
        }

        $stalePendingCount =
            (int) (
                $pendingResult
                    ->fetch_assoc()['total']
                ?? 0
            );

        if ($stalePendingCount > 0) {
            $insights[] = [
                'severity' => 'critical',
                'icon' => 'fa-solid fa-hourglass-half',
                'title' => 'Review delay detected',
                'message' =>
                $stalePendingCount
                    . ' '
                    . (
                        $stalePendingCount === 1
                        ? 'submission has'
                        : 'submissions have'
                    )
                    . ' waited for review for more than 24 hours.',
                'action_label' => 'Review pending content',
                'action_url' =>
                'index.php?page=content_workspace'
                    . '&status=pending_review'
            ];
        }

        /*
     * Rule 2:
     * Published content older than seven days
     * with no recorded views.
     */
        $lowReachResult =
            $this->conn->query("
            SELECT
                COUNT(*) AS total

            FROM (
                SELECT
                    content.content_type,
                    content.content_id

                FROM (
                    SELECT
                        'announcement'
                            AS content_type,

                        announcement_id
                            AS content_id,

                        COALESCE(
                            published_at,
                            created_at
                        ) AS content_date

                    FROM announcements

                    WHERE status = 'active'
                      AND workflow_status =
                            'published'

                    UNION ALL

                    SELECT
                        'event',
                        event_id,
                        created_at

                    FROM events

                    WHERE status = 'active'
                      AND workflow_status =
                            'published'

                    UNION ALL

                    SELECT
                        'document',
                        document_id,
                        created_at

                    FROM documents

                    WHERE status = 'active'
                      AND workflow_status =
                            'published'

                    UNION ALL

                    SELECT
                        'survey',
                        survey_id,

                        COALESCE(
                            published_at,
                            created_at
                        )

                    FROM survey

                    WHERE status = 'Published'
                      AND workflow_status =
                            'published'
                ) AS content

                LEFT JOIN content_view

                    ON content_view.content_type =
                       content.content_type

                   AND content_view.content_id =
                       content.content_id

                WHERE content.content_date <=
                        DATE_SUB(
                            NOW(),
                            INTERVAL 7 DAY
                        )

                GROUP BY
                    content.content_type,
                    content.content_id

                HAVING COUNT(
                    content_view.content_id
                ) = 0
            ) AS zero_view_content
        ");

        if (!$lowReachResult) {
            throw new RuntimeException(
                'Unable to evaluate content-reach insights: '
                    . $this->conn->error
            );
        }

        $lowReachCount =
            (int) (
                $lowReachResult
                    ->fetch_assoc()['total']
                ?? 0
            );

        if ($lowReachCount > 0) {
            $insights[] = [
                'severity' => 'warning',
                'icon' => 'fa-solid fa-eye-slash',
                'title' => 'Low content reach',
                'message' =>
                $lowReachCount
                    . ' published '
                    . (
                        $lowReachCount === 1
                        ? 'item has'
                        : 'items have'
                    )
                    . ' received no views after seven days.',
                'action_label' => 'Inspect least viewed content',
                'action_url' =>
                'index.php?page=dashboard'
                    . '#content-view-rankings'
            ];
        }

        /*
     * Rule 3:
     * Open Surveys closing within 72 hours
     * without any responses.
     */
        $surveyResult =
            $this->conn->query("
            SELECT
                COUNT(*) AS total

            FROM survey

            WHERE status = 'Published'
              AND workflow_status =
                    'published'

              AND closes_at > NOW()

              AND closes_at <=
                    DATE_ADD(
                        NOW(),
                        INTERVAL 72 HOUR
                    )

              AND NOT EXISTS (
                    SELECT 1

                    FROM survey_response

                    WHERE survey_response.survey_id =
                          survey.survey_id
              )
        ");

        if (!$surveyResult) {
            throw new RuntimeException(
                'Unable to evaluate Survey insights: '
                    . $this->conn->error
            );
        }

        $surveyAttentionCount =
            (int) (
                $surveyResult
                    ->fetch_assoc()['total']
                ?? 0
            );

        if ($surveyAttentionCount > 0) {
            $insights[] = [
                'severity' => 'warning',
                'icon' => 'fa-solid fa-square-poll-horizontal',
                'title' => 'Survey participation needed',
                'message' =>
                $surveyAttentionCount
                    . ' '
                    . (
                        $surveyAttentionCount === 1
                        ? 'Survey closes'
                        : 'Surveys close'
                    )
                    . ' within 72 hours without a response.',
                'action_label' => 'Review open Surveys',
                'action_url' =>
                'index.php?page=news'
            ];
        }

        /*
     * Rule 4:
     * Published Events beginning within 24 hours.
     *
     * The nearest matching Event becomes the
     * direct destination of the insight.
     */
        $eventResult =
            $this->conn->query("
        SELECT
            event_id,
            title,
            event_date

        FROM events

        WHERE status = 'active'
          AND workflow_status = 'published'
          AND event_date >= NOW()

          AND event_date <=
                DATE_ADD(
                    NOW(),
                    INTERVAL 24 HOUR
                )

        ORDER BY
            event_date ASC,
            event_id ASC
    ");

        if (!$eventResult) {
            throw new RuntimeException(
                'Unable to evaluate Event insights: '
                    . $this->conn->error
            );
        }

        $upcomingEventCount =
            $eventResult->num_rows;

        $nearestEvent =
            $eventResult->fetch_assoc();

        if (
            $upcomingEventCount > 0 &&
            $nearestEvent
        ) {
            $nearestEventId =
                (int) (
                    $nearestEvent['event_id']
                    ?? 0
                );

            $nearestEventTitle =
                trim(
                    (string) (
                        $nearestEvent['title']
                        ?? ''
                    )
                );

            $insights[] = [
                'severity' =>
                'information',

                'icon' =>
                'fa-solid fa-calendar-day',

                'title' =>
                'Upcoming Event window',

                'message' =>
                $upcomingEventCount
                    . ' published '
                    . (
                        $upcomingEventCount === 1
                        ? 'Event begins'
                        : 'Events begin'
                    )
                    . ' within the next 24 hours.'
                    . (
                        $nearestEventTitle !== ''
                        ? ' Nearest: '
                        . $nearestEventTitle
                        . '.'
                        : ''
                    ),

                'action_label' =>
                'Open nearest Event',

                'action_url' =>
                'index.php?page=news'
                    . '&open_type=event'
                    . '&open_id='
                    . $nearestEventId
            ];
        }

        /*
     * Rule 5:
     * Published acknowledgment-required content
     * with no acknowledgments.
     *
     * The first affected item becomes the
     * direct destination of the insight.
     */
        $acknowledgmentResult =
            $this->conn->query("
        SELECT
            acknowledgment_content.content_type,
            acknowledgment_content.content_id,
            acknowledgment_content.content_title

        FROM (
            SELECT
                'announcement'
                    AS content_type,

                announcement_id
                    AS content_id,

                title
                    AS content_title,

                CASE LOWER(priority)
                    WHEN 'emergency' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'important' THEN 3
                    WHEN 'high' THEN 4
                    ELSE 5
                END
                    AS attention_order,

                COALESCE(
                    published_at,
                    created_at
                )
                    AS content_date

            FROM announcements

            WHERE status = 'active'
              AND workflow_status = 'published'
              AND require_acknowledgment = 1

            UNION ALL

            SELECT
                'event',
                event_id,
                title,
                6,
                created_at

            FROM events

            WHERE status = 'active'
              AND workflow_status = 'published'
              AND require_acknowledgment = 1

            UNION ALL

            SELECT
                'document',
                document_id,

                COALESCE(
                    NULLIF(
                        TRIM(title),
                        ''
                    ),
                    file_name
                ),

                7,
                created_at

            FROM documents

            WHERE status = 'active'
              AND workflow_status = 'published'
              AND require_acknowledgment = 1

            UNION ALL

            SELECT
                'survey',
                survey_id,
                title,
                8,

                COALESCE(
                    published_at,
                    created_at
                )

            FROM survey

            WHERE status = 'Published'
              AND workflow_status = 'published'
              AND require_acknowledgment = 1
        ) AS acknowledgment_content

        WHERE NOT EXISTS (
            SELECT 1

            FROM content_acknowledgment

            WHERE content_acknowledgment.content_type =
                  acknowledgment_content.content_type

              AND content_acknowledgment.content_id =
                  acknowledgment_content.content_id
        )

        ORDER BY
            acknowledgment_content.attention_order ASC,
            acknowledgment_content.content_date DESC,
            acknowledgment_content.content_id DESC
    ");

        if (!$acknowledgmentResult) {
            throw new RuntimeException(
                'Unable to evaluate acknowledgment insights: '
                    . $this->conn->error
            );
        }

        $missingAcknowledgmentCount =
            $acknowledgmentResult->num_rows;

        $firstMissingAcknowledgment =
            $acknowledgmentResult->fetch_assoc();

        if (
            $missingAcknowledgmentCount > 0 &&
            $firstMissingAcknowledgment
        ) {
            $missingContentType =
                strtolower(
                    trim(
                        (string) (
                            $firstMissingAcknowledgment['content_type']
                            ?? ''
                        )
                    )
                );

            $missingContentId =
                (int) (
                    $firstMissingAcknowledgment['content_id']
                    ?? 0
                );

            $missingContentTitle =
                trim(
                    (string) (
                        $firstMissingAcknowledgment['content_title']
                        ?? ''
                    )
                );

            $actionUrl =
                'index.php?page=news';

            if (
                $missingContentType !== '' &&
                $missingContentId > 0
            ) {
                $actionUrl .=
                    '&open_type='
                    . urlencode(
                        $missingContentType
                    )
                    . '&open_id='
                    . $missingContentId;
            }

            $insights[] = [
                'severity' =>
                'warning',

                'icon' =>
                'fa-solid fa-check-double',

                'title' =>
                'Acknowledgment follow-up',

                'message' =>
                $missingAcknowledgmentCount
                    . ' required-acknowledgment '
                    . (
                        $missingAcknowledgmentCount === 1
                        ? 'item has'
                        : 'items have'
                    )
                    . ' no recorded acknowledgment.'
                    . (
                        $missingContentTitle !== ''
                        ? ' First item: '
                        . $missingContentTitle
                        . '.'
                        : ''
                    ),

                'action_label' =>
                'Open first affected item',

                'action_url' =>
                $actionUrl
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'severity' => 'success',
                'icon' => 'fa-solid fa-circle-check',
                'title' => 'No immediate action required',
                'message' =>
                'The current workflow, reach, Events, Surveys, '
                    . 'and acknowledgment rules found no urgent issue.',
                'action_label' => 'Open Information Hub',
                'action_url' =>
                'index.php?page=news'
            ];
        }

        return $insights;
    }


    /* ==========================================
       STUDENT PROFILE SURVEY ANALYTICS
    ========================================== */

    public function getStudentSurveyAnalytics(): array
    {
        $completionResult =
            $this->conn->query("
                SELECT
                    COUNT(*) AS total_students,

                    SUM(
                        CASE
                            WHEN COALESCE(
                                profile.survey_completion_status,
                                'NotStarted'
                            ) = 'NotStarted'
                            THEN 1
                            ELSE 0
                        END
                    ) AS not_started,

                    SUM(
                        CASE
                            WHEN profile.survey_completion_status =
                                'InProgress'
                            THEN 1
                            ELSE 0
                        END
                    ) AS in_progress,

                    SUM(
                        CASE
                            WHEN profile.survey_completion_status =
                                'Completed'
                            THEN 1
                            ELSE 0
                        END
                    ) AS completed

                FROM user student

                INNER JOIN role student_role
                    ON student_role.role_id =
                       student.role_id

                LEFT JOIN student_profile profile
                    ON profile.user_id =
                       student.user_id

                WHERE student.status = 'Active'
                  AND student_role.role_prefix =
                      'Student'
            ");

        if (!$completionResult) {
            throw new RuntimeException(
                'Unable to load Student survey completion analytics: '
                    . $this->conn->error
            );
        }

        $completionRow =
            $completionResult
            ->fetch_assoc();

        $totalStudents =
            (int) (
                $completionRow['total_students']
                ?? 0
            );

        $notStarted =
            (int) (
                $completionRow['not_started']
                ?? 0
            );

        $inProgress =
            (int) (
                $completionRow['in_progress']
                ?? 0
            );

        $completed =
            (int) (
                $completionRow['completed']
                ?? 0
            );

        $completionRate =
            $totalStudents > 0
            ? round(
                (
                    $completed /
                    $totalStudents
                ) * 100,
                1
            )
            : 0.0;

        $versionResult =
            $this->conn->query("
                SELECT
                    MAX(survey_version)
                        AS survey_version

                FROM student_profile_question

                WHERE status = 'Active'
            ");

        if (!$versionResult) {
            throw new RuntimeException(
                'Unable to resolve the active Student survey version: '
                    . $this->conn->error
            );
        }

        $versionRow =
            $versionResult
            ->fetch_assoc();

        $surveyVersion =
            (int) (
                $versionRow['survey_version']
                ?? 0
            );

        $baseAnalytics = [
            'survey_version' =>
            $surveyVersion,

            'minimum_group_size' =>
            self::STUDENT_SURVEY_MINIMUM_GROUP_SIZE,

            'completion' => [
                'total_students' =>
                $totalStudents,

                'not_started' =>
                $notStarted,

                'in_progress' =>
                $inProgress,

                'completed' =>
                $completed,

                'completion_rate' =>
                $completionRate
            ],

            'sections' =>
            []
        ];

        if ($surveyVersion <= 0) {
            return $baseAnalytics;
        }

        $questionStmt =
            $this->conn->prepare("
                SELECT
                    student_profile_question_id,
                    question_key,
                    section_key,
                    section_label,
                    section_sort_order,
                    question_text,
                    response_type,
                    is_sensitive,
                    sort_order

                FROM student_profile_question

                WHERE survey_version = ?
                  AND status = 'Active'
                  AND analytics_enabled = 1

                ORDER BY
                    section_sort_order ASC,
                    sort_order ASC,
                    student_profile_question_id ASC
            ");

        if (!$questionStmt) {
            throw new RuntimeException(
                'Unable to prepare Student survey analytics catalog: '
                    . $this->conn->error
            );
        }

        $questionStmt->bind_param(
            'i',
            $surveyVersion
        );

        if (!$questionStmt->execute()) {
            $error =
                $questionStmt->error;

            $questionStmt->close();

            throw new RuntimeException(
                'Unable to load Student survey analytics catalog: '
                    . $error
            );
        }

        $questionRows =
            $questionStmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $questionStmt->close();

        $questions = [];

        foreach (
            $questionRows
            as $questionRow
        ) {
            $questionId =
                (int) (
                    $questionRow['student_profile_question_id']
                    ?? 0
                );

            if ($questionId <= 0) {
                continue;
            }

            $questions[$questionId] = [
                'question_key' =>
                (string) (
                    $questionRow['question_key']
                    ?? ''
                ),

                'section_key' =>
                (string) (
                    $questionRow['section_key']
                    ?? ''
                ),

                'section_label' =>
                (string) (
                    $questionRow['section_label']
                    ?? ''
                ),

                'section_sort_order' =>
                (int) (
                    $questionRow['section_sort_order']
                    ?? 0
                ),

                'question_text' =>
                (string) (
                    $questionRow['question_text']
                    ?? ''
                ),

                'response_type' =>
                (string) (
                    $questionRow['response_type']
                    ?? ''
                ),

                'is_sensitive' =>
                !empty($questionRow['is_sensitive']),

                'sort_order' =>
                (int) (
                    $questionRow['sort_order']
                    ?? 0
                ),

                'respondent_count' =>
                0,

                'answer_counts' =>
                [],

                'answer_labels' =>
                []
            ];
        }

        if ($questions === []) {
            return $baseAnalytics;
        }

        $responseStmt =
            $this->conn->prepare("
                SELECT
                    question
                        .student_profile_question_id,

                    response
                        .student_profile_id,

                    response
                        .response_json

                FROM student_profile_response
                    response

                INNER JOIN student_profile_question
                    question

                    ON question.student_profile_question_id =
                       response.student_profile_question_id

                INNER JOIN student_profile
                    profile

                    ON profile.student_profile_id =
                       response.student_profile_id

                INNER JOIN user student
                    ON student.user_id =
                       profile.user_id

                INNER JOIN role student_role
                    ON student_role.role_id =
                       student.role_id

                WHERE question.survey_version = ?
                  AND question.status = 'Active'
                  AND question.analytics_enabled = 1

                  AND profile.survey_version = ?
                  AND profile.survey_completion_status =
                      'Completed'

                  AND student.status = 'Active'
                  AND student_role.role_prefix =
                      'Student'

                ORDER BY
                    question.student_profile_question_id ASC,
                    response.student_profile_id ASC
            ");

        if (!$responseStmt) {
            throw new RuntimeException(
                'Unable to prepare Student survey aggregate responses: '
                    . $this->conn->error
            );
        }

        $responseStmt->bind_param(
            'ii',
            $surveyVersion,
            $surveyVersion
        );

        if (!$responseStmt->execute()) {
            $error =
                $responseStmt->error;

            $responseStmt->close();

            throw new RuntimeException(
                'Unable to load Student survey aggregate responses: '
                    . $error
            );
        }

        $responseResult =
            $responseStmt
            ->get_result();

        while (
            $responseRow =
            $responseResult->fetch_assoc()
        ) {
            $questionId =
                (int) (
                    $responseRow['student_profile_question_id']
                    ?? 0
                );

            if (
                $questionId <= 0 ||
                !isset($questions[$questionId])
            ) {
                continue;
            }

            $decodedResponse =
                json_decode(
                    (string) (
                        $responseRow['response_json']
                        ?? 'null'
                    ),
                    true
                );

            if (
                json_last_error() !==
                JSON_ERROR_NONE
            ) {
                continue;
            }

            $normalizedAnswers =
                $this
                ->normalizeStudentSurveyAnalyticsAnswers(
                    $decodedResponse
                );

            if ($normalizedAnswers === []) {
                continue;
            }

            /*
             * A question has at most one response row
             * per Student profile. Multiple selections
             * still count as one respondent.
             */
            $questions[$questionId]['respondent_count']++;

            foreach (
                $normalizedAnswers
                as $answerKey =>
                $answerLabel
            ) {
                $questions[$questionId]['answer_counts'][$answerKey] =
                    (
                        $questions[$questionId]['answer_counts'][$answerKey]
                        ?? 0
                    ) + 1;

                $questions[$questionId]['answer_labels'][$answerKey] =
                    $answerLabel;
            }
        }

        $responseStmt->close();

        $sections = [];

        foreach (
            $questions
            as $question
        ) {
            $sectionKey =
                $question['section_key'];

            if ($sectionKey === '') {
                continue;
            }

            if (!isset($sections[$sectionKey])) {
                $sections[$sectionKey] = [
                    'section_key' =>
                    $sectionKey,

                    'section_label' =>
                    $question['section_label'],

                    'section_sort_order' =>
                    $question['section_sort_order'],

                    'questions' =>
                    []
                ];
            }

            $respondentCount =
                (int) (
                    $question['respondent_count']
                    ?? 0
                );

            $items = [];

            $suppressedCount = 0;

            foreach (
                $question['answer_counts']
                as $answerKey => $answerCount
            ) {
                $answerCount =
                    (int) $answerCount;

                if (
                    $answerCount <
                    self::STUDENT_SURVEY_MINIMUM_GROUP_SIZE
                ) {
                    $suppressedCount +=
                        $answerCount;

                    continue;
                }

                $items[] = [
                    'label' =>
                    (string) (
                        $question['answer_labels'][$answerKey]
                        ?? 'Response'
                    ),

                    'count' =>
                    $answerCount,

                    'percentage' =>
                    $respondentCount > 0
                        ? round(
                            (
                                $answerCount /
                                $respondentCount
                            ) * 100,
                            1
                        )
                        : 0.0
                ];
            }

            usort(
                $items,
                static function (
                    array $first,
                    array $second
                ): int {
                    $countComparison =
                        (
                            $second['count']
                            ?? 0
                        )
                        <=>
                        (
                            $first['count']
                            ?? 0
                        );

                    if ($countComparison !== 0) {
                        return $countComparison;
                    }

                    return strcasecmp(
                        (string) (
                            $first['label']
                            ?? ''
                        ),
                        (string) (
                            $second['label']
                            ?? ''
                        )
                    );
                }
            );

            $sections[$sectionKey]['questions'][] = [
                'question_key' =>
                $question['question_key'],

                'question_text' =>
                $question['question_text'],

                'response_type' =>
                $question['response_type'],

                'is_sensitive' =>
                $question['is_sensitive'],

                'respondent_count' =>
                $respondentCount,

                'available' =>
                $respondentCount >=
                    self::STUDENT_SURVEY_MINIMUM_GROUP_SIZE
                    &&
                    $items !== [],

                'items' =>
                $items,

                /*
                 * The count is disclosed without its
                 * category labels, preventing small
                 * answer groups from being identified.
                 */
                'suppressed_response_count' =>
                $suppressedCount
            ];
        }

        $baseAnalytics['sections'] =
            array_values(
                $sections
            );

        return $baseAnalytics;
    }

    private function normalizeStudentSurveyAnalyticsAnswers(
        mixed $decodedResponse
    ): array {
        $values =
            is_array($decodedResponse)
            ? $decodedResponse
            : [$decodedResponse];

        $normalizedAnswers = [];

        foreach ($values as $value) {
            if (
                is_array($value) ||
                is_object($value) ||
                $value === null
            ) {
                continue;
            }

            if (is_bool($value)) {
                $answerLabel =
                    $value
                    ? 'Yes'
                    : 'No';
            } else {
                $answerLabel =
                    trim(
                        preg_replace(
                            '/\s+/u',
                            ' ',
                            (string) $value
                        )
                    );
            }

            if ($answerLabel === '') {
                continue;
            }

            /*
             * Case-insensitive aggregation avoids
             * splitting equivalent free-text areas
             * such as “Nueva Ecija” and “nueva ecija”.
             */
            $answerKey =
                mb_strtolower(
                    $answerLabel,
                    'UTF-8'
                );

            if (
                !isset(
                    $normalizedAnswers[$answerKey]
                )
            ) {
                $normalizedAnswers[$answerKey] =
                    $answerLabel;
            }
        }

        return $normalizedAnswers;
    }



    public function getLatestAnnouncements(
        int $limit = 5
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    20
                )
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                announcement_id,
                title,
                priority,
                published_at,
                created_at

            FROM announcements

            WHERE status = 'active'
              AND workflow_status = 'published'

            ORDER BY
                COALESCE(
                    published_at,
                    created_at
                ) DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare latest announcements: '
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
                'Unable to load latest announcements: '
                    . $error
            );
        }

        $announcements =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $announcements;
    }
    public function getUpcomingEvents(
        int $limit = 5
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    20
                )
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                event_id,
                title,
                location,
                event_date,
                end_date

            FROM events

            WHERE status = 'active'
              AND workflow_status = 'published'
              AND event_date >= NOW()

            ORDER BY
                event_date ASC,
                created_at DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare upcoming events: '
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
                'Unable to load upcoming events: '
                    . $error
            );
        }

        $events =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $events;
    }
}
