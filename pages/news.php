<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$announcements =
    $viewData['announcements']
    ?? [];

$events =
    $viewData['events']
    ?? [];

$documents =
    $viewData['documents']
    ?? [];

$surveys =
    $viewData['surveys']
    ?? [];

$currentUserInterestWeights =
    is_array(
        $viewData['current_user_interest_weights']
            ?? null
    )
    ? $viewData['current_user_interest_weights']
    : [];

$currentRole =
    $_SESSION['role']
    ?? 'Guest';

$isLoggedIn =
    !empty($_SESSION['user_id']);

$currentUserId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );

$isAdministrator =
    $currentRole === 'Admin';

$hubItems = [];

/* ==========================================
   PERSONALIZED RANKING CALCULATOR
========================================== */

$calculateHubRanking =
    static function (
        array $item
    ) use (
        $currentUserInterestWeights
    ): array {
        $now =
            time();

        $type =
            strtolower(
                trim(
                    (string) (
                        $item['type']
                        ?? ''
                    )
                )
            );

        $priority =
            strtolower(
                trim(
                    (string) (
                        $item['priority']
                        ?? 'normal'
                    )
                )
            );

        $rawData =
            is_array(
                $item['data']
                    ?? null
            )
            ? $item['data']
            : [];

        $priorityScores = [
            'emergency' => 1000,
            'urgent' => 850,
            'important' => 700,
            'high' => 550,
            'medium' => 400,
            'normal' => 350,
            'scheduled' => 300,
            'survey' => 300,
            'reference' => 250,
            'low' => 200
        ];

        $score =
            $priorityScores[$priority]
            ?? 300;

        $reasons = [];

        if (
            in_array(
                $priority,
                [
                    'emergency',
                    'urgent',
                    'important',
                    'high'
                ],
                true
            )
        ) {
            $reasons[] =
                ucfirst($priority)
                . ' priority';
        }

        /*
 * Content matching a more specific part
 * of the current user's profile ranks higher.
 */
        $targetSpecificityScore =
            (int) (
                $rawData['target_specificity_score']
                ?? 0
            );

        $targetSpecificityReason =
            trim(
                (string) (
                    $rawData['target_specificity_reason']
                    ?? ''
                )
            );

        if ($targetSpecificityScore > 0) {
            $score +=
                $targetSpecificityScore;

            if ($targetSpecificityReason !== '') {
                $reasons[] =
                    $targetSpecificityReason;
            }
        }

        /*
 * Student-selected interests provide a
 * controlled relevance boost. Ranking tiers
 * remain unchanged, so interests cannot
 * outrank emergency or required actions.
 */
        $contentInterestIds =
            is_array(
                $rawData['interest_ids']
                    ?? null
            )
            ? $rawData['interest_ids']
            : [];

        $matchedInterestWeightTotal =
            0;

        foreach (
            array_unique(
                array_map(
                    static fn(
                        mixed $interestId
                    ): int =>
                    (int) $interestId,
                    $contentInterestIds
                )
            )
            as $interestId
        ) {
            if (
                $interestId <= 0 ||
                !isset(
                    $currentUserInterestWeights[$interestId]
                )
            ) {
                continue;
            }

            $matchedInterestWeightTotal +=
                max(
                    1,
                    min(
                        5,
                        (int) (
                            $currentUserInterestWeights[$interestId]
                        )
                    )
                );
        }

        if ($matchedInterestWeightTotal > 0) {
            $interestRelevanceScore =
                min(
                    180,
                    $matchedInterestWeightTotal * 30
                );

            $score +=
                $interestRelevanceScore;

            $reasons[] =
                'Matches your interests';
        }

        /*
 * Unread content receives a temporary boost.
 * Viewed content remains available but moves down.
 */
        $userViewed =
            !empty($rawData['user_viewed']);

        if (!$userViewed) {
            $score +=
                120;

            $reasons[] =
                'Not viewed yet';
        } else {
            $score -=
                40;
        }

        /*
 * An eligible unanswered Survey is an
 * incomplete action for the current user.
 */
        if ($type === 'survey') {
            $userResponded =
                !empty($rawData['user_responded']);

            if (!$userResponded) {
                $score +=
                    200;

                $reasons[] =
                    'Survey response needed';
            } else {
                $score -=
                    200;

                $reasons[] =
                    'Survey already completed';
            }
        }

        /*
         * Events are ranked by how soon
         * they will occur.
         */
        if ($type === 'event') {
            $eventDate =
                trim(
                    (string) (
                        $rawData['event_date']
                        ?? ''
                    )
                );

            $endDate =
                trim(
                    (string) (
                        $rawData['end_date']
                        ?? ''
                    )
                );

            $eventTimestamp =
                $eventDate !== ''
                ? strtotime($eventDate)
                : false;

            $endTimestamp =
                $endDate !== ''
                ? strtotime($endDate)
                : false;

            $expirationTimestamp =
                $endTimestamp !== false
                ? $endTimestamp
                : $eventTimestamp;

            if (
                $expirationTimestamp !== false &&
                $expirationTimestamp < $now
            ) {
                $score -= 1000;

                $reasons[] =
                    'Event has ended';
            } elseif (
                $eventTimestamp !== false
            ) {
                $hoursUntilEvent =
                    (
                        $eventTimestamp -
                        $now
                    ) / 3600;

                if ($hoursUntilEvent <= 6) {
                    $score += 250;

                    $reasons[] =
                        'Starting within 6 hours';
                } elseif ($hoursUntilEvent <= 24) {
                    $score += 200;

                    $reasons[] =
                        'Starting within 24 hours';
                } elseif ($hoursUntilEvent <= 72) {
                    $score += 150;

                    $reasons[] =
                        'Starting within 3 days';
                } elseif ($hoursUntilEvent <= 168) {
                    $score += 80;

                    $reasons[] =
                        'Starting this week';
                } else {
                    $score += 20;

                    $reasons[] =
                        'Upcoming event';
                }
            }
        }

        /*
         * Surveys are ranked by their
         * remaining response period.
         */
        if ($type === 'survey') {
            $closesAt =
                trim(
                    (string) (
                        $rawData['closes_at']
                        ?? ''
                    )
                );

            $closesTimestamp =
                $closesAt !== ''
                ? strtotime($closesAt)
                : false;

            if ($closesTimestamp !== false) {
                $hoursUntilClose =
                    (
                        $closesTimestamp -
                        $now
                    ) / 3600;

                if ($hoursUntilClose <= 6) {
                    $score += 250;

                    $reasons[] =
                        'Survey closes within 6 hours';
                } elseif ($hoursUntilClose <= 24) {
                    $score += 200;

                    $reasons[] =
                        'Survey closes within 24 hours';
                } elseif ($hoursUntilClose <= 72) {
                    $score += 150;

                    $reasons[] =
                        'Survey closes within 3 days';
                } elseif ($hoursUntilClose <= 168) {
                    $score += 80;

                    $reasons[] =
                        'Survey closes this week';
                } else {
                    $score += 20;

                    $reasons[] =
                        'Open survey';
                }
            } else {
                $score += 40;

                $reasons[] =
                    'Open survey';
            }
        }

        /*
         * Announcements, documents, and
         * surveys receive a freshness boost.
         */
        if ($type !== 'event') {
            $contentDate =
                trim(
                    (string) (
                        $item['date']
                        ?? ''
                    )
                );

            $contentTimestamp =
                $contentDate !== ''
                ? strtotime($contentDate)
                : false;

            if ($contentTimestamp !== false) {
                $ageInHours =
                    max(
                        0,
                        (
                            $now -
                            $contentTimestamp
                        ) / 3600
                    );

                if ($ageInHours <= 24) {
                    $score += 120;

                    $reasons[] =
                        'Published today';
                } elseif ($ageInHours <= 72) {
                    $score += 90;

                    $reasons[] =
                        'Recently published';
                } elseif ($ageInHours <= 168) {
                    $score += 50;

                    $reasons[] =
                        'Published this week';
                } elseif ($ageInHours <= 720) {
                    $score += 20;
                }
            }
        }

        /*
         * Required actions receive a boost
         * until the current user completes them.
         */
        $requiresAcknowledgment =
            !empty($rawData['require_acknowledgment']);

        $userAcknowledged =
            !empty($rawData['user_acknowledged']);

        if (
            $requiresAcknowledgment &&
            !$userAcknowledged
        ) {
            $score += 250;

            array_unshift(
                $reasons,
                'Acknowledgment required'
            );
        } elseif (
            $requiresAcknowledgment &&
            $userAcknowledged
        ) {
            $score -= 100;
        }

        if (empty($reasons)) {
            $reasons[] =
                'Recommended for your feed';
        }

        /* ==========================================
   NON-NEGOTIABLE RANKING TIER
========================================== */

        $rankingTier =
            1;

        if ($priority === 'emergency') {
            $rankingTier =
                4;
        } elseif (
            in_array(
                $priority,
                [
                    'urgent',
                    'important',
                    'high'
                ],
                true
            )
        ) {
            $rankingTier =
                3;
        } else {
            $requiresUserAction =
                (
                    $requiresAcknowledgment &&
                    !$userAcknowledged
                );

            if ($type === 'survey') {
                $userResponded =
                    !empty($rawData['user_responded']);

                if (!$userResponded) {
                    $requiresUserAction =
                        true;
                }
            }

            if ($requiresUserAction) {
                $rankingTier =
                    2;
            }

            if ($type === 'event') {
                $eventDate =
                    trim(
                        (string) (
                            $rawData['event_date']
                            ?? ''
                        )
                    );

                $endDate =
                    trim(
                        (string) (
                            $rawData['end_date']
                            ?? ''
                        )
                    );

                $eventTimestamp =
                    $eventDate !== ''
                    ? strtotime($eventDate)
                    : false;

                $endTimestamp =
                    $endDate !== ''
                    ? strtotime($endDate)
                    : false;

                $expirationTimestamp =
                    $endTimestamp !== false
                    ? $endTimestamp
                    : $eventTimestamp;

                if (
                    $expirationTimestamp !== false &&
                    $expirationTimestamp < $now
                ) {
                    $rankingTier =
                        0;
                } elseif (
                    $eventTimestamp !== false &&
                    $eventTimestamp >= $now &&
                    (
                        $eventTimestamp -
                        $now
                    ) <= 86400
                ) {
                    $rankingTier =
                        max(
                            $rankingTier,
                            2
                        );
                }
            }
        }

        return [
            'tier' =>
            $rankingTier,

            'score' =>
            $score,

            'reason' =>
            implode(
                ' - ',
                array_slice(
                    $reasons,
                    0,
                    3
                )
            )
        ];
    };

