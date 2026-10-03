<?php

require_once __DIR__
    . '/../models/GovernmentAdvisory.php';

require_once __DIR__
    . '/TrustedGovernmentSourceFetcher.php';

require_once __DIR__
    . '/GovernmentAdvisoryRelevanceService.php';

class GovernmentAdvisoryIntakeService
{
    private GovernmentAdvisory $model;

    private TrustedGovernmentSourceFetcher $fetcher;

    private GovernmentAdvisoryRelevanceService $relevance;

    public function __construct(
        ?GovernmentAdvisory $model = null
    ) {
        $this->model =
            $model
            ?? new GovernmentAdvisory();

        $this->fetcher =
            new TrustedGovernmentSourceFetcher(
                $this->model
            );

        $this->relevance =
            new GovernmentAdvisoryRelevanceService(
                $this->model
            );
    }

    /** Reject incomplete attachment attempts even when JavaScript is bypassed. */
    public function validateAnnouncementAttachment(array $data, int $userId): int
    {
        $rawId = $data['government_advisory_id'] ?? 0;
        $rawUrl = $data['government_advisory_url'] ?? '';
        if (!is_scalar($rawId) || !is_string($rawUrl)
            || filter_var($rawId, FILTER_VALIDATE_INT) === false || (int) $rawId < 0) {
            throw new InvalidArgumentException('Invalid government advisory attachment.');
        }
        $id = (int) $rawId;
        $url = trim($rawUrl);
        if ($id === 0 && $url === '') return 0;
        if (($_SESSION['role'] ?? '') !== 'Admin' || $userId <= 0
            || $userId !== (int) ($_SESSION['user_id'] ?? 0)
            || ($data['post_type'] ?? '') !== 'announcement' || (int) ($data['edit_id'] ?? 0) > 0) {
            throw new InvalidArgumentException('Invalid government advisory attachment.');
        }
        if ($id <= 0) {
            throw new InvalidArgumentException('Check and attach the official link before posting, or remove it.');
        }
        $candidate = $this->getConversionCandidate($id);
        if ($url !== '' && $url !== (string) $candidate['source_url']) {
            throw new InvalidArgumentException('The official link changed. Check and attach it again before posting.');
        }
        // Recheck availability and trusted-source rules immediately before saving.
        $this->fetcher->fetch((string) $candidate['source_url'], (string) $candidate['title']);
        return $id;
    }

