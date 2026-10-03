<?php

require_once __DIR__
    . '/BaseModel.php';

class GovernmentAdvisory extends BaseModel
{
    /* ==========================================
       ACTIVE TRUSTED SOURCES
    ========================================== */

    public function getActiveSources(): array
    {
        $result =
            $this->conn->query("
                SELECT
                    government_source_id,
                    source_name,
                    agency_code,
                    agency_category,
                    base_url,
                    allowed_host,
                    connector_type,
                    authority_weight,
                    status

                FROM government_source

                WHERE status = 'Active'

                ORDER BY
                    agency_category ASC,
                    source_name ASC
            ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load trusted government sources: '
                    . $this->conn->error
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    /* ==========================================
       MATCH ACTIVE SOURCE BY HOST
    ========================================== */

    public function findActiveSourceByHost(
        string $host
    ): ?array {
        $host =
            strtolower(
                trim(
                    $host
                )
            );

        if ($host === '') {
            return null;
        }

        $stmt =
            $this->prepare("
                SELECT
                    government_source_id,
                    source_name,
                    agency_code,
                    agency_category,
                    base_url,
                    allowed_host,
                    connector_type,
                    authority_weight,
                    status

                FROM government_source

                WHERE status = 'Active'

                  AND (
                      allowed_host = ?

                      OR ? LIKE CONCAT(
                          '%.',
                          allowed_host
                      )
                  )

                ORDER BY
                    authority_weight DESC,
                    government_source_id ASC

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare government source lookup.'
            );
        }

        $stmt->bind_param(
            'ss',
            $host,
            $host
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load the government source: '
                    . $error
            );
        }

        $source =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $source ?: null;
    }

    /* ==========================================
       DUPLICATE URL LOOKUP
    ========================================== */

    public function findByUrlHash(
        string $urlHash
    ): ?array {
        $urlHash =
            strtolower(
                trim(
                    $urlHash
                )
            );

        if (
            !preg_match(
                '/^[a-f0-9]{64}$/',
                $urlHash
            )
        ) {
            return null;
        }

        $stmt =
            $this->prepare("
                SELECT
                    government_advisory_id,
                    title,
                    source_url,
                    advisory_type,
                    relevance_score,
                    review_status,
                    created_at

                FROM government_advisory

                WHERE source_url_hash = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare advisory URL lookup.'
            );
        }

