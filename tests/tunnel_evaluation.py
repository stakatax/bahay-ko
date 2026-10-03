"""Check the isolated evaluation listener; no database access or public tunnel."""
from pathlib import Path
import socket
import subprocess
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
EVALUATION = ROOT / 'deployment/tunnel/evaluation'
HTTPD = 'C:/xampp/apache/bin/httpd.exe'
CONF = str(EVALUATION / 'apache.conf')


def status(path):
    try:
        with urllib.request.urlopen('http://127.0.0.1:8080' + path, timeout=3) as response:
            return response.status, response.read()
    except urllib.error.HTTPError as error:
        return error.code, error.read()


with socket.socket() as probe:
    if probe.connect_ex(('127.0.0.1', 8080)) == 0:
        raise RuntimeError('Port 8080 already in use; refusing to test another service.')
subprocess.run([HTTPD, '-t', '-f', CONF], check=True)
for relative in ['private/bootstrap.php', 'private/environment.php',
                 'public/config/email.local.php', 'public/config/push.local.php']:
    subprocess.run(['C:/xampp/php/php.exe', '-l', str(EVALUATION / relative)], check=True)
checks = 0
process = subprocess.Popen([HTTPD, '-X', '-f', CONF], stdout=subprocess.DEVNULL,
                           stderr=subprocess.DEVNULL)
try:
    for attempt in range(40):
        if process.poll() is not None:
            raise RuntimeError('Evaluation Apache exited; inspect its private error log.')
        try:
            code, body = status('/index.php')
            break
        except (OSError, urllib.error.URLError):
            time.sleep(0.2)
    else:
        raise RuntimeError('Evaluation listener did not start.')
    assert code == 503 and body == b'Service configuration is unavailable.'
    checks += 1
    for path in ['/config/database.php', '/vendor/autoload.php', '/composer.json',
                 '/phpmyadmin/', '/xampp/', '/dashboard/', '/scripts/dispatch_browser_push.php',
                 '/pages/home.php', '/app/', '/logs/', '/.git/config', '/private/',
                 '/Assets/uploads/documents/sample.pdf']:
        assert status(path)[0] == 403, path
        checks += 1
    assert status('/Assets/css/root.css')[0] == 200
    checks += 1
    print(f'Passed {checks} local HTTP checks. No database or tunnel was opened.')
finally:
    # Stop only the test process, never the normal XAMPP Apache service.
    process.terminate()
    process.wait(timeout=10)
