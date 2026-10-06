"""Controlled local-only page/SELECT feed load; writes a metrics report, no test accounts."""
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import urllib.request, urllib.error, time, statistics, json, subprocess, math
ROOT=Path(__file__).resolve().parents[1]
BASE='http://127.0.0.1/bahay-ko/'
ROUTES=['index.php?page=login','index.php?page=register','index.php?page=academic','index.php?page=about','index.php?page=contact','Assets/js/news.js']
def summarize(samples):
    ordered=sorted(samples)
    return {'median_ms':round(statistics.median(ordered),2),'p95_ms':round(ordered[max(0,math.ceil(len(ordered)*.95)-1)],2),'max_ms':round(max(ordered),2)}
def request(number):
    path=ROUTES[number%len(ROUTES)];start=time.perf_counter()
    try:
        with urllib.request.urlopen(BASE+path,timeout=15) as response:
            body=response.read();status=response.status
        ok=status==200 and len(body)>100 and b'Fatal error' not in body and b'Database service is unavailable' not in body
        return {'path':path,'ms':(time.perf_counter()-start)*1000,'ok':ok,'status':status}
    except Exception as error:
        return {'path':path,'ms':(time.perf_counter()-start)*1000,'ok':False,'error':type(error).__name__}
def feed(_):
    start=time.perf_counter()
    result=subprocess.run(['C:/xampp/php/php.exe',str(ROOT/'tests/stress_feed_worker.php'),'--read-only'],cwd=ROOT,capture_output=True,text=True,timeout=30)
    if result.returncode: return {'ok':False,'error':'worker failed','ms':(time.perf_counter()-start)*1000}
    try:return {'ok':True,**json.loads(result.stdout)}
    except ValueError:return {'ok':False,'error':'invalid worker response'}
report={'scope':'Main local XAMPP: public GET pages and role feed service SELECT reads','http':[],'feed':[]}
for concurrency in [1,5,10,20,40]:
    start=time.perf_counter()
    with ThreadPoolExecutor(max_workers=concurrency) as pool: samples=list(pool.map(request,range(120)))
    elapsed=time.perf_counter()-start
    summary={'concurrency':concurrency,'requests':len(samples),'failures':sum(not row['ok'] for row in samples),'requests_per_second':round(len(samples)/elapsed,2),**summarize([row['ms'] for row in samples]),'routes':{}}
    for route in ROUTES:
        subset=[row for row in samples if row['path']==route]
        summary['routes'][route]={'failures':sum(not row['ok'] for row in subset),**summarize([row['ms'] for row in subset])}
    report['http'].append(summary);print(json.dumps(summary),flush=True)
    if summary['failures'] or summary['p95_ms']>5000: print('Stopped escalation at safety threshold.',flush=True);break
start=time.perf_counter()
with ThreadPoolExecutor(max_workers=40) as pool: sustained=list(pool.map(request,range(1200)))
elapsed=time.perf_counter()-start
report['sustained']={'concurrency':40,'requests':1200,'elapsed_seconds':round(elapsed,2),'failures':sum(not row['ok'] for row in sustained),'requests_per_second':round(1200/elapsed,2),**summarize([row['ms'] for row in sustained])}
print(json.dumps(report['sustained']),flush=True)
for concurrency in [1,4,8]:
    with ThreadPoolExecutor(max_workers=concurrency) as pool: workers=list(pool.map(feed,range(concurrency)))
    samples=[row for worker in workers if worker['ok'] for row in worker['samples']]
    summary={'workers':concurrency,'failures':sum(not worker['ok'] for worker in workers),'feed_reads':len(samples),'roles':{}}
    for role in sorted({row['role'] for row in samples}):summary['roles'][role]=summarize([row['ms'] for row in samples if row['role']==role])
    summary['peak_worker_memory_mb']=max([worker.get('peak_memory_mb',0) for worker in workers]);report['feed'].append(summary);print(json.dumps(summary),flush=True)
    if summary['failures']:break
output=ROOT/'logs/stress-readonly-20261005.json';output.write_text(json.dumps(report,indent=2),encoding='utf-8');print('Report: '+str(output),flush=True)
