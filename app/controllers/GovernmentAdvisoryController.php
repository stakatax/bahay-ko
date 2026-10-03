<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/GovernmentAdvisoryIntakeService.php';

class GovernmentAdvisoryController extends BaseController
{
    private GovernmentAdvisoryIntakeService $service;

    public function __construct()
    {
        $this->service =
            new GovernmentAdvisoryIntakeService();
    }

    public function prepareAnnouncement(): void
    {
        $this->requireRole('Admin');
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $data = array_intersect_key($_POST, array_flip([
                'source_url', 'source_page_mode', 'title', 'external_reference',
                'summary', 'issued_at', 'geographic_scope', 'scope_value', 'review_confirmed'
            ]));
            ksort($data);
            $key = hash('sha256', $userId . ':' . json_encode($data));
            $previous = $_SESSION['announcement_advisory_prepared'] ?? [];
            if (($previous['key'] ?? '') === $key) {
                // A retry after a lost response must not create another intake record.
                $candidate = $this->service->getConversionCandidate((int) $previous['id']);
            } else {
                $candidate = $this->service->prepareAnnouncement($data, $userId);
                $_SESSION['announcement_advisory_prepared'] = [
                    'key' => $key, 'id' => (int) $candidate['government_advisory_id']
                ];
            }
            echo json_encode(['status' => 'success', 'advisory' => array_intersect_key(
                $candidate, array_flip(['government_advisory_id', 'title', 'summary', 'source_url', 'external_reference'])
            )], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $exception) {
            http_response_code(publicErrorStatus($exception, 422));
            echo json_encode(['status' => 'error', 'message' => publicErrorMessage($exception)]);
        }
    }

    /* ==========================================
       ADMIN PAGE
    ========================================== */

    public function index(): array
    {
        $this->requireRole(
            'Admin'
        );

        $flash =
            $_SESSION['government_advisory_flash']
            ?? null;

        unset(
            $_SESSION['government_advisory_flash']
        );

        $selectedStatus =
            trim(
                (string) (
                    $_GET['status']
                    ?? ''
                )
            );

        $allowedStatuses = [
            '',
            'Pending',
            'Relevant',
            'Irrelevant',
            'Converted',
            'Archived'
        ];

        if (
            !in_array(
                $selectedStatus,
                $allowedStatuses,
                true
            )
        ) {
            $selectedStatus = '';
        }

        try {
            $allAdvisories =
                $this->service
                ->getReviewQueue(
                    null,
                    250
                );

            $displayedAdvisories =
                $selectedStatus !== ''
                ? array_values(
                    array_filter(
                        $allAdvisories,
                        static fn(
                            array $advisory
                        ): bool => (
                            $advisory['review_status']
                            ?? ''
                        ) === $selectedStatus
                    )
                )
                : $allAdvisories;

            $counts = [
                'All' =>
                count(
                    $allAdvisories
                ),

                'Pending' => 0,
                'Relevant' => 0,
                'Irrelevant' => 0,
                'Converted' => 0,
                'Archived' => 0
            ];

            foreach (
                $allAdvisories
                as $advisory
            ) {
                $status =
                    (string) (
                        $advisory['review_status']
                        ?? ''
                    );

                if (
                    array_key_exists(
                        $status,
                        $counts
                    )
                ) {
                    $counts[$status]++;
                }
            }

            return [
                'sources' =>
                $this->service
                    ->getSources(),

                'advisories' =>
                $displayedAdvisories,

                'counts' =>
                $counts,

                'selected_status' =>
                $selectedStatus,

                'flash' =>
                $flash
            ];
        } catch (Throwable $exception) {
            error_log(
                'Government advisory page error: '
                    . publicErrorMessage($exception)
            );

            return [
                'sources' => [],
                'advisories' => [],

                'counts' => [
                    'All' => 0,
                    'Pending' => 0,
                    'Relevant' => 0,
                    'Irrelevant' => 0,
                    'Converted' => 0,
                    'Archived' => 0
                ],

                'selected_status' =>
                $selectedStatus,

                'flash' => [
                    'type' =>
                    'error',

                    'message' =>
                    'The government advisory queue could not be loaded.'
                ]
            ];
        }
    }

    /* ==========================================
       URL PREVIEW
    ========================================== */

