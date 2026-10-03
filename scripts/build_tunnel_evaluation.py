"""Prepare an isolated, unconfigured evaluation copy; never touch MySQL or XAMPP config."""
from pathlib import Path
import hashlib
import json
import runpy
import shutil

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / 'deployment/tunnel/evaluation'


def build():
    if OUTPUT.exists():
        raise RuntimeError('Evaluation directory already exists; refusing to overwrite it.')
    if not (ROOT / 'vendor/autoload.php').is_file():
        raise RuntimeError('Locked Composer dependencies must already be installed.')
    packaging = runpy.run_path(str(ROOT / 'scripts/build_namecheap_release.py'))
    sources = []
    for folder in packaging['FOLDERS']:
        for source in sorted((ROOT / folder).rglob('*')):
            if source.is_symlink():
                raise RuntimeError('Review symbolic link: ' + str(source.relative_to(ROOT)))
            if source.is_file() and packaging['included'](source):
                sources.append(source)
    for relative in packaging['FILES']:
        source = ROOT / relative
        if not source.is_file() or source.is_symlink():
            raise RuntimeError('Required regular file missing: ' + relative)
        sources.append(source)
    if not OUTPUT.resolve().is_relative_to(ROOT):
        raise RuntimeError('Output must remain inside the workspace.')
    public = OUTPUT / 'public'
    private = OUTPUT / 'private'
    manifest = []
    for source in sources:
        target = public / source.relative_to(ROOT)
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(source, target)
        manifest.append({'path': source.relative_to(ROOT).as_posix(),
                         'sha256': hashlib.sha256(target.read_bytes()).hexdigest()})
    for folder in ['sessions', 'logs']:
        (private / folder).mkdir(parents=True)
    (public / 'logs').mkdir()
    (public / 'Assets/uploads/announcement-audio').mkdir(parents=True)
    shutil.copyfile(ROOT / 'deployment/namecheap/public/.htaccess.example', public / '.htaccess')
    # The inherited upload allowlist already protects photos. Avoid copying the
    # current malformed child FilesMatch directive into this evaluation copy.
    (public / 'Assets/uploads/profile-photos/.htaccess').write_text(
        'Options -Indexes -ExecCGI\n', encoding='utf-8')
    original = (ROOT / 'deployment/namecheap/private/bootstrap.php.example').read_text(encoding='utf-8')
    original = original.replace("    foreach ($settings as $key => $value) {",
        "    if (($settings['OLSHCO_DB_NAME'] ?? '') !== 'olshco_evaluation') {\n"
        "        throw new RuntimeException('Dedicated evaluation database required.');\n"
        "    }\n    foreach ($settings as $key => $value) {")
    (private / 'bootstrap.php').write_text(original, encoding='utf-8')
    environment = (ROOT / 'deployment/namecheap/private/environment.php.example').read_text(encoding='utf-8')
    environment = environment.replace("'OLSHCO_DB_NAME' => ''", "'OLSHCO_DB_NAME' => 'olshco_evaluation'")
    (private / 'environment.php').write_text(environment, encoding='utf-8')
    for name in ['email', 'push']:
        shutil.copyfile(ROOT / f'config/{name}.local.php.example', private / f'{name}.php')
        (public / f'config/{name}.local.php').write_text(
            "<?php\nreturn require " + repr((private / f'{name}.php').as_posix()) + ";\n",
            encoding='utf-8')
    modules = ['authz_core', 'authz_host', 'dir', 'env', 'headers', 'log_config', 'mime', 'rewrite']
    config = ['ServerRoot "C:/xampp/apache"', 'Listen 127.0.0.1:8080',
              'ServerName localhost:8080',
              *[f'LoadModule {name}_module modules/mod_{name}.so' for name in modules],
              'LoadFile "C:/xampp/php/php8ts.dll"',
              'LoadFile "C:/xampp/php/libpq.dll"',
              'LoadFile "C:/xampp/php/libsqlite3.dll"',
              'LoadModule php_module "C:/xampp/php/php8apache2_4.dll"',
              'PHPINIDir "C:/xampp/php"', 'TypesConfig conf/mime.types',
              f'PidFile "{(private / "apache.pid").as_posix()}"',
              f'ErrorLog "{(private / "logs/apache-error.log").as_posix()}"',
              'LogLevel warn',
              f'DocumentRoot "{public.as_posix()}"',
              '<Directory />', '    AllowOverride None', '    Require all denied', '</Directory>',
              f'<Directory "{public.as_posix()}">',
              '    Options -Indexes -ExecCGI -Includes', '    AllowOverride All',
              '    Require ip 127.0.0.1', '    DirectoryIndex index.php', '</Directory>',
              '<FilesMatch "\\.php$">', '    SetHandler application/x-httpd-php', '</FilesMatch>',
              # This listener is reserved for the TLS tunnel. Never trust a client header.
              'SetEnv HTTPS on',
              'php_admin_flag display_errors Off', 'php_admin_flag log_errors On',
              f'php_admin_value error_log "{(private / "logs/php-error.log").as_posix()}"',
              f'php_admin_value session.save_path "{(private / "sessions").as_posix()}"',
              f'php_admin_value auto_prepend_file "{(private / "bootstrap.php").as_posix()}"',
              '<LocationMatch "(?i)^/(?:phpmyadmin|xampp|dashboard|licenses|webalizer|php-cgi)(?:/|$)">',
              '    Require all denied', '</LocationMatch>']
    (OUTPUT / 'apache.conf').write_text('\n'.join(config) + '\n', encoding='utf-8')
    (OUTPUT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
    print(f'Prepared {len(manifest)} runtime files. Database, uploads and secrets were NOT copied.')
    print('No Apache service was changed or started. Evaluation configuration is incomplete.')


if __name__ == '__main__':
    build()
