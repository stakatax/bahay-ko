<?php

/** Validate HTTP value shapes without altering valid text or credentials. */
function validateRequestInput(array $query, array $post): void
{
    $route = $query['page'] ?? 'home';
    if (!is_string($route)) {
        throw new InvalidArgumentException('Invalid page request.');
    }

    $arrayFields = match ($route) {
        'post_store' => ['target_roles', 'audience_scopes', 'content_interest_ids'],
        'survey_store' => ['target_roles', 'audience_scopes', 'content_interest_ids', 'questions'],
        'student_profile_save' => ['interest_ids', 'interest_weights'],
        'student_profile_survey_save' => ['survey_responses', 'survey_consents'],
        'student_profile_question_create', 'student_profile_question_update' => ['options'],
        'notification_update_category_preferences' => ['categories'],
        'survey_submit_response' => ['answers'],
        default => []
    };

    foreach ($query as $key => $value) {
        validateRequestInputValue($value, (string) $key, 0, false);
    }
    foreach ($post as $key => $value) {
        $maximumDepth = match ($key) {
            'questions' => 3,
            'audience_scopes', 'categories', 'answers', 'survey_responses' => 2,
            default => 1
        };
        validateRequestInputValue($value, (string) $key, 0, in_array($key, $arrayFields, true), $maximumDepth, (string) $key);
    }
}

function validateRequestInputValue(mixed $value, string $field, int $depth, bool $allowArray, int $maximumDepth = 0, string $container = ''): void
{
    if (is_array($value)) {
        if (!$allowArray || $depth >= $maximumDepth
            || ($container === 'questions' && $depth === 2 && $field !== 'choices')) {
            throw new InvalidArgumentException('Invalid form values. Please review your entries and try again.');
        }
        foreach ($value as $key => $child) {
            validateRequestInputValue($child, (string) $key, $depth + 1, true, $maximumDepth, $container);
        }
        return;
    }
    if (!is_string($value) && !is_int($value)) {
        throw new InvalidArgumentException('Invalid form values. Please review your entries and try again.');
    }
    $text = (string) $value;
    if (!mb_check_encoding($text, 'UTF-8') || str_contains($text, "\0")) {
        throw new InvalidArgumentException('Invalid text encoding. Please review your entries and try again.');
    }
    // School-issued Student IDs are textual identifiers, not database row IDs.
    $numericField = str_ends_with($field, '_id')
        || in_array($field, ['target_department', 'target_education_level', 'target_program', 'target_grade_level', 'target_section', 'survey_version', 'sort_order'], true);
    if ($numericField && !in_array($field, ['student_id', 'child_student_id'], true) && trim($text) !== '') {
        $digits = ltrim(trim($text), '0');
        $maximum = (string) PHP_INT_MAX;
        if (!ctype_digit(trim($text)) || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new InvalidArgumentException('Invalid record identifier. Please review your selection and try again.');
        }
    }
}