        $stmt->bind_param(
            's',
            $urlHash
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to check the advisory URL: '
                    . $error
            );
        }

        $advisory =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $advisory ?: null;
    }

    /* ==========================================
       DUPLICATE URL AND REFERENCE LOOKUP
    ========================================== */

    public function findByUrlHashAndReference(
        string $urlHash,
        string $externalReference
    ): ?array {
        $urlHash =
            strtolower(
                trim(
                    $urlHash
                )
            );

        $externalReference =
            mb_strtolower(
                trim(
                    $externalReference
                ),
                'UTF-8'
            );

        if (
            !preg_match(
                '/^[a-f0-9]{64}$/',
                $urlHash
            ) ||
            $externalReference === ''
        ) {
            return null;
        }

        $stmt =
            $this->prepare("
                SELECT
                    government_advisory_id,
                    external_reference,
                    title,
                    source_url,
                    advisory_type,
                    relevance_score,
                    review_status,
                    created_at

                FROM government_advisory

                WHERE source_url_hash = ?
                  AND LOWER(
                        TRIM(
                            external_reference
                        )
                      ) = ?

                ORDER BY
                    government_advisory_id ASC

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare advisory URL and reference lookup.'
            );
        }

        $stmt->bind_param(
            'ss',
            $urlHash,
            $externalReference
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to check the advisory URL and reference: '
                    . $error
            );
        }

        $advisory =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $advisory ?: null;
    }


    /* ==========================================
       DUPLICATE CONTENT LOOKUP
    ========================================== */

    public function findByContentHash(
        string $contentHash
    ): ?array {
        $contentHash =
            strtolower(
                trim(
                    $contentHash
                )
            );

        if (
            !preg_match(
                '/^[a-f0-9]{64}$/',
                $contentHash
            )
        ) {
            return null;
        }

        $stmt =
            $this->prepare("
                SELECT
                    government_advisory_id,
                    title,
                    source_url,
                    advisory_type,
                    relevance_score,
                    review_status,
                    created_at

                FROM government_advisory

                WHERE content_hash = ?

                ORDER BY
                    government_advisory_id ASC

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare advisory content lookup.'
            );
        }

        $stmt->bind_param(
            's',
            $contentHash
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to check advisory content: '
                    . $error
            );
        }

        $advisory =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $advisory ?: null;
    }

    /* ==========================================
       CREATE ADVISORY
    ========================================== */

    public function create(
        array $data
    ): int {
        $stmt =
            $this->prepare("
                INSERT INTO government_advisory
                (
                    government_source_id,
                    external_reference,
                    source_url,
                    source_url_hash,
                    source_page_mode,
                    title,
                    summary,
                    extracted_text,
                    content_hash,
                    advisory_type,
                    geographic_scope,
                    scope_value,
                    issued_at,
                    effective_from,
                    effective_until,
                    relevance_score,
                    relevance_reasons,
                    fetch_status,
                    retrieval_error,
                    submitted_by,
                    fetched_at,
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
                    ?,
                    NOW()
                )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare government advisory creation: '
                    . $this->conn->error
            );
        }

        $parameters = [
            (int) (
                $data['government_source_id']
                ?? 0
            ),

            $data['external_reference']
                ?? null,

            (string) (
                $data['source_url']
                ?? ''
            ),

            (string) (
                $data['source_url_hash']
                ?? ''
            ),

            in_array(
                $data['source_page_mode']
                    ?? '',
                [
                    'Specific',
                    'Reusable'
                ],
                true
            )
                ? $data['source_page_mode']
                : 'Specific',

            (string) (
                $data['title']
                ?? ''
            ),
            $data['summary']
                ?? null,

            $data['extracted_text']
                ?? null,

            $data['content_hash']
                ?? null,

            (string) (
                $data['advisory_type']
                ?? 'Other'
            ),

            (string) (
                $data['geographic_scope']
                ?? 'Nationwide'
            ),

            $data['scope_value']
                ?? null,

            $data['issued_at']
                ?? null,

            $data['effective_from']
                ?? null,

            $data['effective_until']
                ?? null,

            (int) (
                $data['relevance_score']
                ?? 0
            ),

            $data['relevance_reasons']
                ?? null,

            (string) (
                $data['fetch_status']
                ?? 'Pending'
            ),

            $data['retrieval_error']
                ?? null,

            !empty($data['submitted_by'])
                ? (int) $data['submitted_by']
                : null,

            $data['fetched_at']
                ?? null
        ];

        if (!$stmt->execute($parameters)) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to create government advisory: '
                    . $error
            );
        }

        $advisoryId =
            (int) $this->conn->insert_id;

        $stmt->close();

        return $advisoryId;
    }

    /* ==========================================
       REVIEW QUEUE
    ========================================== */

    public function getReviewQueue(
        ?string $reviewStatus = null,
        int $limit = 100
    ): array {
        $allowedStatuses = [
            'Pending',
            'Relevant',
            'Irrelevant',
            'Converted',
            'Archived'
        ];

        if (
            $reviewStatus !== null &&
            !in_array(
                $reviewStatus,
                $allowedStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid advisory review status.'
            );
        }

        $limit =
            max(
                1,
                min(
                    $limit,
                    250
                )
            );

        $sql = "
            SELECT
                ga.government_advisory_id,
                ga.external_reference,
                ga.source_url,
                ga.source_page_mode,
                ga.title,
                ga.summary,
                ga.advisory_type,
                ga.geographic_scope,
                ga.scope_value,
                ga.issued_at,
                ga.effective_from,
                ga.effective_until,
                ga.relevance_score,
                ga.relevance_reasons,
                ga.fetch_status,
                ga.review_status,
                ga.linked_content_type,
                ga.linked_content_id,
                ga.created_at,

                gs.source_name,
                gs.agency_code,
                gs.agency_category,
                gs.authority_weight

            FROM government_advisory ga

            INNER JOIN government_source gs
                ON gs.government_source_id =
                    ga.government_source_id
        ";

        if ($reviewStatus !== null) {
            $sql .= "
                WHERE ga.review_status = ?
            ";
        }

        $sql .= "
            ORDER BY
                CASE
                    WHEN ga.review_status = 'Pending'
                    THEN 0
                    ELSE 1
                END ASC,

                ga.relevance_score DESC,
                ga.created_at DESC

            LIMIT ?
        ";

        $stmt =
            $this->prepare(
                $sql
            );

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare government advisory queue.'
            );
        }

        if ($reviewStatus !== null) {
            $stmt->bind_param(
                'si',
                $reviewStatus,
                $limit
            );
        } else {
            $stmt->bind_param(
                'i',
                $limit
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load government advisory queue: '
                    . $error
            );
        }

        $advisories =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $advisories;
    }

    /* ==========================================
       ADVISORY DETAILS
    ========================================== */

    public function findById(
        int $advisoryId
    ): ?array {
        if ($advisoryId <= 0) {
            return null;
        }

        $stmt =
            $this->prepare("
                SELECT
                    ga.*,

                    gs.source_name,
                    gs.agency_code,
                    gs.agency_category,
                    gs.base_url,
                    gs.allowed_host,
                    gs.authority_weight,

                    TRIM(
                        CONCAT(
                            COALESCE(
                                submitter.first_name,
                                ''
                            ),
                            ' ',
                            COALESCE(
                                submitter.last_name,
                                ''
                            )
                        )
                    ) AS submitted_by_name,

                    TRIM(
                        CONCAT(
                            COALESCE(
                                reviewer.first_name,
                                ''
                            ),
                            ' ',
                            COALESCE(
                                reviewer.last_name,
                                ''
                            )
                        )
                    ) AS reviewed_by_name

                FROM government_advisory ga

                INNER JOIN government_source gs
                    ON gs.government_source_id =
                        ga.government_source_id

                LEFT JOIN user submitter
                    ON submitter.user_id =
                        ga.submitted_by

                LEFT JOIN user reviewer
                    ON reviewer.user_id =
                        ga.reviewed_by

                WHERE ga.government_advisory_id = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare government advisory details.'
            );
        }

        $stmt->bind_param(
            'i',
            $advisoryId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load government advisory details: '
                    . $error
            );
        }

        $advisory =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $advisory ?: null;
    }

    /* ==========================================
       REVIEW ADVISORY
    ========================================== */

    public function review(
        int $advisoryId,
        string $reviewStatus,
        ?string $reviewNotes,
        int $reviewerId
    ): bool {
        $allowedStatuses = [
            'Relevant',
            'Irrelevant',
            'Archived'
        ];

        if (
            $advisoryId <= 0 ||
            $reviewerId <= 0 ||
            !in_array(
                $reviewStatus,
                $allowedStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid government advisory review.'
            );
        }

        $stmt =
            $this->prepare("
                UPDATE government_advisory

                SET
                    review_status = ?,
                    review_notes = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW()

                WHERE government_advisory_id = ?
                  AND review_status IN (
                      'Pending',
                      'Relevant',
                      'Irrelevant'
                  )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare government advisory review.'
            );
        }

        $stmt->bind_param(
            'ssii',
            $reviewStatus,
            $reviewNotes,
            $reviewerId,
            $advisoryId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to review government advisory: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows === 1;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       LINK CONVERTED INTERNAL CONTENT
    ========================================== */

    /** Called on the announcement connection inside its publication transaction. */
    public function linkAnnouncement(int $advisoryId, int $announcementId, int $userId): void
    {
        if ($advisoryId <= 0 || $announcementId <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('Invalid advisory conversion link.');
        }
        $stmt = $this->prepare("UPDATE government_advisory
            SET review_status = 'Converted', linked_content_type = 'announcement',
                linked_content_id = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE government_advisory_id = ? AND review_status = 'Relevant'
              AND linked_content_id IS NULL");
        $stmt->bind_param('iii', $announcementId, $userId, $advisoryId);
        $stmt->execute();
        $linked = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$linked) throw new InvalidArgumentException('The advisory is no longer available. Check and attach the source again.');
    }

    public function markConverted(
        int $advisoryId,
        string $contentType,
        int $contentId,
        int $reviewerId
    ): bool {
        $allowedContentTypes = [
            'announcement',
            'event',
            'document',
            'survey',
            'holiday'
        ];

        if (
            $advisoryId <= 0 ||
            $contentId <= 0 ||
            $reviewerId <= 0 ||
            !in_array(
                $contentType,
                $allowedContentTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid advisory conversion link.'
            );
        }

        $stmt =
            $this->prepare("
                UPDATE government_advisory

                SET
                    review_status =
                        'Converted',

                    linked_content_type = ?,
                    linked_content_id = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW()

                WHERE government_advisory_id = ?
                  AND review_status IN (
                      'Pending',
                      'Relevant'
                  )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare advisory conversion.'
            );
        }

        $stmt->bind_param(
            'siii',
            $contentType,
            $contentId,
            $reviewerId,
            $advisoryId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to link converted advisory: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows === 1;

        $stmt->close();

        return $updated;
    }


    /* ==========================================
   ACTIVE RELEVANCE RULES
========================================== */

    public function getActiveRules(): array
    {
        $result =
            $this->conn->query("
            SELECT
                government_advisory_rule_id,
                rule_name,
                match_field,
                match_value,
                advisory_type,
                geographic_scope,
                score_adjustment,
                priority_order

            FROM government_advisory_rule

            WHERE status = 'Active'

            ORDER BY
                priority_order ASC,
                government_advisory_rule_id ASC
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load government advisory rules: '
                    . $this->conn->error
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }
}