/* ==========================================
   NORMALIZE ANNOUNCEMENTS
========================================== */

foreach (
    $announcements as $announcement
) {
    $priority =
        trim(
            (string) (
                $announcement['priority']
                ?? 'Normal'
            )
        );

    $releaseMode =
        trim(
            (string) (
                $announcement['release_mode']
                ?? $announcement['announcement_type']
                ?? 'Regular'
            )
        );

    $audienceLabel =
        trim(
            (string) (
                $announcement['audience_label']
                ?? $announcement['target_audience']
                ?? 'All Authorized Users'
            )
        );

    $workflowStatus =
        trim(
            (string) (
                $announcement['workflow_status']
                ?? $announcement['status']
                ?? 'Published'
            )
        );

    $likeCount =
        (int) (
            $announcement['like_count']
            ?? $announcement['reaction_like_count']
            ?? 0
        );

    $loveCount =
        (int) (
            $announcement['love_count']
            ?? $announcement['reaction_love_count']
            ?? 0
        );

    $careCount =
        (int) (
            $announcement['care_count']
            ?? $announcement['reaction_care_count']
            ?? 0
        );

    $wowCount =
        (int) (
            $announcement['wow_count']
            ?? $announcement['reaction_wow_count']
            ?? 0
        );

    $reactionTotal =
        (int) (
            $announcement['reaction_count']
            ?? (
                $likeCount
                + $loveCount
                + $careCount
                + $wowCount
            )
        );

    $hubItems[] = [
        'type' =>
        'announcement',

        'title' =>
        $announcement['title']
            ?? 'Untitled Announcement',

        'description' =>
        $announcement['content']
            ?? '',

        'date' =>
        $announcement['published_at']
            ?? $announcement['created_at']
            ?? null,

        'priority' =>
        $priority,

        'release_mode' =>
        $releaseMode,

        'audience_label' =>
        $audienceLabel,

        'workflow_status' =>
        $workflowStatus,

        'reaction_breakdown' => [
            'Like' =>
            $likeCount,

            'Love' =>
            $loveCount,

            'Care' =>
            $careCount,

            'Wow' =>
            $wowCount
        ],

        'reaction_total' =>
        $reactionTotal,
        'data' =>
        $announcement
    ];
}

/* ==========================================
   NORMALIZE EVENTS
========================================== */

foreach (
    $events as $event
) {
    $hubItems[] = [
        'type' =>
        'event',

        'title' =>
        $event['title']
            ?? 'Untitled Event',

        'description' =>
        $event['description']
            ?? 'Scheduled school event',

        'date' =>
        $event['event_date']
            ?? null,

        'priority' =>
        'Scheduled',

        'release_mode' =>
        'Calendar-Based',

        'audience_label' =>
        $event['audience_label']
            ?? $event['target_audience']
            ?? 'School Community',

        'workflow_status' =>
        $event['workflow_status']
            ?? $event['status']
            ?? 'Published',

        'reaction_breakdown' => [
            'Like' =>
            (int) (
                $event['like_count']
                ?? 0
            ),

            'Love' =>
            (int) (
                $event['love_count']
                ?? 0
            ),

            'Care' =>
            (int) (
                $event['care_count']
                ?? 0
            ),

            'Wow' =>
            (int) (
                $event['wow_count']
                ?? 0
            )
        ],

        'reaction_total' =>
        (int) (
            $event['reaction_count']
            ?? 0
        ),

        'data' =>
        $event
    ];
}

/* ==========================================
   NORMALIZE DOCUMENTS
========================================== */

foreach (
    $documents as $document
) {
    $hubItems[] = [
        'type' =>
        'document',

        'title' =>
        trim(
            (string) (
                $document['title']
                ?? ''
            )
        ) !== ''
            ? trim(
                (string) $document['title']
            )
            : (
                $document['file_name']
                ?? 'Untitled Document'
            ),

        'description' =>
        trim(
            (string) (
                $document['description']
                ?? ''
            )
        ),

        'date' =>
        $document['created_at']
            ?? null,

        'priority' =>
        'Reference',

        'release_mode' =>
        'Document',

        'audience_label' =>
        $document['audience_label']
            ?? $document['target_audience']
            ?? 'Authorized Users',

        'workflow_status' =>
        $document['workflow_status']
            ?? $document['status']
            ?? 'Published',

        'reaction_breakdown' => [
            'Like' =>
            (int) (
                $document['like_count']
                ?? 0
            ),

            'Love' =>
            (int) (
                $document['love_count']
                ?? 0
            ),

            'Care' =>
            (int) (
                $document['care_count']
                ?? 0
            ),

            'Wow' =>
            (int) (
                $document['wow_count']
                ?? 0
            )
        ],

        'reaction_total' =>
        (int) (
            $document['reaction_count']
            ?? 0
        ),

        'data' =>
        $document
    ];
}


/* ==========================================
   NORMALIZE SURVEYS
========================================== */

foreach (
    $surveys as $survey
) {
    $hubItems[] = [
        'type' =>
        'survey',

        'title' =>
        $survey['title']
            ?? 'Untitled Survey',

        'description' =>
        $survey['description']
            ?? 'School survey',

        'date' =>
        $survey['published_at']
            ?? $survey['created_at']
            ?? null,

        'priority' =>
        'Survey',

        'release_mode' =>
        $survey['release_mode']
            ?? 'Immediate',

        'audience_label' =>
        $survey['audience_label']
            ?? $survey['target_audience']
            ?? 'Authorized Users',

        'workflow_status' =>
        $survey['workflow_status']
            ?? $survey['status']
            ?? 'Published',

        'reaction_breakdown' => [
            'Like' =>
            (int) (
                $survey['like_count']
                ?? 0
            ),

            'Love' =>
            (int) (
                $survey['love_count']
                ?? 0
            ),

            'Care' =>
            (int) (
                $survey['care_count']
                ?? 0
            ),

            'Wow' =>
            (int) (
                $survey['wow_count']
                ?? 0
            )
        ],

        'reaction_total' =>
        (int) (
            $survey['reaction_count']
            ?? 0
        ),

        'data' =>
        $survey
    ];
}

