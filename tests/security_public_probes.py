"""Controlled loopback-only attack probes; no authenticated writes or external targets."""
import json
from pathlib import Path
from urllib.request import Request, build_opener, HTTPRedirectHandler
from urllib.error import HTTPError
from urllib.parse import urlencode

BASE = 'http://127.0.0.1/bahay-ko/'
class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None
opener = build_opener(NoRedirect())
results = []
def probe(label, path, allowed, payload=None):
    request = Request(BASE + path, data=urlencode(payload).encode() if payload else None)
    try:
        response = opener.open(request, timeout=15)
    except HTTPError as error:
        response = error
    body = response.read().decode('utf-8', 'replace')
    headers = response.headers
    passed = response.code in allowed
    passed &= not any(marker in body for marker in ['<script>alert("audit")</script>', 'Fatal error:', 'Stack trace:', 'OLSHCO_DB_PASSWORD'])
    passed &= not headers.get('Location', '').startswith(('https://evil.example', '//evil.example'))
    if path.startswith('index.php'):
        passed &= headers.get('X-Frame-Options') == 'SAMEORIGIN'
        passed &= "frame-ancestors 'self'" in headers.get('Content-Security-Policy', '')
    results.append({'probe': label, 'status': response.code, 'passed': bool(passed)})
    print(('PASS' if passed else 'FAIL') + ': ' + label + ' (' + str(response.code) + ')')

probe('Clickjacking/login', 'index.php?page=login', [200])
probe('SQL identifier injection', 'index.php?' + urlencode({'page':'content_open','content_id':"1 OR 1=1"}), [400,422])
probe('Reflected XSS', 'index.php?' + urlencode({'page':'login','error':'<script>alert("audit")</script>'}), [200])
probe('Page traversal', 'index.php?' + urlencode({'page':'../../config/database.local'}), [404])
probe('Unauthenticated IDOR', 'index.php?page=content_open', [401,403], {'content_type':'document','content_id':'1'})
probe('CSRF login missing token', 'index.php?page=login_action', [302,403,422], {'identifier':"' OR 1=1 -- ",'password':'audit-only'})
probe('CSRF login wrong token', 'index.php?page=login_action', [302,403,422], {'identifier':'audit-only','password':'audit-only','csrf_token':'wrong-audit-token'})
probe('External redirect parameter', 'index.php?page=login&redirect_url=https%3A%2F%2Fevil.example', [200,302])
for path in ['config/database.local.php','config/email.local.php','.git/config','.env','composer.lock','olshcodb-structure.sql','logs/browser-push-dispatch.log']:
    probe('Secret/source denial: ' + path, path, [403,404])
Path('logs/security-public-probes-20261006.json').write_text(json.dumps(results, indent=2))
if not all(result['passed'] for result in results):
    raise SystemExit(1)
