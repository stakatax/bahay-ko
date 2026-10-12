"""Windows XAMPP rehearsal of container Apache rules; loopback fixtures only."""
from pathlib import Path
import json,socket,subprocess,time,urllib.request,urllib.error
ROOT=Path(__file__).resolve().parents[1]
FIXTURE=ROOT/'logs/docker-apache-check'
FIXTURE.mkdir(exist_ok=True);WEB=FIXTURE/'public';WEB.mkdir(exist_ok=True)
for folder in ['config','app','vendor','scripts','logs','Assets/uploads/documents','Assets/uploads/profile-photos']:
 p=WEB/folder;p.mkdir(parents=True,exist_ok=True);(p/'probe.php').write_text('<?php echo "PRIVATE-FIXTURE";')
for name in ['composer.json','.env','backup.sql']:(WEB/name).write_text('PRIVATE-FIXTURE')
for rel in ['Assets/uploads/.htaccess','Assets/uploads/documents/.htaccess','Assets/uploads/profile-photos/.htaccess']:
 (WEB/rel).write_bytes((ROOT/rel).read_bytes())
(WEB/'Assets/uploads/test.jpg').write_bytes(b'fixture-media')
(WEB/'Assets/uploads/test.php.jpg').write_bytes(b'fixture-executable-name')
(WEB/'index.php').write_text('<?php require '+repr((ROOT/'config/security.php').as_posix())+'; startSecureSession(); echo json_encode(["https"=>requestUsesHttps()]);')
base=(ROOT/'deployment/tunnel/evaluation/apache.conf').read_text()
base=base.replace('127.0.0.1:8080','127.0.0.1:8092').replace('localhost:8080','localhost:8092')
base=base.replace((ROOT/'deployment/tunnel/evaluation/public').as_posix(),WEB.as_posix())
base=base.replace((ROOT/'deployment/tunnel/evaluation/private').as_posix(),FIXTURE.as_posix())
base='\n'.join(line for line in base.splitlines() if 'auto_prepend_file' not in line and line!='SetEnv HTTPS on')
base+='\nLoadModule setenvif_module modules/mod_setenvif.so\n'
(FIXTURE/'logs').mkdir(exist_ok=True);(FIXTURE/'sessions').mkdir(exist_ok=True)
rules=(ROOT/'deployment/docker/apache.conf').read_text().replace('/var/www/html',WEB.as_posix()).replace('/run/olshco/proxy.conf',(FIXTURE/'proxy.conf').as_posix())
configuration=FIXTURE/'apache.conf';configuration.write_text(base+'\n'+rules)
opener=urllib.request.build_opener(urllib.request.ProxyHandler({}));checks=0
def request(path='',headers=None):
 try:response=opener.open(urllib.request.Request('http://127.0.0.1:8092/'+path,headers=headers or {}),timeout=5)
 except urllib.error.HTTPError as error:response=error
 with response:return response.status,response.read(),response.headers
probe=socket.socket();probe.bind(('127.0.0.1',8092));probe.close()
for peer,expected in [('127.0.0.1/32',True),('192.0.2.8/32',False)]:
 (FIXTURE/'proxy.conf').write_text('SetEnvIfExpr "%{CONN_REMOTE_ADDR} -ipmatch \''+peer+'\' && tolower(req(\'X-Forwarded-Proto\')) == \'https\'" HTTPS=on\n')
 args=['C:/xampp/apache/bin/httpd.exe','-f',configuration.as_posix()]
 subprocess.run(args+['-t'],check=True,capture_output=True)
 process=subprocess.Popen(args,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=0x08000000)
 try:
  for attempt in range(30):
   try:status,body,headers=request('index.php',{'X-Forwarded-Proto':'https'});break
   except (OSError,urllib.error.URLError):time.sleep(.2)
  else:raise RuntimeError('Fixture Apache did not start.')
  assert status==200 and json.loads(body)['https']==expected;checks+=1
  cookie=headers.get('Set-Cookie','').lower();assert ('secure' in cookie)==expected and 'httponly' in cookie;checks+=1
  if expected:
   for path in ['config/probe.php','app/probe.php','vendor/probe.php','scripts/probe.php','logs/probe.php','composer.json','.env','backup.sql','Assets/uploads/documents/probe.php','Assets/uploads/test.php.jpg']:
    assert request(path)[0]==403,path;checks+=1
   assert request('Assets/uploads/test.jpg')[0]==200;checks+=1
   assert request('index.php')[0]==200;checks+=1
 finally:
  process.terminate();process.wait(timeout=10)
print(f'PASS: {checks} isolated Apache HTTPS/proxy, cookie and private/upload access checks; no database or deliveries.')
