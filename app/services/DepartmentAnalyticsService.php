<?php

require_once __DIR__
    . '/../models/Dashboard.php';

require_once __DIR__
    . '/ContentWorkspaceService.php';



class DepartmentAnalyticsService
{
    private Dashboard $dashboard;

    private ContentWorkspaceService $workspace;

    public function __construct()
    {
        $this->dashboard =
            new Dashboard();

        $this->workspace =
            new ContentWorkspaceService();
    }

    public function index(
        int $departmentId,
        string $departmentName,
        mixed $requestedRange
    ): array {
        if ($departmentId <= 0) {
            throw new RuntimeException(
                'Your Faculty account is not assigned to a department.'
            );
        }

        $selectedRange =
            $this->resolveAnalyticsRange(
                $requestedRange
            );

        /*
         * The department ID comes from the
         * authenticated session, never the URL.
         */
        $breakdown =
            $this->dashboard
            ->getDepartmentContentBreakdown(
                $departmentId,
                $selectedRange,
                50
            );

        if (empty($breakdown)) {
            throw new RuntimeException(
                'Your assigned department could not be found or is inactive.'
            );
        }

        $departmentStatistics =
            $this->dashboard
            ->getDepartmentPostingStatistics(
                $selectedRange
            );

        $summary =
            $this->findDepartmentSummary(
                $departmentStatistics,
                $departmentId
            );

        $engagement =
            $this->dashboard
            ->getDepartmentEngagementStatistics(
                $departmentId,
                $selectedRange
            );

        $contentViewRankings =
            $this->dashboard
            ->getDepartmentContentViewRankings(
                $departmentId,
                $selectedRange
            );

        return [
            'department_id' =>
            $departmentId,

            'department_name' =>
            trim(
                $departmentName
            ) !== ''
                ? trim(
                    $departmentName
                )
                : (
                    $breakdown['department_name']
                    ?? 'Faculty Department'
                ),

            'selected_range' =>
            $selectedRange,

            'range_label' =>
            $this->resolveRangeLabel(
                $selectedRange
            ),

            'range_options' => [
                '7' =>
                'Last 7 days',

                '30' =>
                'Last 30 days',

                '90' =>
                'Last 90 days',

                'all' =>
                'All time'
            ],

            'summary' =>
            $summary,

            'content_items' =>
            $breakdown['items']
                ?? [],

            'engagement' =>
            $engagement,

            'content_view_rankings' =>
            $contentViewRankings,

            'recommendations' =>
            $this->buildRecommendations(
                $summary,
                $engagement,
                $contentViewRankings
            )
        ];
    }







    /* ==========================================
       SECURE DEPARTMENT CONTENT PREVIEW
    ========================================== */

    public function preview(
        int $departmentId,
        string $departmentName,
        mixed $requestedContentType,
        mixed $requestedContentId
    ): array {
        if ($departmentId <= 0) {
            throw new RuntimeException(
                'Your Faculty account is not assigned to a department.'
            );
        }

        if (is_array($requestedContentType)) {
            throw new InvalidArgumentException(
                'Invalid content type.'
            );
        }

        $contentType =
            strtolower(
                trim(
                    (string) $requestedContentType
                )
            );

        $allowedTypes = [
            'announcement',
            'event',
            'document',
            'survey'
        ];

        if (
            !in_array(
                $contentType,
                $allowedTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid content type.'
            );
        }

        if (is_array($requestedContentId)) {
            throw new InvalidArgumentException(
                'Invalid content ID.'
            );
        }

        $contentId =
            filter_var(
                $requestedContentId,
                FILTER_VALIDATE_INT
            );

        if (
            !$contentId ||
            $contentId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content ID.'
            );
        }

        /*
         * Authorization happens before loading
         * the full item. The requested content
         * must belong to a Faculty author in the
         * current session department.
         */
        $authorized =
            $this->dashboard
            ->canDepartmentAccessContent(
                $contentType,
                (int) $contentId,
                $departmentId
            );

        if (!$authorized) {
            throw new RuntimeException(
                'You are not authorized to preview this department content.'
            );
        }

        $content =
            $this->workspace
            ->findItem(
                $contentType,
                (int) $contentId
            );