/* ==========================================
   ATTACH PERSONALIZED RANKING
========================================== */

foreach ($hubItems as &$hubItem) {
    $ranking =
        $calculateHubRanking(
            $hubItem
        );

    $hubItem['ranking_tier'] =
        (int) (
            $ranking['tier']
            ?? 1
        );

    $hubItem['ranking_score'] =
        (int) (
            $ranking['score']
            ?? 0
        );

    $hubItem['ranking_reason'] =
        trim(
            (string) (
                $ranking['reason']
                ?? 'Recommended for your feed'
            )
        );
}

unset($hubItem);


/* ==========================================
   SORT BY PERSONALIZED RECOMMENDATION
========================================== */

usort(
    $hubItems,
    static function (
        array $first,
        array $second
    ): int {

        $firstTier =
            (int) (
                $first['ranking_tier']
                ?? 1
            );

        $secondTier =
            (int) (
                $second['ranking_tier']
                ?? 1
            );

        if ($firstTier !== $secondTier) {
            return $secondTier
                <=> $firstTier;
        }

        $firstScore =
            (int) (
                $first['ranking_score']
                ?? 0
            );

        $secondScore =
            (int) (
                $second['ranking_score']
                ?? 0
            );

        if ($firstScore !== $secondScore) {
            return $secondScore
                <=> $firstScore;
        }

        $firstDate =
            strtotime(
                $first['date']
                    ?? '1970-01-01'
            );

        $secondDate =
            strtotime(
                $second['date']
                    ?? '1970-01-01'
            );

        if ($firstDate !== $secondDate) {
            return $secondDate
                <=> $firstDate;
        }

        $typeComparison =
            strcmp(
                (string) (
                    $first['type']
                    ?? ''
                ),
                (string) (
                    $second['type']
                    ?? ''
                )
            );

        if ($typeComparison !== 0) {
            return $typeComparison;
        }

        return strcmp(
            (string) (
                $first['title']
                ?? ''
            ),
            (string) (
                $second['title']
                ?? ''
            )
        );
    }
);

/* ==========================================
   DIVERSIFY ITEMS WITHIN EACH TIER
========================================== */

$diversifyHubItems =
    static function (
        array $items,
        int $maximumConsecutive = 3
    ): array {
        if (
            count($items) <= 1 ||
            $maximumConsecutive <= 0
        ) {
            return $items;
        }

        $tierGroups = [];

        foreach ($items as $item) {
            $tier =
                (int) (
                    $item['ranking_tier']
                    ?? 1
                );

            $tierGroups[$tier][] =
                $item;
        }

        krsort(
            $tierGroups,
            SORT_NUMERIC
        );

        $diversified = [];

        foreach ($tierGroups as $tierItems) {
            $remaining =
                array_values(
                    $tierItems
                );

            $lastType =
                null;

            $consecutiveCount =
                0;

            while (!empty($remaining)) {
                $selectedIndex =
                    0;

                if (
                    $lastType !== null &&
                    $consecutiveCount >=
                    $maximumConsecutive
                ) {
                    foreach (
                        $remaining
                        as
                        $candidateIndex =>
                        $candidate
                    ) {
                        $candidateType =
                            (string) (
                                $candidate['type']
                                ?? ''
                            );

                        if (
                            $candidateType !==
                            $lastType
                        ) {
                            $selectedIndex =
                                $candidateIndex;

                            break;
                        }
                    }
                }

                $selected =
                    $remaining[$selectedIndex];

                array_splice(
                    $remaining,
                    $selectedIndex,
                    1
                );

                $selectedType =
                    (string) (
                        $selected['type']
                        ?? ''
                    );

                if (
                    $selectedType ===
                    $lastType
                ) {
                    $consecutiveCount++;
                } else {
                    $lastType =
                        $selectedType;

                    $consecutiveCount =
                        1;
                }

                $diversified[] =
                    $selected;
            }
        }

        return $diversified;
    };

$hubItems =
    $diversifyHubItems(
        $hubItems
    );
/* ==========================================
   TYPE CONFIGURATION
========================================== */

$typeConfig = [
    'announcement' => [
        'label' =>
        'Announcement',

        'icon' =>
        'fa-solid fa-bullhorn'
    ],

    'event' => [
        'label' =>
        'Event',

        'icon' =>
        'fa-regular fa-calendar'
    ],

    'document' => [
        'label' =>
        'Document',

        'icon' =>
        'fa-regular fa-file-lines'
    ],

    'survey' => [
        'label' =>
        'Survey',

        'icon' =>
        'fa-solid fa-square-poll-horizontal'
    ]
];

$totalItems =
    count($hubItems);

$totalAnnouncementCount =
    (int) (
        $viewData['total_announcement_count']
        ?? count($announcements)
    );

$totalEventCount =
    (int) (
        $viewData['total_event_count']
        ?? count($events)
    );

$totalDocumentCount =
    (int) (
        $viewData['total_document_count']
        ?? count($documents)
    );

$totalSurveyCount =
    (int) (
        $viewData['total_survey_count']
        ?? count($surveys)
    );

$featuredAnnouncement =
    null;

foreach (
    $hubItems as $hubItem
) {
    if (
        $hubItem['type']
        === 'announcement'
    ) {
        $featuredAnnouncement =
            $hubItem;

        break;
    }
}

/* ==========================================
   REACTION HELPERS
========================================== */

function reactionIcon(
    string $reaction
): string {
    return match ($reaction) {
        'Like' =>
        'fa-solid fa-thumbs-up',

        'Love' =>
        'fa-solid fa-heart',

        'Care' =>
        'fa-solid fa-hand-holding-heart',

        'Wow' =>
        'fa-solid fa-face-surprise',

        default =>
        'fa-regular fa-heart'
    };
}

function reactionClass(
    string $reaction
): string {
    return match ($reaction) {
        'Like' =>
        'reaction-like',

        'Love' =>
        'reaction-love',

        'Care' =>
        'reaction-care',

        'Wow' =>
        'reaction-wow',

        default =>
        ''
    };
}

function topReactions(
    array $breakdown,
    int $limit = 3
): array {
    $filtered =
        array_filter(
            $breakdown,
            fn(
                int $count
            ): bool =>
            $count > 0
        );

    arsort($filtered);

    return array_slice(
        $filtered,
        0,
        $limit,
        true
    );
}

?>

