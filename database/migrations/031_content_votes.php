<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../config/dbconnect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Explicitly approved fresh start: remove old reactions, never existing votes.
// DDL implicitly commits in MySQL; expand first, reset with a transaction,
// then restrict the enum. Re-running after any interrupted step is safe.
$column = $conn->query("SHOW COLUMNS FROM content_reaction LIKE 'reaction_type'")->fetch_assoc();
if ($column['Type'] !== "enum('Upvote','Downvote')") {
    $conn->query("ALTER TABLE content_reaction MODIFY reaction_type
        ENUM('Like','Love','Care','Wow','Upvote','Downvote') NOT NULL");
}
try {
    $conn->begin_transaction();
    $conn->query("DELETE FROM content_reaction WHERE reaction_type IN ('Like','Love','Care','Wow')");
    $removedCurrent = $conn->affected_rows;
    $conn->query("DELETE FROM announcement_reaction WHERE reaction IN ('Like','Love','Care','Wow')");
    $removedHistorical = $conn->affected_rows;
    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();
    throw $exception;
}
$conn->query("ALTER TABLE content_reaction MODIFY reaction_type ENUM('Upvote','Downvote') NOT NULL");
echo "Voting ready. Removed {$removedCurrent} unified and {$removedHistorical} historical reactions."
    . PHP_EOL;
