<?php

class DashboardExcelExporter
{
    private XMLWriter $xml;

    /* ==========================================
       DOWNLOAD WORKBOOK
    ========================================== */

    public function download(
        array $data
    ): void {
        if (!class_exists('XMLWriter')) {
            throw new RuntimeException(
                'The XMLWriter PHP extension is required.'
            );
        }

        $range =
            (string) (
                $data['selected_range']
                ?? '30'
            );

        $filename =
            'olshco-dashboard-report-'
            . preg_replace(
                '/[^a-zA-Z0-9_-]/',
                '-',
                $range
            )
            . '-'
            . date('Y-m-d-His')
            . '.xml';

        header(
            'Content-Type: application/vnd.ms-excel; charset=UTF-8'
        );

        header(
            'Content-Disposition: attachment; filename="'
                . $filename
                . '"'
        );

        header(
            'Cache-Control: no-store, no-cache, must-revalidate'
        );

        header(
            'Pragma: no-cache'
        );

        $this->xml =
            new XMLWriter();

        if (
            !$this->xml->openURI(
                'php://output'
            )
        ) {
            throw new RuntimeException(
                'Unable to create the Excel report.'
            );
        }

        $this->xml->startDocument(
            '1.0',
            'UTF-8'
        );

        $this->xml->writePi(
            'mso-application',
            'progid="Excel.Sheet"'
        );

        $this->xml->startElement(
            'Workbook'
        );

        $this->xml->writeAttribute(
            'xmlns',
            'urn:schemas-microsoft-com:office:spreadsheet'
        );

        $this->xml->writeAttribute(
            'xmlns:o',
            'urn:schemas-microsoft-com:office:office'
        );

        $this->xml->writeAttribute(
            'xmlns:x',
            'urn:schemas-microsoft-com:office:excel'
        );

        $this->xml->writeAttribute(
            'xmlns:ss',
            'urn:schemas-microsoft-com:office:spreadsheet'
        );

        $this->xml->writeAttribute(
            'xmlns:html',
            'http://www.w3.org/TR/REC-html40'
        );

        $this->writeStyles();

        $metrics =
            $this->calculateMetrics(
                $data
            );

        $this->writeExecutiveSummary(
            $data,
            $metrics
        );

        $this->writeEngagementAndWorkflow(
            $data
        );

        $this->writeContentRankings(
            $data
        );

        $this->writeDepartmentAnalytics(
            $data
        );

        if (
            !empty($data['department_breakdown'])
        ) {
            $this->writeSelectedDepartment(
                $data
            );
        }

        $this->writeActionCenter(
            $data
        );

        $this->writeStudentSurveyAnalytics(
            $data
        );

        $this->xml->endElement();
        $this->xml->endDocument();
        $this->xml->flush();

        exit;
    }

    /* ==========================================
       METRICS
    ========================================== */

    private function calculateMetrics(
        array $data
    ): array {
        $departments =
            $data['department_posting_statistics']
            ?? [];

        $engagement =
            $data['engagement']
            ?? [];

        $rankings =
            $data['content_view_rankings']
            ?? [];

        $postCount = 0;
        $publishedCount = 0;
        $inactiveDepartmentCount = 0;

        foreach (
            $departments
            as $department
        ) {
            $departmentPosts =
                (int) (
                    $department['total_posts']
                    ?? 0
                );

            $postCount +=
                $departmentPosts;

            $publishedCount +=
                (int) (
                    $department['published_posts']
                    ?? 0
                );

            if (
                !empty($department['department_id']) &&
                $departmentPosts === 0
            ) {
                $inactiveDepartmentCount++;
            }
        }

        $views =
            (int) (
                $engagement['views']
                ?? 0
            );

        $reactions =
            (int) (
                $engagement['reactions']
                ?? 0
            );

        $comments =
            (int) (
                $engagement['comments']
                ?? 0
            );

        $acknowledgments =
            (int) (
                $engagement['acknowledgments']
                ?? 0
            );

        $interactions =
            $reactions
            + $comments
            + $acknowledgments;

        $zeroViewCount = 0;

        foreach (
            $rankings['least_viewed']
                ?? []
            as $item
        ) {
            if (
                (int) (
                    $item['view_count']
                    ?? 0
                ) === 0
            ) {
                $zeroViewCount++;
            }
        }

        return [
            'post_count' =>
            $postCount,

            'published_count' =>
            $publishedCount,

            'publication_rate' =>
            $postCount > 0
                ? (
                    $publishedCount
                    / $postCount
                )
                : 0,

            'views' =>
            $views,

            'reactions' =>
            $reactions,

            'comments' =>
            $comments,

            'acknowledgments' =>
            $acknowledgments,

            'interactions' =>
            $interactions,

            'interaction_rate' =>
            $views > 0
                ? (
                    $interactions
                    / $views
                )
                : 0,

            'inactive_departments' =>
            $inactiveDepartmentCount,

            'zero_view_sample' =>
            $zeroViewCount,

            'most_active_department' =>
            $departments[0]
                ?? null,

            'top_viewed_content' =>
            $rankings['most_viewed'][0]
                ?? null
        ];
    }

