<?php
// Included only by the isolated CGI upload harness.
if (!defined('UPLOAD_TEST_ROOT')) { http_response_code(404); exit; }
require_once UPLOAD_TEST_ROOT . '/config/database.php';
function logActivity($connection, $action, $description, ?int $target = null): void {}
function replacementFixture(?array $file, bool $forceFailure): array
{
    $db = openDatabaseConnection();
    $oldPath = 'Assets/uploads/documents/h5-replacement-' . bin2hex(random_bytes(12)) . '.txt';
    $paths = [$oldPath];
    try {
        foreach (['documents', 'document_target', 'user', 'role', 'publication_notification_outbox'] as $table) {
            $ddl = $db->query('SHOW CREATE TABLE `' . $table . '`')->fetch_assoc()['Create Table'];
            $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
            $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
            $ddl = preg_replace('/,\n\)/', "\n)", $ddl);
            $db->query($ddl);
        }
        $db->query("INSERT INTO role (role_id,role_prefix) VALUES (1,'Admin'),(2,'Student')");
        $db->query("INSERT INTO user (user_id,first_name,last_name,password,gender,age,status,role_id) VALUES (1,'Upload','Fixture','unused','Other',20,'Active',1)");
        $_SESSION = ['role' => 'Admin', 'user_id' => 1];
        $document = new Document($db);
        file_put_contents(UPLOAD_TEST_ROOT . '/' . $oldPath, 'Original document');
        $id = $document->create(['title' => 'Original fixture', 'description' => 'Fixture description',
            'file_name' => 'original.txt', 'file_path' => $oldPath, 'file_type' => 'txt',
            'file_size' => 17, 'user_id' => 1, 'workflow_status' => 'draft', 'release_mode' => 'immediate']);
        if ($forceFailure) {
            $db->query("ALTER TABLE documents ADD CONSTRAINT h5_reject_replacement CHECK (title <> 'Replacement fixture')");
        }
        $service = (new ReflectionClass(PostService::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(PostService::class, 'document'))->setValue($service, $document);
        (new ReflectionProperty(PostService::class, 'announcement'))->setValue($service, new Announcement($db));
        $before = glob(UPLOAD_TEST_ROOT . '/Assets/uploads/documents/document_*');
        $error = null;
        try {
            (new ReflectionMethod(PostService::class, 'updateDocument'))->invoke($service, $id,
                ['target_roles' => ['Student'], 'audience_scope' => 'schoolwide',
                    'document_title' => 'Replacement fixture', 'document_description' => 'Fixture description',
                    'workflow_action' => 'draft', 'release_mode' => 'immediate'],
                ['document_file' => $file], 1, 'Upload Fixture', $db);
        } catch (Throwable $exception) { $error = get_class($exception); }
        $saved = $db->query('SELECT file_path,file_name,file_size,title FROM documents WHERE document_id=' . (int) $id)->fetch_assoc();
        $paths[] = $saved['file_path'];
        $after = glob(UPLOAD_TEST_ROOT . '/Assets/uploads/documents/document_*');
        return ['error' => $error, 'old_exists' => is_file(UPLOAD_TEST_ROOT . '/' . $oldPath),
            'saved' => $saved, 'same_path' => $saved['file_path'] === $oldPath,
            'saved_exists' => is_file(UPLOAD_TEST_ROOT . '/' . $saved['file_path']),
            'new_files' => count(array_diff($after, $before))];
    } finally {
        foreach (array_unique($paths) as $path) {
            if (is_file(UPLOAD_TEST_ROOT . '/' . $path)) { unlink(UPLOAD_TEST_ROOT . '/' . $path); }
        }
        $db->close();
    }
}
