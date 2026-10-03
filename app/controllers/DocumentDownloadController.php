<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/DocumentDownloadService.php';

class DocumentDownloadController extends BaseController
{
    public function download(): void
    {
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''));
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            header('Allow: GET, HEAD');
            $this->fail(405, 'Method not allowed.');
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->fail(401, 'Authentication required.');
        }

        $requestedId = $_GET['document_id'] ?? null;
        $context = $_GET['context'] ?? 'published';
        if (!is_string($requestedId) || !ctype_digit($requestedId)
            || !is_string($context)) {
            $this->fail(404, 'Document not found.');
        }

        try {
            $file = (new DocumentDownloadService())->resolve((int) $requestedId, $userId, $context);
            $handle = fopen($file['path'], 'rb');
            if ($handle === false) {
                throw new RuntimeException('Unable to open authorized document.');
            }

            try {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['path']);
                $extension = strtolower(pathinfo($file['path'], PATHINFO_EXTENSION));
                $inline = ($_GET['inline'] ?? '') === '1'
                    && (($extension === 'pdf' && $mime === 'application/pdf')
                        || ($extension === 'txt' && $mime === 'text/plain'));
                $disposition = $inline ? 'inline' : 'attachment';
                $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']) ?: 'document';

                header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
                header('Content-Security-Policy: sandbox');
                header("Content-Disposition: {$disposition}; filename=\"{$fallback}\"; filename*=UTF-8''"
                    . rawurlencode($file['name']));
                header('Content-Length: ' . (string) fstat($handle)['size']);

                // Release the session lock before streaming a potentially large file.
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                if ($method !== 'HEAD') {
                    fpassthru($handle);
                }
            } finally {
                fclose($handle);
            }
        } catch (DomainException $exception) {
            $this->fail(404, 'Document not found.');
        } catch (Throwable $exception) {
            error_log('Document delivery failed: ' . publicErrorMessage($exception));
            if (!headers_sent()) {
                header_remove('Content-Length');
                header_remove('Content-Disposition');
                $this->fail(500, 'Unable to download the document.');
            }
        }
        exit;
    }

    private function fail(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
            echo $message;
        }
        exit;
    }
}
