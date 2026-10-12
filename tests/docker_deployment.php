<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/deployment/docker/configure.php';
$base=['OLSHCO_APP_ENV'=>'production','OLSHCO_APP_URL'=>'https://hub.example.invalid','OLSHCO_APP_KEY'=>str_repeat('a',64),'OLSHCO_DB_HOST'=>'fixture-db','OLSHCO_DB_NAME'=>'fixture','OLSHCO_DB_USER'=>'fixture','OLSHCO_DB_PASSWORD'=>'fixture-only','OLSHCO_TRUSTED_PROXY_CIDRS'=>'192.0.2.8/32,2001:db8::1/128'];
$checks=0;
function dockerCheck(bool $ok):void {global $checks;if(!$ok)throw new RuntimeException('Docker configuration regression.');$checks++;}
$settings=dockerRuntimeConfiguration($base);
dockerCheck(!$settings['email']['enabled']);
dockerCheck(substr_count($settings['proxy'],'SetEnvIfExpr')===2);
dockerCheck(str_contains($settings['proxy'],'CONN_REMOTE_ADDR') && str_contains($settings['proxy'],'https'));
foreach(['OLSHCO_APP_URL','OLSHCO_APP_KEY','OLSHCO_DB_HOST','OLSHCO_DB_NAME','OLSHCO_DB_USER','OLSHCO_DB_PASSWORD','OLSHCO_TRUSTED_PROXY_CIDRS'] as $name){
 $bad=$base;unset($bad[$name]);try{dockerRuntimeConfiguration($bad);throw new LogicException('Missing configuration accepted.');}catch(RuntimeException $e){$checks++;}
}
foreach([
 ['OLSHCO_APP_ENV','development'],['OLSHCO_APP_KEY','short'],['OLSHCO_APP_URL','http://hub.example.invalid'],
 ['OLSHCO_APP_URL','https://user:password@hub.example.invalid'],['OLSHCO_APP_URL','https://hub.example.invalid/?x=1'],
 ['OLSHCO_DB_USER','root'],['OLSHCO_TRUSTED_PROXY_CIDRS','0.0.0.0/0'],['OLSHCO_TRUSTED_PROXY_CIDRS','::/0'],
 ['OLSHCO_TRUSTED_PROXY_CIDRS',"192.0.2.8/32\nSetEnv HTTPS on"],['OLSHCO_TRUSTED_PROXY_CIDRS','192.0.2.8/33'],
 ['OLSHCO_EMAIL_ENABLED','1'],['OLSHCO_WORKER_ENABLED','1']
] as [$name,$value]){
 $bad=$base;$bad[$name]=$value;try{dockerRuntimeConfiguration($bad);throw new LogicException('Unsafe configuration accepted.');}catch(RuntimeException $e){$checks++;}
}
$settings=dockerRuntimeConfiguration(array_merge($base,['OLSHCO_SMTP_PASSWORD'=>"quote'and\\slash\$value"]));
dockerCheck($settings['email']['password']==="quote'and\\slash\$value");
$root=dirname(__DIR__);$docker=file_get_contents($root.'/Dockerfile');$compose=file_get_contents($root.'/docker-compose.yaml');
dockerCheck(str_contains($docker,'composer install --no-dev') && str_contains($docker,'composer check-platform-reqs'));
dockerCheck(!str_contains($compose,"\n    ports:"));
dockerCheck(str_contains($compose,'uploads:/var/www/html/Assets/uploads'));
dockerCheck(str_contains($compose,'OLSHCO_WORKER_ENABLED:-0'));
dockerCheck(!str_contains($compose,'image: mariadb'));
echo "PASS: $checks Docker configuration, proxy validation and packaging checks; no database connections or deliveries.\n";
