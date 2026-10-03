"""Read-only HTTP checks against the approved evaluation tunnel; no login credentials."""
from pathlib import Path
import http.cookiejar
import json
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
PRIVATE = ROOT / 'deployment/tunnel/evaluation/private'
BASE = (PRIVATE / 'public-url.txt').read_text(encoding='utf-8-sig').strip()
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
results = []


def fetch(path, expected, headers=None):
    request = urllib.request.Request(BASE + path, headers=headers or {})
    try:
        response = client.open(request, timeout=20)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        body = response.read()
        assert response.code == expected, (path, response.code)
        results.append({'path': path, 'status': response.code})
        return body, response.headers


body, headers = fetch('/index.php?page=login', 200)
assert b'name="identifier"' in body and b'name="password"' in body
cookies = headers.get_all('Set-Cookie') or []
assert any(all(value in cookie.lower() for value in ['secure', 'httponly', 'samesite=lax']) for cookie in cookies)
for path in ['/config/database.php', '/vendor/autoload.php', '/composer.json',
             '/phpmyadmin/', '/xampp/', '/scripts/dispatch_browser_push.php',
             '/pages/home.php', '/logs/', '/.git/config', '/private/',
             '/Assets/uploads/documents/document_07357ee79eec5e39f7abb54e.pdf']:
    fetch(path, 403)
for path in ['/Assets/css/root.css', '/Assets/js/login.js',
             '/Assets/uploads/profile-photos/profile-4-8435bebd50e1822cdfe2eb125d4a396e.jpg']:
    fetch(path, 200)
fetch('/index.php?page=not_a_real_evaluation_route', 404)
body, headers = fetch('/index.php?page[]=login', 422, {'Accept': 'application/json'})
assert 'application/json' in headers.get('Content-Type', '')
assert json.loads(body).get('success') is False
report = {'base_url': BASE, 'checks': len(results) + 3, 'results': results,
          'login_form_rendered': True, 'secure_cookie_verified': True,
          'ajax_invalid_input_json_verified': True, 'authenticated_roles_tested': False}
(PRIVATE / 'public-smoke-results.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
print(f'PASS: {report["checks"]} public HTTPS smoke checks; no authenticated accounts used.')
