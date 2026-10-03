<?php

class DepartmentAnalyticsExcelExporter
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

        $departmentName =
            trim(
                (string) (
                    $data['department_name']
                    ?? 'Faculty Department'
                )
            );

        $range =
            (string) (
                $data['selected_range']
                ?? '30'
            );

        $safeDepartment =
            preg_replace(
                '/[^a-zA-Z0-9_-]+/',
                '-',
                strtolower($departmentName)
            );

        $safeDepartment =
            trim(
                (string) $safeDepartment,
                '-'
            );

        if ($safeDepartment === '') {
            $safeDepartment =
                'faculty-department';
        }

        $filename =
            'olshco-department-report-'
            . $safeDepartment
            . '-'
            . $range
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
                'Unable to create the department report.'
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

        $this->writeStyles();

        $metrics =
            $this->calculateMetrics(
                $data
            );

        $this->writeExecutiveSummary(
            $data,
            $metrics
        );

        $this->writeEngagement(
            $data
        );

        $this->writeRankings(
            $data
        );

        $this->writeContentInventory(
            $data
        );

        $this->writeRecommendations(
            $data,
            $metrics
        );

        $this->xml->endElement();
        $this->xml->endDocument();
        $this->xml->flush();

        exit;
    }

    /* ==========================================
   DEPARTMENT DECISION METRICS
========================================== */

    private function calculateMetrics(
        array $data
    ): array {
        $summary =
            $data['summary']
            ?? [];

        $engagement =
            $data['engagement']
            ?? [];

        $rankings =
            $data['content_view_rankings']
            ?? [];

        $contentItems =
            $data['content_items']
            ?? [];

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

        $workflowCounts = [
            'draft' => 0,
            'pending_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'scheduled' => 0,
            'published' => 0,
            'archived' => 0
        ];

        foreach (
            $contentItems
            as $item
        ) {
            $status =
                strtolower(
                    trim(
                        (string) (
                            $item['workflow_status']
                            ?? ''
                        )
                    )
                );

            if (
                array_key_exists(
                    $status,
                    $workflowCounts
                )
            ) {
                $workflowCounts[$status]++;
            }
        }

        $contentTypeCounts = [
            'announcement' =>
            (int) (
                $summary['announcement_count']
                ?? 0
            ),

            'event' =>
            (int) (
                $summary['event_count']
                ?? 0
            ),

            'document' =>
            (int) (
                $summary['document_count']
                ?? 0
            ),

            'survey' =>
            (int) (
                $summary['survey_count']
                ?? 0
            )
        ];

        arsort(
            $contentTypeCounts
        );

        $dominantContentType =
            array_key_first(
                $contentTypeCounts
            );

        return [
            'total_posts' =>
            $totalPosts,

            'published_posts' =>
            $publishedPosts,

            'publication_rate' =>
            $totalPosts > 0
                ? (
                    $publishedPosts
                    / $totalPosts
                )
                : 0,

            'views' =>
            $views,

            'interactions' =>
            $interactions,

            'interaction_rate' =>
            $views > 0
                ? (
                    $interactions
                    / $views
                )
                : 0,

            'zero_view_sample' =>
            $zeroViewCount,

            'workflow_counts' =>
            $workflowCounts,

            'unfinished_workflow_count' =>
            $workflowCounts['draft']
                + $workflowCounts['pending_review']
                + $workflowCounts['rejected'],

            'dominant_content_type' =>
            $dominantContentType
                ?? 'content',

            'dominant_content_count' =>
            $dominantContentType !== null
                ? (
                    $contentTypeCounts[$dominantContentType]
                    ?? 0
                )
                : 0,

            'top_viewed_content' =>
            $rankings['most_viewed'][0]
                ?? null,

            'least_viewed_content' =>
            $rankings['least_viewed'][0]
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
        $summary =
            $data['summary']
            ?? [];

        $engagement =
            $data['engagement']
            ?? [];

        $this->startWorksheet(
            'Executive Summary',
            [
                185,
                120,
                285
            ]
        );

        $this->writeTitle(
            'OLSHCO Department Analytics Report',
            2
        );

        $this->writeSubtitle(
            (
                $data['department_name']
                ?? 'Faculty Department'
            )
                . ' | '
                . (
                    $data['range_label']
                    ?? 'Last 30 days'
                ),
            2
        );

        $this->writeBlankRow();

        $this->writeSection(
            'Report Information',
            2
        );

        $this->writeKeyValue(
            'Department',
            $data['department_name']
                ?? 'Faculty Department'
        );

        $this->writeKeyValue(
            'Analytics Period',
            $data['range_label']
                ?? 'Last 30 days'
        );

        $this->writeKeyValue(
            'Generated At',
            date('M d, Y g:i A')
        );

        $this->writeBlankRow();

        $this->writeSection(
            'Content Summary',
            2
        );

        $contentMetrics = [
            'Total Content' =>
            $summary['total_posts']
                ?? 0,

            'Published Content' =>
            $summary['published_posts']
                ?? 0,

            'Announcements' =>
            $summary['announcement_count']
                ?? 0,

            'Events' =>
            $summary['event_count']
                ?? 0,

            'Documents' =>
            $summary['document_count']
                ?? 0,

            'Surveys' =>
            $summary['survey_count']
                ?? 0
        ];

        foreach (
            $contentMetrics
            as $label => $value
        ) {
            $this->writeKeyValue(
                $label,
                (int) $value,
                'Number'
            );
        }

        $this->writeBlankRow();

        $this->writeSection(
            'Engagement Summary',
            2
        );

        $engagementMetrics = [
            'Views' =>
            $engagement['views']
                ?? 0,

            'Reactions' =>
            $engagement['reactions']
                ?? 0,

            'Comments' =>
            $engagement['comments']
                ?? 0,

            'Acknowledgments' =>
            $engagement['acknowledgments']
                ?? 0
        ];

        foreach (
            $engagementMetrics
            as $label => $value
        ) {
            $this->writeKeyValue(
                $label,
                (int) $value,
                'Number'
            );
        }

        $this->writeBlankRow();

        $this->writeSection(
            'Executive Decision Indicators',
            2
        );

        $this->writeKeyValue(
            'Publication Completion Rate',
            number_format(
                (
                    $metrics['publication_rate']
                    ?? 0
                ) * 100,
                2
            )
                . '%'
        );

        $this->writeKeyValue(
            'Interactions per 100 Views',
            number_format(
                (
                    $metrics['interaction_rate']
                    ?? 0
                ) * 100,
                2
            )
        );

        $this->writeKeyValue(
            'Unfinished Workflow Items',
            (int) (
                $metrics['unfinished_workflow_count']
                ?? 0
            ),
            'Number'
        );

        $this->writeKeyValue(
            'Zero-View Items in Ranking Sample',
            (int) (
                $metrics['zero_view_sample']
                ?? 0
            ),
            'Number'
        );

        $this->writeKeyValue(
            'Most Used Content Type',
            ucfirst(
                (string) (
                    $metrics['dominant_content_type']
                    ?? 'content'
                )
            )
        );

        $topViewedContent =
            $metrics['top_viewed_content']
            ?? null;

        $this->writeKeyValue(
            'Strongest Content by Reach',
            !empty($topViewedContent)
                ? (
                    (
                        $topViewedContent['title']
                        ?? 'Untitled Content'
                    )
                    . ' ('
                    . number_format(
                        (int) (
                            $topViewedContent['view_count']
                            ?? 0
                        )
                    )
                    . ' views)'
                )
                : 'No ranking data available'
        );

        $this->endWorksheet();
    }

    /* ==========================================
       ENGAGEMENT DETAILS
    ========================================== */

    private function writeEngagement(
        array $data
    ): void {
        $engagement =
            $data['engagement']
            ?? [];

        $views =
            (int) (
                $engagement['views']
                ?? 0
            );

        $metrics = [
            'Views' =>
            $views,

            'Reactions' =>
            (int) (
                $engagement['reactions']
                ?? 0
            ),

            'Comments' =>
            (int) (
                $engagement['comments']
                ?? 0
            ),

            'Acknowledgments' =>
            (int) (
                $engagement['acknowledgments']
                ?? 0
            )
        ];

        $this->startWorksheet(
            'Engagement',
            [
                180,
                110,
                180
            ]
        );

        $this->writeTitle(
            'Department Audience Engagement',
            2
        );

        $this->writeSubtitle(
            $data['range_label']
                ?? 'Last 30 days',
            2
        );

        $this->writeTableHeader([
            'Metric',
            'Total',
            'Rate per 100 Views'
        ]);

        foreach (
            $metrics
            as $label => $value
        ) {
            $rate =
                $label === 'Views' ||
                $views <= 0
                ? 0
                : (
                    $value
                    / $views
                ) * 100;

            $this->writeRow([
                $this->cell(
                    $label
                ),

                $this->cell(
                    $value,
                    'Number',
                    'Metric'
                ),

                $this->cell(
                    number_format(
                        $rate,
                        2
                    )
                        . ' per 100 views'
                )
            ]);
        }

        $this->endWorksheet();
    }

    /* ==========================================
       CONTENT VIEW RANKINGS
    ========================================== */

    private function writeRankings(
        array $data
    ): void {
        $rankings =
            $data['content_view_rankings']
            ?? [];

        $this->startWorksheet(
            'Content Rankings',
            [
                110,
                265,
                100,
                105,
                140
            ]
        );

        $this->writeTitle(
            'Department Content Rankings',
            4
        );

        $this->writeSubtitle(
            $data['range_label']
                ?? 'Last 30 days',
            4
        );

        $rankingGroups = [
            'Most Viewed Content' =>
            $rankings['most_viewed']
                ?? [],

            'Least Viewed Content' =>
            $rankings['least_viewed']
                ?? []
        ];

        foreach (
            $rankingGroups
            as $sectionTitle => $items
        ) {
            $this->writeSection(
                $sectionTitle,
                4
            );

            $this->writeTableHeader([
                'Type',
                'Title',
                'Views',
                'Content ID',
                'Content Date'
            ]);

            if (empty($items)) {
                $this->writeRow([
                    $this->cell(
                        'No ranking data is available.'
                    )
                ]);
            } else {
                foreach ($items as $item) {
                    $this->writeRow([
                        $this->cell(
                            ucfirst(
                                (string) (
                                    $item['content_type']
                                    ?? 'content'
                                )
                            )
                        ),

                        $this->cell(
                            $item['title']
                                ?? 'Untitled Content'
                        ),

                        $this->cell(
                            (int) (
                                $item['view_count']
                                ?? 0
                            ),
                            'Number',
                            'Metric'
                        ),

                        $this->cell(
                            (int) (
                                $item['content_id']
                                ?? 0
                            ),
                            'Number'
                        ),

                        $this->cell(
                            $this->formatDate(
                                $item['content_date']
                                    ?? null
                            )
                        )
                    ]);
                }
            }

            $this->writeBlankRow();
        }

        $this->endWorksheet();
    }

    /* ==========================================
       CONTENT INVENTORY
    ========================================== */

    private function writeContentInventory(
        array $data
    ): void {
        $items =
            $data['content_items']
            ?? [];

        $this->startWorksheet(
            'Content Inventory',
            [
                105,
                270,
                125,
                180,
                145,
                100
            ]
        );

        $this->writeTitle(
            'Department Content Inventory',
            5
        );

        $this->writeSubtitle(
            $data['range_label']
                ?? 'Last 30 days',
            5
        );

        $this->writeTableHeader([
            'Type',
            'Title',
            'Workflow Status',
            'Author',
            'Content Date',
            'Content ID'
        ]);

        if (empty($items)) {
            $this->writeRow([
                $this->cell(
                    'No department content was recorded during this period.'
                )
            ]);
        } else {
            foreach ($items as $item) {
                $workflowStatus =
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            (string) (
                                $item['workflow_status']
                                ?? 'unknown'
                            )
                        )
                    );

                $this->writeRow([
                    $this->cell(
                        ucfirst(
                            (string) (
                                $item['content_type']
                                ?? 'content'
                            )
                        )
                    ),

                    $this->cell(
                        $item['title']
                            ?? 'Untitled Content'
                    ),

                    $this->cell(
                        $workflowStatus
                    ),

                    $this->cell(
                        $item['author_name']
                            ?? 'Faculty Member'
                    ),

                    $this->cell(
                        $this->formatDate(
                            $item['content_date']
                                ?? null
                        )
                    ),

                    $this->cell(
                        (int) (
                            $item['content_id']
                            ?? 0
                        ),
                        'Number'
                    )
                ]);
            }
        }

        $this->endWorksheet();
    }

    /* ==========================================
       DECISION-SUPPORT RECOMMENDATIONS
    ========================================== */

    private function writeRecommendations(
        array $data,
        array $metrics
    ): void {
        $recommendations = [];

        $totalPosts =
            (int) (
                $metrics['total_posts']
                ?? 0
            );

        $publishedPosts =
            (int) (
                $metrics['published_posts']
                ?? 0
            );

        $publicationRate =
            (float) (
                $metrics['publication_rate']
                ?? 0
            );

        $views =
            (int) (
                $metrics['views']
                ?? 0
            );

        $interactionRate =
            (float) (
                $metrics['interaction_rate']
                ?? 0
            );

        $unfinishedWorkflowCount =
            (int) (
                $metrics['unfinished_workflow_count']
                ?? 0
            );

        $zeroViewCount =
            (int) (
                $metrics['zero_view_sample']
                ?? 0
            );

        if ($totalPosts === 0) {
            $recommendations[] = [
                'severity' =>
                'Information',

                'finding' =>
                'No department content was created',

                'action' =>
                'Confirm whether department updates are being published through the Digital Hub during the selected period.'
            ];
        }

        if (
            $totalPosts > 0 &&
            $publicationRate < 0.60
        ) {
            $recommendations[] = [
                'severity' =>
                'Warning',

                'finding' =>
                'Publication completion is below 60%',

                'action' =>
                'Review drafts, rejected submissions, and pending-review items to identify workflow delays.'
            ];
        } elseif (
            $totalPosts > 0 &&
            $publicationRate < 0.80
        ) {
            $recommendations[] = [
                'severity' =>
                'Information',

                'finding' =>
                'Publication completion can improve',

                'action' =>
                'Review unfinished department content and confirm whether it should be completed, revised, or archived.'
            ];
        }

        if ($unfinishedWorkflowCount > 0) {
            $recommendations[] = [
                'severity' =>
                'Warning',

                'finding' =>
                $unfinishedWorkflowCount
                    . ' unfinished workflow item(s) appear in the report sample',

                'action' =>
                'Open Content Workspace and prioritize drafts, rejected content, and submissions awaiting review.'
            ];
        }

        if ($zeroViewCount > 0) {
            $recommendations[] = [
                'severity' =>
                'Warning',

                'finding' =>
                $zeroViewCount
                    . ' item(s) in the Least Viewed sample recorded zero views',

                'action' =>
                'Review recipient targeting, publication timing, notification settings, and whether the content title clearly communicates its purpose.'
            ];
        }

        if (
            $publishedPosts > 0 &&
            $views === 0
        ) {
            $recommendations[] = [
                'severity' =>
                'Warning',

                'finding' =>
                'Published department content recorded no views',

                'action' =>
                'Verify that published items reached their intended audience and that users can access them from the Information Hub.'
            ];
        } elseif (
            $views > 0 &&
            $interactionRate < 0.10
        ) {
            $recommendations[] = [
                'severity' =>
                'Information',

                'finding' =>
                'Audience interaction is below 10 actions per 100 views',

                'action' =>
                'Use clearer calls to action and enable reactions, comments, or acknowledgments only when they support the content objective.'
            ];
        }

        $dominantContentCount =
            (int) (
                $metrics['dominant_content_count']
                ?? 0
            );

        if (
            $totalPosts >= 4 &&
            $dominantContentCount /
            $totalPosts >= 0.75
        ) {
            $dominantType =
                ucfirst(
                    (string) (
                        $metrics['dominant_content_type']
                        ?? 'content'
                    )
                );

            $recommendations[] = [
                'severity' =>
                'Information',

                'finding' =>
                $dominantType
                    . ' content represents at least 75% of department output',

                'action' =>
                'Confirm that this content mix reflects department needs. Consider supporting Events, Documents, or Surveys when appropriate.'
            ];
        }

        $topViewedContent =
            $metrics['top_viewed_content']
            ?? null;

        if (
            !empty($topViewedContent) &&
            (int) (
                $topViewedContent['view_count']
                ?? 0
            ) > 0
        ) {
            $recommendations[] = [
                'severity' =>
                'Success',

                'finding' =>
                '"'
                    . (
                        $topViewedContent['title']
                        ?? 'Untitled Content'
                    )
                    . '" currently has the strongest recorded reach',

                'action' =>
                'Review its title, audience targeting, timing, and format for patterns that may improve future department content.'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'severity' =>
                'Success',

                'finding' =>
                'No immediate performance concern was detected',

                'action' =>
                'Continue monitoring publication completion, audience reach, and meaningful interaction during the next reporting period.'
            ];
        }

        $this->startWorksheet(
            'Recommendations',
            [
                120,
                275,
                460
            ]
        );

        $this->writeTitle(
            'Faculty Decision Support',
            2
        );

        $this->writeSubtitle(
            (
                $data['department_name']
                ?? 'Faculty Department'
            )
                . ' | '
                . (
                    $data['range_label']
                    ?? 'Last 30 days'
                ),
            2
        );

        $this->writeTableHeader([
            'Severity',
            'Finding',
            'Recommended Action'
        ]);

        foreach (
            $recommendations
            as $recommendation
        ) {
            $severity =
                $recommendation['severity']
                ?? 'Information';

            $style =
                $severity === 'Warning'
                ? 'Warning'
                : 'Body';

            $this->writeRow([
                $this->cell(
                    $severity,
                    'String',
                    $style
                ),

                $this->cell(
                    $recommendation['finding']
                        ?? '',
                    'String',
                    $style
                ),

                $this->cell(
                    $recommendation['action']
                        ?? '',
                    'String',
                    'Recommendation'
                )
            ]);
        }

        $this->endWorksheet();
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
            10,
            'Center',
            '#,##0'
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
       WORKSHEET START AND END
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
        /*
         * End Table.
         */
        $this->xml->endElement();

        $this->xml->startElement(
            'WorksheetOptions'
        );

        $this->xml->writeAttribute(
            'xmlns',
            'urn:schemas-microsoft-com:office:excel'
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

        /*
         * End Worksheet.
         */
        $this->xml->endElement();
    }

    /* ==========================================
       HEADING HELPERS
    ========================================== */

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
        int $height
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

    /* ==========================================
       TABLE AND ROW HELPERS
    ========================================== */

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
                $key
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
        $this->writeRow(
            [
                $this->cell('')
            ],
            9
        );
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
                $cell['value']
                    ?? '',
                $cell['type']
                    ?? 'String'
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

    /* ==========================================
       VALUE FORMATTING
    ========================================== */

    private function formatDate(
        mixed $value
    ): string {
        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return 'Date unavailable';
        }

        $timestamp =
            strtotime($value);

        if ($timestamp === false) {
            return $value;
        }

        return date(
            'M d, Y g:i A',
            $timestamp
        );
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
