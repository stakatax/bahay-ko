> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Reference caching

File caches live in the operating system temporary directory, outside the web document root. Keys include the application path, database host, port, and database name, isolating local and evaluation copies. No credentials, personal responses, recipient eligibility, or account data are cached.

| Reference list | TTL | Refresh |
| --- | --- | --- |
| Public academic catalog | 5 minutes | Academic write/commit or expiry |
| Registration academic choices | 5 minutes | Academic write/commit or expiry |
| Publishing topic choices | 1 minute | Expiry |
| Student profile interest choices | 1 minute | Expiry |
| Calendar holidays by year | 5 minutes | Expiry |

Submission validation and role/access checks remain live. Injected model connections bypass shared caches for fixture isolation. Academic transactions bypass catalog caching, invalidate after commit, and preserve committed caches on rollback. File locks serialize cache loads; unreadable cache files fall back to database reads.

After manual reference imports or migrations, clear the appropriate application's caches:

```powershell
C:/xampp/php/php.exe scripts/clear_reference_cache.php --clear
```

For evaluation, load its private bootstrap before invoking the command so its database namespace is selected. Never copy private settings into public directories. Cache files are disposable; the next request reloads the reference lists.
