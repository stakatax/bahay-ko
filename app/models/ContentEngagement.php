<?php

require_once 'BaseModel.php';

class ContentEngagement extends BaseModel
{
    private array $allowedTypes = [
        'announcement',
        'event',
        'document',
        'survey'
    ];

    private function validateType(
        string $contentType
    ): void {

        if (
            !in_array(
                $contentType,
                $this->allowedTypes,
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Invalid content type.'
            );
        }
    }

    /* ==========================================
       VIEW
    ========================================== */

    public function recordView(
        string $contentType,
        int $contentId,
        int $userId
    ): int {

        $this->validateType(
            $contentType
        );

        $stmt = $this->prepare("
            INSERT IGNORE INTO
            content_view
            (
                content_type,
                content_id,
                user_id
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $stmt->bind_param(
            'sii',
            $contentType,
            $contentId,
            $userId
        );

        return $stmt->execute();
    }

    /* ==========================================
       REACTION
    ========================================== */

    public function react(
        string $contentType,
        int $contentId,
        int $userId,
        string $reaction
    ): int {
        $this->validateType(
            $contentType
        );

        $stmt = $this->prepare("
        INSERT INTO content_reaction
        (
            content_type,
            content_id,
            user_id,
            reaction_type
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )

        ON DUPLICATE KEY UPDATE
            reaction_type =
                VALUES(reaction_type),

            updated_at =
                NOW()
    ");

        $stmt->bind_param(
            'siis',
            $contentType,
            $contentId,
            $userId,
            $reaction
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save the reaction: '
                    . $error
            );
        }

        /*
     * affected_rows:
     * 1 = new reaction
     * 2 = existing reaction changed
     * 0 = same reaction submitted again
     */
        $affectedRows =
            (int) $stmt->affected_rows;

        $stmt->close();

        return $affectedRows;
    }

    /* ==========================================
       COMMENT
    ========================================== */

    public function getActiveComment(
        int $commentId
    ): ?array {
        if ($commentId <= 0) {
            return null;
        }

        $stmt =
            $this->prepare("
            SELECT
                comment_id,
                content_type,
                content_id,
                user_id,
                parent_comment_id

            FROM content_comment

            WHERE comment_id = ?
              AND status = 'Active'

            LIMIT 1
        ");

        $stmt->bind_param(
            'i',
            $commentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load the parent comment: '
                    . $error
            );
        }

        $comment =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $comment ?: null;
    }

    public function comment(
        string $contentType,
        int $contentId,
        int $userId,
        string $comment,
        ?int $parentCommentId = null
    ): int {
        $this->validateType(
            $contentType
        );

        $stmt =
            $this->prepare("
            INSERT INTO content_comment
            (
                content_type,
                content_id,
                user_id,
                parent_comment_id,
                comment
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $stmt->bind_param(
            'siiis',
            $contentType,
            $contentId,
            $userId,
            $parentCommentId,
            $comment
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save the comment: '
                    . $error
            );
        }

        $commentId =
            (int) $stmt->insert_id;

        $stmt->close();

        return $commentId;
    }

    /* ==========================================
       ACKNOWLEDGMENT
    ========================================== */

    public function acknowledge(
        string $contentType,
        int $contentId,
        int $userId
    ): bool {
        $this->validateType(
            $contentType
        );

        $stmt = $this->prepare("
        INSERT IGNORE INTO content_acknowledgment
        (
            content_type,
            content_id,
            user_id
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

        $stmt->bind_param(
            'sii',
            $contentType,
            $contentId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save the acknowledgment: '
                    . $error
            );
        }

        /*
     * True only when a new acknowledgment
     * record was inserted.
     */
        $wasInserted =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $wasInserted;
    }

    /* ==========================================
       COMMENTS
    ========================================== */

    public function getComments(
        string $contentType,
        int $contentId
    ): array {
        $this->validateType(
            $contentType
        );

        $stmt =
            $this->prepare("
            SELECT
                cc.comment_id,
                cc.comment,
                cc.created_at,
                cc.user_id,
                cc.parent_comment_id,

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
                ) AS user_name,

                u.profile_photo,
                r.role_prefix

            FROM content_comment cc

            INNER JOIN user u
                ON u.user_id =
                   cc.user_id

            LEFT JOIN role r
                ON r.role_id =
                   u.role_id

            WHERE cc.content_type = ?
              AND cc.content_id = ?
              AND cc.status = 'Active'

            ORDER BY
                cc.created_at ASC,
                cc.comment_id ASC
        ");

        $stmt->bind_param(
            'si',
            $contentType,
            $contentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load comments: '
                    . $error
            );
        }

        $comments =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $comments;
    }
    /* ==========================================
       ENGAGEMENT
    ========================================== */

    /** Feed-only batch reads. Caller must filter content authorization first. */
    public function getEngagementBatch(string $contentType, array $contentIds, int $userId): array
    {
        $this->validateType($contentType);
        $ids = array_values(array_unique(array_filter(array_map('intval', $contentIds), static fn(int $id): bool => $id > 0)));
        if ($ids === []) { return []; }
        $map = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach ($chunk as $id) {
                $map[$id] = [
                    'view_count' => 0, 'comment_count' => 0, 'reaction_count' => 0,
                    'acknowledgment_count' => 0, 'user_reaction' => null,
                    'user_acknowledged' => false, 'user_viewed' => false,
                    'reaction_breakdown' => ['Like' => 0, 'Love' => 0, 'Care' => 0, 'Wow' => 0]
                ];
            }
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            foreach (['view', 'comment', 'reaction', 'acknowledgment'] as $kind) {
                $reactionColumn = $kind === 'reaction' ? ', reaction_type' : '';
                // Preserve existing comment-count semantics, including moderated comments.
                $stmt = $this->prepare("SELECT content_id{$reactionColumn}, COUNT(*) AS total,
                    MAX(CASE WHEN user_id = ? THEN 1 ELSE 0 END) AS mine
                    FROM content_{$kind} WHERE content_type = ? AND content_id IN ({$placeholders})
                    GROUP BY content_id{$reactionColumn}");
                if (!$stmt) { throw new RuntimeException('Unable to load engagement totals.'); }
                try {
                    $values = [$userId, $contentType, ...$chunk];
                    $stmt->bind_param('is' . str_repeat('i', count($chunk)), ...$values);
                    if (!$stmt->execute()) { throw new RuntimeException('Unable to load engagement totals.'); }
                    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                        $id = (int) $row['content_id'];
                        $map[$id][$kind . '_count'] += (int) $row['total'];
                        if ($kind === 'reaction') {
                            $map[$id]['reaction_breakdown'][$row['reaction_type']] = (int) $row['total'];
                            if ($row['mine']) { $map[$id]['user_reaction'] = $row['reaction_type']; }
                        } elseif ($kind === 'view') {
                            $map[$id]['user_viewed'] = (bool) $row['mine'];
                        } elseif ($kind === 'acknowledgment') {
                            $map[$id]['user_acknowledged'] = (bool) $row['mine'];
                        }
                    }
                } finally { $stmt->close(); }
            }
        }
        return $map;
    }
    public function getEngagement(
        string $contentType,
        int $contentId,
        int $userId
    ): array {

        $this->validateType(
            $contentType
        );

        $engagement = [

            'view_count' => 0,

            'comment_count' => 0,

            'reaction_count' => 0,

            'acknowledgment_count' => 0,

            'user_reaction' => null,

            'user_acknowledged' => false,

            'user_viewed' => false,

            'reaction_breakdown' => [

                'Like' => 0,

                'Love' => 0,

                'Care' => 0,

                'Wow' => 0
            ]
        ];

        foreach (

            [
                'view' => 'content_view',
                'comment' => 'content_comment',
                'reaction' => 'content_reaction',
                'acknowledgment' => 'content_acknowledgment'

            ]

            as

            $key => $table

        ) {

            $stmt = $this->prepare("
                SELECT COUNT(*) total
                FROM {$table}
                WHERE
                    content_type = ?
                AND
                    content_id = ?
            ");

            $stmt->bind_param(
                'si',
                $contentType,
                $contentId
            );

            $stmt->execute();

            $row =
                $stmt
                ->get_result()
                ->fetch_assoc();

            $engagement["{$key}_count"] =
                (int)
                $row['total'];
        }

        $stmt = $this->prepare("
            SELECT reaction_type
            FROM content_reaction
            WHERE
                content_type = ?
            AND
                content_id = ?
            AND
                user_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            'sii',
            $contentType,
            $contentId,
            $userId
        );

        $stmt->execute();

        $reaction =
            $stmt
            ->get_result()
            ->fetch_assoc();

        if ($reaction) {

            $engagement['user_reaction'] =
                $reaction['reaction_type'];
        }

        $stmt = $this->prepare("
            SELECT
                reaction_type,
                COUNT(*) total
            FROM content_reaction
            WHERE
                content_type = ?
            AND
                content_id = ?
            GROUP BY
                reaction_type
        ");

        $stmt->bind_param(
            'si',
            $contentType,
            $contentId
        );

        $stmt->execute();

        $result =
            $stmt
            ->get_result();

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $engagement['reaction_breakdown'][$row['reaction_type']] =
                (int)
                $row['total'];
        }

        $stmt = $this->prepare("
            SELECT 1
            FROM content_acknowledgment
            WHERE
                content_type = ?
            AND
                content_id = ?
            AND
                user_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            'sii',
            $contentType,
            $contentId,
            $userId
        );

        $stmt->execute();

        $engagement['user_acknowledged'] =
            $stmt
            ->get_result()
            ->num_rows > 0;

        $stmt =
            $this->prepare("
        SELECT 1

        FROM content_view

        WHERE content_type = ?
          AND content_id = ?
          AND user_id = ?

        LIMIT 1
    ");

        $stmt->bind_param(
            'sii',
            $contentType,
            $contentId,
            $userId
        );

        $stmt->execute();

        $engagement['user_viewed'] =
            $stmt
            ->get_result()
            ->num_rows > 0;

        return $engagement;
    }
}
