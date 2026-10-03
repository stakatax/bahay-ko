<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting government advisory rule refinement..."
    . PHP_EOL;

$ruleNames = [
    'Class suspension',
    'Suspension of classes',
    'No classes',
    'Non-working day',
    'Holiday',
    'Scholarship',
    'Tropical cyclone',
    'Typhoon',
    'Heavy rainfall',
    'Heat index',
    'Flood',
    'Earthquake',
    'Health advisory'
];

$placeholders =
    implode(
        ', ',
        array_fill(
            0,
            count($ruleNames),
            '?'
        )
    );

$stmt =
    $conn->prepare("
        UPDATE government_advisory_rule

        SET match_field = 'Title'

        WHERE rule_name
            IN ({$placeholders})
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare advisory rule refinement: '
            . $conn->error
    );
}

$types =
    str_repeat(
        's',
        count($ruleNames)
    );

$stmt->bind_param(
    $types,
    ...$ruleNames
);

if (!$stmt->execute()) {
    $error =
        $stmt->error;

    $stmt->close();

    throw new RuntimeException(
        'Unable to refine government advisory rules: '
            . $error
    );
}

$updatedRules =
    $stmt->affected_rows;

$stmt->close();

echo $updatedRules
    . " advisory rules refined."
    . PHP_EOL;

$stmt =
    $conn->prepare("
        INSERT INTO government_advisory_rule
        (
            rule_name,
            match_field,
            match_value,
            advisory_type,
            geographic_scope,
            score_adjustment,
            priority_order,
            status,
            created_at
        )
        VALUES
        (
            'Thunderstorm',
            'Title',
            'thunderstorm',
            'Weather',
            NULL,
            35,
            20,
            'Active',
            NOW()
        )

        ON DUPLICATE KEY UPDATE
            match_field =
                VALUES(match_field),

            match_value =
                VALUES(match_value),

            advisory_type =
                VALUES(advisory_type),

            geographic_scope =
                VALUES(geographic_scope),

            score_adjustment =
                VALUES(score_adjustment),

            priority_order =
                VALUES(priority_order),

            status =
                VALUES(status)
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare the Thunderstorm rule: '
            . $conn->error
    );
}

if (!$stmt->execute()) {
    $error =
        $stmt->error;

    $stmt->close();

    throw new RuntimeException(
        'Unable to store the Thunderstorm rule: '
            . $error
    );
}

$stmt->close();

echo "Thunderstorm advisory rule ready."
    . PHP_EOL;

echo "Government advisory rule refinement completed successfully."
    . PHP_EOL;
