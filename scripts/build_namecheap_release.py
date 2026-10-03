"""Build a secret-free Namecheap upload directory from the current workspace.

No ZIP, Git operations, database connection, deployment or existing-data copy.
Refuses to overwrite an existing output. Dependencies must already be installed.
"""
from pathlib import Path
import hashlib
import json
import shutil
import sys

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / 'deployment' / 'namecheap' / 'release'
FOLDERS = ['app', 'config', 'include', 'pages', 'Assets/css', 'Assets/js', 'Assets/Images', 'vendor']
FILES = ['index.php', 'push-service-worker.js', 'composer.json', 'composer.lock',
         'scripts/dispatch_browser_push.php', 'scripts/check_deployment_configuration.php',
         'Assets/uploads/.htaccess', 'Assets/uploads/documents/.htaccess',
         'Assets/uploads/profile-photos/.htaccess']


def included(path):
    name = path.name.lower()
    return not (name.endswith(('.local.php', '.example', '.md', '.log', '.sql', '.bak', '.backup'))
                or name.startswith('.env') or '__pycache__' in path.parts)


def build():
    if OUTPUT.exists(): raise RuntimeError('Release directory already exists; preserve/review it before preparing a new one.')
    if not (ROOT/'vendor/autoload.php').is_file(): raise RuntimeError('Install locked Composer dependencies first.')
    sources=[]
    for folder in FOLDERS:
        for path in sorted((ROOT/folder).rglob('*')):
            if path.is_symlink(): raise RuntimeError('Review symbolic link before packaging: '+str(path.relative_to(ROOT)))
            if path.is_file() and included(path): sources.append(path)
    for relative in FILES:
        path=ROOT/relative
        if not path.is_file() or path.is_symlink(): raise RuntimeError('Required regular file missing: '+relative)
        sources.append(path)
    # Validate everything before writing an isolated output directory.
    if not OUTPUT.resolve().is_relative_to(ROOT): raise RuntimeError('Output outside workspace.')
    public=OUTPUT/'public'; private=OUTPUT/'private'; manifest=[]
    for source in sources:
        relative=source.relative_to(ROOT); target=public/relative
        target.parent.mkdir(parents=True,exist_ok=True); shutil.copyfile(source,target)
        manifest.append({'path':relative.as_posix(),'sha256':hashlib.sha256(target.read_bytes()).hexdigest()})
    for folder in ['logs','Assets/uploads/documents','Assets/uploads/profile-photos','Assets/uploads/announcement-audio']:
        (public/folder).mkdir(parents=True,exist_ok=True)
    private.mkdir(parents=True); (private/'sessions').mkdir()
    templates=ROOT/'deployment/namecheap'
    for source in (templates/'private').glob('*.example'):
        shutil.copyfile(source,private/source.name.removesuffix('.example'))
    for name in ['email','push']:
        shutil.copyfile(ROOT/f'config/{name}.local.php.example',private/(name+'.php'))
    shutil.copyfile(templates/'public/.htaccess.example',public/'.htaccess')
    for name in ['.user.ini','email.local.php','push.local.php']:
        destination=public/('config/'+name if name.endswith('.php') else name)
        shutil.copyfile(templates/'public'/(name+'.example'),destination)
    (OUTPUT/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
    (OUTPUT/'NOT_READY_TO_UPLOAD.txt').write_text(
        'Preparation only. Replace private settings/account paths; verify dependencies and access rules.\n'
        'Upload public to the chosen app document root; private MUST go outside every document root.\n'
        'No database rows or existing uploads were copied. Review Assets/Images for intended public images.\n'
        'Follow docs/namecheap-deployment.md. Do not upload this release parent directory wholesale.\n',encoding='utf-8')
    print(f'Prepared {len(manifest)} runtime files in deployment/namecheap/release/public.')
    print('Secrets, database rows and existing uploads excluded. Host configuration remains required.')


if __name__=='__main__':
    try: build()
    except Exception as error:
        print('Release preparation stopped: '+str(error),file=sys.stderr); sys.exit(1)
