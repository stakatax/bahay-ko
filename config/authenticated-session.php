<?php
require_once __DIR__ . '/../app/services/SessionSecurityService.php';
require_once __DIR__ . '/request-errors.php';

function sessionRequestExpectsJson(): bool
{
    return requestErrorExpectsJson();
}

function stopInvalidAccountSession(int $status): never
{
    $message = $status === 401
        ? 'Your account session is no longer valid. Please sign in again.'
        : 'Account verification is temporarily unavailable. Please try again later.';
    header('Cache-Control: no-store');
    if (sessionRequestExpectsJson()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'message'=>$message]);
    } elseif ($status === 401) {
        header('Location: index.php?page=login&error='.rawurlencode($message),true,303);
    } else {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
    exit;
}

function enforceCurrentAccountSession(mysqli $connection): void
{
    if (empty($_SESSION['user_id'])) {
        if (!empty($_SESSION['session_expired']) && sessionRequestExpectsJson()) stopInvalidAccountSession(401);
        return;
    }
    try {
        $state = (new SessionSecurityService(new SessionAccount($connection)))->refresh($_SESSION);
    } catch (Throwable $exception) {
        error_log('Account session verification failed: '.get_class($exception).' (code '.(int)$exception->getCode().').');
        stopInvalidAccountSession(503);
    }
    if ($state === 'revoked') {
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
        stopInvalidAccountSession(401);
    }
}
