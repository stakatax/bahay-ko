<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

$conn->set_charset(
    'utf8mb4'
);

echo PHP_EOL;
echo "Starting legal document content migration...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    $columnResult =
        $conn->query("
            SHOW COLUMNS

            FROM legal_document_version

            LIKE 'content'
        ");

    if ($columnResult->num_rows === 0) {
        $conn->query("
            ALTER TABLE legal_document_version

            ADD COLUMN content
                LONGTEXT
                NULL
                AFTER title
        ");

        echo "Legal document content column added.";
        echo PHP_EOL;
    } else {
        echo "Legal document content column already exists.";
        echo PHP_EOL;
    }

    $termsContent = <<<'HTML'
<div class="register-legal-content">
    <div class="register-legal-introduction">
        Please read these conditions before creating or continuing to use your OLSHCO Digital Hub account.
    </div>

    <h3>1. Account Information</h3>
    <p>
        You agree to provide complete and accurate registration information. Your account remains subject to school verification and administrative approval.
    </p>

    <h3>2. Account Security</h3>
    <ul>
        <li>Keep your password private and secure.</li>
        <li>Do not share your account with another person.</li>
        <li>Do not impersonate another system user.</li>
        <li>Report suspected unauthorized access to an authorized school representative.</li>
    </ul>

    <h3>3. Acceptable Use</h3>
    <p>
        The Digital Hub must be used only for legitimate educational and school-related purposes.
    </p>
    <ul>
        <li>Do not publish false, harmful, abusive, or unauthorized content.</li>
        <li>Do not bypass security or access information without permission.</li>
        <li>Follow school policies when interacting with content and other users.</li>
    </ul>

    <h3>4. Content and Communication</h3>
    <p>
        Announcements, events, documents, surveys, reminders, and notifications support official school communication. Urgent instructions should be verified with the appropriate school office when necessary.
    </p>

    <h3>5. Administrative Action</h3>
    <p>
        Authorized administrators may review account applications and system activity, moderate content, restrict access, or suspend accounts when required for security, policy compliance, or protection of the school community.
    </p>

    <h3>6. System Availability</h3>
    <p>
        The Digital Hub may occasionally be unavailable because of maintenance, security updates, technical problems, or circumstances outside the school's control.
    </p>

    <h3>7. Changes to These Terms</h3>
    <p>
        These conditions may be updated when school policies or system requirements change. Users may be required to review and accept an updated version before continuing to use the system.
    </p>
</div>
HTML;

    $privacyContent = <<<'HTML'
<div class="register-legal-content">
    <div class="register-legal-introduction">
        This notice explains what information the OLSHCO Digital Hub processes and why it is needed.
    </div>

    <h3>1. Information We Process</h3>
    <ul>
        <li>Name, email address, birthdate, gender, and contact information.</li>
        <li>Account role and academic assignment.</li>
        <li>Content views, acknowledgments, reactions, comments, survey responses, and notification activity.</li>
        <li>Selected interests and personalization preferences.</li>
    </ul>

    <h3>2. Why We Process Information</h3>
    <p>
        Information is used to verify accounts, control access, deliver relevant school content, support academic communication, personalize the information feed, and produce authorized administrative reports.
    </p>

    <h3>3. Notifications</h3>
    <p>
        Depending on your preferences, the system may deliver information through the Digital Hub, browser notifications, or email. Available delivery preferences can be changed from notification settings.
    </p>

    <h3>4. Sensitive Information</h3>
    <p>
        Optional health, accessibility, geographic, or wellbeing information requested through profile surveys requires separate and specific consent. It is not automatically covered by general registration acceptance.
    </p>

    <h3>5. Access and Protection</h3>
    <p>
        Personal information should be accessed only for authorized school and system purposes. Reasonable safeguards are used to reduce unauthorized access, disclosure, alteration, or loss.
    </p>

    <h3>6. Analytics and Reporting</h3>
    <p>
        Administrative analytics should use combined or aggregate information whenever identifying an individual is unnecessary. Sensitive individual responses must not be exposed in general dashboard reports.
    </p>

    <h3>7. Retention</h3>
    <p>
        Account and activity records may be retained while required for school operations, security, auditing, legal obligations, or legitimate system administration.
    </p>

    <h3>8. Your Choices</h3>
    <ul>
        <li>Provide accurate account information.</li>
        <li>Manage available notification and personalization preferences.</li>
        <li>Contact an authorized representative to request a correction or raise a privacy concern.</li>
    </ul>

    <h3>9. Updates to This Notice</h3>
    <p>
        This notice may be updated when data practices or school requirements change. Users may be required to acknowledge significant changes.
    </p>
</div>
HTML;

    $documents = [
        [
            'document_type' =>
            'Terms',

            'version' =>
            '1.0',

            'content' =>
            $termsContent
        ],
        [
            'document_type' =>
            'Privacy',

            'version' =>
            '1.0',

            'content' =>
            $privacyContent
        ]
    ];

    $stmt =
        $conn->prepare("
            UPDATE legal_document_version

            SET content = ?

            WHERE document_type = ?
              AND version = ?
              AND (
                    content IS NULL
                    OR TRIM(content) = ''
                  )
        ");

    foreach ($documents as $document) {
        $content =
            $document['content'];

        $documentType =
            $document['document_type'];

        $version =
            $document['version'];

        $stmt->bind_param(
            'sss',
            $content,
            $documentType,
            $version
        );

        $stmt->execute();
    }

    $stmt->close();

    $missingResult =
        $conn->query("
            SELECT
                COUNT(*) AS total

            FROM legal_document_version

            WHERE content IS NULL
               OR TRIM(content) = ''
        ");

    $missingRow =
        $missingResult->fetch_assoc();

    $missingCount =
        (int) (
            $missingRow['total']
            ?? 0
        );

    if ($missingCount > 0) {
        throw new RuntimeException(
            'Every legal document version must contain readable content before this migration can finish.'
        );
    }

    $conn->query("
        ALTER TABLE legal_document_version

        MODIFY COLUMN content
            LONGTEXT
            NOT NULL
            AFTER title
    ");

    $conn->commit();

    echo "Terms and Privacy content stored successfully.";
    echo PHP_EOL;
    echo "Legal document content migration completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Migration failed: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