    /** Record the Admin's review without leaving the announcement editor. */
    public function prepareAnnouncement(array $data, int $userId): array
    {
        if (($_SESSION['role'] ?? '') !== 'Admin' || $userId <= 0
            || $userId !== (int) ($_SESSION['user_id'] ?? 0)) {
            throw new InvalidArgumentException('Only administrators can use government advisories.');
        }
        if (($data['review_confirmed'] ?? '') !== '1') {
            throw new InvalidArgumentException('Review the advisory before using it in an announcement.');
        }
        $preview = $this->previewAnnouncement($data);
        $connection = $this->model->getDatabaseConnection();
        $connection->begin_transaction();
        try {
            // Persist the server-fetched source and its review atomically.
            $result = $this->persistPreview($preview, $userId);
            $id = (int) $result['government_advisory_id'];
            $this->review($id, 'Relevant', 'Reviewed for use in an announcement.', $userId);
            $candidate = $this->getConversionCandidate($id);
            $connection->commit();
            return $candidate;
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }

    /* ==========================================
       TRUSTED SOURCE DIRECTORY
    ========================================== */

    public function getSources(): array
    {
        return $this->model
            ->getActiveSources();
    }

    /* ==========================================
       PREVIEW URL INTAKE
    ========================================== */

    /** Attach a source page without treating it as a separately imported bulletin. */
    public function previewAnnouncement(array $data): array
    {
        if (($_SESSION['role'] ?? '') !== 'Admin') {
            throw new InvalidArgumentException('Only administrators can use government advisories.');
        }
        if (!is_string($data['source_url'] ?? null)) {
            throw new InvalidArgumentException('Enter an official government advisory URL.');
        }
        return $this->buildPreview(['source_url' => $data['source_url']], true);
    }

    public function preview(array $data): array
    {
        return $this->buildPreview($data, false);
    }

    private function buildPreview(array $data, bool $announcementLink): array
    {
        $sourceUrl =
            trim(
                (string) (
                    $data['source_url']
                    ?? ''
                )
            );

        if ($sourceUrl === '') {
            throw new InvalidArgumentException(
                'Enter an official government advisory URL.'
            );
        }

        $manualTitle =
            $this->normalizeOptionalText(
                $data['title']
                    ?? null,
                255
            );

        $manualSummary =
            $this->normalizeOptionalText(
                $data['summary']
                    ?? null,
                2000
            );

        $externalReference =
            $this->normalizeOptionalText(
                $data['external_reference']
                    ?? null,
                150
            );

        $scopeValue =
            $this->normalizeOptionalText(
                $data['scope_value']
                    ?? null,
                150
            );

        $sourcePageMode =
            strtolower(
                trim(
                    (string) (
                        $data['source_page_mode']
                        ?? 'specific'
                    )
                )
            );

        if (
            !in_array(
                $sourcePageMode,
                [
                    'specific',
                    'reusable'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid government source page type.'
            );
        }

        $fetched =
            $this->fetcher
            ->fetch(
                $sourceUrl,
                $manualTitle
            );

        $effectiveUrl =
            (string) (
                $fetched['effective_url']
                ?? ''
            );

        $urlHash =
            hash(
                'sha256',
                mb_strtolower(
                    trim(
                        $effectiveUrl
                    ),
                    'UTF-8'
                )
            );

        $existingByUrl =
            $this->model
            ->findByUrlHash(
                $urlHash
            );

        /*
         * Administrators can explicitly identify a
         * reusable live page on its first submission.
         * A URL already in the database is also
         * automatically treated as reusable.
         */
        $isReusableUrlSubmission =
            $sourcePageMode ===
            'reusable' ||
            $existingByUrl !== null;

        if ($isReusableUrlSubmission && !$announcementLink) {
            if (
                $externalReference === null ||
                $externalReference === ''
            ) {
                throw new InvalidArgumentException(
                    'Enter the advisory\'s unique official reference when using a reusable live page.'
                );
            }

            if ($manualTitle === null) {
                throw new InvalidArgumentException(
                    'Enter the advisory\'s official title when using a reusable live page.'
                );
            }

            if (
                $manualSummary === null ||
                mb_strlen(
                    trim(
                        $manualSummary
                    ),
                    'UTF-8'
                ) < 20
            ) {
                throw new InvalidArgumentException(
                    'Explain the advisory\'s possible effect on school operations using at least 20 characters.'
                );
            }
        }

        /*
         * For an existing URL, prevent the same
         * official reference from being submitted
         * more than once.
         */
        if ($existingByUrl && !$announcementLink) {
            $existingByReference =
                $this->model
                ->findByUrlHashAndReference(
                    $urlHash,
                    (string) $externalReference
                );

            if ($existingByReference) {
                throw new InvalidArgumentException(
                    'This government advisory URL and official reference already exist as advisory #'
                        . (int) (
                            $existingByReference['government_advisory_id']
                            ?? 0
                        )
                        . '.'
                );
            }
        }


        $contentHash =
            trim(
                (string) (
                    $fetched['content_hash']
                    ?? ''
                )
            );

        /*
         * For a new URL, prevent identical content
         * copied from another source. For a reused
         * URL, the unique official reference acts as
         * the advisory version identifier.
         */
        if (
            $contentHash !== '' &&
            !$announcementLink &&
            !$isReusableUrlSubmission
        ) {
            $existingByContent =
                $this->model
                ->findByContentHash(
                    $contentHash
                );

            if ($existingByContent) {
                throw new InvalidArgumentException(
                    'Identical government advisory content already exists as advisory #'
                        . (int) (
                            $existingByContent['government_advisory_id']
                            ?? 0
                        )
                        . '.'
                );
            }
        }


        $title =
            $manualTitle
            ?? trim(
                (string) (
                    $fetched['title']
                    ?? ''
                )
            );

        if ($title === '') {
            throw new RuntimeException(
                'The advisory title could not be determined.'
            );
        }

        $summary =
            $manualSummary
            ?? trim(
                (string) (
                    $fetched['summary']
                    ?? ''
                )
            );

        $extractedText =
            trim(
                (string) (
                    $fetched['extracted_text']
                    ?? ''
                )
            );

        /*
         * Reusable live pages commonly expose site
         * navigation instead of one advisory body.
         * Do not display or classify that unrelated
         * page text. Use the manually verified title,
         * reference, summary, and scope instead.
         */
        if ($isReusableUrlSubmission) {
            $extractedText =
                '';
        }

        $classificationText =
            trim(
                implode(
                    ' ',
                    array_filter(
                        [
                            $externalReference,
                            $summary,
                            $scopeValue,
                            $extractedText
                        ],
                        static fn(
                            $value
                        ): bool =>
                        is_string(
                            $value
                        ) &&
                            trim(
                                $value
                            ) !== ''
                    )
                )
            );

        $assessment =
            $this->relevance
            ->assess(
                $fetched['source'],
                $title,
                $classificationText
            );

        $allowedScopes = [
            'Nationwide',
            'Region',
            'Province',
            'Municipality',
            'School'
        ];

        $requestedScope =
            trim(
                (string) (
                    $data['geographic_scope']
                    ?? ''
                )
            );

        $geographicScope =
            in_array(
                $requestedScope,
                $allowedScopes,
                true
            )
            ? $requestedScope
            : (
                $assessment['geographic_scope']
                ?? 'Nationwide'
            );

        $issuedAt =
            $this->normalizeOptionalDateTime(
                $data['issued_at']
                    ?? null,
                'issuance date'
            );

        $effectiveFrom =
            $this->normalizeOptionalDateTime(
                $data['effective_from']
                    ?? null,
                'effective start date'
            );

        $effectiveUntil =
            $this->normalizeOptionalDateTime(
                $data['effective_until']
                    ?? null,
                'effective end date'
            );

        if (
            $effectiveFrom !== null &&
            $effectiveUntil !== null &&
            strtotime(
                $effectiveUntil
            ) <
            strtotime(
                $effectiveFrom
            )
        ) {
            throw new InvalidArgumentException(
                'The effective end date cannot be earlier than the start date.'
            );
        }

        return [
            'government_source_id' =>
            (int) (
                $fetched['source']['government_source_id']
                ?? 0
            ),

            'source' =>
            $fetched['source'],

            'external_reference' =>
            $externalReference,

            'source_url' =>
            $effectiveUrl,

            'source_url_hash' =>
            $urlHash,

            'source_page_mode' =>
            $isReusableUrlSubmission
                ? 'Reusable'
                : 'Specific',

            'content_type' =>
            $fetched['content_type']
                ?? '',

            'title' =>
            $title,

            'summary' =>
            $summary,

            'extracted_text' =>
            $extractedText,

            'content_hash' =>
            $contentHash !== ''
                ? $contentHash
                : null,

            'advisory_type' =>
            $assessment['advisory_type']
                ?? 'Other',

            'geographic_scope' =>
            $geographicScope,

            'scope_value' =>
            $scopeValue,

            'issued_at' =>
            $issuedAt,

            'effective_from' =>
            $effectiveFrom,

            'effective_until' =>
            $effectiveUntil,

            'relevance_score' =>
            (int) (
                $assessment['relevance_score']
                ?? 0
            ),

            'relevance_level' =>
            $assessment['relevance_level']
                ?? 'Low',

            'recommendation' =>
            $assessment['recommendation']
                ?? '',

            'relevance_reasons' =>
            $assessment['reasons']
                ?? [],

            'matched_rule_ids' =>
            $assessment['matched_rule_ids']
                ?? [],

            'fetched_at' =>
            $fetched['fetched_at']
                ?? date(
                    'Y-m-d H:i:s'
                )
        ];
    }

    /* ==========================================
       SUBMIT TO REVIEW QUEUE
    ========================================== */

    public function submit(
        array $data,
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'A valid submitting administrator is required.'
            );
        }

        /*
         * Re-fetch and revalidate during submission.
         * Browser preview data is never trusted.
         */
        $preview =
            $this->preview(
                $data
            );

        return $this->persistPreview($preview, $userId);
    }