    /* ==========================================
       EXECUTIVE SUMMARY
    ========================================== */

    private function writeExecutiveSummary(
        array $data,
        array $metrics
    ): void {
        $this->startWorksheet(
            'Executive Summary',
            [
                170,
                120,
                110,
                360
            ]
        );

        $this->writeTitle(
            'OLSHCO Digital Hub Dashboard Report',
            3
        );

        $this->writeSubtitle(
            'Decision-support summary for '
                . (
                    $data['range_label']
                    ?? 'Last 30 days'
                ),
            3
        );

        $this->writeBlankRow();

        $this->writeSection(
            'Report Information',
            3
        );

        $this->writeKeyValue(
            'Analytics period',
            $data['range_label']
                ?? 'Last 30 days'
        );

        $this->writeKeyValue(
            'Generated at',
            date(
                'M d, Y g:i A'
            )
        );

        $this->writeKeyValue(
            'Timezone',
            'Asia/Manila'
        );

        $this->writeBlankRow();

        $this->writeSection(
            'Executive Indicators',
            3
        );

        $this->writeTableHeader([
            'Indicator',
            'Value',
            'Interpretation',
            'Decision Use'
        ]);

        $this->writeRow([
            $this->cell(
                'Posts created',
                'String'
            ),
            $this->cell(
                $metrics['post_count'],
                'Number',
                'Metric'
            ),
            $this->cell(
                'Content created during the selected period'
            ),
            $this->cell(
                'Measures posting activity'
            )
        ]);

        $this->writeRow([
            $this->cell(
                'Posts published'
            ),
            $this->cell(
                $metrics['published_count'],
                'Number',
                'Metric'
            ),
            $this->cell(
                'Created content currently published'
            ),
            $this->cell(
                'Shows completed dissemination'
            )
        ]);

        $this->writeRow([
            $this->cell(
                'Publication rate'
            ),
            $this->cell(
                $metrics['publication_rate'],
                'Number',
                'Percent'
            ),
            $this->cell(
                'Published posts divided by posts created'
            ),
            $this->cell(
                'Highlights workflow completion'
            )
        ]);

        $this->writeRow([
            $this->cell(
                'Views'
            ),
            $this->cell(
                $metrics['views'],
                'Number',
                'Metric'
            ),
            $this->cell(
                'Recorded content opens'
            ),
            $this->cell(
                'Indicates audience reach'
            )
        ]);

        $this->writeRow([
            $this->cell(
                'Interactions'
            ),
            $this->cell(
                $metrics['interactions'],
                'Number',
                'Metric'
            ),
            $this->cell(
                'Votes, comments, and acknowledgments'
            ),
            $this->cell(
                'Indicates audience response'
            )
        ]);

        $this->writeRow([
            $this->cell(
                'Interactions per view'
            ),
            $this->cell(
                $metrics['interaction_rate'],
                'Number',
                'Percent'
            ),
            $this->cell(
                'Directional indicator, not a unique-user rate'
            ),
            $this->cell(
                'Compares response against reach'
            )
        ]);

        $this->writeBlankRow();

        $this->writeSection(
            'Performance Highlights',
            3
        );

        $mostActive =
            $metrics['most_active_department'];

        $topContent =
            $metrics['top_viewed_content'];

        $this->writeKeyValue(
            'Most active posting group',
            $mostActive['department_name']
                ?? 'No posting data'
        );

        $this->writeKeyValue(
            'Posts by most active group',
            $mostActive['total_posts']
                ?? 0,
            'Number'
        );

        $this->writeKeyValue(
            'Top viewed content',
            $topContent['title']
                ?? 'No view data'
        );

        $this->writeKeyValue(
            'Top content views',
            $topContent['view_count']
                ?? 0,
            'Number'
        );

        $this->writeBlankRow();

        $this->writeSection(
            'Recommended Actions',
            3
        );

        foreach (
            $this->buildRecommendations(
                $metrics
            )
            as $recommendation
        ) {
            $this->writeMergedText(
                '• ' . $recommendation,
                3,
                'Recommendation'
            );
        }

        $this->endWorksheet();
    }

