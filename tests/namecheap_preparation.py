"""Isolated bootstrap and Apache-template tests; no DB or notification calls."""
from pathlib import Path
import json
import os
import shutil
import subprocess
import tempfile
import urllib.request
import urllib.error
import uuid

ROOT=Path(__file__).resolve().parents[1]
PHP=Path('C:/xampp/php/php.exe')
CGI=Path('C:/xampp/php/php-cgi.exe')
checks=0


def check(value,label):
    global checks
    if not value: raise AssertionError(label)
    checks+=1


def settings(values):
    # Fixture values have no PHP quotes; never read real local credential files.
    return '<?php return ['+','.join("'"+k+"'=>'"+v+"'" for k,v in values.items())+'];'


with tempfile.TemporaryDirectory(prefix='olshco-namecheap-check-') as directory:
    fixture=Path(directory)
    bootstrap=fixture/'bootstrap.php'
    shutil.copyfile(ROOT/'deployment/namecheap/private/bootstrap.php.example',bootstrap)
    entry=fixture/'entry.php'
    entry.write_text('<?php echo json_encode([getenv("OLSHCO_APP_ENV")==="production",getenv("OLSHCO_DB_PASSWORD")===" fixture-password=unchanged ",date_default_timezone_get()==="Asia/Manila"]);',encoding='utf-8')
    values={'OLSHCO_APP_ENV':'production','OLSHCO_APP_KEY':'a'*64,'OLSHCO_APP_URL':'https://school.example.invalid','OLSHCO_DB_HOST':'localhost','OLSHCO_DB_USER':'fixture_user','OLSHCO_DB_PASSWORD':' fixture-password=unchanged ','OLSHCO_DB_NAME':'fixture_db','OLSHCO_DB_PORT':'3306'}
    for valid,changes in [(True,{}),(False,{'OLSHCO_APP_KEY':''}),(False,{'OLSHCO_APP_ENV':'development'}),(False,{'OLSHCO_APP_URL':'http://school.example.invalid'}),(False,{'OLSHCO_DB_USER':'root'}),(False,{'UNEXPECTED_SECRET':'must-not-appear'})]:
        (fixture/'environment.php').write_text(settings(values|changes),encoding='utf-8')
        result=subprocess.run([str(PHP),'-d','auto_prepend_file='+str(bootstrap),str(entry)],capture_output=True,text=True)
        if valid: check(result.returncode==0 and json.loads(result.stdout)==[True,True,True],'Private CLI settings preserve values and timezone')
        else:
            check(result.returncode!=0 and not result.stdout,'Invalid private settings stop before application')
            check('fixture-password' not in result.stderr and 'must-not-appear' not in result.stderr,'No settings disclosed in failure')
    env=os.environ.copy(); env.update({'SCRIPT_FILENAME':str(entry),'REQUEST_METHOD':'GET','REDIRECT_STATUS':'1'})
    result=subprocess.run([str(CGI),'-d','auto_prepend_file='+str(bootstrap)],env=env,capture_output=True,text=True)
    check('Status: 503' in result.stdout and 'Service configuration is unavailable.' in result.stdout,'Invalid web settings return safe 503')
    check('fixture-password' not in result.stdout and 'must-not-appear' not in result.stdout,'Web failure does not leak settings')
    (fixture/'environment.php').write_text(settings(values),encoding='utf-8')
    result=subprocess.run([str(CGI),'-d','auto_prepend_file='+str(bootstrap)],env=env,capture_output=True,text=True)
    check('[true,true,true]' in result.stdout,'Private settings also load for web PHP CGI')

# Temporary independent Apache directory, never the live root configuration.
fixture=ROOT/('namecheap-check-'+uuid.uuid4().hex)
fixture.mkdir()
try:
    shutil.copyfile(ROOT/'deployment/namecheap/public/.htaccess.example',fixture/'.htaccess')
    blocked=['app','config','include','pages','vendor','scripts','database','dev','docs','tests','logs','deployment','private','Assets/documents','Assets/documents-covers','Assets/post']
    for folder in blocked:
        path=fixture/folder/'probe.php'; path.parent.mkdir(parents=True,exist_ok=True); path.write_text('<?php echo "PRIVATE-FIXTURE";',encoding='utf-8')
    (fixture/'index.php').write_text('<?php require __DIR__."/app/probe.php"; echo " PUBLIC-ENTRY";',encoding='utf-8')
    for name in ['.env','.user.ini','backup.sql','archive.zip','composer.json','composer.lock','README.md','draft.php.example']:
        (fixture/name).write_text('BLOCKED-FIXTURE',encoding='utf-8')
    (fixture/'Assets/js').mkdir(parents=True); (fixture/'Assets/js/check.js').write_text('/*PUBLIC-ASSET*/')
    opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
    def request(relative):
        url='http://127.0.0.1/bahay-ko/'+fixture.name+'/'+relative
        try:
            with opener.open(url,timeout=10) as response: return response.status,response.read().decode('utf-8',errors='replace')
        except urllib.error.HTTPError as error: return error.code,error.read().decode('utf-8',errors='replace')
    status,body=request('index.php?page=home')
    check(status==200 and 'PRIVATE-FIXTURE PUBLIC-ENTRY' in body,'Public entry and internal PHP includes work')
    check(request('Assets/js/check.js')[0]==200,'Public JavaScript remains available')
    for folder in blocked: check(request(folder+'/probe.php')[0]==403,'Internal directory blocked: '+folder)
    for name in ['.env','.user.ini','backup.sql','archive.zip','composer.json','composer.lock','README.md','draft.php.example']:
        check(request(name)[0]==403,'Sensitive filename blocked: '+name)
    (fixture/'.well-known/acme-challenge').mkdir(parents=True)
    (fixture/'.well-known/acme-challenge/check').write_text('CERTIFICATE-FIXTURE')
    check(request('.well-known/acme-challenge/check')[0]==200,'Certificate challenge remains available')
finally:
    target=fixture.resolve()
    if target.parent!=ROOT.resolve() or not target.name.startswith('namecheap-check-'): raise RuntimeError('Invalid cleanup target')
    shutil.rmtree(target)

print('PASS:',checks,'Namecheap preparation checks; fixtures only, no database or delivery operations.')
