"""Run the existing dispatcher only for the evaluation DB while its tunnel is alive."""
from pathlib import Path
import ctypes
from ctypes import wintypes
import os
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[1]
EVALUATION = ROOT / 'deployment/tunnel/evaluation'
PRIVATE = EVALUATION / 'private'
if sys.argv[1:] != ['--run-approved-evaluation']:
    raise SystemExit('Explicit evaluation worker flag required.')
if not (PRIVATE / 'delivery-initialization.json').is_file():
    raise SystemExit('Evaluation delivery initialization required.')

kernel = ctypes.WinDLL('kernel32', use_last_error=True)
kernel.OpenProcess.argtypes = [wintypes.DWORD, wintypes.BOOL, wintypes.DWORD]
kernel.OpenProcess.restype = wintypes.HANDLE
kernel.GetExitCodeProcess.argtypes = [wintypes.HANDLE, ctypes.POINTER(wintypes.DWORD)]
kernel.QueryFullProcessImageNameW.argtypes = [wintypes.HANDLE, wintypes.DWORD, wintypes.LPWSTR, ctypes.POINTER(wintypes.DWORD)]
kernel.CloseHandle.argtypes = [wintypes.HANDLE]


def matches_process(pid_file, executable):
    try:
        process_id = int((PRIVATE / pid_file).read_text(encoding='utf-8-sig').strip())
    except (OSError, ValueError):
        return False
    handle = kernel.OpenProcess(0x1000, False, process_id)
    if not handle:
        return False
    try:
        code = wintypes.DWORD()
        size = wintypes.DWORD(32768)
        name = ctypes.create_unicode_buffer(size.value)
        return (kernel.GetExitCodeProcess(handle, ctypes.byref(code)) and code.value == 259
                and kernel.QueryFullProcessImageNameW(handle, 0, name, ctypes.byref(size))
                and Path(name.value).resolve() == executable.resolve())
    finally:
        kernel.CloseHandle(handle)


command = ['C:/xampp/php/php.exe', '-d', 'auto_prepend_file=' + (PRIVATE / 'bootstrap.php').as_posix(),
           '-d', 'sys_temp_dir=' + (PRIVATE / 'worker-temp').as_posix(),
           '-d', 'error_log=' + (PRIVATE / 'logs/worker-php-error.log').as_posix(),
           '-d', 'display_errors=0', (EVALUATION / 'public/scripts/dispatch_browser_push.php').as_posix()]
logfile = PRIVATE / 'logs/worker-supervisor.log'
environment = os.environ.copy()
# Set before PHP initializes OpenSSL; setting it inside PHP is too late on this XAMPP build.
environment['OPENSSL_CONF'] = 'C:/xampp/php/extras/openssl/openssl.cnf'
while (matches_process('evaluation-tunnel-process.txt', ROOT / 'deployment/tunnel/tools/cloudflared.exe')
       and matches_process('evaluation-apache-process.txt', Path('C:/xampp/apache/bin/httpd.exe'))):
    with logfile.open('a', encoding='utf-8') as output:
        try:
            result = subprocess.run(command, cwd=EVALUATION / 'public', stdout=output,
                                    stderr=output, timeout=120, env=environment,
                                    creationflags=subprocess.CREATE_NO_WINDOW)
            output.write(f'Dispatcher exit: {result.returncode}\n')
        except subprocess.TimeoutExpired:
            output.write('Dispatcher exceeded 120-second evaluation limit.\n')
    time.sleep(60)