    /* ==========================================
       ENGAGEMENT AND WORKFLOW
    ========================================== */

    private function writeEngagementAndWorkflow(
        array $data
    ): void {
        $this->startWorksheet(
            'Engagement and Workflow',
            [
                190,
                120,
                150,
                330
            ]
        );

        $this->writeTitle(
            'Engagement and Workflow',
            3
        );

        $this->writeSubtitle(
            'Selected-period engagement and current workflow state',
            3
        );

        $this->writeBlankRow();

        $this->writeSection(
            'Selected-Period Engagement',
            3
        );

        $this->writeTableHeader([
            'Metric',
            'Value',
            'Period',
            'Meaning'
        ]);

        foreach (
            [
                'views' =>
                [
                    'Views',
                    'Recorded content opens'
                ],

                'reactions' =>
                [
                    'Votes',
                    'Like, Love, Care, and Wow'
                ],

                'comments' =>
                [
                    'Comments',
                    'Recorded audience discussions'
                ],

                'acknowledgments' =>
                [
                    'Acknowledgments',
                    'Confirmed required-content receipts'
                ]
            ]
            as $key => $configuration
        ) {
            $this->writeRow([
                $this->cell(
                    $configuration[0]
                ),
                $this->cell(
                    $data['engagement'][$key]
                        ?? 0,
                    'Number',
                    'Metric'
                ),
                $this->cell(
                    $data['range_label']
                        ?? 'Last 30 days'
                ),
                $this->cell(
                    $configuration[1]
                )
            ]);
        }

        $this->writeBlankRow();

        $this->writeSection(
            'Current Workflow',
            3
        );

        $this->writeTableHeader([
            'Status',
            'Content Count',
            'Scope',
            'Administrative Meaning'
        ]);

        foreach (
            [
                'pending_review' =>
                [
                    'Pending Review',
                    'Waiting for administrative review'
                ],

                'approved' =>
                [
                    'Approved',
                    'Cleared for release'
                ],

                'scheduled' =>
                [
                    'Scheduled',
                    'Waiting for release condition'
                ],

                'published' =>
                [
                    'Published',
                    'Available to eligible recipients'
                ]
            ]
            as $key => $configuration
        ) {
            $this->writeRow([
                $this->cell(
                    $configuration[0]
                ),
                $this->cell(
                    $data['workflow_statistics'][$key]
                        ?? 0,
                    'Number',
                    'Metric'
                ),
                $this->cell(
                    'Current state'
                ),
                $this->cell(
                    $configuration[1]
                )
            ]);
        }

        $this->endWorksheet();
    }

    /* ==========================================
       CONTENT RANKINGS
    ========================================== */

    private function writeContentRankings(
        array $data
    ): void {
        $this->startWorksheet(
            'Content Rankings',
            [
                130,
                310,
                100,
                120,
                170
            ]
        );

        $this->writeTitle(
            'Content Reach Rankings',
            4
        );

        $this->writeSubtitle(
            'Published content ranked during '
                . (
                    $data['range_label']
                    ?? 'Last 30 days'
                ),
            4
        );

        foreach (
            [
                'most_viewed' =>
                'Most Viewed Content',

                'least_viewed' =>
                'Least Viewed Content'
            ]
            as $key => $label
        ) {
            $this->writeBlankRow();

            $this->writeSection(
                $label,
                4
            );

            $this->writeTableHeader([
                'Type',
                'Title',
                'Views',
                'Content Date',
                'Decision Use'
            ]);

            foreach (
                $data['content_view_rankings'][$key]
                    ?? []
                as $content
            ) {
                $views =
                    (int) (
                        $content['view_count']
                        ?? 0
                    );

                $this->writeRow([
                    $this->cell(
                        ucfirst(
                            $content['content_type']
                                ?? 'content'
                        )
                    ),
                    $this->cell(
                        $content['title']
                            ?? 'Untitled Content'
                    ),
                    $this->cell(
                        $views,
                        'Number',
                        $views === 0
                            ? 'WarningNumber'
                            : 'Metric'
                    ),
                    $this->cell(
                        $content['content_date']
                            ?? ''
                    ),
                    $this->cell(
                        $views === 0
                            ? 'Review targeting and notification settings'
                            : 'Monitor reach and audience response'
                    )
                ]);
            }
        }

        $this->endWorksheet();
    }

