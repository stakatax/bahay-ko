<?php
if (PHP_SAPI !== 'cli' && !defined('OLSHCO_SESSION_TEST_HARNESS')) { http_response_code(404); exit; }

function sessionFixture(mysqli $connection): array
{
    foreach (['role','user','department','education_level','academic_program','grade_level','section'] as $table) {
        $ddl = $connection->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);
        $ddl = preg_replace('/,\n\)/',"\n)",$ddl);
        $connection->query($ddl);
    }
    $connection->query("INSERT INTO role (role_id,role_prefix) VALUES (1,'Admin'),(2,'Faculty'),(3,'Student'),(4,'Parent')");
    $password = password_hash('FixtureOnlyAa9!'.bin2hex(random_bytes(8)),PASSWORD_DEFAULT);
    $stmt=$connection->prepare("INSERT INTO user (user_id,first_name,last_name,password,gender,age,birthdate,status,role_id) VALUES (?,'Session','Fixture',?,'Other',30,'1996-01-01','Active',?)");
    foreach ([1,2,3,4] as $id) { $stmt->bind_param('isi',$id,$password,$id);$stmt->execute(); }
    $stmt->close();
    return ['user_id'=>1,'role_id'=>1,'role'=>'Admin','auth_credential_version'=>hash('sha256',$password),'csrf_token'=>'fixture-token','last_activity_at'=>time(),'session_regenerated_at'=>time()];
}
