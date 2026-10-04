<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/services/ContentEngagementService.php';
require_once __DIR__ . '/../app/services/DocumentDownloadService.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require __DIR__ . '/../config/dbconnect.php';
$conn->set_charset('utf8mb4');

final class AccessTestNotifications extends NotificationService
{
    public int $calls = 0;
    public function __construct() {}
    public function notifyContentEngagement(
        int $recipientId, int $actorId, string $action, string $contentType,
        int $contentId, string $contentTitle, string $interactionKey, ?string $reaction = null
    ): bool {
        $this->calls++;
        return true;
    }
}

function accessTestModel(string $class, mysqli $connection): object
{
    $model = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(BaseModel::class, 'conn'))->setValue($model, $connection);
    return $model;
}

$checks = 0;
function checkAccess(bool $condition, string $description): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $description);
    }
    $checks++;
}

function deniesAccess(callable $operation, string $description): void
{
    try {
        $operation();
    } catch (DomainException | InvalidArgumentException $exception) {
        checkAccess(true, $description);
        return;
    } catch (RuntimeException $exception) {
        if (in_array($exception->getMessage(), [
            'Content not found.', 'Document not found.',
            'Voting is disabled for this content.',
            'Comments are disabled for this content.',
            'Acknowledgment is not required for this content.'
        ], true)) {
            checkAccess(true, $description);
            return;
        }
        throw $exception;
    }
    checkAccess(false, $description);
}

