<?php

class ContentRedundancyService
{
    private mysqli $conn;

    private const WARNING_THRESHOLD = 72.0;
    private const MAXIMUM_CANDIDATES = 200;
    private const MAXIMUM_MATCHES = 3;

    public function __construct()
    {
        require __DIR__
            . '/../../config/dbconnect.php';

        $this->conn = $conn;
    }

    public function assessSubmission(
        array $data
    ): array {
        $contentType =
            strtolower(
                trim(
                    (string) (
                        $data['post_type']
                        ?? ''
                    )
                )
            );

        $configuration =
            $this->resolveConfiguration(
                $contentType
            );

        $title =
            trim(
                (string) (
                    $data[$configuration['title_input']]
                    ?? ''
                )
            );

        $body =
            trim(
                (string) (
                    $data[$configuration['body_input']]
                    ?? ''
                )
            );

        $editId =
            max(
                0,
                (int) (
                    $data['edit_id']
                    ?? 0
                )
            );

        if (
            $title === '' ||
            $body === ''
        ) {
            return $this->emptyAssessment(
                $contentType
            );
        }

        $normalizedTitle =
            $this->normalizeText(
                $title
            );

        $normalizedBody =
            $this->normalizeText(
                $body
            );

        if ($normalizedTitle === '') {
            return $this->emptyAssessment(
                $contentType
            );
        }

        $submittedEventDate =
            $contentType === 'event'
            ? $this->normalizeEventDateTime(
                $data['event_date']
                    ?? null
            )
            : null;

        $matches = [];

        foreach (
            $this->loadCandidates(
                $configuration,
                $editId
            )
            as $candidate
        ) {
            $candidateTitle =
                $this->normalizeText(
                    $candidate['title']
                        ?? ''
                );

            $candidateBody =
                $this->normalizeText(
                    $candidate['body']
                        ?? ''
                );

            if ($candidateTitle === '') {
                continue;
            }

            $titleSimilarity =
                $this->calculateSimilarity(
                    $normalizedTitle,
                    $candidateTitle
                );

            $bodySimilarity =
                $this->calculateSimilarity(
                    $normalizedBody,
                    $candidateBody
                );

            $candidateEventDate =
                $contentType === 'event'
                ? $this->normalizeEventDateTime(
                    $candidate['event_date']
                        ?? null
                )
                : null;

            $sameEventDate =
                $contentType !== 'event' ||
                (
                    $submittedEventDate !== null &&
                    $candidateEventDate !== null &&
                    $submittedEventDate ===
                    $candidateEventDate
                );

            $exactDuplicate =
                $normalizedTitle ===
                $candidateTitle &&
                $normalizedBody !== '' &&
                $normalizedBody ===
                $candidateBody &&
                $sameEventDate;

            $combinedSimilarity =
                round(
                    (
                        $titleSimilarity * 0.65
                    ) +
                        (
                            $bodySimilarity * 0.35
                        ),
                    1
                );

            if (
                !$exactDuplicate &&
                $combinedSimilarity <
                self::WARNING_THRESHOLD
            ) {
                continue;
            }

            $matches[] = [
                'content_type' =>
                $contentType,

                'content_id' =>
                (int) (
                    $candidate['content_id']
                    ?? 0
                ),

                'title' =>
                trim(
                    (string) (
                        $candidate['title']
                        ?? 'Untitled content'
                    )
                ),

                'workflow_status' =>
                (string) (
                    $candidate['workflow_status']
                    ?? 'draft'
                ),

                'created_at' =>
                $candidate['created_at']
                    ?? null,

                'event_date' =>
                $candidate['event_date']
                    ?? null,

                'author_name' =>
                trim(
                    (string) (
                        $candidate['author_name']
                        ?? 'Unknown author'
                    )
                ),

                'title_similarity' =>
                $titleSimilarity,

                'body_similarity' =>
                $bodySimilarity,

                'similarity' =>
                $exactDuplicate
                    ? 100.0
                    : $combinedSimilarity,

                'exact_duplicate' =>
                $exactDuplicate
            ];
        }

        usort(
            $matches,
            static function (
                array $first,
                array $second
            ): int {
                $exactComparison =
                    (int) (
                        $second['exact_duplicate']
                        ?? false
                    )
                    <=>
                    (int) (
                        $first['exact_duplicate']
                        ?? false
                    );

                if ($exactComparison !== 0) {
                    return $exactComparison;
                }

                return (
                    (float) (
                        $second['similarity']
                        ?? 0
                    )
                )
                    <=>
                    (
                        (float) (
                            $first['similarity']
                            ?? 0
                        )
                    );
            }
        );

        $matches =
            array_slice(
                $matches,
                0,
                self::MAXIMUM_MATCHES
            );

        $hasExactDuplicate =
            !empty(array_filter(
                $matches,
                static fn(
                    array $match
                ): bool =>
                !empty($match['exact_duplicate'])
            ));

        return [
            'content_type' =>
            $contentType,

            'level' =>
            $hasExactDuplicate
                ? 'blocked'
                : (
                    $matches !== []
                    ? 'warning'
                    : 'clear'
                ),

            'blocked' =>
            $hasExactDuplicate,

            'requires_confirmation' =>
            !$hasExactDuplicate &&
                $matches !== [],

            'matches' =>
            $matches
        ];
    }

