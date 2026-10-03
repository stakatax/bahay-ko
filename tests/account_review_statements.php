<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/account-review-statements.php';
$checks = 0;
function statementCheck(bool $ok, string $message): void {
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
foreach (['approve', 'reject'] as $decision) {
    foreach (accountReviewStatements($decision) as $key=>$statement) {
        statementCheck(resolveAccountReviewStatement($decision, ['review_statement'=>$key, 'review_notes'=>'Tampered message']) === $statement['message'], 'Server uses canonical statement');
    }
    statementCheck(resolveAccountReviewStatement($decision, ['review_statement'=>'custom', 'review_notes'=>' Custom verification ']) === 'Custom verification', 'Custom note preserved');
}
foreach ([['reject',[]], ['approve',['review_statement'=>'custom']], ['reject',['review_statement'=>'records_verified']], ['approve',['review_statement'=>'unknown']], ['approve',['review_statement'=>[]]], ['approve',['review_statement'=>'custom','review_notes'=>str_repeat('a',1001)]]] as [$decision,$input]) {
    try { resolveAccountReviewStatement($decision,$input); throw new LogicException('Invalid statement accepted'); }
    catch (InvalidArgumentException $e) { statementCheck(true,'Invalid or missing statement rejected'); }
}
statementCheck(resolveAccountReviewStatement('approve',['review_notes'=>'Existing verification note']) === 'Existing verification note','Existing form requests remain compatible');
echo "PASS: $checks account review statement checks; no account changes or emails.\n";