        return [
            'department_id' =>
            $departmentId,

            'department_name' =>
            trim(
                $departmentName
            ) !== ''
                ? trim(
                    $departmentName
                )
                : 'Faculty Department',

            'content_type' =>
            $contentType,

            'content_id' =>
            (int) $contentId,

            'content' =>
            $content
        ];
    }

    /* ==========================================
       FIND CURRENT DEPARTMENT SUMMARY
    ========================================== */

    private function findDepartmentSummary(
        array $statistics,
        int $departmentId
    ): array {
        foreach (
            $statistics
            as $department
        ) {
            if (
                (int) (
                    $department['department_id']
                    ?? 0
                ) === $departmentId
            ) {
                return $department;
            }
        }

        return [
            'department_id' =>
            $departmentId,

            'department_name' =>
            'Faculty Department',

            'total_posts' => 0,
            'published_posts' => 0,
            'announcement_count' => 0,
            'event_count' => 0,
            'document_count' => 0,
            'survey_count' => 0
        ];
    }

    /* ==========================================
       DEPARTMENT RECOMMENDATIONS
    ========================================== */

    private function buildRecommendations(
        array $summary,
        array $engagement,
        array $rankings
    ): array {
        $recommendations = [];

        $totalPosts =
            (int) (
                $summary['total_posts']
                ?? 0
            );

        $publishedPosts =
            (int) (
                $summary['published_posts']
                ?? 0
            );

        $views =
            (int) (
                $engagement['views']
                ?? 0
            );

        $interactions =
            (int) (
                $engagement['reactions']
                ?? 0
            )
            +
            (int) (
                $engagement['comments']
                ?? 0
            )
            +
            (int) (
                $engagement['acknowledgments']
                ?? 0
            );

        if ($totalPosts === 0) {
            $recommendations[] = [
                'severity' =>
                'information',

                'title' =>
                'No recent department content',

                'message' =>
                'Your department has no recorded content during the selected period.'
            ];

            return $recommendations;
        }

        $publicationRate =
            $publishedPosts
            / $totalPosts;

        if ($publicationRate < 0.60) {
            $recommendations[] = [
                'severity' =>
                'warning',

                'title' =>
                'Publication completion needs review',

                'message' =>
                'Less than 60% of department content is currently published. '
                    . 'Review drafts, rejected items, and submissions awaiting action.'
            ];
        }

        if (
            (int) (
                $summary['announcement_count']
                ?? 0
            ) === 0
        ) {
            $recommendations[] = [
                'severity' =>
                'information',

                'title' =>
                'No department announcements',

                'message' =>
                'No Faculty-authored announcement was created during this period.'
            ];
        }

        $zeroViewCount = 0;

        foreach (
            $rankings['least_viewed']
                ?? []
            as $content
        ) {
            if (
                (int) (
                    $content['view_count']
                    ?? 0
                ) === 0
            ) {
                $zeroViewCount++;
            }
        }

        if ($zeroViewCount > 0) {
            $recommendations[] = [
                'severity' =>
                'warning',

                'title' =>
                'Department content has no recorded reach',

                'message' =>
                $zeroViewCount
                    . ' item(s) in the Least Viewed sample have zero views. '
                    . 'Review recipient targeting, release timing, and notification settings.'
            ];
        }

        if (
            $publishedPosts > 0 &&
            $views === 0
        ) {
            $recommendations[] = [
                'severity' =>
                'warning',

                'title' =>
                'No department content views',

                'message' =>
                'Published department content received no recorded views during this period.'
            ];
        } elseif (
            $views > 0 &&
            (
                $interactions
                / $views
            ) < 0.10
        ) {
            $recommendations[] = [
                'severity' =>
                'information',

                'title' =>
                'Low audience interaction',

                'message' =>
                'Department content recorded fewer than 10 interactions per 100 views. '
                    . 'Consider clearer calls to action and appropriate engagement settings.'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'severity' =>
                'success',

                'title' =>
                'Department analytics are stable',

                'message' =>
                'No immediate publishing, reach, or engagement concern was detected.'
            ];
        }

        return $recommendations;
    }

    /* ==========================================
       RANGE VALIDATION
    ========================================== */

    private function resolveAnalyticsRange(
        mixed $range
    ): string {
        if (is_array($range)) {
            return '30';
        }

        $range =
            strtolower(
                trim(
                    (string) $range
                )
            );

        return in_array(
            $range,
            [
                '7',
                '30',
                '90',
                'all'
            ],
            true
        )
            ? $range
            : '30';
    }

    private function resolveRangeLabel(
        string $range
    ): string {
        return match ($range) {
            '7' =>
            'Last 7 days',

            '90' =>
            'Last 90 days',

            'all' =>
            'All time',

            default =>
            'Last 30 days'
        };
    }
}