    public function enforceSubmission(
        array $data
    ): array {
        $assessment =
            $this->assessSubmission(
                $data
            );

        if (!empty($assessment['blocked'])) {
            $match =
                $assessment['matches'][0]
                ?? [];

            $title =
                trim(
                    (string) (
                        $match['title']
                        ?? 'existing content'
                    )
                );

            throw new InvalidArgumentException(
                'An identical '
                    . (
                        $assessment['content_type']
                        ?? 'content item'
                    )
                    . ' already exists: '
                    . $title
                    . '. Open the existing record instead of creating a duplicate.'
            );
        }

        if (
            empty($assessment['requires_confirmation'])
        ) {
            return $assessment;
        }

        $overrideConfirmed =
            (string) (
                $data['redundancy_override']
                ?? ''
            ) === '1';

        if (!$overrideConfirmed) {
            throw new InvalidArgumentException(
                'Possible duplicate content was detected. Review the similar records before continuing.'
            );
        }

        $currentRole =
            trim(
                (string) (
                    $_SESSION['role']
                    ?? ''
                )
            );

        if ($currentRole === 'Admin') {
            $assessment['override_reason'] =
                'Administrative override after reviewing similar content.';

            return $assessment;
        }

        $overrideReason =
            trim(
                (string) (
                    $data['redundancy_override_reason']
                    ?? ''
                )
            );

        $wordCount =
            preg_match_all(
                '/[\p{L}\p{N}]+/u',
                $overrideReason
            );

        if (
            mb_strlen(
                $overrideReason,
                'UTF-8'
            ) < 10 ||
            $wordCount < 2
        ) {
            throw new InvalidArgumentException(
                'Explain why the similar content should still be saved using at least two words and 10 characters.'
            );
        }

        $assessment['override_reason'] =
            $overrideReason;

        return $assessment;
    }

    private function resolveConfiguration(
        string $contentType
    ): array {
        $configurations = [
            'announcement' => [
                'table' =>
                'announcements',

                'id_column' =>
                'announcement_id',

                'body_column' =>
                'content',

                'title_input' =>
                'announcement_title',

                'body_input' =>
                'announcement_content',

                'event_date_column' =>
                null
            ],

            'event' => [
                'table' =>
                'events',

                'id_column' =>
                'event_id',

                'body_column' =>
                'description',

                'title_input' =>
                'event_title',

                'body_input' =>
                'event_description',

                'event_date_column' =>
                'event_date'
            ],

            'document' => [
                'table' =>
                'documents',

                'id_column' =>
                'document_id',

                'body_column' =>
                'description',

                'title_input' =>
                'document_title',

                'body_input' =>
                'document_description',

                'event_date_column' =>
                null
            ],

            'survey' => [
                'table' =>
                'survey',

                'id_column' =>
                'survey_id',

                'body_column' =>
                'description',

                'title_input' =>
                'survey_title',

                'body_input' =>
                'survey_description',

                'event_date_column' =>
                null
            ]
        ];

        if (
            !isset(
                $configurations[$contentType]
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid content type for redundancy checking.'
            );
        }

        return $configurations[$contentType];
    }

    private function loadCandidates(
        array $configuration,
        int $editId
    ): array {
        $table =
            $configuration['table'];

        $idColumn =
            $configuration['id_column'];

        $bodyColumn =
            $configuration['body_column'];

        $eventDateSelection =
            $configuration['event_date_column'] !== null
            ? 'content.'
            . $configuration['event_date_column']
            . ' AS event_date'
            : 'NULL AS event_date';

        $limit =
            self::MAXIMUM_CANDIDATES;

        $sql = "
            SELECT
                content.{$idColumn}
                    AS content_id,

                content.title,
                content.{$bodyColumn}
                    AS body,

                content.workflow_status,
                content.created_at,
                {$eventDateSelection},

                CONCAT_WS(
                    ' ',
                    user.first_name,
                    user.last_name
                ) AS author_name

            FROM {$table} content

            LEFT JOIN user
                ON user.user_id =
                   content.user_id

            WHERE content.workflow_status
                NOT IN (
                    'rejected',
                    'archived'
                )

              AND (
                    ? = 0 OR
                    content.{$idColumn} != ?
                  )

            ORDER BY
                content.created_at DESC,
                content.{$idColumn} DESC

            LIMIT {$limit}
        ";

        $stmt =
            $this->conn->prepare(
                $sql
            );

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare content redundancy analysis: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $editId,
            $editId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to analyze similar content: '
                    . $error
            );
        }

        $candidates =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $candidates;
    }

    private function normalizeText(
        mixed $value
    ): string {
        $value =
            html_entity_decode(
                strip_tags(
                    (string) $value
                ),
                ENT_QUOTES |
                    ENT_HTML5,
                'UTF-8'
            );

        $value =
            mb_strtolower(
                $value,
                'UTF-8'
            );

        $value =
            preg_replace(
                '/[^\p{L}\p{N}]+/u',
                ' ',
                $value
            )
            ?? '';

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            )
                ?? ''
        );
    }

    private function calculateSimilarity(
        string $first,
        string $second
    ): float {
        if (
            $first === '' ||
            $second === ''
        ) {
            return 0.0;
        }

        if ($first === $second) {
            return 100.0;
        }

        similar_text(
            $first,
            $second,
            $percentage
        );

        return round(
            (float) $percentage,
            1
        );
    }

    private function normalizeEventDateTime(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        $timestamp =
            strtotime(
                $value
            );

        return $timestamp !== false
            ? date(
                'Y-m-d H:i:s',
                $timestamp
            )
            : null;
    }

    private function emptyAssessment(
        string $contentType
    ): array {
        return [
            'content_type' =>
            $contentType,

            'level' =>
            'clear',

            'blocked' =>
            false,

            'requires_confirmation' =>
            false,

            'matches' =>
            []
        ];
    }
}