    private function persistPreview(array $preview, int $userId): array
    {
        $relevanceMetadata = [
            'level' =>
            $preview['relevance_level'],

            'recommendation' =>
            $preview['recommendation'],

            'matched_rule_ids' =>
            $preview['matched_rule_ids'],

            'reasons' =>
            $preview['relevance_reasons']
        ];

        $encodedReasons =
            json_encode(
                $relevanceMetadata,
                JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
            );

        if ($encodedReasons === false) {
            throw new RuntimeException(
                'Unable to encode advisory relevance results.'
            );
        }

        $advisoryId =
            $this->model
            ->create([
                'government_source_id' =>
                $preview['government_source_id'],

                'external_reference' =>
                $preview['external_reference'],

                'source_url' =>
                $preview['source_url'],

                'source_url_hash' =>
                $preview['source_url_hash'],

                'source_page_mode' =>
                $preview['source_page_mode'],

                'title' =>
                $preview['title'],

                'summary' =>
                $preview['summary'],

                'extracted_text' =>
                $preview['extracted_text'],

                'content_hash' =>
                $preview['content_hash'],

                'advisory_type' =>
                $preview['advisory_type'],

                'geographic_scope' =>
                $preview['geographic_scope'],

                'scope_value' =>
                $preview['scope_value'],

                'issued_at' =>
                $preview['issued_at'],

                'effective_from' =>
                $preview['effective_from'],

                'effective_until' =>
                $preview['effective_until'],

                'relevance_score' =>
                $preview['relevance_score'],

                'relevance_reasons' =>
                $encodedReasons,

                'fetch_status' =>
                'Fetched',

                'retrieval_error' =>
                null,

                'submitted_by' =>
                $userId,

                'fetched_at' =>
                $preview['fetched_at']
            ]);

        return [
            'government_advisory_id' =>
            $advisoryId,

            'preview' =>
            $preview
        ];
    }

