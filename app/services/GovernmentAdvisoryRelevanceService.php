<?php

require_once __DIR__
    . '/../models/GovernmentAdvisory.php';

class GovernmentAdvisoryRelevanceService
{
    private GovernmentAdvisory $model;

    public function __construct(
        ?GovernmentAdvisory $model = null
    ) {
        $this->model =
            $model
            ?? new GovernmentAdvisory();
    }

    /* ==========================================
       ASSESS GOVERNMENT ADVISORY
    ========================================== */

    public function assess(
        array $source,
        string $title,
        string $content = ''
    ): array {
        $title =
            $this->normalizeText(
                $title
            );

        $content =
            $this->normalizeText(
                $content
            );

        $combinedText =
            trim(
                $title
                    . ' '
                    . $content
            );

        $agencyCategory =
            $this->normalizeText(
                (string) (
                    $source['agency_category']
                    ?? ''
                )
            );

        $sourceHost =
            $this->normalizeText(
                (string) (
                    $source['allowed_host']
                    ?? ''
                )
            );

        $authorityWeight =
            max(
                0,
                min(
                    100,
                    (int) (
                        $source['authority_weight']
                        ?? 0
                    )
                )
            );

        /*
         * A trusted source contributes at most
         * 20 points. Content rules provide the
         * remaining relevance evidence.
         */
        $authorityScore =
            (int) round(
                $authorityWeight * 0.20
            );

        $rawScore =
            $authorityScore;

        $reasons = [
            [
                'rule_id' =>
                null,

                'rule_name' =>
                'Trusted source authority',

                'score_adjustment' =>
                $authorityScore
            ]
        ];

        $matchedRuleIds = [];

        $typeScores = [];

        $scopeScores = [];

        $rules =
            $this->model
            ->getActiveRules();

        foreach ($rules as $rule) {
            $matchField =
                (string) (
                    $rule['match_field']
                    ?? ''
                );

            $matchValue =
                $this->normalizeText(
                    (string) (
                        $rule['match_value']
                        ?? ''
                    )
                );

            if ($matchValue === '') {
                continue;
            }

            $searchableValue =
                match ($matchField) {
                    'Title' =>
                    $title,

                    'Content' =>
                    $content,

                    'CombinedText' =>
                    $combinedText,

                    'AgencyCategory' =>
                    $agencyCategory,

                    'SourceHost' =>
                    $sourceHost,

                    default =>
                    ''
                };

            if (
                $searchableValue === '' ||
                !$this->matches(
                    $searchableValue,
                    $matchValue,
                    $matchField
                )
            ) {
                continue;
            }

            $ruleId =
                (int) (
                    $rule['government_advisory_rule_id']
                    ?? 0
                );

            if ($ruleId > 0) {
                $matchedRuleIds[] =
                    $ruleId;
            }

            $scoreAdjustment =
                (int) (
                    $rule['score_adjustment']
                    ?? 0
                );

            $rawScore +=
                $scoreAdjustment;

            $ruleName =
                trim(
                    (string) (
                        $rule['rule_name']
                        ?? 'Matched rule'
                    )
                );

            $reasons[] = [
                'rule_id' =>
                $ruleId > 0
                    ? $ruleId
                    : null,

                'rule_name' =>
                $ruleName,

                'score_adjustment' =>
                $scoreAdjustment
            ];

            $advisoryType =
                trim(
                    (string) (
                        $rule['advisory_type']
                        ?? ''
                    )
                );

            if (
                $advisoryType !== '' &&
                $scoreAdjustment > 0
            ) {
                $typeScores[$advisoryType] =
                    (
                        $typeScores[$advisoryType]
                        ?? 0
                    )
                    + $scoreAdjustment;
            }

            $geographicScope =
                trim(
                    (string) (
                        $rule['geographic_scope']
                        ?? ''
                    )
                );

            if (
                $geographicScope !== '' &&
                $scoreAdjustment > 0
            ) {
                $scopeScores[$geographicScope] =
                    (
                        $scopeScores[$geographicScope]
                        ?? 0
                    )
                    + $scoreAdjustment;
            }
        }

        $relevanceScore =
            max(
                0,
                min(
                    100,
                    $rawScore
                )
            );

        $advisoryType =
            $this->selectHighestCandidate(
                $typeScores,
                'Other'
            );

        /*
 * Weak overall relevance must not receive a
 * confident advisory classification merely
 * because a directory page mentions a keyword.
 */
        if ($relevanceScore < 40) {
            $advisoryType =
                'Other';
        }

        $geographicScope =
            $this->selectHighestCandidate(
                $scopeScores,
                'Nationwide'
            );

        $relevanceLevel =
            match (true) {
                $relevanceScore >= 70 =>
                'High',

                $relevanceScore >= 40 =>
                'Medium',

                default =>
                'Low'
            };

        $recommendation =
            match ($relevanceLevel) {
                'High' =>
                'Review promptly for possible school publication.',

                'Medium' =>
                'Review its applicability to school operations.',

                default =>
                'Likely low relevance; verify before retaining.'
            };

        return [
            'relevance_score' =>
            $relevanceScore,

            'raw_score' =>
            $rawScore,

            'relevance_level' =>
            $relevanceLevel,

            'advisory_type' =>
            $advisoryType,

            'geographic_scope' =>
            $geographicScope,

            'recommendation' =>
            $recommendation,

            'matched_rule_ids' =>
            array_values(
                array_unique(
                    $matchedRuleIds
                )
            ),

            'reasons' =>
            $reasons,

            'requires_review' =>
            true
        ];
    }

    /* ==========================================
       RULE MATCHING
    ========================================== */

    private function matches(
        string $searchableValue,
        string $matchValue,
        string $matchField
    ): bool {
        if (
            in_array(
                $matchField,
                [
                    'AgencyCategory',
                    'SourceHost'
                ],
                true
            )
        ) {
            return $searchableValue ===
                $matchValue;
        }

        return str_contains(
            $searchableValue,
            $matchValue
        );
    }

    /* ==========================================
       SELECT STRONGEST CLASSIFICATION
    ========================================== */

    private function selectHighestCandidate(
        array $scores,
        string $default
    ): string {
        if ($scores === []) {
            return $default;
        }

        arsort(
            $scores,
            SORT_NUMERIC
        );

        return (string) array_key_first(
            $scores
        );
    }

    /* ==========================================
       TEXT NORMALIZATION
    ========================================== */

    private function normalizeText(
        string $value
    ): string {
        $value =
            html_entity_decode(
                $value,
                ENT_QUOTES |
                    ENT_HTML5,
                'UTF-8'
            );

        $value =
            strip_tags(
                $value
            );

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            )
            ?? $value;

        return mb_strtolower(
            trim(
                $value
            ),
            'UTF-8'
        );
    }
}
