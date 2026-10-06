<?php

class RequestNotFoundException extends InvalidArgumentException {}

function requestErrorReference(): string
{
    static $reference;
    if ($reference === null) {
        try { $reference = bin2hex(random_bytes(8)); }
        catch (Throwable $exception) { $reference = dechex((int)(microtime(true) * 1000000)); }
    }
    return $reference;
}

function logRequestFailure(Throwable $exception): void
{
    static $logged;
    $logged ??= new WeakMap();
    if (isset($logged[$exception])) return;
    $logged[$exception] = true;
    // No exception message, SQL, URL/query, request body, cookies, or stack arguments.
    error_log('[request '.requestErrorReference().'] '.get_class($exception)
        .' code='.(int)$exception->getCode().' at '.$exception->getFile().':'.$exception->getLine());
}

function isPublicRequestError(Throwable $exception): bool
{
    if ($exception instanceof RequestRateLimitException) return true;
    if ($exception instanceof InvalidArgumentException || $exception instanceof DomainException) return true;
    if (get_class($exception) !== RuntimeException::class && get_class($exception) !== Exception::class) return false;
    static $messages;
    $messages ??= require __DIR__.'/public-error-messages.php';
    $message = $exception->getMessage();
    return in_array($message, $messages, true)
        || preg_match('/^Incorrect password\. [0-9]+ attempt\(s\) remaining\.$/D', $message) === 1
        || preg_match('/^Your account is temporarily locked\. Try again in [0-9]+ minute\(s\)\.$/D', $message) === 1;
}

function publicErrorMessage(Throwable $exception): string
{
    if (isPublicRequestError($exception)) return $exception->getMessage();
    logRequestFailure($exception);
    return 'We could not complete this request. Please try again later. Reference: '.requestErrorReference().'.';
}

function publicErrorStatus(Throwable $exception, int $expectedStatus): int
{
    if ($exception instanceof RequestNotFoundException) return 404;
    if ($exception instanceof RequestRateLimitException) {
        if (!headers_sent()) {
            header('Retry-After: ' . max(1, $exception->retryAfter));
            header('Cache-Control: no-store');
        }
        return 429;
    }
    return isPublicRequestError($exception) ? $expectedStatus : 500;
}

function requestErrorExpectsJson(): bool
{
    $page = $_GET['page'] ?? '';
    return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
        || (is_string($page) && in_array($page, [
            'session_keep_alive','content_open','content_react','content_comment','content_acknowledge',
            'content_redundancy_check','browser_push_configuration','browser_push_subscribe',
            'browser_push_unsubscribe','government_advisory_preview','government_advisory_prepare'
        ], true));
}

function renderRequestFailure(Throwable $exception): void
{
    if (!$exception instanceof RequestRateLimitException) logRequestFailure($exception);
    $message = publicErrorMessage($exception);
    $status = publicErrorStatus($exception, $exception instanceof DomainException ? 403 : 422);
    $baseLevel = $GLOBALS['request_error_buffer_level'] ?? ob_get_level();
    while (ob_get_level() > $baseLevel) {
        if (!ob_end_clean()) break;
    }
    if (!headers_sent()) {
        foreach (['Location','Content-Length','Content-Disposition','Content-Encoding','Refresh'] as $header) header_remove($header);
        http_response_code($status);
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        if (requestErrorExpectsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false,'message'=>$message,'reference'=>requestErrorReference()], JSON_INVALID_UTF8_SUBSTITUTE);
            return;
        }
        $page = $_GET['page'] ?? '';
        if (is_string($page) && in_array($page, ['document_download','dashboard_export','department_analytics_export'], true)) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $message;
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
    } else {
        // A streamed download cannot be retracted; never append diagnostics or HTML to it.
        return;
    }
    $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<title>Request could not be completed</title><link rel="stylesheet" href="Assets/css/root.css">'
        .'<link rel="stylesheet" href="Assets/css/request-error.css"></head><body><main class="request-error">'
        .'<h1>Request could not be completed</h1><p>'.$safe.'</p><a href="index.php?page=home">Return to home</a></main></body></html>';
}

function installRequestErrorHandling(): void
{
    static $installed = false;
    if ($installed) return;
    $installed = true;
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: frame-ancestors 'self'");
        header('X-Content-Type-Options: nosniff');
    }
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    $GLOBALS['request_error_buffer_level'] = ob_get_level();
    $page = $_GET['page'] ?? '';
    // Leave successful file streaming unbuffered to avoid loading entire downloads into memory.
    if (!is_string($page) || !in_array($page, ['document_download','dashboard_export','department_analytics_export'], true)) ob_start();
    set_exception_handler('renderRequestFailure');
    set_error_handler(static function(int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) return false;
        $exception = new ErrorException('PHP diagnostic', 0, $severity, $file, $line);
        logRequestFailure($exception);
        if (in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
            renderRequestFailure($exception);
            exit;
        }
        // Keep existing warning behavior while preventing diagnostics from corrupting HTML/JSON.
        return true;
    });
    $reserve = str_repeat('x', 32768);
    register_shutdown_function(static function() use (&$reserve): void {
        $reserve = '';
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR], true)) {
            renderRequestFailure(new ErrorException('PHP fatal error', 0, $error['type'], $error['file'], $error['line']));
        }
    });
}
