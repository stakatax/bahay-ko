> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Feed profiling and engagement batching ? 5 October 2026

Eight concurrent CLI workers, five reads per active sampled role per worker: 160 feed-service reads in each run. Read-only existing local data; no real-record writes, notification dispatch, schema changes, or deployment.

The profiler instruments mysqli query/prepare/execute calls. Database timings include client round trips and statement preparation/execution; residual time includes result fetching, PHP work, and instrumentation. These are not browser or full HTTP page timings.

| Role | Queries before ? after | Median before ? after | Reduction |
| --- | --- | --- | --- |
| Admin | 31 ? 19 | 41.94 ? 31.98 ms | 23.7% |
| Faculty | 35 ? 23 | 40.97 ? 26.96 ms | 34.2% |
| Parent | 6 ? 6 | 8.77 ? 7.96 ms | 9.2% |
| Student | 36 ? 24 | 47.59 ? 32.92 ms | 30.8% |

## Root cause and change

Engagement was batched per content type, but the four aggregate queries were repeated for announcements, events, documents, and surveys. The feed now passes all already-authorized type/ID sets to one batch loader, using four aggregate queries per 500 pairs. Grouping includes both content type and ID, so equal numeric IDs from different tables stay separate. Empty eligible sets make no engagement queries.

The single-type loader delegates to the same implementation, preserving existing callers. All personal vote/read/acknowledgment fields and count semantics, including moderated comment counts, are preserved. Recipient eligibility still runs before batching. No shared personalized cache was added.

## Verification

Before/after serialized feed fingerprints match exactly for sampled Admin, Faculty, Parent, and Student accounts. All 156 request-performance fixture checks and 95 HTTP access checks passed, plus feed-ranking checks. Mixed-type fixture tests cover identical IDs, invalid/empty sets, multiple actors, four-query batching, and chunk boundaries. Fixtures use temporary tables only.

## Limits

Results are short local service tests on the current small dataset and include profiler overhead. They do not establish maximum concurrent-user capacity, full authenticated HTTP throughput, performance on large data, public tunnel latency, or the specific cause of Apache throughput limits. Timing improvements can vary between runs; the query reduction is deterministic.

## Repeat

```powershell
python tests/profile_feed_load.py measurement
C:/xampp/php/php.exe tests/request_performance.php
C:/xampp/php/php.exe tests/content_access_http.php
```

Raw profiles are stored in logs/feed-profile-baseline.json and logs/feed-profile-optimized.json. Changes are local and have not been synced to the tunnel.

## Second pass: topic assignments

The eligible feed now fetches active topic assignments across all content types in one query per 500 type/ID pairs, rather than up to four separate queries. Single-type consumers share the implementation. Ordering, inactive-topic filtering, integer topic IDs, and name/slug trimming are preserved.

Eight-worker profiling confirmed three fewer queries for the sampled Admin (19 to 16), Faculty (23 to 20), and Student (24 to 21) feeds. Parent remained at six. Exact serialized feed fingerprints match the previous implementation. Timing varied across roles in this run; this pass establishes a query-count reduction, not a consistent additional latency improvement.

Verification: 37 interest fixture checks, 156 request-performance checks, 95 HTTP access checks, and ranking checks passed. Raw second-pass profile: logs/feed-profile-topics.json. No schema changes or tunnel sync.

## Third pass: audience display labels

Display labels now use one UNION ALL lookup across eligible types per 500 type/ID pairs, replacing up to four round trips. Authorization remains on its existing independent path; no recipient state is retained or cached. Label deduplication and the six label dimensions remain unchanged.

Query counts reached Admin 13, Faculty 17, Student 18, Parent 6 in the sampled feeds. Exact feed fingerprints still match. Role timing varied in this run, so no additional latency improvement is claimed. Mixed-label temporary-table tests cover type/ID separation, normalization, single/mixed parity, empty IDs, and chunking. Existing access, performance, and interest tests passed. Raw profile: logs/feed-profile-labels.json. Changes remain local.

## Fourth pass: live recipient target reads

The recipient filter batches all target tables into one live UNION ALL query per 500 type/ID pairs. It still resolves the current actor and verified parent profiles before reading targets. Admin access bypass and matching/specificity rules are unchanged. No authorization state is cached or retained between calls; rows contain the same fields as the original prepared lookup.

Sampled query totals: Admin 13 (unchanged), Faculty 14, Student 15, Parent 6 (unchanged; sampled parent has no eligible profile). Exact feed fingerprints match the previous pass. Raw profile: logs/feed-profile-recipients.json. Timing remains variable; no further latency gain is claimed.

Validation passed: 139 content-access fixture checks, 61 Parent/child registration fixture checks, 95 content HTTP checks, 30 Parent HTTP guards, 156 request-performance checks, plus mixed-target exact prepared-row parity, type separation, chunking and empty-set tests. The upload fixture test ran with permission to create/remove its temporary file. Existing application records were not changed. No schema changes or deployment.
