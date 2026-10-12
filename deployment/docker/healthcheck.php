<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
try {
    require '/var/www/html/config/database.php';
    $db=openDatabaseConnection();$db->query('SELECT 1');$db->close();
    $socket=@fsockopen('127.0.0.1',80,$errno,$error,3);
    if(!$socket)exit(1);fclose($socket);exit(0);
} catch(Throwable $error){exit(1);}
