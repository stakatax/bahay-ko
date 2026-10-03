<?php

require_once __DIR__
    . '/../models/Dashboard.php';

class DashboardService
{
    private Dashboard $dashboard;

    public function __construct()
    {
        $this->dashboard =
            new Dashboard();
    }

    public function index(): array
    {
        $selectedRange =
            $this->resolveAnalyticsRange(
                $_GET['range']
                    ?? '30'
            );

        $selectedDepartmentScope =
            $this->resolveDepartmentScope(
                $_GET['scope']
                    ?? ''
            );

        $selectedDepartmentId =
            $this->resolveDepartmentId(
                $_GET['department_id']
                    ?? 0
            );

        if (
            $selectedDepartmentScope ===
            'schoolwide'
        ) {
            $selectedDepartmentId =
                0;

            $departmentBreakdown =
                $this->dashboard
                ->getSchoolwideContentBreakdown(
                    $selectedRange
                );
        } elseif (
            $selectedDepartmentId > 0
        ) {
            $departmentBreakdown =
                $this->dashboard
                ->getDepartmentContentBreakdown(
                    $selectedDepartmentId,
                    $selectedRange
                );
        } else {
            $departmentBreakdown = [];
        }

        /*
         * An invalid department selection must
         * not remain active. The school-wide
         * scope is always a valid analytics scope.
         */
        if (
            $selectedDepartmentScope !==
            'schoolwide' &&
            empty($departmentBreakdown)
        ) {
            $selectedDepartmentId =
                0;

            $selectedDepartmentScope =
                '';
        }

        return [
            /*
             * Current inventory and workflow.
             */
            'statistics' =>
            $this->dashboard
                ->getStatistics(),

            'workflow_statistics' =>
            $this->dashboard
                ->getWorkflowStatistics(),

            'actionable_insights' =>
            $this->dashboard
                ->getActionableInsights(),

            'student_survey_analytics' =>
            $this->dashboard
                ->getStudentSurveyAnalytics(),

            /*
             * Historical analytics.
             */
            'engagement' =>
            $this->dashboard
                ->getEngagementStatistics(
                    $selectedRange
                ),

            'content_view_rankings' =>
            $this->dashboard
                ->getContentViewRankings(
                    $selectedRange
                ),

            'department_posting_statistics' =>
            $this->dashboard
                ->getDepartmentPostingStatistics(
                    $selectedRange
                ),

            'department_breakdown' =>
            $departmentBreakdown,

            'selected_department_id' =>
            $selectedDepartmentId,

            'selected_department_scope' =>
            $selectedDepartmentScope,

            'recent_activity' =>
            $this->dashboard
                ->getRecentActivity(
                    $selectedRange
                ),

            /*
             * Operational lists.
             */
            'announcements' =>
            $this->dashboard
                ->getLatestAnnouncements(),

            'events' =>
            $this->dashboard
                ->getUpcomingEvents(),

            /*
             * Range metadata.
             */
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

            'notifications' =>
            []
        ];
    }

    /* ==========================================
       DASHBOARD REPORT DATA
    ========================================== */

    public function export(): array
    {
        /*
         * Reuse the same validated filters and
         * model queries used by the Dashboard.
         * This prevents screen/export mismatch.
         */
        return $this->index();
    }

    /* ==========================================
       VALIDATE ANALYTICS RANGE
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

    /* ==========================================
       ANALYTICS RANGE LABEL
    ========================================== */

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

    /* ==========================================
       VALIDATE DEPARTMENT FILTER
    ========================================== */

    private function resolveDepartmentId(
        mixed $departmentId
    ): int {
        if (is_array($departmentId)) {
            return 0;
        }

        $departmentId =
            trim(
                (string) $departmentId
            );

        if (
            $departmentId === '' ||
            !ctype_digit(
                $departmentId
            )
        ) {
            return 0;
        }

        $departmentId =
            (int) $departmentId;

        return $departmentId > 0
            ? $departmentId
            : 0;
    }


    /* ==========================================
       VALIDATE DEPARTMENT SCOPE
    ========================================== */

    private function resolveDepartmentScope(
        mixed $scope
    ): string {
        if (is_array($scope)) {
            return '';
        }

        $scope =
            strtolower(
                trim(
                    (string) $scope
                )
            );

        return $scope === 'schoolwide'
            ? 'schoolwide'
            : '';
    }
}