    /* ==========================================
       REVIEW QUEUE
    ========================================== */

    public function getReviewQueue(
        ?string $status = null,
        int $limit = 100
    ): array {
        return $this->model
            ->getReviewQueue(
                $status,
                $limit
            );
    }

    public function findById(
        int $advisoryId
    ): ?array {
        return $this->model
            ->findById(
                $advisoryId
            );
    }

    /* ==========================================
   REVIEW ADVISORY
========================================== */

    public function review(
        int $advisoryId,
        string $decision,
        ?string $notes,
        int $reviewerId
    ): array {
        if (
            $advisoryId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid advisory review request.'
            );
        }

        $allowedDecisions = [
            'Relevant',
            'Irrelevant',
            'Archived'
        ];

        if (
            !in_array(
                $decision,
                $allowedDecisions,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid advisory review decision.'
            );
        }

        $notes =
            $this->normalizeOptionalText(
                $notes,
                1000
            );

        if (
            in_array(
                $decision,
                [
                    'Irrelevant',
                    'Archived'
                ],
                true
            )
        ) {
            $wordCount =
                $notes !== null
                ? preg_match_all(
                    '/[\p{L}\p{N}]+/u',
                    $notes
                )
                : 0;

            if (
                $notes === null ||
                mb_strlen(
                    $notes,
                    'UTF-8'
                ) < 10 ||
                $wordCount < 2
            ) {
                throw new InvalidArgumentException(
                    'Explain the decision using at least two words and 10 characters.'
                );
            }
        }

        $existing =
            $this->model
            ->findById(
                $advisoryId
            );

        if (!$existing) {
            throw new RuntimeException(
                'The government advisory could not be found.'
            );
        }

        if (
            ($existing['review_status'] ?? '')
            === 'Converted'
        ) {
            throw new RuntimeException(
                'A converted advisory can no longer be reviewed.'
            );
        }

        $updated =
            $this->model
            ->review(
                $advisoryId,
                $decision,
                $notes,
                $reviewerId
            );

        if (!$updated) {
            throw new RuntimeException(
                'The advisory review could not be saved.'
            );
        }

        $reviewed =
            $this->model
            ->findById(
                $advisoryId
            );

        if (!$reviewed) {
            throw new RuntimeException(
                'The reviewed advisory could not be reloaded.'
            );
        }

        return $reviewed;
    }

    /* ==========================================
   CONVERSION CANDIDATE
========================================== */

    public function getConversionCandidate(
        int $advisoryId
    ): array {
        if ($advisoryId <= 0) {
            throw new InvalidArgumentException(
                'Invalid government advisory ID.'
            );
        }

        $advisory =
            $this->model
            ->findById(
                $advisoryId
            );

        if (!$advisory) {
            throw new RuntimeException(
                'The government advisory could not be found.'
            );
        }

        if (
            ($advisory['review_status'] ?? '')
            !== 'Relevant'
        ) {
            throw new RuntimeException(
                'Only an advisory marked Relevant can be converted into internal content.'
            );
        }

        if (
            !empty($advisory['linked_content_id'])
        ) {
            throw new RuntimeException(
                'This government advisory is already linked to internal content.'
            );
        }

        return $advisory;
    }

    /* ==========================================
   COMPLETE CONVERSION LINK
========================================== */

    public function markConverted(
        int $advisoryId,
        string $contentType,
        int $contentId,
        int $reviewerId
    ): array {
        /*
     * Revalidate immediately before linking.
     */
        $this->getConversionCandidate(
            $advisoryId
        );

        $updated =
            $this->model
            ->markConverted(
                $advisoryId,
                $contentType,
                $contentId,
                $reviewerId
            );

        if (!$updated) {
            throw new RuntimeException(
                'The advisory conversion link could not be saved.'
            );
        }

        $converted =
            $this->model
            ->findById(
                $advisoryId
            );

        if (!$converted) {
            throw new RuntimeException(
                'The converted advisory could not be reloaded.'
            );
        }

        return $converted;
    }

    /* ==========================================
       VALUE NORMALIZATION
    ========================================== */

    private function normalizeOptionalText(
        $value,
        int $maximumLength
    ): ?string {
        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $value
                )
            )
            ?? trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen(
                $value,
                'UTF-8'
            ) > $maximumLength
        ) {
            throw new InvalidArgumentException(
                'An advisory field exceeds its maximum allowed length.'
            );
        }

        return $value;
    }

    private function normalizeOptionalDateTime(
        $value,
        string $fieldLabel
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

        if ($timestamp === false) {
            throw new InvalidArgumentException(
                'Invalid '
                    . $fieldLabel
                    . '.'
            );
        }

        return date(
            'Y-m-d H:i:s',
            $timestamp
        );
    }
}