    public function preview(): void
    {
        $this->requireRole(
            'Admin'
        );

        header(
            'Content-Type: application/json; charset=UTF-8'
        );

        try {
            $this->requirePostMethod();



            $this->requireCsrfToken();

            $preview = ($_POST['announcement_link'] ?? '') === '1'
                ? $this->service->previewAnnouncement($_POST)
                : $this->service->preview($_POST);

            $excerpt =
                trim(
                    (string) (
                        $preview['extracted_text']
                        ?? ''
                    )
                );

            if (
                mb_strlen(
                    $excerpt,
                    'UTF-8'
                ) > 1200
            ) {
                $shortenedExcerpt =
                    mb_substr(
                        $excerpt,
                        0,
                        1200,
                        'UTF-8'
                    );

                $nextCharacter =
                    mb_substr(
                        $excerpt,
                        1200,
                        1,
                        'UTF-8'
                    );

                /*
                 * If the limit falls inside a word,
                 * remove that incomplete final word.
                 */
                if (
                    $nextCharacter !== '' &&
                    preg_match(
                        '/^\s$/u',
                        $nextCharacter
                    ) !== 1
                ) {
                    $shortenedExcerpt =
                        preg_replace(
                            '/\s+\S*$/u',
                            '',
                            $shortenedExcerpt
                        )
                        ?? $shortenedExcerpt;
                }

                $excerpt =
                    rtrim(
                        $shortenedExcerpt
                    )
                    . '…';
            }

            echo json_encode(
                [
                    'status' =>
                    'success',

                    'preview' => [
                        'source_name' =>
                        $preview['source']['source_name']
                            ?? '',

                        'agency_code' =>
                        $preview['source']['agency_code']
                            ?? '',

                        'source_url' =>
                        $preview['source_url'],

                        'title' =>
                        $preview['title'],

                        'summary' =>
                        $preview['summary'],

                        'excerpt' =>
                        $excerpt,

                        'content_type' =>
                        $preview['content_type'],

                        'advisory_type' =>
                        $preview['advisory_type'],

                        'geographic_scope' =>
                        $preview['geographic_scope'],

                        'scope_value' =>
                        $preview['scope_value'],

                        'relevance_score' =>
                        $preview['relevance_score'],

                        'relevance_level' =>
                        $preview['relevance_level'],

                        'recommendation' =>
                        $preview['recommendation'],

                        'relevance_reasons' =>
                        $preview['relevance_reasons']
                    ]
                ],
                JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
            );
        } catch (Throwable $exception) {
            http_response_code(
                publicErrorStatus($exception, 422)
            );

            echo json_encode(
                [
                    'status' =>
                    'error',

                    'message' =>
                    publicErrorMessage($exception)
                ],
                JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
            );
        }
    }

    /* ==========================================
       SUBMIT TO REVIEW QUEUE
    ========================================== */

    public function submit(): void
    {
        $this->requireRole(
            'Admin'
        );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $result =
                $this->service
                ->submit(
                    $_POST,
                    $adminId
                );

            $advisoryId =
                (int) (
                    $result['government_advisory_id']
                    ?? 0
                );

            try {
                $this->log(
                    'CREATE_GOVERNMENT_ADVISORY',
                    'Submitted government advisory #'
                        . $advisoryId
                        . ' for administrative review.'
                );
            } catch (Throwable $loggingException) {
                /*
                 * Intake remains successful if general
                 * activity logging is unavailable.
                 */
            }

            $this->setFlash(
                'success',
                'Government advisory added to the review queue.'
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception)
            );
        }

        $this->redirect(
            'index.php?page=government_advisories'
        );
    }

    /* ==========================================
       REVIEW DECISION
    ========================================== */

    public function review(): void
    {
        $this->requireRole(
            'Admin'
        );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $advisoryId =
                (int) (
                    $_POST['government_advisory_id']
                    ?? 0
                );

            $decision =
                trim(
                    (string) (
                        $_POST['review_status']
                        ?? ''
                    )
                );

            $notes =
                trim(
                    (string) (
                        $_POST['review_notes']
                        ?? ''
                    )
                );

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $reviewed =
                $this->service
                ->review(
                    $advisoryId,
                    $decision,
                    $notes,
                    $adminId
                );

            try {
                $this->log(
                    'REVIEW_GOVERNMENT_ADVISORY',
                    'Marked government advisory #'
                        . $advisoryId
                        . ' as '
                        . $decision
                        . '.'
                );
            } catch (Throwable $loggingException) {
                /*
                 * Review remains successful if general
                 * activity logging is unavailable.
                 */
            }

            $this->setFlash(
                'success',
                'Government advisory marked as '
                    . strtolower(
                        (string) (
                            $reviewed['review_status']
                            ?? $decision
                        )
                    )
                    . '.'
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception)
            );
        }

        $this->redirect(
            'index.php?page=government_advisories'
        );
    }

    /* ==========================================
       FLASH MESSAGE
    ========================================== */

    private function setFlash(
        string $type,
        string $message
    ): void {
        $_SESSION['government_advisory_flash'] = [
            'type' =>
            $type,

            'message' =>
            $message
        ];
    }
}
