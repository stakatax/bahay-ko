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

        if (!in_array($reaction, ['Upvote', 'Downvote'], true)) {
            throw new InvalidArgumentException('Invalid vote type.');
        }

        // Serialize votes by actor, including the first vote when no row exists.
        $this->conn->begin_transaction();
        try {
            $lock = $this->prepare('SELECT user_id FROM user WHERE user_id = ? FOR UPDATE');
            $lock->bind_param('i', $userId);
            $lock->execute();
            if (!$lock->get_result()->fetch_assoc()) {
                throw new InvalidArgumentException('Invalid user.');
            }
            $lock->close();
            $current = $this->prepare('SELECT reaction_type FROM content_reaction
                WHERE content_type = ? AND content_id = ? AND user_id = ? FOR UPDATE');
            $current->bind_param('sii', $contentType, $contentId, $userId);
            $current->execute();
            $existing = $current->get_result()->fetch_assoc();
            $current->close();

            if (($existing['reaction_type'] ?? null) === $reaction) {
                $stmt = $this->prepare('DELETE FROM content_reaction
                    WHERE content_type = ? AND content_id = ? AND user_id = ?');
                $stmt->bind_param('sii', $contentType, $contentId, $userId);
                $result = -1; // Vote withdrawn; no engagement notification.
            } else {
                $stmt = $this->prepare('INSERT INTO content_reaction
                    (content_type, content_id, user_id, reaction_type) VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type), updated_at = NOW()');
                $stmt->bind_param('siis', $contentType, $contentId, $userId, $reaction);
                $result = $existing ? 2 : 1;
            }
            $stmt->execute();
            $stmt->close();
            $this->conn->commit();
            return $result;
        } catch (Throwable $exception) {
            $this->conn->rollback();
            throw $exception;
        }
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
        return $this->getEngagementSets([$contentType => $contentIds], $userId)[$contentType] ?? [];
    }

    /** Four aggregate queries per 500 eligible type/ID pairs, across all feed types. */
    public function getEngagementSets(array $sets, int $userId): array
    {
        $pairs = [];
        $map = [];
        foreach ($sets as $type => $ids) {
            $this->validateType($type);
            $map[$type] = [];
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
            foreach ($ids as $id) {
                $pairs[] = [$type, $id];
                $map[$type][$id] = [
                    'view_count' => 0, 'comment_count' => 0, 'reaction_count' => 0,
                    'acknowledgment_count' => 0, 'user_reaction' => null,
                    'user_acknowledged' => false, 'user_viewed' => false,
                    'reaction_breakdown' => ['Upvote' => 0, 'Downvote' => 0]
                ];
            }
        }
        foreach (array_chunk($pairs, 500) as $chunk) {
            $groups = [];
            foreach ($chunk as [$type, $id]) { $groups[$type][] = $id; }
            $conditions = []; $parameters = []; $parameterTypes = '';
            foreach ($groups as $type => $ids) {
                $conditions[] = '(content_type = ? AND content_id IN (' . implode(',', array_fill(0, count($ids), '?')) . '))';
                $parameters = [...$parameters, $type, ...$ids];
                $parameterTypes .= 's' . str_repeat('i', count($ids));
            }
            $where = implode(' OR ', $conditions);
            foreach (['view', 'comment', 'reaction', 'acknowledgment'] as $kind) {
                $reactionColumn = $kind === 'reaction' ? ', reaction_type' : '';
                // Preserve existing comment-count semantics, including moderated comments.
                $stmt = $this->prepare("SELECT content_type, content_id{$reactionColumn}, COUNT(*) AS total,
                    MAX(CASE WHEN user_id = ? THEN 1 ELSE 0 END) AS mine
                    FROM content_{$kind} WHERE {$where}
                    GROUP BY content_type, content_id{$reactionColumn}");
                if (!$stmt) { throw new RuntimeException('Unable to load engagement totals.'); }
                try {
                    $values = [$userId, ...$parameters];
                    $stmt->bind_param('i' . $parameterTypes, ...$values);
                    if (!$stmt->execute()) { throw new RuntimeException('Unable to load engagement totals.'); }
                    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                        $type = $row['content_type']; $id = (int) $row['content_id'];
                        $map[$type][$id][$kind . '_count'] += (int) $row['total'];
                        if ($kind === 'reaction') {
                            $map[$type][$id]['reaction_breakdown'][$row['reaction_type']] = (int) $row['total'];
                            if ($row['mine']) { $map[$type][$id]['user_reaction'] = $row['reaction_type']; }
                        } elseif ($kind === 'view') {
                            $map[$type][$id]['user_viewed'] = (bool) $row['mine'];
                        } elseif ($kind === 'acknowledgment') {
                            $map[$type][$id]['user_acknowledged'] = (bool) $row['mine'];
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

            'reaction_breakdown' => ['Upvote' => 0, 'Downvote' => 0]
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