    /* ==========================================
       DEPARTMENT ANALYTICS
    ========================================== */

    private function writeDepartmentAnalytics(
        array $data
    ): void {
        $this->startWorksheet(
            'Department Analytics',
            [
                220,
                100,
                100,
                105,
                105,
                105,
                105,
                120
            ]
        );

        $this->writeTitle(
            'Department Posting Analytics',
            7
        );

        $this->writeSubtitle(
            'Posting activity during '
                . (
                    $data['range_label']
                    ?? 'Last 30 days'
                ),
            7
        );

        $this->writeBlankRow();

        $this->writeTableHeader([
            'Posting Group',
            'Total',
            'Published',
            'Announcements',
            'Events',
            'Documents',
            'Surveys',
            'Observation'
        ]);

        foreach (
            $data['department_posting_statistics']
                ?? []
            as $department
        ) {
            $total =
                (int) (
                    $department['total_posts']
                    ?? 0
                );

            $this->writeRow([
                $this->cell(
                    $department['department_name']
                        ?? 'Unknown Department'
                ),
                $this->cell(
                    $total,
                    'Number',
                    'Metric'
                ),
                $this->cell(
                    $department['published_posts']
                        ?? 0,
                    'Number'
                ),
                $this->cell(
                    $department['announcement_count']
                        ?? 0,
                    'Number'
                ),
                $this->cell(
                    $department['event_count']
                        ?? 0,
                    'Number'
                ),
                $this->cell(
                    $department['document_count']
                        ?? 0,
                    'Number'
                ),
                $this->cell(
                    $department['survey_count']
                        ?? 0,
                    'Number'
                ),
                $this->cell(
                    $total === 0
                        ? 'No posting activity during this period'
                        : 'Active during the selected period',
                    'String',
                    $total === 0
                        ? 'Warning'
                        : 'Body'
                )
            ]);
        }

        $this->endWorksheet();
    }

    /* ==========================================
       SELECTED DEPARTMENT
    ========================================== */

