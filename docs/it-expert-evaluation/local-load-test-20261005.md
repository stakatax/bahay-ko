> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Controlled local load test ? 5 October 2026

Tested the main XAMPP application via loopback HTTP. The isolated tunnel server was stopped. Public GET routes: login, registration, academics, about, contact, and feed JavaScript. No authentication submissions, test accounts, votes, uploads, or new content were generated. Normal application GET behavior remained enabled.

| Concurrent requests | Requests | Failures | Median | P95 | Requests/second |
| --- | --- | --- | --- | --- | --- |
| 1 | 120 | 0 | 75.58 ms | 132.38 ms | 13.24 |
| 5 | 120 | 0 | 97.63 ms | 131.85 ms | 55.94 |
| 10 | 120 | 0 | 191.04 ms | 319.77 ms | 54.06 |
| 20 | 120 | 0 | 286.04 ms | 390.93 ms | 67.45 |
| 40 | 120 | 0 | 504.21 ms | 631.4 ms | 67.77 |

Additional batch: 1200 requests at 40 concurrency over 20.13 seconds; 0 failures. Median 641.56 ms, P95 832.74 ms, maximum 1650.68 ms, throughput 59.6 requests/second.

SELECT-only feed service workloads used sampled active Admin, Faculty, Student, and Parent accounts with 1, 4, and 8 simultaneous PHP workers. All 156 feed reads passed, with up to 4 MB PHP peak allocated memory per worker. At 8 workers, role P95 timings ranged from 10.61 to 56.34 ms. These are service timings, not authenticated page rendering or authorization regression tests.

No new Apache errors appeared in the inspected error-log tail. Feed ranking and card-link focused tests passed after load.

## Interpretation and limits

Response latency increased with concurrency, while mixed-route throughput approached 60?68 requests/second in this short run. This does not establish maximum capacity or identify a specific bottleneck. The dataset is small and reference caches become warm during testing. Forty simultaneous in-flight requests are not equivalent to forty logged-in users.

This is a controlled load test, not a destructive saturation test or production capacity guarantee. It does not cover sustained hours of traffic, public network/tunnel latency, authenticated HTTP session contention, write operations, uploads, notification delivery, large datasets, or other hosting hardware.

## Re-run

```powershell
C:/xampp/php/php.exe -l tests/stress_feed_worker.php
python tests/stress_readonly.py
```

The runner is pinned to the local main XAMPP URL. It escalates short stages up to 40 concurrent requests and stops short-stage escalation on errors or P95 above five seconds. The fixed 1,200-request batch and feed service stages follow. Review local resource availability before repeating. Raw metrics are stored under logs/stress-readonly-20261005.json.
