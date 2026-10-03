"""Download portable Cloudflare release into the ignored, HTTP-blocked tools directory."""
from pathlib import Path
import hashlib
import json
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
TOOLS = ROOT / 'deployment/tunnel/tools'
REQUEST_HEADERS = {'User-Agent': 'OLSHCO-evaluation-preparation', 'Accept': 'application/vnd.github+json'}
request = urllib.request.Request('https://api.github.com/repos/cloudflare/cloudflared/releases/latest', headers=REQUEST_HEADERS)
with urllib.request.urlopen(request, timeout=30) as response:
    release = json.load(response)
asset = next(item for item in release['assets'] if item['name'] == 'cloudflared-windows-amd64.exe')
digest = asset.get('digest', '')
if not digest.startswith('sha256:'):
    raise RuntimeError('Official asset checksum unavailable; review download before use.')
url = asset['browser_download_url']
if not url.startswith('https://github.com/cloudflare/cloudflared/releases/download/'):
    raise RuntimeError('Unexpected release download location.')
TOOLS.mkdir(parents=True, exist_ok=True)
target = TOOLS / 'cloudflared.exe'
if target.exists():
    if hashlib.sha256(target.read_bytes()).hexdigest() != digest[7:]:
        raise RuntimeError('Existing binary differs; refusing overwrite.')
else:
    with urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent': REQUEST_HEADERS['User-Agent']}), timeout=30) as response:
        content = response.read()
    if hashlib.sha256(content).hexdigest() != digest[7:]:
        raise RuntimeError('Release checksum mismatch.')
    target.write_bytes(content)
(TOOLS / 'release.json').write_text(json.dumps({'version': release['tag_name'], 'url': url,
    'sha256': digest[7:]}, indent=2) + '\n', encoding='utf-8')
print('PASS: official portable cloudflared ' + release['tag_name'] + ' downloaded; SHA-256 verified.')