    private function writeSelectedDepartment(
        array $data
    ): void {
        $breakdown =
            $data['department_breakdown'];

        $this->startWorksheet(
            'Selected Department',
            [
                130,
                310,
                125,
                180,
                150
            ]
        );

        $this->writeTitle(
            $breakdown['department_name']
                ?? 'Selected Department',
            4
        );

        $this->writeSubtitle(
            'Content breakdown for '
                . (
                    $data['range_label']
                    ?? 'Last 30 days'
                ),
            4
        );

        $this->writeBlankRow();

        $this->writeTableHeader([
            'Type',
            'Title',
            'Workflow Status',
            'Author',
            'Content Date'
        ]);

        foreach (
            $breakdown['items']
                ?? []
            as $content
        ) {
            $this->writeRow([
                $this->cell(
                    ucfirst(
                        $content['content_type']
                            ?? 'content'
                    )
                ),
                $this->cell(
                    $content['title']
                        ?? 'Untitled Content'
                ),
                $this->cell(
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $content['workflow_status']
                                ?? ''
                        )
                    )
                ),
                $this->cell(
                    $content['author_name']
                        ?? 'Unknown Author'
                ),
                $this->cell(
                    $content['content_date']
                        ?? ''
                )
            ]);
        }

        $this->endWorksheet();
    }

    /* ==========================================
       STUDENT SURVEY ANALYTICS
    ========================================== */

    private function writeStudentSurveyAnalytics(
        array $data
    ): void {
        $analytics =
            is_array(
                $data['student_survey_analytics']
                    ?? null
            )
            ? $data['student_survey_analytics']
            : [];

        $completion =
            is_array(
                $analytics['completion']
                    ?? null
            )
            ? $analytics['completion']
            : [];

        $minimumGroupSize =
            max(
                1,
                (int) (
                    $analytics['minimum_group_size']
                    ?? 5
                )
            );

        $this->startWorksheet(
            'Student Survey',
            [
                170,
                330,
                220,
                90,
                100,
                210
            ]
        );

        $this->writeTitle(
            'Student Access and Learning Survey Analytics',
            5
        );

        $this->writeSubtitle(
            'Current aggregate profile survey results. '
                . 'Individual responses and small answer groups '
                . 'are not included.',
            5
        );

        $this->writeBlankRow();

        $this->writeSection(
            'Survey Completion',
            5
        );

        $this->writeTableHeader([
            'Metric',
            'Value',
            'Context',
            '',
            '',
            ''
        ]);

        $totalStudents =
            (int) (
                $completion['total_students']
                ?? 0
            );

        $completedStudents =
            (int) (
                $completion['completed']
                ?? 0
            );

        $this->writeRow([
            $this->cell(
                'Active Student accounts'
            ),
            $this->cell(
                $totalStudents,
                'Number',
                'Metric'
            ),
            $this->cell(
                'All active accounts assigned to the Student role'
            ),
            $this->cell(''),
            $this->cell(''),
            $this->cell('')
        ]);

        $this->writeRow([
            $this->cell(
                'Not started'
            ),
            $this->cell(
                (int) (
                    $completion['not_started']
                    ?? 0
                ),
                'Number'
            ),
            $this->cell(
                'Students with no saved survey progress'
            ),
            $this->cell(''),
            $this->cell(''),
            $this->cell('')
        ]);

        $this->writeRow([
            $this->cell(
                'In progress'
            ),
            $this->cell(
                (int) (
                    $completion['in_progress']
                    ?? 0
                ),
                'Number'
            ),
            $this->cell(
                'Students with saved but incomplete profiles'
            ),
            $this->cell(''),
            $this->cell(''),
            $this->cell('')
        ]);

        $this->writeRow([
            $this->cell(
                'Completed'
            ),
            $this->cell(
                $completedStudents,
                'Number',
                'Metric'
            ),
            $this->cell(
                'Completed survey version '
                    . (
                        (int) (
                            $analytics['survey_version']
                            ?? 0
                        )
                    )
            ),
            $this->cell(''),
            $this->cell(''),
            $this->cell('')
        ]);

        $this->writeRow([
            $this->cell(
                'Completion rate'
            ),
            $this->cell(
                (float) (
                    $completion['completion_rate']
                    ?? 0
                ),
                'Number',
                'Metric'
            ),
            $this->cell(
                'Percentage of active Students with a completed survey'
            ),
            $this->cell(''),
            $this->cell(''),
            $this->cell('')
        ]);

        $this->writeBlankRow();

        $this->writeSection(
            'Aggregate Response Indicators',
            5
        );

        $this->writeMergedText(
            'Privacy rule: a response category is exported only '
                . 'when at least '
                . $minimumGroupSize
                . ' completed Students share that category. '
                . 'Accessibility and sensitive wellbeing answers '
                . 'are excluded from analytics.',
            5,
            'Subtitle'
        );

        $this->writeTableHeader([
            'Section',
            'Indicator',
            'Response Category',
            'Count',
            'Percentage',
            'Privacy Status'
        ]);

        $sections =
            is_array(
                $analytics['sections']
                    ?? null
            )
            ? $analytics['sections']
            : [];

        if ($sections === []) {
            $this->writeRow([
                $this->cell(
                    'No active survey'
                ),
                $this->cell(
                    'No aggregate indicators are configured'
                ),
                $this->cell(''),
                $this->cell(''),
                $this->cell(''),
                $this->cell(
                    'Unavailable',
                    'String',
                    'Warning'
                )
            ]);
        }

        foreach ($sections as $section) {
            $sectionLabel =
                (string) (
                    $section['section_label']
                    ?? 'Survey Section'
                );

            $questions =
                is_array(
                    $section['questions']
                        ?? null
                )
                ? $section['questions']
                : [];

            foreach ($questions as $question) {
                $questionText =
                    (string) (
                        $question['question_text']
                        ?? 'Survey indicator'
                    );

                $respondentCount =
                    (int) (
                        $question['respondent_count']
                        ?? 0
                    );

                $available =
                    !empty($question['available']);

                $items =
                    is_array(
                        $question['items']
                            ?? null
                    )
                    ? $question['items']
                    : [];

                if (
                    !$available ||
                    $items === []
                ) {
                    $this->writeRow([
                        $this->cell(
                            $sectionLabel
                        ),
                        $this->cell(
                            $questionText
                        ),
                        $this->cell(
                            'Protected small group'
                        ),
                        $this->cell(''),
                        $this->cell(''),
                        $this->cell(
                            'Aggregate unavailable; '
                                . $respondentCount
                                . ' completed response'
                                . (
                                    $respondentCount === 1
                                    ? ''
                                    : 's'
                                ),
                            'String',
                            'Warning'
                        )
                    ]);

                    continue;
                }

                foreach ($items as $item) {
                    $this->writeRow([
                        $this->cell(
                            $sectionLabel
                        ),
                        $this->cell(
                            $questionText
                        ),
                        $this->cell(
                            $item['label']
                                ?? 'Response'
                        ),
                        $this->cell(
                            (int) (
                                $item['count']
                                ?? 0
                            ),
                            'Number',
                            'Metric'
                        ),
                        $this->cell(
                            (float) (
                                $item['percentage']
                                ?? 0
                            ),
                            'Number'
                        ),
                        $this->cell(
                            'Reportable aggregate'
                        )
                    ]);
                }

                if (
                    (
                        $question['suppressed_response_count']
                        ?? 0
                    ) > 0
                ) {
                    /*
                     * Do not export the exact suppressed
                     * count or its response labels.
                     */
                    $this->writeRow([
                        $this->cell(
                            $sectionLabel
                        ),
                        $this->cell(
                            $questionText
                        ),
                        $this->cell(
                            'Additional protected categories'
                        ),
                        $this->cell(''),
                        $this->cell(''),
                        $this->cell(
                            'Small groups suppressed',
                            'String',
                            'Warning'
                        )
                    ]);
                }
            }
        }

        $this->endWorksheet();
    }


    /* ==========================================
       ACTION CENTER
    ========================================== */

    private function writeActionCenter(
        array $data
    ): void {
        $this->startWorksheet(
            'Action Center',
            [
                190,
                120,
                430
            ]
        );

        $this->writeTitle(
            'Administrative Action Center',
            2
        );

        $this->writeSubtitle(
            'Live system findings requiring review or follow-up',
            2
        );

        $this->writeBlankRow();

        $this->writeTableHeader([
            'Finding',
            'Severity',
            'Explanation'
        ]);

        foreach (
            $data['actionable_insights']
                ?? []
            as $insight
        ) {
            $severity =
                strtolower(
                    $insight['severity']
                        ?? 'information'
                );

            $this->writeRow([
                $this->cell(
                    $insight['title']
                        ?? 'System Finding'
                ),
                $this->cell(
                    ucfirst(
                        $severity
                    ),
                    'String',
                    in_array(
                        $severity,
                        [
                            'warning',
                            'danger'
                        ],
                        true
                    )
                        ? 'Warning'
                        : 'Body'
                ),
                $this->cell(
                    $insight['message']
                        ?? ''
                )
            ]);
        }

        $this->endWorksheet();
    }

    /* ==========================================
       RECOMMENDATION RULES
    ========================================== */

    private function buildRecommendations(
        array $metrics
    ): array {
        $recommendations = [];

        if (
            $metrics['post_count'] === 0
        ) {
            $recommendations[] =
                'No content was created during the selected period. '
                . 'Confirm whether departments are using the publishing workflow.';
        }

        if (
            $metrics['post_count'] > 0 &&
            $metrics['publication_rate'] < 0.60
        ) {
            $recommendations[] =
                'The publication rate is below 60%. '
                . 'Review drafts, rejected content, and approval bottlenecks.';
        }

        if (
            $metrics['zero_view_sample'] > 0
        ) {
            $recommendations[] =
                $metrics['zero_view_sample']
                . ' item(s) in the Least Viewed sample have zero views. '
                . 'Review recipient targeting, timing, and notification settings.';
        }

        if (
            $metrics['inactive_departments'] > 0
        ) {
            $recommendations[] =
                $metrics['inactive_departments']
                . ' active department(s) posted no content during the selected period.';
        }

        if (
            $metrics['views'] > 0 &&
            $metrics['interaction_rate'] < 0.10
        ) {
            $recommendations[] =
                'Audience interaction is below 10 actions per 100 views. '
                . 'Consider clearer calls to action and appropriate engagement options.';
        }

        if (empty($recommendations)) {
            $recommendations[] =
                'No immediate performance concern was detected from the selected-period indicators.';
        }

        return $recommendations;
    }

    /* ==========================================
       WORKBOOK STYLES
    ========================================== */

    private function writeStyles(): void
    {
        $this->xml->startElement(
            'Styles'
        );

        $this->writeStyle(
            'Default',
            '#20242A',
            '#FFFFFF',
            false,
            10,
            'Left'
        );

        $this->writeStyle(
            'Title',
            '#FFFFFF',
            '#8B0000',
            true,
            18,
            'Left'
        );

        $this->writeStyle(
            'Subtitle',
            '#5B6470',
            '#F6EEEE',
            false,
            10,
            'Left'
        );

        $this->writeStyle(
            'Section',
            '#FFFFFF',
            '#A60000',
            true,
            11,
            'Left'
        );

        $this->writeStyle(
            'Header',
            '#FFFFFF',
            '#315DB5',
            true,
            10,
            'Center'
        );

        $this->writeStyle(
            'Body',
            '#20242A',
            '#FFFFFF',
            false,
            10,
            'Left'
        );

        $this->writeStyle(
            'Metric',
            '#8B0000',
            '#FFF7F7',
            true,
            11,
            'Center',
            '#,##0'
        );

        $this->writeStyle(
            'Percent',
            '#8B0000',
            '#FFF7F7',
            true,
            11,
            'Center',
            '0.00%'
        );

        $this->writeStyle(
            'Warning',
            '#8A4B00',
            '#FFF2CF',
            true,
            10,
            'Left'
        );

        $this->writeStyle(
            'WarningNumber',
            '#B42328',
            '#FEE4E4',
            true,
            11,
            'Center',
            '#,##0'
        );

        $this->writeStyle(
            'Recommendation',
            '#20242A',
            '#F3F7FF',
            false,
            10,
            'Left'
        );

        $this->xml->endElement();
    }

    private function writeStyle(
        string $id,
        string $fontColor,
        string $backgroundColor,
        bool $bold,
        int $size,
        string $horizontal,
        ?string $numberFormat = null
    ): void {
        $this->xml->startElement(
            'Style'
        );

        $this->xml->writeAttribute(
            'ss:ID',
            $id
        );

        $this->xml->startElement(
            'Font'
        );

        $this->xml->writeAttribute(
            'ss:FontName',
            'Calibri'
        );

        $this->xml->writeAttribute(
            'ss:Size',
            (string) $size
        );

        $this->xml->writeAttribute(
            'ss:Color',
            $fontColor
        );

        if ($bold) {
            $this->xml->writeAttribute(
                'ss:Bold',
                '1'
            );
        }

        $this->xml->endElement();

        $this->xml->startElement(
            'Interior'
        );

        $this->xml->writeAttribute(
            'ss:Color',
            $backgroundColor
        );

        $this->xml->writeAttribute(
            'ss:Pattern',
            'Solid'
        );

        $this->xml->endElement();

        $this->xml->startElement(
            'Alignment'
        );

        $this->xml->writeAttribute(
            'ss:Horizontal',
            $horizontal
        );

        $this->xml->writeAttribute(
            'ss:Vertical',
            'Center'
        );

        $this->xml->writeAttribute(
            'ss:WrapText',
            '1'
        );

        $this->xml->endElement();

        $this->xml->startElement(
            'Borders'
        );

        foreach (
            [
                'Bottom',
                'Left',
                'Right',
                'Top'
            ]
            as $position
        ) {
            $this->xml->startElement(
                'Border'
            );

            $this->xml->writeAttribute(
                'ss:Position',
                $position
            );

            $this->xml->writeAttribute(
                'ss:LineStyle',
                'Continuous'
            );

            $this->xml->writeAttribute(
                'ss:Weight',
                '1'
            );

            $this->xml->writeAttribute(
                'ss:Color',
                '#E1E5EA'
            );

            $this->xml->endElement();
        }

        $this->xml->endElement();

        if ($numberFormat !== null) {
            $this->xml->startElement(
                'NumberFormat'
            );

            $this->xml->writeAttribute(
                'ss:Format',
                $numberFormat
            );

            $this->xml->endElement();
        }

        $this->xml->endElement();
    }

    /* ==========================================
       WORKSHEET HELPERS
    ========================================== */

    private function startWorksheet(
        string $name,
        array $columnWidths
    ): void {
        $this->xml->startElement(
            'Worksheet'
        );

        $this->xml->writeAttribute(
            'ss:Name',
            mb_substr(
                $name,
                0,
                31
            )
        );

        $this->xml->startElement(
            'Table'
        );

        foreach (
            $columnWidths
            as $width
        ) {
            $this->xml->startElement(
                'Column'
            );

            $this->xml->writeAttribute(
                'ss:Width',
                (string) $width
            );

            $this->xml->endElement();
        }
    }

    private function endWorksheet(): void
    {
        $this->xml->endElement();

        $this->xml->startElement(
            'WorksheetOptions'
        );

        $this->xml->writeAttribute(
            'xmlns',
            'urn:schemas-microsoft-com:office:excel'
        );

        $this->xml->writeElement(
            'Selected',
            ''
        );

        $this->xml->writeElement(
            'ProtectObjects',
            'False'
        );

        $this->xml->writeElement(
            'ProtectScenarios',
            'False'
        );

        $this->xml->endElement();
        $this->xml->endElement();
    }

    private function writeTitle(
        string $value,
        int $mergeAcross
    ): void {
        $this->writeMergedText(
            $value,
            $mergeAcross,
            'Title',
            34
        );
    }

    private function writeSubtitle(
        string $value,
        int $mergeAcross
    ): void {
        $this->writeMergedText(
            $value,
            $mergeAcross,
            'Subtitle',
            26
        );
    }

    private function writeSection(
        string $value,
        int $mergeAcross
    ): void {
        $this->writeMergedText(
            $value,
            $mergeAcross,
            'Section',
            24
        );
    }

    private function writeMergedText(
        string $value,
        int $mergeAcross,
        string $style,
        int $height = 30
    ): void {
        $this->xml->startElement(
            'Row'
        );

        $this->xml->writeAttribute(
            'ss:Height',
            (string) $height
        );

        $this->xml->startElement(
            'Cell'
        );

        $this->xml->writeAttribute(
            'ss:StyleID',
            $style
        );

        $this->xml->writeAttribute(
            'ss:MergeAcross',
            (string) $mergeAcross
        );

        $this->writeData(
            $value,
            'String'
        );

        $this->xml->endElement();
        $this->xml->endElement();
    }

    private function writeTableHeader(
        array $labels
    ): void {
        $cells = [];

        foreach (
            $labels
            as $label
        ) {
            $cells[] =
                $this->cell(
                    $label,
                    'String',
                    'Header'
                );
        }

        $this->writeRow(
            $cells,
            24
        );
    }

    private function writeKeyValue(
        string $key,
        mixed $value,
        string $type = 'String'
    ): void {
        $this->writeRow([
            $this->cell(
                $key,
                'String',
                'Body'
            ),
            $this->cell(
                $value,
                $type,
                $type === 'Number'
                    ? 'Metric'
                    : 'Body'
            )
        ]);
    }

    private function writeBlankRow(): void
    {
        $this->writeRow([
            $this->cell('')
        ], 9);
    }

    private function writeRow(
        array $cells,
        int $height = 22
    ): void {
        $this->xml->startElement(
            'Row'
        );

        $this->xml->writeAttribute(
            'ss:AutoFitHeight',
            '1'
        );

        $this->xml->writeAttribute(
            'ss:Height',
            (string) $height
        );

        foreach (
            $cells
            as $cell
        ) {
            $this->xml->startElement(
                'Cell'
            );

            if (
                !empty($cell['style'])
            ) {
                $this->xml->writeAttribute(
                    'ss:StyleID',
                    $cell['style']
                );
            }

            $this->writeData(
                $cell['value'],
                $cell['type']
            );

            $this->xml->endElement();
        }

        $this->xml->endElement();
    }

    private function writeData(
        mixed $value,
        string $type
    ): void {
        $this->xml->startElement(
            'Data'
        );

        $this->xml->writeAttribute(
            'ss:Type',
            $type
        );

        $this->xml->text(
            $this->sanitizeText(
                $value
            )
        );

        $this->xml->endElement();
    }

    private function cell(
        mixed $value,
        string $type = 'String',
        string $style = 'Body'
    ): array {
        return [
            'value' =>
            $value,

            'type' =>
            $type,

            'style' =>
            $style
        ];
    }

    private function sanitizeText(
        mixed $value
    ): string {
        $value =
            (string) $value;

        return preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $value
        )
            ?? '';
    }
}
