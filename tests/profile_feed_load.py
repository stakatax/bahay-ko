from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import subprocess,json,statistics,sys
ROOT=Path(__file__).resolve().parents[1]
label=sys.argv[1] if len(sys.argv)>1 else 'baseline'
def run(_):
    result=subprocess.run(['C:/xampp/php/php.exe',str(ROOT/'tests/profile_feed_worker.php')],cwd=ROOT,capture_output=True,text=True,timeout=30)
    if result.returncode:raise RuntimeError(result.stderr)
    return json.loads(result.stdout)
with ThreadPoolExecutor(max_workers=8) as pool:rows=[row for worker in pool.map(run,range(8)) for row in worker]
report={'workers':8,'samples':len(rows),'roles':{},'queries':[]}
for role in sorted({row['role'] for row in rows}):
    subset=[row for row in rows if row['role']==role]
    report['roles'][role]={key:round(statistics.median(row[key] for row in subset),2) for key in ['total_ms','database_ms','other_ms','executions']}
queries={}
for row in rows:
    for timing in row['timings']:
        item=queries.setdefault(timing['sql'],{'sql':timing['sql'],'calls':0,'total_ms':0})
        item['total_ms']+=timing['ms'];item['calls']+=timing['phase']!='prepare'
report['queries']=sorted(queries.values(),key=lambda item:item['total_ms'],reverse=True)
report['fingerprints']={role:sorted({row['fingerprint'] for row in rows if row['role']==role}) for role in report['roles']}
(ROOT/'logs'/('feed-profile-'+label+'.json')).write_text(json.dumps(report,indent=2))
print(json.dumps({'workers':8,'samples':len(rows),'roles':report['roles']},indent=2))
for query in report['queries'][:8]:print(round(query['total_ms'],2),'ms;',query['calls'],'calls;',query['sql'][:190])