$fixture = null;
try {
    require_once __DIR__ . '/../database/migrations/029_parent_child_record.php';
    $childDdl=str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', parentChildRecordSql());
    $childDdl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $childDdl);
    $childDdl=preg_replace('/,\s*\)/', "\n)", $childDdl);
    $conn->query($childDdl);

    // All subsequent fixture writes go to connection-local temporary tables.
    // SHOW CREATE supplies schema only; failure aborts before any fixture INSERT.
    foreach ([
        'role', 'user', 'parent_student', 'announcements', 'events', 'documents',
        'announcement_target', 'event_target', 'document_target', 'survey_target',
        'content_view', 'content_reaction', 'content_comment', 'content_acknowledgment', 'request_rate_limit'
    ] as $table) {
        $definition = $conn->query("SHOW CREATE TABLE {$table}")->fetch_assoc()['Create Table'];
        $definition = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition, 1);
        // Temporary tables cannot carry foreign keys; all other schema definitions stay intact.
        $definition = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $definition);
        $definition = preg_replace('/,\n\)/', "\n)", $definition);
        if ($table === 'content_reaction') {
            $definition = str_replace("enum('Like','Love','Care','Wow')", "enum('Upvote','Downvote')", $definition);
        }
        $conn->query($definition);
    }

    $conn->query("INSERT INTO role (role_id, role_prefix)
        VALUES (1,'Admin'), (2,'Student'), (3,'Parent'), (4,'Faculty')");
    $stmt = $conn->prepare("INSERT INTO user
        (user_id, first_name, last_name, password, gender, age, status, role_id,
         department_id, education_level_id, academic_program_id, grade_level_id, section_id)
        VALUES (?, 'Fixture', 'User', 'not-a-login-hash', 'Other', 20, ?, ?, ?, ?, ?, ?, ?)");
    foreach ([
        [1,'Active',1,0,0,0,0,0],
        [2,'Active',2,10,1000,100,1,11],
        [3,'Active',2,20,2000,200,2,22],
        [4,'Active',3,99,99,99,99,99],
        [5,'Active',3,10,1000,100,1,11],
        [6,'Active',4,10,1000,100,1,11],
        [7,'Active',4,20,2000,200,2,22],
        [8,'Inactive',2,10,1000,100,1,11],
        [9,'Active',4,10,1000,999,1,11],
        [10,'Active',3,10,1000,100,1,11]
    ] as $row) {
        $stmt->bind_param('isiiiiii', ...$row);
        $stmt->execute();
    }
    $stmt->close();
    $conn->query("INSERT INTO parent_student
        (parent_user_id, student_user_id, relationship, status)
        VALUES (4,2,'Guardian','Verified'), (4,3,'Guardian','Verified'),
               (5,2,'Guardian','Pending'), (5,3,'Guardian','Rejected'),
               (10,8,'Guardian','Verified'), (10,6,'Guardian','Verified')");

    $uploadDirectory = realpath(__DIR__ . '/../Assets/uploads/documents');
    if ($uploadDirectory === false) {
        throw new RuntimeException('Document upload directory is required for the file-delivery fixture.');
    }
    $fixture = $uploadDirectory . DIRECTORY_SEPARATOR . 'access-test-' . bin2hex(random_bytes(12)) . '.txt';
    if (file_put_contents($fixture, 'Content-access regression fixture.') === false) {
        throw new RuntimeException('Unable to create the temporary upload fixture.');
    }
    $relative = 'Assets/uploads/documents/' . basename($fixture);
    $stmt = $conn->prepare("INSERT INTO documents
        (document_id,title,description,file_name,file_path,file_type,user_id,status,workflow_status,
         allow_reactions,allow_comments,require_acknowledgment)
        VALUES (?, 'Fixture', 'Fixture', 'fixture.txt', ?, 'txt', 6, ?, ?, 1, 1, 1)");
    foreach ([
        [1,'active','published'],[2,'active','published'],[3,'active','published'],
        [4,'active','published'],[5,'active','published'],
        [10,'active','draft'],[11,'active','pending_review'],[12,'active','scheduled'],
        [13,'active','rejected'],[14,'active','archived'],[15,'inactive','published'],
        [16,'active','published']
    ] as [$id,$status,$workflow]) {
        $stmt->bind_param('isss', $id, $relative, $status, $workflow);
        $stmt->execute();
    }
    $stmt->close();
    $conn->query("INSERT INTO document_target (document_id,role_id,department_id,academic_program_id)
        VALUES (1,2,10,100), (2,3,10,100), (4,3,10,200), (5,4,10,100)");
    $conn->query("INSERT INTO announcements
        (announcement_id,title,content,type,user_id,status,workflow_status,
         allow_reactions,allow_comments,require_acknowledgment)
        VALUES (1,'Fixture','Fixture','General',6,'active','published',1,1,1)");
    $conn->query("INSERT INTO events
        (event_id,title,description,user_id,status,workflow_status,
         allow_reactions,allow_comments,require_acknowledgment)
        VALUES (1,'Fixture','Fixture',6,'active','published',1,1,1)");
    foreach (['announcement','event'] as $type) {
        $conn->query("INSERT INTO {$type}_target ({$type}_id,role_id,department_id,academic_program_id)
            VALUES (1,2,10,100)");
    }

    $audience = new ContentAudienceService(new ContentAudience($conn));
    $document = accessTestModel(Document::class, $conn);
    $dashboard = accessTestModel(Dashboard::class, $conn);
    $downloads = new DocumentDownloadService($document, $audience, $dashboard);
    $doc = $document->findById(1);

    foreach ([1,2] as $actor) {
        $audience->requirePublishedAccess('document', $doc, $actor);
        checkAccess(true, 'Admin / eligible Student can access published content');
    }
    foreach ([0,3,4,5,6,7,8,99999] as $actor) {
        deniesAccess(fn() => $audience->requirePublishedAccess('document', $doc, $actor),
            'Wrong role, academic scope, inactive, missing or anonymous actor denied');
    }
    $audience->requirePublishedAccess('document', $document->findById(2), 4);
    checkAccess(true, 'Parent uses verified Student profile and keeps Parent role');
    foreach ([5,10] as $actor) {
        deniesAccess(fn() => $audience->requirePublishedAccess('document', $document->findById(2), $actor),
            'Pending/rejected links and inactive/non-Student children denied');
        deniesAccess(fn() => $audience->requirePublishedAccess('document', $document->findById(3), $actor),
            'Unlinked Parent cannot fall back to a schoolwide audience');
    }
    deniesAccess(fn() => $audience->requirePublishedAccess('document', $document->findById(4), 4),
        'Cannot combine department of one linked child with program of another');
    $audience->requirePublishedAccess('document', $document->findById(5), 6);
    checkAccess(true, 'Eligible Faculty allowed');
    deniesAccess(fn() => $audience->requirePublishedAccess('document', $document->findById(5), 9),
        'Faculty in wrong program denied normal recipient access');
    foreach ([2,3,4,6] as $actor) {
        $audience->requirePublishedAccess('document', $document->findById(3), $actor);
        checkAccess(true, 'No target rows means schoolwide for eligible accounts');
    }

    $ranked = $audience->filterForUser('document', 'document_id', [$doc], 2);
    checkAccess($ranked[0]['target_specificity_score'] === 170, 'Existing role/department/program ranking preserved');
    $guest = $audience->filterForUser('document', 'document_id', [$doc, $document->findById(3)], 0, true);
    checkAccess(count($guest) === 1 && $guest[0]['document_id'] === 3, 'Public summary sees schoolwide only');
    checkAccess($audience->filterForUser('document', 'document_id', [], 2) === [], 'Empty list preserved');

    $conn->query("INSERT INTO document_target
        (document_id,role_id,department_id,education_level_id,academic_program_id,grade_level_id,section_id)
        VALUES (3,2,10,1000,100,1,11)");
    $audience->requirePublishedAccess('document', $document->findById(3), 2);
    checkAccess(true, 'All academic target dimensions match together');
    foreach ([
        'department_id'=>10, 'education_level_id'=>1000, 'academic_program_id'=>100,
        'grade_level_id'=>1, 'section_id'=>11
    ] as $dimension=>$originalValue) {
        $conn->query("UPDATE user SET {$dimension}=9999 WHERE user_id=2");
        deniesAccess(fn() => $audience->requirePublishedAccess('document', $document->findById(3), 2),
            'Each changed academic assignment takes effect immediately');
        $conn->query("UPDATE user SET {$dimension}={$originalValue} WHERE user_id=2");
    }
    $conn->query("UPDATE user SET status='Inactive' WHERE user_id=2");
    deniesAccess(fn() => $audience->requirePublishedAccess('document', $doc, 2),
        'Suspended actor loses content access immediately');
    $conn->query("UPDATE user SET status='Active' WHERE user_id=2");

    // A link revoked during an existing session must no longer grant access.
    $conn->query("UPDATE parent_student SET status='Rejected' WHERE parent_user_id=4");
    deniesAccess(fn() => $audience->requirePublishedAccess('document', $document->findById(2), 4),
        'Revoked relationship takes effect without a new login');
    $conn->query("UPDATE parent_student SET status='Verified' WHERE parent_user_id=4");

    $engagement = accessTestModel(ContentEngagement::class, $conn);
    $notifications = new AccessTestNotifications();
    $service = (new ReflectionClass(ContentEngagementService::class))->newInstanceWithoutConstructor();
    foreach ([
        'engagement' => $engagement,
        'announcement' => accessTestModel(Announcement::class, $conn),
        'event' => accessTestModel(Event::class, $conn),
        'document' => $document,
        'audience' => $audience,
        'notificationService' => $notifications
    ] as $property => $value) {
        (new ReflectionProperty($service, $property))->setValue($service, $value);
    }

    foreach (['announcement','event','document'] as $type) {
        foreach ([
            fn() => $service->open($type, 1, 3),
            fn() => $service->react($type, 1, 3, 'Upvote'),
            fn() => $service->comment($type, 1, 3, 'Denied fixture'),
            fn() => $service->acknowledge($type, 1, 3)
        ] as $operation) {
            deniesAccess($operation, 'Cross-audience engagement rejected');
        }
    }
    foreach (['content_view','content_reaction','content_comment','content_acknowledgment'] as $table) {
        checkAccess((int) $conn->query("SELECT COUNT(*) AS total FROM {$table}")->fetch_assoc()['total'] === 0,
            'Denied requests leave no engagement writes');
    }
    checkAccess($notifications->calls === 0, 'Denied requests create no notifications');

    foreach (['announcement','event','document'] as $type) {
        $opened = $service->open($type, 1, 2);
        checkAccess(isset($opened['content']), 'Eligible content opens');
        $service->open($type, 1, 2);
        checkAccess($service->react($type, 1, 2, 'Upvote')['reaction_changed'], 'First reaction saved');
        $callsBeforeWithdrawal = $notifications->calls;
        $withdrawn = $service->react($type, 1, 2, 'Upvote');
        checkAccess($withdrawn['selected_reaction'] === null, 'Same vote withdraws selection');
        checkAccess($notifications->calls === $callsBeforeWithdrawal, 'Withdrawal creates no notification');
        $reactionCount = $conn->query("SELECT COUNT(*) AS total FROM content_reaction
            WHERE content_type='{$type}' AND content_id=1 AND user_id=2")->fetch_assoc()['total'];
        checkAccess((int) $reactionCount === 0, 'Withdrawal removes the vote record');
        $service->react($type, 1, 2, 'Downvote');
        $switched = $service->react($type, 1, 2, 'Upvote');
        checkAccess($switched['engagement']['reaction_count'] === 1, 'Switch retains one vote per user');
        checkAccess($switched['engagement']['reaction_breakdown'] === ['Upvote'=>1,'Downvote'=>0],
            'Switch updates both vote totals');
        deniesAccess(fn() => $service->react($type, 1, 2, 'Like'), 'Legacy reaction rejected');
        checkAccess($service->acknowledge($type, 1, 2)['acknowledgment_created'], 'First acknowledgment saved');
        checkAccess(!$service->acknowledge($type, 1, 2)['acknowledgment_created'], 'Duplicate acknowledgment unchanged');
        $service->comment($type, 1, 2, 'Allowed fixture');
        checkAccess(true, 'Eligible comment saved');
        if ($type === 'document') {
            checkAccess(str_starts_with($opened['content']['file_path'], 'index.php?page=document_download&'),
                'JSON returns authorized URL instead of storage path');
        }
    }
    checkAccess((int) $conn->query('SELECT COUNT(*) AS total FROM content_view')->fetch_assoc()['total'] === 3,
        'Repeated opens preserve view deduplication');

    foreach (['announcement'=>'announcements','event'=>'events','document'=>'documents'] as $type=>$table) {
        $conn->query("UPDATE {$table} SET allow_reactions=0,allow_comments=0,require_acknowledgment=0
            WHERE {$type}_id=1");
        deniesAccess(fn() => $service->react($type, 1, 2, 'Downvote'), 'Reaction setting preserved');
        deniesAccess(fn() => $service->comment($type, 1, 2, 'Disabled'), 'Comment setting preserved');
        deniesAccess(fn() => $service->acknowledge($type, 1, 2), 'Acknowledgment setting preserved');
    }
    foreach ([0,-1,99999] as $id) {
        deniesAccess(fn() => $service->open('document', $id, 2), 'Invalid or missing ID rejected');
    }

    checkAccess($downloads->resolve(1, 2)['path'] === realpath($fixture), 'Eligible download resolves authorized file');
    foreach ([0,3,5,8] as $actor) {
        deniesAccess(fn() => $downloads->resolve(1, $actor), 'Unauthorized download denied');
    }
    foreach ([10,11,12,13,14,15] as $id) {
        deniesAccess(fn() => $service->open('document', $id, 2), 'Unpublished/inactive engagement denied');
        deniesAccess(fn() => $downloads->resolve($id, 2), 'Unpublished/inactive normal download denied');
        checkAccess($downloads->resolve($id, 6, 'workspace')['path'] === realpath($fixture),
            'Owner workspace preview preserved');
        checkAccess($downloads->resolve($id, 1, 'workspace')['path'] === realpath($fixture),
            'Administrator review preserved');
    }
    deniesAccess(fn() => $downloads->resolve(10, 2, 'workspace'), 'Student cannot forge workspace context');
    deniesAccess(fn() => $downloads->resolve(10, 7, 'workspace'), 'Faculty cannot forge ownership');
    checkAccess($downloads->resolve(10, 9, 'department')['path'] === realpath($fixture),
        'Existing same-department Faculty preview policy preserved');
    deniesAccess(fn() => $downloads->resolve(10, 7, 'department'), 'Cross-department preview denied');
    deniesAccess(fn() => $downloads->resolve(10, 2, 'department'), 'Student cannot forge department context');
    deniesAccess(fn() => $downloads->resolve(1, 2, 'other'), 'Unknown context rejected');

    $stmt = $conn->prepare('UPDATE documents SET file_path=? WHERE document_id=16');
    foreach ([
        'config/dbconnect.php', '../config/dbconnect.php',
        'Assets/uploads/documents/../../config/dbconnect.php',
        'Assets/uploads/documents/' . bin2hex(random_bytes(8)) . '.txt',
        'https://example.invalid/file.pdf',
        'Assets/uploads/profile-photos/avatar.php',
        "Assets/uploads/documents/file\0.txt"
    ] as $unsafe) {
        $stmt->bind_param('s', $unsafe);
        $stmt->execute();
        deniesAccess(fn() => $downloads->resolve(16, 2), 'Unsafe or missing file path denied');
    }
    $stmt->close();

    echo "PASS: {$checks} content-access checks; only temporary tables and a temporary upload fixture used.\n";
} finally {
    $conn->close(); // Automatically removes this connection's temporary tables.
    if ($fixture !== null && is_file($fixture)) {
        unlink($fixture); // Only the exact random fixture created by this test.
    }
}
