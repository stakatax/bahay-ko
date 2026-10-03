"""Copy existing media/documents without overwriting evaluation uploads or access rules."""
from pathlib import Path
import hashlib
import json
import shutil

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / 'Assets/uploads'
TARGET = ROOT / 'deployment/tunnel/evaluation/public/Assets/uploads'
PRIVATE = ROOT / 'deployment/tunnel/evaluation/private'
ALLOWED = {'.jpg', '.jpeg', '.png', '.webp', '.mp3', '.m4a', '.wav', '.webm',
           '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.txt'}

if not (PRIVATE / 'database-verification.json').is_file():
    raise RuntimeError('Verified evaluation database required.')
files = []
for path in sorted(SOURCE.rglob('*')):
    if path.is_symlink():
        raise RuntimeError('Review symbolic link before copying uploads.')
    if not path.is_file() or path.name == '.htaccess':
        continue
    if path.suffix.lower() not in ALLOWED or path.name.startswith('.'):
        raise RuntimeError('Unexpected upload type; review before copying.')
    target = TARGET / path.relative_to(SOURCE)
    digest = hashlib.sha256(path.read_bytes()).hexdigest()
    if target.exists() and hashlib.sha256(target.read_bytes()).hexdigest() != digest:
        raise RuntimeError('Evaluation upload differs; refusing overwrite.')
    files.append((path, target, digest))
for source, target, digest in files:
    target.parent.mkdir(parents=True, exist_ok=True)
    if not target.exists():
        shutil.copyfile(source, target)
    if hashlib.sha256(target.read_bytes()).hexdigest() != digest:
        raise RuntimeError('Upload copy verification failed.')
report = [{'path': source.relative_to(SOURCE).as_posix(), 'bytes': source.stat().st_size,
           'sha256': digest} for source, target, digest in files]
(PRIVATE / 'upload-verification.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
print(f'PASS: {len(files)} uploaded files copied and verified; originals/access rules unchanged.')