<div
    id="newsSecurity"
    hidden
    data-csrf-token="<?= htmlspecialchars(
                            csrfToken(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">
</div>

<section class="app-page hub-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->

    <header class="page-header hub-page-header">

        <div class="page-header-copy">

            <h1>
                Home
            </h1>

        </div>

        <div class="page-actions">

            <a
                href="index.php?page=calendar"
                class="app-button secondary">
                <i class="fa-solid fa-calendar-days"></i>

                School Calendar
            </a>

            <?php if (
                in_array(
                    $currentRole,
                    [
                        'Admin',
                        'Faculty'
                    ],
                    true
                )
            ): ?>

                <a
                    href="index.php?page=postings"
                    class="app-button primary">
                    <i class="fa-solid fa-pen-to-square"></i>

                    Create Content
                </a>

            <?php endif; ?>

        </div>

    </header>

    <div class="hub-feed-layout">
    <div class="hub-feed-main">

    <!-- ======================================
         FEATURED ANNOUNCEMENT
    ======================================= -->

    <?php if (
        $featuredAnnouncement !== null
    ): ?>

        <?php

        $featuredData =
            $featuredAnnouncement['data'];

        $featuredTitle =
            $featuredAnnouncement['title'];

        $featuredDescription =
            $featuredAnnouncement['description']
            ?? '';

        $featuredDescription =
            preg_replace(
                '/<(br|\/p|\/div|\/li|\/ul|\/ol)>/i',
                ' ',
                $featuredDescription
            );

        $featuredDescription =
            strip_tags(
                $featuredDescription
            );

        $featuredDescription =
            html_entity_decode(
                $featuredDescription,
                ENT_QUOTES |
                    ENT_HTML5,
                'UTF-8'
            );

        $featuredDescription =
            preg_replace(
                '/\s+/u',
                ' ',
                $featuredDescription
            );

        $featuredDescription =
            trim(
                $featuredDescription
            );

        $featuredPreview =
            mb_strlen(
                $featuredDescription
            ) > 270
            ? mb_substr(
                $featuredDescription,
                0,
                270
            ) . '...'
            : $featuredDescription;

        $featuredDate =
            $featuredAnnouncement['date'];

        $featuredBreakdown =
            $featuredAnnouncement['reaction_breakdown'];

        $featuredTopReactions =
            topReactions(
                $featuredBreakdown
            );

        ?>

        <section
            class="hub-featured-card"
            data-content-type="announcement"
            data-engagement-id="<?= (int) (
                                    $featuredData['announcement_id']
                                    ?? 0
                                ) ?>">

            <div class="hub-featured-accent">

                <i class="fa-solid fa-bullhorn"></i>

            </div>

            <div class="hub-featured-content">

                <div class="hub-featured-labels">
                    <?php if (!empty($featuredData['government_source_url'])): ?>
                        <span class="hub-government-badge">Government advisory</span>
                    <?php endif; ?>


                    <span class="hub-featured-badge">
                        Featured Announcement
                    </span>

                    <span
                        class="hub-priority-badge priority-<?=
                                                            htmlspecialchars(
                                                                strtolower(
                                                                    $featuredAnnouncement['priority']
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            )
                                                            ?>">
                        <?= htmlspecialchars(
                            $featuredAnnouncement['priority'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <span class="hub-release-badge">

                        <i class="fa-solid fa-paper-plane"></i>

                        <?= htmlspecialchars(
                            $featuredAnnouncement['release_mode'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                </div>

                <h2>
                    <?= htmlspecialchars(
                        $featuredTitle,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <?php if (
                    $featuredPreview !== ''
                ): ?>

                    <p>
                        <?= htmlspecialchars(
                            $featuredPreview,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                <?php endif; ?>

                <?php if (
                    trim(
                        (string) (
                            $featuredData['audio_path']
                            ?? ''
                        )
                    ) !== ''
                ): ?>

                    <span class="hub-audio-available">

                        <i
                            class="fa-solid fa-volume-high"
                            aria-hidden="true"></i>

                        Audio broadcast available

                    </span>

                <?php endif; ?>

                <div class="hub-featured-meta">

                    <span>

                        <i class="fa-regular fa-calendar"></i>

                        <?= !empty($featuredDate)
                            ? htmlspecialchars(
                                date(
                                    'F d, Y',
                                    strtotime(
                                        $featuredDate
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'Date unavailable'
                        ?>

                    </span>

                    <span>

                        <i class="fa-solid fa-bullseye"></i>

                        <?= htmlspecialchars(
                            $featuredAnnouncement['audience_label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>


                    <span>
                        <i class="fa-regular fa-eye"></i>

                        <b data-view-count>
                            <?= number_format(
                                (int) (
                                    $featuredData['view_count']
                                    ?? 0
                                )
                            ) ?>
                        </b>

                        Views
                    </span>

                    <span class="hub-featured-reactions">

                        <?php if (
                            !empty($featuredTopReactions)
                        ): ?>

                            <span class="hub-reaction-stack">

                                <?php foreach (
                                    $featuredTopReactions
                                    as $reaction =>
                                    $reactionCount
                                ): ?>

                                    <i
                                        class="<?=
                                                reactionIcon(
                                                    $reaction
                                                )
                                                ?> <?=
                                                    reactionClass(
                                                        $reaction
                                                    )
                                                    ?>"
                                        title="<?= htmlspecialchars(
                                                    $reaction
                                                        . ': '
                                                        . $reactionCount,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"></i>

                                <?php endforeach; ?>

                            </span>

                        <?php else: ?>

                            <i class="fa-regular fa-heart"></i>

                        <?php endif; ?>

                        <b data-reaction-count>
                            <?= number_format(
                                $featuredAnnouncement['reaction_total']
                            ) ?>
                        </b>

                        Reactions

                    </span>

                </div>

            </div>

            <button
                type="button"
                class="hub-featured-open"
                data-open-announcement
                data-announcement-id="<?=
                                        (int) (
                                            $featuredData['announcement_id']
                                            ?? 0
                                        )
                                        ?>">
                Read Announcement

                <i class="fa-solid fa-arrow-right"></i>
            </button>

        </section>

    <?php endif; ?>

    <!-- ======================================
         HUB WORKSPACE
    ======================================= -->

    <section class="hub-workspace">

        <!-- ==================================
             CONTENT FILTERS
        =================================== -->

        <nav
            class="hub-tabs"
            aria-label="Information types">

            <!-- ALL -->

            <button
                type="button"
                class="hub-tab active"
                data-view="all"
                data-label="All Updates">

                <i class="fa-solid fa-layer-group"></i>

                <span class="hub-tab-label">
                    All Updates
                </span>

                <span class="hub-tab-count">
                    <?= $totalItems ?>
                </span>

            </button>


            <!-- ANNOUNCEMENTS -->

            <button
                type="button"
                class="hub-tab"
                data-view="announcement"
                data-label="Announcements">

                <i class="fa-solid fa-bullhorn"></i>

                <span class="hub-tab-label">
                    Announcements
                </span>

                <span class="hub-tab-count">
                    <?= $totalAnnouncementCount ?>
                </span>

            </button>


            <!-- EVENTS -->

            <button
                type="button"
                class="hub-tab"
                data-view="event"
                data-label="Events">

                <i class="fa-regular fa-calendar"></i>

                <span class="hub-tab-label">
                    Events
                </span>

                <span class="hub-tab-count">
                    <?= $totalEventCount ?>
                </span>

            </button>


            <!-- DOCUMENTS -->

            <button
                type="button"
                class="hub-tab"
                data-view="document"
                data-label="Documents">

                <i class="fa-regular fa-file-lines"></i>

                <span class="hub-tab-label">
                    Documents
                </span>

                <span class="hub-tab-count">
                    <?= $totalDocumentCount ?>
                </span>

            </button>


            <!-- SURVEYS -->

            <button
                type="button"
                class="hub-tab"
                data-view="survey"
                data-label="Surveys">

                <i class="fa-solid fa-square-poll-horizontal"></i>

                <span class="hub-tab-label">
                    Surveys
                </span>

                <span class="hub-tab-count">
                    <?= $totalSurveyCount ?>
                </span>

            </button>


            <!-- URGENT -->

            <button
                type="button"
                class="hub-tab"
                data-view="urgent"
                data-label="Urgent Updates">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <span class="hub-tab-label">
                    Urgent
                </span>

            </button>


            <!-- SCHEDULED -->

            <button
                type="button"
                class="hub-tab"
                data-view="scheduled"
                data-label="Scheduled Updates">

                <i class="fa-regular fa-clock"></i>

                <span class="hub-tab-label">
                    Scheduled
                </span>

            </button>


            <!-- RECENT -->

            <button
                type="button"
                class="hub-tab"
                data-view="recent"
                data-label="Recent Updates">

                <i class="fa-regular fa-clock"></i>

                <span class="hub-tab-label">
                    Recent
                </span>

            </button>

        </nav>

        <main class="hub-results">

            <!-- ==================================
                 RESULT HEADER
            =================================== -->

            <header class="hub-results-header">

                <div class="hub-results-title">

                    <h2 id="hubViewTitle">
                        All Updates
                    </h2>

                    <span id="hubResultCount">
                        <?= $totalItems ?>
                        <?= $totalItems === 1
                            ? 'result'
                            : 'results'
                        ?>
                    </span>

                </div>

                <div class="hub-results-controls">

                    <label
                        class="hub-search-control"
                        for="hubSearch">

                        <i
                            class="fa-solid fa-magnifying-glass"
                            aria-hidden="true"></i>

                        <input
                            type="search"
                            id="hubSearch"
                            placeholder="Search updates"
                            aria-label="Search updates"
                            autocomplete="off">

                        <button
                            type="button"
                            id="clearHubSearch"
                            aria-label="Clear search"
                            title="Clear search">

                            <i
                                class="fa-solid fa-xmark"
                                aria-hidden="true"></i>

                        </button>

                    </label>

                    <!-- Keep the existing hub-sort-control label here. -->



                    <label class="hub-sort-control">

                        <i class="fa-solid fa-arrow-down-wide-short"></i>

                        <select
                            id="hubSort"
                            aria-label="Sort updates">
                            <option value="newest">
                                Newest first
                            </option>

                            <option value="recommended">
                                Recommended
                            </option>

                            <option value="oldest">
                                Oldest first
                            </option>

                            <option value="title">
                                Title A-Z
                            </option>
                        </select>

                    </label>

                </div>

            </header>

            <!-- ==================================
                 HUB LIST
            =================================== -->

            <?php if (
                !empty($hubItems)
            ): ?>

                <div
                    id="hubList"
                    class="hub-list">

                    <?php foreach (
                        $hubItems as $item
                    ): ?>

                        <?php

                        $type =
                            $item['type'];

                        $config =
                            $typeConfig[$type];

                        $title =
                            $item['title'];

                        $date =
                            $item['date'];

                        $rawData =
                            $item['data'];

                        $contentId =
                            (int) (
                                $rawData['announcement_id']
                                ?? $rawData['event_id']
                                ?? $rawData['document_id']
                                ?? $rawData['survey_id']
                                ?? 0
                            );

                        $priority =
                            $item['priority'];

                        $releaseMode =
                            $item['release_mode'];

                        $audienceLabel =
                            $item['audience_label'];

                        $workflowStatus =
                            $item['workflow_status'];


                        $rankingTier =
                            (int) (
                                $item['ranking_tier']
                                ?? 1
                            );

                        $rankingScore =
                            (int) (
                                $item['ranking_score']
                                ?? 0
                            );

                        $rankingReason =
                            trim(
                                (string) (
                                    $item['ranking_reason']
                                    ?? 'Recommended for your feed'
                                )
                            );


                        $isUnread =
                            empty($rawData['user_viewed']);

                        $rankingReasonParts =
                            array_values(
                                array_filter(
                                    array_map(
                                        'trim',
                                        explode(
                                            'â€¢',
                                            $rankingReason
                                        )
                                    ),
                                    static function (
                                        string $reason
                                    ): bool {
                                        return (
                                            $reason !== '' &&
                                            strtolower($reason) !==
                                            'not viewed yet'
                                        );
                                    }
                                )
                            );

                        $description =
                            trim(
                                strip_tags(
                                    $item['description']
                                )
                            );

                        $preview =
                            mb_strlen(
                                $description
                            ) > 180
                            ? mb_substr(
                                $description,
                                0,
                                180
                            ) . '...'
                            : $description;

                        $searchText =
                            strtolower(
                                $config['label']
                                    . ' '
                                    . $title
                                    . ' '
                                    . $description
                                    . ' '
                                    . $priority
                                    . ' '
                                    . $releaseMode
                                    . ' '
                                    . $audienceLabel
                            );

                        $reactionBreakdown =
                            $item['reaction_breakdown'];

                        $topReactionItems =
                            topReactions(
                                $reactionBreakdown
                            );

                        $acknowledgmentCount =
                            (int) (
                                $rawData['acknowledgment_count']
                                ?? 0
                            );

                        $userAcknowledged =
                            !empty($rawData['user_acknowledged']);

                        $isUrgent =
                            in_array(
                                strtolower(
                                    $priority
                                ),
                                [
                                    'urgent',
                                    'emergency',
                                    'high'
                                ],
                                true
                            );

                        $isScheduled =
                            str_contains(
                                strtolower(
                                    $releaseMode
                                ),
                                'scheduled'
                            )
                            || str_contains(
                                strtolower(
                                    $releaseMode
                                ),
                                'calendar'
                            );

                        ?>

                        <article
                            class="hub-list-item hub-item-<?=
                                                            htmlspecialchars(
                                                                $type,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            )
                                                            ?>"
                            data-hub-item
                            data-content-id="<?= $contentId ?>"
                            data-ranking-tier="<?= $rankingTier ?>"
                            data-ranking-score="<?= $rankingScore ?>"
                            data-ranking-reason="<?= htmlspecialchars(
                                                        $rankingReason,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                            data-type="<?= htmlspecialchars(
                                            $type,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                            data-priority="<?= htmlspecialchars(
                                                strtolower(
                                                    $priority
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                            data-release-mode="<?= htmlspecialchars(
                                                    strtolower(
                                                        $releaseMode
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                            data-urgent="<?=
                                            $isUrgent
                                                ? '1'
                                                : '0'
                                            ?>"
                            data-scheduled="<?=
                                            $isScheduled
                                                ? '1'
                                                : '0'
                                            ?>"
                            data-date="<?= htmlspecialchars(
                                            $date
                                                ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                            data-title="<?= htmlspecialchars(
                                            strtolower(
                                                $title
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                            data-search="<?= htmlspecialchars(
                                                $searchText,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                            <div class="hub-item-icon">

                                <i class="<?= htmlspecialchars(
                                                $config['icon'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"></i>

                            </div>

                            <div class="hub-item-content">

                                <div class="hub-item-label-row">
                                    <?php if ($type === 'announcement' && !empty($rawData['government_source_url'])): ?>
                                        <span class="hub-government-badge">Government advisory</span>
                                    <?php endif; ?>


                                    <span class="hub-type-badge">
                                        <?= htmlspecialchars(
                                            $config['label'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <?php if (
                                        $type ===
                                        'announcement'
                                    ): ?>

                                        <span
                                            class="hub-priority-badge priority-<?=
                                                                                htmlspecialchars(
                                                                                    strtolower(
                                                                                        $priority
                                                                                    ),
                                                                                    ENT_QUOTES,
                                                                                    'UTF-8'
                                                                                )
                                                                                ?>">
                                            <?= htmlspecialchars(
                                                $priority,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <span class="hub-release-badge">

                                            <?php if (
                                                $isScheduled
                                            ): ?>

                                                <i class="fa-regular fa-clock"></i>

                                            <?php elseif (
                                                $isUrgent
                                            ): ?>

                                                <i class="fa-solid fa-bolt"></i>

                                            <?php else: ?>

                                                <i class="fa-solid fa-paper-plane"></i>

                                            <?php endif; ?>

                                            <?= htmlspecialchars(
                                                $releaseMode,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                    <?php if (
                                        $isAdministrator
                                    ): ?>

                                        <span class="hub-workflow-badge">
                                            <?= htmlspecialchars(
                                                $workflowStatus,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="hub-item-meta">

                                    <?php if (
                                        !empty($date)
                                    ): ?>

                                        <time
                                            datetime="<?= htmlspecialchars(
                                                            $date,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">
                                            <i class="fa-regular fa-calendar"></i>

                                            <?= htmlspecialchars(
                                                date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $date
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </time>

                                    <?php endif; ?>

                                    <span>

                                        <i class="fa-solid fa-bullseye"></i>

                                        <?= htmlspecialchars(
                                            $audienceLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                    <?php if ($isUnread): ?>

                                        <span
                                            class="hub-unread-badge"
                                            title="You have not opened this content yet">

                                            <i class="fa-solid fa-circle"></i>

                                            Unread

                                        </span>

                                    <?php endif; ?>

                                    <?php foreach (
                                        $rankingReasonParts
                                        as $reasonPart
                                    ): ?>

                                        <span
                                            class="hub-ranking-chip"
                                            title="Why this content is recommended">

                                            <i class="fa-solid fa-wand-magic-sparkles"></i>

                                            <?= htmlspecialchars(
                                                $reasonPart,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php endforeach; ?>

                                </div>

                                <h3>
                                    <?= htmlspecialchars(
                                        $title,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h3>

                                <?php if (
                                    $type ===
                                    'announcement'
                                ): ?>

                                    <?php if (
                                        $preview !== ''
                                    ): ?>

                                        <p>
                                            <?= htmlspecialchars(
                                                $preview,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (
                                        trim(
                                            (string) (
                                                $rawData['audio_path']
                                                ?? ''
                                            )
                                        ) !== ''
                                    ): ?>

                                        <span class="hub-audio-available">

                                            <i
                                                class="fa-solid fa-volume-high"
                                                aria-hidden="true"></i>

                                            Audio broadcast available

                                        </span>

                                    <?php endif; ?>

                                    <div
                                        class="hub-engagement"
                                        data-content-type="announcement"
                                        data-engagement-id="<?=
                                                            (int) (
                                                                $rawData['announcement_id']
                                                                ?? 0
                                                            )
                                                            ?>">

                                        <span title="Views">

                                            <i class="fa-regular fa-eye"></i>

                                            <b data-view-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['view_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                Views
                                            </small>

                                        </span>

                                        <span
                                            class="hub-reaction-summary"
                                            title="Reactions">

                                            <?php

                                            $contentBreakdown = [
                                                'Like' =>
                                                (int) (
                                                    $rawData['like_count']
                                                    ?? 0
                                                ),

                                                'Love' =>
                                                (int) (
                                                    $rawData['love_count']
                                                    ?? 0
                                                ),

                                                'Care' =>
                                                (int) (
                                                    $rawData['care_count']
                                                    ?? 0
                                                ),

                                                'Wow' =>
                                                (int) (
                                                    $rawData['wow_count']
                                                    ?? 0
                                                )
                                            ];

                                            $contentTopReactions =
                                                topReactions(
                                                    $contentBreakdown
                                                );

                                            ?>

                                            <?php if (
                                                !empty($contentTopReactions)
                                            ): ?>

                                                <span class="hub-reaction-stack">

                                                    <?php foreach (
                                                        $contentTopReactions
                                                        as $reaction =>
                                                        $reactionCount
                                                    ): ?>

                                                        <i
                                                            class="<?=
                                                                    reactionIcon(
                                                                        $reaction
                                                                    )
                                                                    ?> <?=
                                                                        reactionClass(
                                                                            $reaction
                                                                        )
                                                                        ?>"
                                                            title="<?= htmlspecialchars(
                                                                        $reaction
                                                                            . ': '
                                                                            . $reactionCount,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>"></i>

                                                    <?php endforeach; ?>

                                                </span>

                                            <?php else: ?>

                                                <span class="hub-reaction-stack empty">

                                                    <i class="fa-solid fa-thumbs-up reaction-like"></i>
                                                    <i class="fa-solid fa-heart reaction-love"></i>
                                                    <i class="fa-solid fa-hand-holding-heart reaction-care"></i>
                                                    <i class="fa-solid fa-face-surprise reaction-wow"></i>

                                                </span>

                                            <?php endif; ?>

                                            <b data-reaction-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['reaction_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                Reactions
                                            </small>

                                        </span>

                                        <span title="Comments">

                                            <i class="fa-regular fa-comment"></i>

                                            <b data-comment-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['comment_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                Comments
                                            </small>

                                        </span>

                                        <span
                                            class="hub-acknowledgment-summary<?=
                                                                                $userAcknowledged
                                                                                    ? ' acknowledged'
                                                                                    : ''
                                                                                ?>"
                                            title="Acknowledgments">

                                            <i class="fa-solid fa-check-double"></i>

                                            <b data-acknowledgment-count>
                                                <?= number_format(
                                                    $acknowledgmentCount
                                                ) ?>
                                            </b>

                                            <small data-acknowledgment-label>
                                                <?= $userAcknowledged
                                                    ? 'Acknowledged'
                                                    : 'Acknowledgments'
                                                ?>
                                            </small>

                                        </span>

                                    </div>

                                <?php elseif (
                                    $type === 'event'
                                ): ?>

                                    <p>
                                        <?= htmlspecialchars(
                                            $preview
                                                ?: 'Scheduled school event',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <?php if (
                                            !empty($rawData['location'])
                                        ): ?>

                                            <span class="hub-inline-divider">
                                                â€¢
                                            </span>

                                            <i class="fa-solid fa-location-dot"></i>

                                            <?= htmlspecialchars(
                                                $rawData['location'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        <?php endif; ?>

                                    </p>

                                    <div
                                        class="hub-engagement"
                                        data-content-type="event"
                                        data-engagement-id="<?= (int) (
                                                                $rawData['event_id']
                                                                ?? 0
                                                            ) ?>">

                                        <span title="Views">

                                            <i class="fa-regular fa-eye"></i>

                                            <b data-view-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['view_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                Views
                                            </small>

                                        </span>

                                        <span
                                            class="hub-reaction-summary"
                                            title="Reactions">

                                            <?php

                                            $eventBreakdown = [
                                                'Like' =>
                                                (int) (
                                                    $rawData['like_count']
                                                    ?? 0
                                                ),

                                                'Love' =>
                                                (int) (
                                                    $rawData['love_count']
                                                    ?? 0
                                                ),

                                                'Care' =>
                                                (int) (
                                                    $rawData['care_count']
                                                    ?? 0
                                                ),

                                                'Wow' =>
                                                (int) (
                                                    $rawData['wow_count']
                                                    ?? 0
                                                )
                                            ];

                                            $eventTopReactions =
                                                topReactions(
                                                    $eventBreakdown
                                                );

                                            ?>

                                            <?php if (
                                                !empty($eventTopReactions)
                                            ): ?>

                                                <span class="hub-reaction-stack">

                                                    <?php foreach (
                                                        $eventTopReactions
                                                        as $reaction =>
                                                        $reactionCount
                                                    ): ?>

                                                        <i
                                                            class="<?=
                                                                    reactionIcon(
                                                                        $reaction
                                                                    )
                                                                    ?> <?=
                                                                        reactionClass(
                                                                            $reaction
                                                                        )
                                                                        ?>"
                                                            title="<?= htmlspecialchars(
                                                                        $reaction
                                                                            . ': '
                                                                            . $reactionCount,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>"></i>

                                                    <?php endforeach; ?>

                                                </span>

                                            <?php else: ?>

                                                <span class="hub-reaction-stack empty">

                                                    <i class="fa-solid fa-thumbs-up reaction-like"></i>

                                                    <i class="fa-solid fa-heart reaction-love"></i>

                                                    <i class="fa-solid fa-hand-holding-heart reaction-care"></i>

                                                    <i class="fa-solid fa-face-surprise reaction-wow"></i>

                                                </span>

                                            <?php endif; ?>

                                            <b data-reaction-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['reaction_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                Reactions
                                            </small>

                                        </span>

                                        <span title="Comments">

                                            <i class="fa-regular fa-comment"></i>

                                            <b data-comment-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['comment_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                Comments
                                            </small>

                                        </span>

                                        <span
                                            class="hub-acknowledgment-summary<?=
                                                                                !empty($rawData['user_acknowledged'])
                                                                                    ? ' acknowledged'
                                                                                    : ''
                                                                                ?>"
                                            title="Acknowledgments">

                                            <i class="fa-solid fa-check-double"></i>

                                            <b data-acknowledgment-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['acknowledgment_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                <?= !empty($rawData['user_acknowledged'])
                                                    ? 'Acknowledged'
                                                    : 'Acknowledgments'
                                                ?>
                                            </small>

                                        </span>

                                        <span
                                            class="hub-user-reaction"
                                            data-user-reaction-indicator
                                            hidden></span>

                                    </div>

                                <?php elseif (
                                    $type === 'document'
                                ): ?>

                                    <div class="hub-document-meta">

                                        <span>
                                            <i class="fa-regular fa-file-lines"></i>

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    $rawData['file_type']
                                                        ?? 'FILE'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <?php if (
                                            !empty($rawData['file_size'])
                                        ): ?>

                                            <span>
                                                <i class="fa-solid fa-database"></i>

                                                <?= number_format(
                                                    $rawData['file_size'] / 1024,
                                                    1
                                                ) ?>

                                                KB
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div
                                        class="hub-engagement"
                                        data-content-type="document"
                                        data-engagement-id="<?= (int) (
                                                                $rawData['document_id']
                                                                ?? 0
                                                            ) ?>"
                                        data-user-reaction="<?= htmlspecialchars(
                                                                (string) (
                                                                    $rawData['user_reaction']
                                                                    ?? ''
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>">

                                        <span title="Views">
                                            <i class="fa-regular fa-eye"></i>

                                            <b data-view-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['view_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>Views</small>
                                        </span>

                                        <span
                                            class="hub-reaction-summary"
                                            title="Reactions">

                                            <?php if (
                                                !empty($topReactionItems)
                                            ): ?>

                                                <span class="hub-reaction-stack">

                                                    <?php foreach (
                                                        $topReactionItems
                                                        as $reaction =>
                                                        $reactionCount
                                                    ): ?>

                                                        <i
                                                            class="<?=
                                                                    reactionIcon(
                                                                        $reaction
                                                                    )
                                                                    ?> <?=
                                                                        reactionClass(
                                                                            $reaction
                                                                        )
                                                                        ?>"
                                                            title="<?= htmlspecialchars(
                                                                        $reaction
                                                                            . ': '
                                                                            . $reactionCount,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>"></i>

                                                    <?php endforeach; ?>

                                                </span>



                                            <?php else: ?>

                                                <span class="hub-reaction-stack empty">

                                                    <i class="fa-solid fa-thumbs-up reaction-like"></i>
                                                    <i class="fa-solid fa-heart reaction-love"></i>
                                                    <i class="fa-solid fa-hand-holding-heart reaction-care"></i>
                                                    <i class="fa-solid fa-face-surprise reaction-wow"></i>

                                                </span>

                                            <?php endif; ?>

                                            <b data-reaction-count>
                                                <?= number_format(
                                                    (int) (
                                                        $item['reaction_total']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>
                                                Reactions
                                            </small>

                                        </span>

                                        <span title="Comments">
                                            <i class="fa-regular fa-comment"></i>

                                            <b data-comment-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['comment_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>Comments</small>
                                        </span>

                                        <span title="Acknowledgments">
                                            <i class="fa-solid fa-check-double"></i>

                                            <b data-acknowledgment-count>
                                                <?= number_format(
                                                    (int) (
                                                        $rawData['acknowledgment_count']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </b>

                                            <small>Acknowledged</small>
                                        </span>

                                    </div>

                                <?php elseif (
                                    $type === 'survey'
                                ): ?>

                                    <?php
                                    $questionCount =
                                        (int) (
                                            $rawData['question_count']
                                            ?? 0
                                        );

                                    $responseCount =
                                        (int) (
                                            $rawData['response_count']
                                            ?? 0
                                        );

                                    $closesAt =
                                        $rawData['closes_at']
                                        ?? null;
                                    ?>

                                    <div class="hub-survey-details">

                                        <?php if (
                                            $preview !== ''
                                        ): ?>

                                            <p class="hub-item-description">
                                                <?= htmlspecialchars(
                                                    $preview,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </p>

                                        <?php endif; ?>

                                        <div class="hub-survey-meta">

                                            <span>
                                                <i class="fa-solid fa-list-check"></i>

                                                <?= $questionCount ?>

                                                <?= $questionCount === 1
                                                    ? 'Question'
                                                    : 'Questions'
                                                ?>
                                            </span>

                                            <span>
                                                <i class="fa-solid fa-users"></i>

                                                <?= $responseCount ?>

                                                <?= $responseCount === 1
                                                    ? 'Response'
                                                    : 'Responses'
                                                ?>
                                            </span>

                                            <?php if (
                                                !empty($closesAt)
                                            ): ?>

                                                <span>
                                                    <i class="fa-regular fa-clock"></i>

                                                    Closes

                                                    <?= htmlspecialchars(
                                                        date(
                                                            'M d, Y g:i A',
                                                            strtotime(
                                                                $closesAt
                                                            )
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                <?php endif; ?>

                                <div class="hub-item-action">

                                    <?php if (
                                        $type ===
                                        'announcement'
                                    ): ?>

                                        <button
                                            type="button"
                                            class="hub-open-button"
                                            data-open-announcement
                                            data-announcement-id="<?=
                                                                    (int) (
                                                                        $rawData['announcement_id']
                                                                        ?? 0
                                                                    )
                                                                    ?>">
                                            <span>
                                                Open
                                            </span>

                                            <i class="fa-solid fa-chevron-right"></i>

                                        </button>

                                    <?php elseif (
                                        $type === 'event'
                                    ): ?>

                                        <button
                                            type="button"
                                            class="hub-open-button"
                                            data-open-event
                                            data-event-id="<?=
                                                            (int) (
                                                                $rawData['event_id']
                                                                ?? 0
                                                            )
                                                            ?>"
                                            data-event-title="<?= htmlspecialchars(
                                                                    $title,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-event-description="<?= htmlspecialchars(
                                                                        $rawData['description']
                                                                            ?? '',
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>"
                                            data-event-location="<?= htmlspecialchars(
                                                                        $rawData['location']
                                                                            ?? '',
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>"
                                            data-event-start="<?= htmlspecialchars(
                                                                    $rawData['event_date']
                                                                        ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-event-end="<?= htmlspecialchars(
                                                                $rawData['end_date']
                                                                    ?? '',
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                            data-event-image="<?= htmlspecialchars(
                                                                    $rawData['image_path']
                                                                        ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-event-author="<?= htmlspecialchars(
                                                                    $rawData['author_name']
                                                                        ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">
                                            <span>
                                                View
                                            </span>

                                            <i class="fa-solid fa-chevron-right"></i>

                                        </button>

                                    <?php elseif (
                                        $type === 'document'
                                    ): ?>

                                        <button
                                            type="button"
                                            class="hub-open-button"
                                            data-open-document
                                            data-document-id="<?=
                                                                (int) (
                                                                    $rawData['document_id']
                                                                    ?? 0
                                                                )
                                                                ?>"
                                            data-document-name="<?= htmlspecialchars(
                                                                    $rawData['file_name']
                                                                        ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-document-path="<?= htmlspecialchars(
                                                                    'index.php?page=document_download&document_id='
                                                                        . (int) ($rawData['document_id'] ?? 0),
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-document-type="<?= htmlspecialchars(
                                                                    $rawData['file_type']
                                                                        ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            data-document-size="<?=
                                                                (int) (
                                                                    $rawData['file_size']
                                                                    ?? 0
                                                                )
                                                                ?>"
                                            data-document-cover="<?= htmlspecialchars(
                                                                        $rawData['cover_image_path']
                                                                            ?? '',
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>"
                                            data-document-date="<?= htmlspecialchars(
                                                                    $rawData['created_at']
                                                                        ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">
                                            <span>
                                                Open
                                            </span>

                                            <i class="fa-solid fa-chevron-right"></i>

                                        </button>

                                    <?php elseif (
                                        $type === 'survey'
                                    ): ?>

                                        <?php

                                        $surveyId =
                                            (int) (
                                                $rawData['survey_id']
                                                ?? 0
                                            );

                                        $surveyAuthorId =
                                            (int) (
                                                $rawData['user_id']
                                                ?? 0
                                            );

                                        $canViewSurveyResults =
                                            $isLoggedIn &&
                                            (
                                                $isAdministrator ||
                                                (
                                                    $currentUserId > 0 &&
                                                    $currentUserId ===
                                                    $surveyAuthorId
                                                )
                                            );

                                        ?>

                                        <?php if (
                                            $canViewSurveyResults
                                        ): ?>

                                            <a
                                                href="index.php?page=survey_results&amp;survey_id=<?= $surveyId ?>&amp;return_to=news"
                                                class="hub-open-button">

                                                <span>
                                                    View Results
                                                </span>

                                                <i class="fa-solid fa-chart-column"></i>

                                            </a>

                                        <?php endif; ?>

                                        <?php if (
                                            empty($rawData['user_responded'])
                                        ): ?>

                                            <a
                                                href="index.php?page=survey_participate&amp;survey_id=<?= $surveyId ?>"
                                                class="hub-open-button">

                                                <span>
                                                    Take Survey
                                                </span>

                                                <i class="fa-solid fa-chevron-right"></i>

                                            </a>

                                        <?php else: ?>

                                            <span class="hub-survey-completed">

                                                <i class="fa-solid fa-circle-check"></i>

                                                Response Submitted

                                            </span>

                                        <?php endif; ?>

                                    <?php endif; ?>

                                </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="hub-empty-state">

                    <span>

                        <i class="fa-regular fa-folder-open"></i>

                    </span>

                    <h3>
                        No updates available
                    </h3>

                    <p>
                        New information will appear here
                        once it is published.
                    </p>

                </div>

            <?php endif; ?>

            <div
                id="hubNoResults"
                class="hub-empty-state"
                hidden>
                <span>

                    <i class="fa-solid fa-magnifying-glass"></i>

                </span>

                <h3>
                    No matching information
                </h3>

                <p>
                    Try another filter or search keyword.
                </p>

            </div>

        </main>

    </section>

    </div>

    <?php
    // Reuse the eligible events already loaded for this feed.
    $upcomingFeedEvents = array_values(array_filter($events, static function (array $event): bool {
        $date = strtotime((string) ($event['event_date'] ?? ''));
        return $date !== false && $date >= strtotime('today');
    }));
    usort($upcomingFeedEvents, static fn(array $first, array $second): int =>
        strtotime((string) $first['event_date']) <=> strtotime((string) $second['event_date']));
    ?>
    <aside class="hub-feed-aside" aria-label="Upcoming events">
        <section class="hub-upcoming">
            <h2>Upcoming events</h2>
            <?php if ($upcomingFeedEvents === []): ?>
                <p>No upcoming events in your feed.</p>
            <?php else: ?>
                <ul>
                    <?php foreach (array_slice($upcomingFeedEvents, 0, 3) as $upcomingEvent): ?>
                        <li>
                            <time datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($upcomingEvent['event_date'])), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(date('M j', strtotime($upcomingEvent['event_date'])), ENT_QUOTES, 'UTF-8') ?>
                            </time>
                            <a href="index.php?page=calendar&amp;date=<?= urlencode(date('Y-m-d', strtotime($upcomingEvent['event_date']))) ?>&amp;month=<?= (int) date('n', strtotime($upcomingEvent['event_date'])) ?>&amp;year=<?= (int) date('Y', strtotime($upcomingEvent['event_date'])) ?>#calendar">
                                <?= htmlspecialchars((string) ($upcomingEvent['title'] ?? 'School event'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="hub-calendar-link" href="index.php?page=calendar">View calendar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </section>
    </aside>
    </div>

</section>

<!-- ==========================================
     UNIVERSAL DETAIL DRAWER
========================================== -->

<div
    id="contentDrawerOverlay"
    class="thread-drawer-overlay"
    aria-hidden="true"></div>

<aside
    id="contentDrawer"
    class="thread-drawer"
    aria-hidden="true"
    aria-labelledby="contentDrawerTitle">

    <header class="thread-drawer-header">

        <div>

            <span
                id="contentDrawerType"
                class="drawer-type-label">
                <i
                    id="contentDrawerTypeIcon"
                    class="fa-solid fa-bullhorn"></i>

                <span id="contentDrawerTypeText">
                    Announcement
                </span>

            </span>

            <h2 id="contentDrawerTitle">
                Information
            </h2>

        </div>

        <button
            type="button"
            id="closeContentDrawer"
            class="drawer-close-button"
            aria-label="Close details">
            <i class="fa-solid fa-xmark"></i>
        </button>

    </header>

    <div class="thread-drawer-meta">

        <span id="contentDrawerAuthorWrap">

            <i class="fa-regular fa-user"></i>

            <span id="contentDrawerAuthor">
                School Administrator
            </span>

        </span>

        <span>

            <i class="fa-regular fa-calendar"></i>

            <span id="contentDrawerDate">
                Date unavailable
            </span>

        </span>

        <span
            id="contentDrawerLocationWrap"
            hidden>
            <i class="fa-solid fa-location-dot"></i>

            <span id="contentDrawerLocation"></span>

        </span>

    </div>

    <div
        id="contentDrawerMedia"
        class="thread-drawer-media"
        hidden>
        <img
            id="contentDrawerImage"
            src=""
            alt="">
    </div>

    <div
        id="contentDrawerContent"
        class="thread-drawer-content">
        Loading information...
    </div>

    <!-- ======================================
         ANNOUNCEMENT ENGAGEMENT
    ======================================= -->

    <section
        id="announcementInteraction"
        class="drawer-engagement"
        hidden>

        <div class="drawer-statistics">

            <span>

                <i class="fa-regular fa-eye"></i>

                <b id="drawerViewCount">
                    0
                </b>

                Views

            </span>

            <span>

                <span
                    id="drawerReactionStack"
                    class="hub-reaction-stack drawer-reaction-stack">
                    <i class="fa-solid fa-thumbs-up reaction-like"></i>

                    <i class="fa-solid fa-heart reaction-love"></i>

                    <i class="fa-solid fa-hand-holding-heart reaction-care"></i>

                    <i class="fa-solid fa-face-surprise reaction-wow"></i>
                </span>

                <b id="drawerReactionCount">
                    0
                </b>

                Reactions

            </span>

            <span>

                <i class="fa-regular fa-comment"></i>

                <b id="drawerCommentCount">
                    0
                </b>

                Comments

            </span>

            <span>

                <i class="fa-solid fa-check-double"></i>

                <b id="drawerAcknowledgmentCount">
                    0
                </b>

                Acknowledged

            </span>

        </div>

        <!-- ==================================
             REACTION BREAKDOWN
        =================================== -->
        <!-- <div
            id="reactionDisabledNotice"
            class="interaction-disabled-notice"
            hidden>
            <i class="fa-solid fa-lock"></i>

            <span>
                Reactions are disabled for this content.
            </span>
        </div>

        <div
            id="commentDisabledNotice"
            class="interaction-disabled-notice"
            hidden>
            <i class="fa-solid fa-lock"></i>

            <span>
                Comments are disabled for this content.
            </span>
        </div> -->

        <div
            id="reactionPicker"
            class="reaction-picker">

            <button
                type="button"
                data-reaction="Like">
                <i class="fa-solid fa-thumbs-up reaction-like"></i>

                <span>
                    Like
                </span>

                <b data-reaction-button-count="Like">
                    0
                </b>
            </button>

            <button
                type="button"
                data-reaction="Love">
                <i class="fa-solid fa-heart reaction-love"></i>

                <span>
                    Love
                </span>

                <b data-reaction-button-count="Love">
                    0
                </b>
            </button>

            <button
                type="button"
                data-reaction="Care">
                <i class="fa-solid fa-hand-holding-heart reaction-care"></i>

                <span>
                    Care
                </span>

                <b data-reaction-button-count="Care">
                    0
                </b>
            </button>

            <button
                type="button"
                data-reaction="Wow">
                <i class="fa-solid fa-face-surprise reaction-wow"></i>

                <span>
                    Wow
                </span>

                <b data-reaction-button-count="Wow">
                    0
                </b>
            </button>

        </div>

        <!-- ==================================
             ACKNOWLEDGMENT PREPARATION
        =================================== -->

        <?php if ($isLoggedIn): ?>

            <button
                type="button"
                id="announcementAcknowledgeButton"
                class="drawer-acknowledge-button"
                hidden>
                <i class="fa-solid fa-check-double"></i>

                <span>
                    Acknowledge Content
                </span>
            </button>

        <?php endif; ?>

    </section>

    <!-- ======================================
         DOCUMENT ACTION
    ======================================= -->

    <section
        id="documentDrawerActions"
        class="drawer-engagement"
        hidden>

        <div class="drawer-statistics">

            <span>

                <i class="fa-regular fa-file-lines"></i>

                <b id="documentDrawerType">
                    FILE
                </b>

            </span>

            <span>

                <i class="fa-solid fa-database"></i>

                <b id="documentDrawerSize">
                    0 KB
                </b>

            </span>

        </div>

        <a
            id="documentDrawerDownload"
            href="#"
            class="hub-open-button"
            download>
            <i class="fa-solid fa-arrow-down"></i>

            Download Document
        </a>

    </section>

    <!-- ======================================
         DISCUSSION
    ======================================= -->

    <section
        id="announcementComments"
        class="drawer-comments"
        hidden>

        <div class="drawer-section-title">

            <div>

                <span class="page-eyebrow">
                    Feedback Avenue
                </span>

                <h3>
                    Discussion
                </h3>

            </div>

        </div>

        <div
            id="drawerCommentList"
            class="drawer-comment-list">
            <div class="drawer-comments-empty">
                No comments yet.
            </div>
        </div>

        <form
            id="announcementCommentForm"
            class="drawer-comment-form">

            <div
                id="drawerReplyContext"
                class="drawer-reply-context"
                hidden>

                <span>
                    Replying to

                    <strong id="drawerReplyName"></strong>
                </span>

                <button
                    type="button"
                    id="cancelDrawerReply"
                    aria-label="Cancel reply"
                    title="Cancel reply">

                    <i
                        class="fa-solid fa-xmark"
                        aria-hidden="true"></i>

                </button>

            </div>

            <label for="announcementCommentInput">
                Question, comment, or suggestion
            </label>

            <textarea
                id="announcementCommentInput"
                name="comment"
                maxlength="1000"
                placeholder="Write your feedback..."
                required></textarea>

            <button type="submit">

                <i class="fa-solid fa-paper-plane"></i>

                Post Feedback

            </button>

        </form>

    </section>

</aside>