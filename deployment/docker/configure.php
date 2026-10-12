<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function dockerRuntimeConfiguration(array $env): array
{
    foreach (['OLSHCO_APP_URL','OLSHCO_APP_KEY','OLSHCO_DB_HOST','OLSHCO_DB_NAME','OLSHCO_DB_USER','OLSHCO_DB_PASSWORD','OLSHCO_TRUSTED_PROXY_CIDRS'] as $name) {
        if (!isset($env[$name]) || !is_string($env[$name]) || $env[$name] === '') throw new RuntimeException('Required container configuration is missing: ' . $name);
    }
    if (($env['OLSHCO_APP_ENV'] ?? '') !== 'production' || strlen($env['OLSHCO_APP_KEY']) < 64) throw new RuntimeException('Production environment and stable application key are required.');
    $url=parse_url($env['OLSHCO_APP_URL']);
    if (!$url || ($url['scheme']??'')!=='https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) throw new RuntimeException('Canonical HTTPS application URL is required.');
    if (strtolower($env['OLSHCO_DB_USER'])==='root') throw new RuntimeException('Use the dedicated normal database user.');
    $rules=[];
    foreach (explode(',', $env['OLSHCO_TRUSTED_PROXY_CIDRS']) as $cidr) {
        $parts=explode('/',trim($cidr));$ip=$parts[0];$max=str_contains($ip,':')?128:32;
        $bits=$parts[1]??(string)$max;
        if(count($parts)>2 || !filter_var($ip,FILTER_VALIDATE_IP) || !ctype_digit($bits) || (int)$bits<1 || (int)$bits>$max) throw new RuntimeException('Trusted proxy CIDRs are invalid or unrestricted.');
        $rules[]="SetEnvIfExpr \"%{CONN_REMOTE_ADDR} -ipmatch '".$ip.'/'.$bits."' && tolower(req('X-Forwarded-Proto')) == 'https'\" HTTPS=on";
    }
    $email=['enabled'=>($env['OLSHCO_EMAIL_ENABLED']??'0')==='1','host'=>$env['OLSHCO_SMTP_HOST']??'', 'port'=>(int)($env['OLSHCO_SMTP_PORT']??587), 'encryption'=>$env['OLSHCO_SMTP_ENCRYPTION']??'tls', 'username'=>$env['OLSHCO_SMTP_USER']??'', 'password'=>$env['OLSHCO_SMTP_PASSWORD']??'', 'from_email'=>$env['OLSHCO_EMAIL_FROM']??'', 'from_name'=>'OLSHCO Digital Hub', 'reply_to_email'=>$env['OLSHCO_EMAIL_REPLY_TO']??'', 'timeout'=>20];
    if($email['enabled'] && ($email['host']==='' || $email['username']==='' || $email['password']==='' || !filter_var($email['from_email'],FILTER_VALIDATE_EMAIL))) throw new RuntimeException('Enabled email requires complete private SMTP settings.');
    $push=['subject'=>$env['OLSHCO_VAPID_SUBJECT']??'', 'public_key'=>$env['OLSHCO_VAPID_PUBLIC_KEY']??'', 'private_key'=>$env['OLSHCO_VAPID_PRIVATE_KEY']??''];
    if(($env['OLSHCO_WORKER_ENABLED']??'0')==='1' && ($push['subject']==='' || $push['public_key']==='' || $push['private_key']==='')) throw new RuntimeException('Enable the worker only after configuring VAPID keys.');
    return ['email'=>$email,'push'=>$push,'proxy'=>implode("\n",$rules)."\n"];
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')!==__FILE__)return;
try {
    $settings=dockerRuntimeConfiguration(getenv());
    foreach(['email','push'] as $kind) {
        if(file_put_contents('/run/olshco/'.$kind.'.php',"<?php\nreturn ".var_export($settings[$kind],true).";\n",LOCK_EX)===false)throw new RuntimeException('Private configuration could not be written.');
        chmod('/run/olshco/'.$kind.'.php',0640);
    }
    if(file_put_contents('/run/olshco/proxy.conf',$settings['proxy'],LOCK_EX)===false)throw new RuntimeException('Proxy configuration could not be written.');
} catch(Throwable $error) { fwrite(STDERR,'Container startup configuration rejected. Check required variables, HTTPS URL, dedicated DB user and trusted proxy CIDRs.' . PHP_EOL);exit(1); }
