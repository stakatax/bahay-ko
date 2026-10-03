<?php

require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/ContentAudienceService.php';

class DocumentDownloadService
{
    private Document $document;
    private ContentAudienceService $audience;
    private ?Dashboard $dashboard;

    public function __construct(
        ?Document $document = null,
        ?ContentAudienceService $audience = null,
        ?Dashboard $dashboard = null
    ) {
        $this->document = $document ?? new Document();
        $this->audience = $audience ?? new ContentAudienceService();
        $this->dashboard = $dashboard;
    }

    public function resolve(int $documentId, int $userId, string $context = 'published'): array
    {
        if ($documentId <= 0 || $userId <= 0
            || !in_array($context, ['published', 'workspace', 'department'], true)) {
            throw new DomainException('Document not found.');
        }

        $actor = $this->audience->actor($userId);
        if (!$actor) {
            throw new DomainException('Document not found.');
        }

        $document = $this->document->findWorkflowItemById($documentId);
        if (!$document) {
            throw new DomainException('Document not found.');
        }

        if ($context === 'published') {
            $this->audience->requirePublishedAccess('document', $document, $userId);
        } elseif ($context === 'workspace') {
            // Preserve Administrator review and Faculty access to their own workspace files.
            if ($actor['role_prefix'] !== 'Admin'
                && !($actor['role_prefix'] === 'Faculty' && (int) $document['user_id'] === $userId)) {
                throw new DomainException('Document not found.');
            }
        } else {
            // Reuse the existing department-preview authorization with current account data.
            if ($actor['role_prefix'] !== 'Faculty') {
                throw new DomainException('Document not found.');
            }
            $this->dashboard ??= new Dashboard();
            if (!$this->dashboard->canDepartmentAccessContent(
                'document', $documentId, (int) ($actor['department_id'] ?? 0)
            )) {
                throw new DomainException('Document not found.');
            }
        }

        $relative = trim((string) ($document['file_path'] ?? ''));
        if ($relative === '') {
            $legacyName = (string) ($document['file_name'] ?? '');
            if ($legacyName === '' || basename(str_replace('\\', '/', $legacyName)) !== $legacyName) {
                throw new DomainException('Document not found.');
            }
            $relative = 'Assets/uploads/' . $legacyName;
        }

        // Accept only the current document directory or a single legacy upload filename.
        if (preg_match('/[\x00-\x1f\x7f]/', $relative)
            || !preg_match('~\AAssets/uploads/(?:documents/)?[^/\\\\]+\.(?:pdf|docx?|xlsx?|pptx?|txt)\z~i', $relative)) {
            throw new DomainException('Document not found.');
        }

        $root = realpath(__DIR__ . '/../../Assets/uploads');
        $absolute = realpath(__DIR__ . '/../../' . $relative);
        $normalize = static function (string $path): string {
            $path = str_replace('\\', '/', $path);
            return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
        };
        if ($root === false || $absolute === false || !is_file($absolute) || !is_readable($absolute)
            || !str_starts_with($normalize($absolute), $normalize($root) . '/')) {
            throw new DomainException('Document not found.');
        }

        $name = basename(str_replace('\\', '/', (string) ($document['file_name'] ?? 'document')));
        $name = preg_replace('/[\x00-\x1f\x7f]/', '', $name) ?: 'document';
        return ['path' => $absolute, 'name' => $name];
    }
}
