<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function migrateFacultyPersonalProfile(mysqli $db): bool
{
    $columns=[];
    foreach($db->query("SHOW COLUMNS FROM user WHERE Field IN ('gender','age')") as $column) $columns[$column['Field']]=$column;
    if(count($columns)!==2 || $columns['gender']['Type']!=="enum('Male','Female','Other')" || $columns['age']['Type']!=='int(11)') {
        throw new RuntimeException('Unexpected personal-profile schema.');
    }
    if($columns['gender']['Null']==='YES' && $columns['age']['Null']==='YES') return false;
    $db->query("ALTER TABLE user MODIFY gender ENUM('Male','Female','Other') NULL DEFAULT NULL, MODIFY age INT NULL DEFAULT NULL");
    return true;
}
if(realpath($_SERVER['SCRIPT_FILENAME'] ?? '')!==__FILE__)return;
require_once __DIR__.'/../../config/database.php';
$mode=$argv[1] ?? '--check';
if(!in_array($mode,['--check','--apply'],true)||count($argv)>2) { fwrite(STDERR,"Use --check or --apply.\n");exit(2); }
$db=openDatabaseConnection();
if($mode==='--check') {
    foreach($db->query("SHOW COLUMNS FROM user WHERE Field IN ('gender','age')") as $column)echo $column['Field'].': nullable='.$column['Null'].PHP_EOL;
} else echo migrateFacultyPersonalProfile($db)?"Personal-profile fields now permit pending values.\n":"Already applied; no changes.\n";
