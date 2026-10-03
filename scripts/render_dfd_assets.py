"""Render local SVG documentation as PNG using isolated headless Chrome.

Requires Python websocket-client and Chrome; no database or network delivery.
"""
from pathlib import Path
import base64
import json
import subprocess
import tempfile
import time
import urllib.request
import xml.etree.ElementTree as ET
import websocket

ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / 'docs' / 'dfd-rebuilt' / 'assets'
CHROME = Path('C:/Program Files/Google/Chrome/Application/chrome.exe')


def render():
    with tempfile.TemporaryDirectory(prefix='olshco-dfd-render-') as profile:
        process = subprocess.Popen([str(CHROME),'--headless','--disable-gpu','--disable-background-networking','--no-first-run','--no-default-browser-check','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
        connection = None
        try:
            active = Path(profile) / 'DevToolsActivePort'
            port = None
            for _ in range(100):
                try:
                    lines = active.read_text(encoding='utf-8').splitlines()
                    if lines and lines[0].isdigit():
                        port = lines[0]
                        break
                except (FileNotFoundError, PermissionError):
                    pass
                time.sleep(.1)
            if port is None: raise RuntimeError('Chrome debugging port did not become available.')
            targets = json.load(urllib.request.urlopen('http://127.0.0.1:'+port+'/json',timeout=10))
            page = next(t for t in targets if t['type']=='page')
            connection = websocket.create_connection(page['webSocketDebuggerUrl'],timeout=20,suppress_origin=True)
            seq = 0
            def call(method,params=None):
                nonlocal seq
                seq+=1; connection.send(json.dumps({'id':seq,'method':method,'params':params or {}}))
                while True:
                    reply=json.loads(connection.recv())
                    if reply.get('id')==seq:
                        if 'error' in reply: raise RuntimeError(reply['error'])
                        return reply.get('result',{})
            call('Page.enable')
            for i,path in enumerate(sorted(ASSETS.glob('*.svg')),1):
                root=ET.parse(path).getroot(); width=int(root.attrib['width']); height=int(root.attrib['height'])
                call('Emulation.setDeviceMetricsOverride',{'width':width,'height':height,'deviceScaleFactor':1.25,'mobile':False})
                call('Page.navigate',{'url':path.resolve().as_uri()})
                for _ in range(50):
                    result=call('Runtime.evaluate',{'expression':'document.readyState','returnByValue':True})
                    if result.get('result',{}).get('value')=='complete': break
                    time.sleep(.02)
                shot=call('Page.captureScreenshot',{'format':'png','captureBeyondViewport':False})
                path.with_suffix('.png').write_bytes(base64.b64decode(shot['data']))
                print('Rendered',i,path.stem,flush=True)
        finally:
            if connection is not None:
                try:
                    call('Browser.close')
                except Exception:
                    pass
            if connection is not None: connection.close()
            if process.poll() is None: process.terminate()
            try: process.wait(timeout=10)
            except subprocess.TimeoutExpired:
                process.kill(); process.wait(timeout=10)


if __name__=='__main__': render()
