<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/dbconnect.php';

echo "<h2>Resetting Development Data...</h2>";

$conn->query("SET FOREIGN_KEY_CHECKS=0");

/* Child tables first */

$conn->query("TRUNCATE TABLE announcement_view");
$conn->query("TRUNCATE TABLE announcement_reaction");
$conn->query("TRUNCATE TABLE announcement_comment");

$conn->query("TRUNCATE TABLE survey_answer");
$conn->query("TRUNCATE TABLE survey_choice");
$conn->query("TRUNCATE TABLE survey_question");

$conn->query("TRUNCATE TABLE notification");

$conn->query("TRUNCATE TABLE announcement_target");
$conn->query("TRUNCATE TABLE event_target");
$conn->query("TRUNCATE TABLE document_target");
$conn->query("TRUNCATE TABLE survey_target");

/* Parent tables */

$conn->query("TRUNCATE TABLE announcements");
$conn->query("TRUNCATE TABLE events");
$conn->query("TRUNCATE TABLE documents");
$conn->query("TRUNCATE TABLE survey");

/* Logs */

$conn->query("TRUNCATE TABLE activity_log");

/*
Delete only development users.

Keeps the admin account (user_id=1).
Adjust this if needed.
*/

$conn->query("
DELETE FROM user
WHERE role_id <> 1
");

$conn->query("ALTER TABLE user AUTO_INCREMENT = 2");

$conn->query("SET FOREIGN_KEY_CHECKS=1");

echo "<h3>Development database has been reset.</h3>";
