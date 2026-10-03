<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting government advisory intake migration..."
    . PHP_EOL;

/* ==========================================
   GOVERNMENT SOURCE DIRECTORY
========================================== */

if (
    !$conn->query("
        CREATE TABLE IF NOT EXISTS government_source
        (
            government_source_id
                INT NOT NULL AUTO_INCREMENT,

            source_name
                VARCHAR(150) NOT NULL,

            agency_code
                VARCHAR(30) DEFAULT NULL,

            agency_category
                ENUM(
                    'NationalGovernment',
                    'Education',
                    'WeatherEmergency',
                    'LocalGovernment',
                    'HealthSafety',
                    'Other'
                ) NOT NULL DEFAULT 'Other',

            base_url
                VARCHAR(500) NOT NULL,

            allowed_host
                VARCHAR(255) NOT NULL,

            connector_type
                ENUM(
                    'ManualUrl',
                    'Html',
                    'Rss',
                    'JsonApi'
                ) NOT NULL DEFAULT 'ManualUrl',

            authority_weight
                TINYINT UNSIGNED
                NOT NULL DEFAULT 50,

            status
                ENUM(
                    'Active',
                    'Inactive'
                ) NOT NULL DEFAULT 'Active',

            created_by
                INT DEFAULT NULL,

            created_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at
                DATETIME DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                government_source_id
            ),

            UNIQUE KEY
                uq_government_source_host
                (
                    allowed_host
                ),

            KEY
                idx_government_source_directory
                (
                    status,
                    agency_category,
                    source_name
                ),

            KEY
                fk_government_source_creator
                (
                    created_by
                ),

            CONSTRAINT
                fk_government_source_creator

                FOREIGN KEY (
                    created_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ")
) {
    throw new RuntimeException(
        'Unable to create government source table: '
            . $conn->error
    );
}

echo "Government source directory ready."
    . PHP_EOL;

/* ==========================================
   GOVERNMENT ADVISORY REVIEW QUEUE
========================================== */

if (
    !$conn->query("
        CREATE TABLE IF NOT EXISTS government_advisory
        (
            government_advisory_id
                INT NOT NULL AUTO_INCREMENT,

            government_source_id
                INT NOT NULL,

            external_reference
                VARCHAR(150) DEFAULT NULL,

            source_url
                VARCHAR(1000) NOT NULL,

            source_url_hash
                CHAR(64) NOT NULL,

            title
                VARCHAR(255) NOT NULL,

            summary
                TEXT DEFAULT NULL,

            extracted_text
                MEDIUMTEXT DEFAULT NULL,

            content_hash
                CHAR(64) DEFAULT NULL,

            advisory_type
                ENUM(
                    'Holiday',
                    'EducationPolicy',
                    'ClassSuspension',
                    'Emergency',
                    'Weather',
                    'HealthSafety',
                    'Scholarship',
                    'Compliance',
                    'Other'
                ) NOT NULL DEFAULT 'Other',

            geographic_scope
                ENUM(
                    'Nationwide',
                    'Region',
                    'Province',
                    'Municipality',
                    'School'
                ) NOT NULL DEFAULT 'Nationwide',

            scope_value
                VARCHAR(150) DEFAULT NULL,

            issued_at
                DATETIME DEFAULT NULL,

            effective_from
                DATETIME DEFAULT NULL,

            effective_until
                DATETIME DEFAULT NULL,

            relevance_score
                TINYINT UNSIGNED
                NOT NULL DEFAULT 0,

            relevance_reasons
                TEXT DEFAULT NULL,

            fetch_status
                ENUM(
                    'Pending',
                    'Fetched',
                    'Failed',
                    'Manual'
                ) NOT NULL DEFAULT 'Pending',

            retrieval_error
                VARCHAR(500) DEFAULT NULL,

            review_status
                ENUM(
                    'Pending',
                    'Relevant',
                    'Irrelevant',
                    'Converted',
                    'Archived'
                ) NOT NULL DEFAULT 'Pending',

            review_notes
                VARCHAR(1000) DEFAULT NULL,

            reviewed_by
                INT DEFAULT NULL,

            reviewed_at
                DATETIME DEFAULT NULL,

            linked_content_type
                ENUM(
                    'announcement',
                    'event',
                    'document',
                    'survey',
                    'holiday'
                ) DEFAULT NULL,

            linked_content_id
                INT DEFAULT NULL,

            submitted_by
                INT DEFAULT NULL,

            fetched_at
                DATETIME DEFAULT NULL,

            created_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at
                DATETIME DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                government_advisory_id
            ),

            UNIQUE KEY
                uq_government_advisory_url_hash
                (
                    source_url_hash
                ),

            KEY
                idx_government_advisory_review
                (
                    review_status,
                    relevance_score,
                    created_at
                ),

            KEY
                idx_government_advisory_type
                (
                    advisory_type,
                    geographic_scope,
                    effective_from
                ),

            KEY
                idx_government_advisory_reference
                (
                    government_source_id,
                    external_reference
                ),

            KEY
                idx_government_advisory_content_hash
                (
                    content_hash
                ),

            KEY
                idx_government_advisory_link
                (
                    linked_content_type,
                    linked_content_id
                ),

            KEY
                fk_government_advisory_reviewer
                (
                    reviewed_by
                ),

            KEY
                fk_government_advisory_submitter
                (
                    submitted_by
                ),

            CONSTRAINT
                fk_government_advisory_source

                FOREIGN KEY (
                    government_source_id
                )

                REFERENCES government_source (
                    government_source_id
                )

                ON DELETE RESTRICT
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_government_advisory_reviewer

                FOREIGN KEY (
                    reviewed_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_government_advisory_submitter

                FOREIGN KEY (
                    submitted_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ")
) {
    throw new RuntimeException(
        'Unable to create government advisory table: '
            . $conn->error
    );
}

echo "Government advisory review queue ready."
    . PHP_EOL;

/* ==========================================
   DEFAULT CONFIGURABLE TRUSTED SOURCES
========================================== */

$sources = [
    [
        'Official Gazette of the Republic of the Philippines',
        'OG',
        'NationalGovernment',
        'https://www.officialgazette.gov.ph',
        'officialgazette.gov.ph',
        100
    ],
    [
        'Presidential Communications Office',
        'PCO',
        'NationalGovernment',
        'https://pco.gov.ph',
        'pco.gov.ph',
        95
    ],
    [
        'Department of Education',
        'DEPED',
        'Education',
        'https://www.deped.gov.ph',
        'deped.gov.ph',
        100
    ],
    [
        'Commission on Higher Education',
        'CHED',
        'Education',
        'https://ched.gov.ph',
        'ched.gov.ph',
        100
    ],
    [
        'PAGASA',
        'PAGASA',
        'WeatherEmergency',
        'https://www.pagasa.dost.gov.ph',
        'pagasa.dost.gov.ph',
        100
    ],
    [
        'National Disaster Risk Reduction and Management Council',
        'NDRRMC',
        'WeatherEmergency',
        'https://ndrrmc.gov.ph',
        'ndrrmc.gov.ph',
        100
    ]
];

$stmt =
    $conn->prepare("
        INSERT INTO government_source
        (
            source_name,
            agency_code,
            agency_category,
            base_url,
            allowed_host,
            connector_type,
            authority_weight,
            status,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            'ManualUrl',
            ?,
            'Active',
            NOW()
        )

        ON DUPLICATE KEY UPDATE
            source_name =
                VALUES(source_name),

            agency_code =
                VALUES(agency_code),

            agency_category =
                VALUES(agency_category),

            base_url =
                VALUES(base_url),

            authority_weight =
                VALUES(authority_weight)
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare government source seed: '
            . $conn->error
    );
}

foreach ($sources as $source) {
    [
        $sourceName,
        $agencyCode,
        $agencyCategory,
        $baseUrl,
        $allowedHost,
        $authorityWeight
    ] = $source;

    $stmt->bind_param(
        'sssssi',
        $sourceName,
        $agencyCode,
        $agencyCategory,
        $baseUrl,
        $allowedHost,
        $authorityWeight
    );

    if (!$stmt->execute()) {
        $error =
            $stmt->error;

        $stmt->close();

        throw new RuntimeException(
            'Unable to store trusted government source: '
                . $error
        );
    }
}

$stmt->close();

echo count($sources)
    . " trusted government sources ready."
    . PHP_EOL;

/* ==========================================
   AUDIT ACTIONS
========================================== */

$actionNames = [
    'CREATE_GOVERNMENT_ADVISORY',
    'REVIEW_GOVERNMENT_ADVISORY',
    'CONVERT_GOVERNMENT_ADVISORY',
    'MANAGE_GOVERNMENT_SOURCE'
];

$stmt =
    $conn->prepare("
        INSERT INTO actions
        (
            action_name
        )
        VALUES
        (
            ?
        )

        ON DUPLICATE KEY UPDATE
            action_name =
                VALUES(action_name)
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare government advisory actions: '
            . $conn->error
    );
}

foreach ($actionNames as $actionName) {
    $stmt->bind_param(
        's',
        $actionName
    );

    if (!$stmt->execute()) {
        $error =
            $stmt->error;

        $stmt->close();

        throw new RuntimeException(
            'Unable to store government advisory action: '
                . $error
        );
    }
}

$stmt->close();

echo "Government advisory audit actions ready."
    . PHP_EOL;

echo "Government advisory intake migration completed successfully."
    . PHP_EOL;
