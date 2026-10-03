<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../scripts/check_database_baseline.php';
$checks=0;
function baselineCheck(bool $condition): void {
    global $checks;
    if (!$condition) throw new RuntimeException('Baseline comparison regression failed.');
    $checks++;
}
$a="CREATE TABLE `sample` ( `id` int NOT NULL, `label` varchar(40) DEFAULT 'a  b', PRIMARY KEY (`id`), KEY `idx_label` (`label`) ) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;";
$b=str_replace('AUTO_INCREMENT=10','AUTO_INCREMENT=900',$a);
baselineCheck(normalizeBaselineDdl($a)===normalizeBaselineDdl($b));
baselineCheck(normalizeBaselineDdl($a)===normalizeBaselineDdl(str_replace('ENGINE=',"\n ENGINE=",$a)));
foreach([str_replace('a  b','a b',$a),str_replace('int NOT NULL','bigint NOT NULL',$a),str_replace('idx_label','idx_other',$a),str_replace('InnoDB','MyISAM',$a),str_replace('utf8mb4','latin1',$a)] as $changed) {
    baselineCheck(normalizeBaselineDdl($a)!==normalizeBaselineDdl($changed));
}
$expected=['sample'=>normalizeBaselineDdl($a)];
baselineCheck(compareBaselineTables($expected,$expected)===[]);
baselineCheck(compareBaselineTables($expected,[])===['Missing table: sample']);
baselineCheck(compareBaselineTables([],$expected)===['Unexpected table: sample']);
baselineCheck(compareBaselineTables($expected,['sample'=>'different'])===['Definition differs: sample']);
baselineCheck(count(readBaselineTables(__DIR__.'/../olshcodb-structure.sql'))>0);
echo "PASS: {$checks} baseline verifier checks.\n";
