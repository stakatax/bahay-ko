<?php

require_once __DIR__ . '/database.php';

try {
    $conn = openDatabaseConnection();
} catch (Throwable $exception) {
    // Do not log credentials, connection strings, SQL, or exception messages.
    error_log('Database initialization failed: ' . get_class($exception) . ' (code ' . (int) $exception->getCode() . ').');
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Database service is unavailable. Check server configuration and logs.\n");
        exit(1);
    }
    http_response_code(503);
    header('Cache-Control: no-store');
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $ajax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    if ($ajax || stripos($accept, 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false, 'message'=>'Database service is temporarily unavailable. Please try again later.']);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Database service is temporarily unavailable. Please try again later.';
    }
    exit;
}
