> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# OPcache runtime preparation ? 5 October 2026

The Apache web runtime was verified to have OPcache unloaded. The DLL exists. An isolated candidate configuration successfully loaded Zend OPcache through PHP CLI.

With explicit user approval, C:/xampp/php/php.ini was updated. The prior file and validated candidate are preserved in the private directory referenced by deployment/tunnel/evaluation/private/pending-opcache.txt. No database or application source data was changed.

Settings: OPcache enabled, CLI caching disabled, 128 MB cache, 8 MB interned strings, 10,000 accelerated files, timestamp validation enabled, revalidation frequency zero, JIT buffer disabled. File-change checks remain enabled for development. See https://www.php.net/manual/en/opcache.configuration.php.

## Activation verified

Initial automated Apache restart attempts were denied by Windows permissions. The user restarted Apache through XAMPP. A temporary loopback-only web probe then confirmed apache2handler, OPcache loaded/enabled, timestamp validation 1, and revalidation frequency 0. The probe was removed immediately.

A comparable warmed 600-request mixed-page test at concurrency 20 completed with zero failures before and after activation.

| Metric | Before | OPcache active |
| --- | --- | --- |
| Median | 326.51 ms | 49.32 ms |
| P95 | 481.32 ms | 136.13 ms |
| Throughput | 61.14 requests/second | 292.32 requests/second |
| Maximum | 765.29 ms | 1392.09 ms |

The maximum after activation was a slower outlier despite the improved median and P95. These are short local mixed public-page measurements on the same small dataset; host contention and warm-up can affect results. They do not establish authenticated-user capacity, large-data scalability, or public-host performance. The earlier unverified measurement was superseded. Raw results: logs/opcache-http-before.json, logs/opcache-http-after.json, and logs/opcache-web-verification.json.

## Rollback

If Apache fails to start, restore php.ini.before from the saved private backup to C:/xampp/php/php.ini, then restart Apache. The private copy includes the full original configuration; never publish it.

OPcache is a server configuration change. It is not activated by copying application files to the tunnel or a different host. The tunnel Apache is currently stopped and will use its applicable PHP configuration when launched.
