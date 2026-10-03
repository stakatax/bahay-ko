<?php

function accountReviewStatements(string $decision): array
{
    return match ($decision) {
        'approve' => [
            'records_verified' => ['label'=>'Verified against school records', 'message'=>'Your registration details were verified against school records. Your account is approved.'],
            'parent_verified' => ['label'=>'Child enrollment and relationship verified', 'message'=>'The child\'s enrollment and the claimed parent or guardian relationship were verified against school records. Your account is approved.'],
        ],
        'reject' => [
            'incomplete' => ['label'=>'Incomplete registration information', 'message'=>'Your registration contains incomplete information. Please submit a new registration with all required details.'],
            'records_mismatch' => ['label'=>'Details do not match school records', 'message'=>'The registration details could not be matched to school records. Please contact the school to confirm your information before registering again.'],
            'enrollment_unverified' => ['label'=>'Enrollment could not be verified', 'message'=>'Current enrollment could not be verified against school records. Please contact the school for assistance.'],
            'relationship_unverified' => ['label'=>'Parent or guardian relationship not verified', 'message'=>'The claimed parent or guardian relationship could not be verified against school records. Please contact the school for assistance.'],
            'duplicate' => ['label'=>'An account already exists', 'message'=>'An account already exists for this applicant. Please use the existing account or request password recovery.'],
        ],
        default => throw new InvalidArgumentException('Invalid review decision.'),
    };
}

function resolveAccountReviewStatement(string $decision, array $input): string
{
    $selected = $input['review_statement'] ?? '';
    $custom = $input['review_notes'] ?? '';
    if (!is_string($selected) || !is_string($custom)) {
        throw new InvalidArgumentException('Invalid review statement.');
    }
    $selected = trim($selected);
    $custom = trim($custom);
    if ($selected === '' || $selected === 'custom') {
        if (($decision === 'reject' || $selected === 'custom') && $custom === '') {
            throw new InvalidArgumentException('Select a statement or enter a custom note.');
        }
        if (mb_strlen($custom) > 1000) {
            throw new InvalidArgumentException('The review note must not exceed 1000 characters.');
        }
        return $custom;
    }
    $statements = accountReviewStatements($decision);
    if (!isset($statements[$selected])) {
        throw new InvalidArgumentException('Select a valid review statement.');
    }
    return $statements[$selected]['message'];
}
