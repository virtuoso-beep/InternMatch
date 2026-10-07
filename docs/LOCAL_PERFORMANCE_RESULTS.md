# Local performance verification

Measured on 7 October 2026. These results verify the documented local conditions below; they do not establish production capacity, institutional dataset approval or human acceptance.

## Conditions

- Hardware: Acer Aspire AG14-71M, Intel Core Ultra 5 125H, 14 physical cores / 18 logical processors, approximately 16 GB physical RAM.
- OS: Windows 11 Home Single Language, build 26200; Docker services running through WSL. Other desktop applications remained open. Free host memory at the hardware snapshot was approximately 1.36 GiB.
- Target: the main frontend/API proxy at `http://localhost:8080`, Laravel/PHP and MySQL, with the existing pinned multilingual E5 service. Embeddings were already cached, as required by the matching design.
- Identities: the five existing original role accounts. No new accounts or password changes.
- Dataset before measurement: 31 users, 16 programs, 10 students/enrollments, 5 hosts, 6 opportunities, 6 placements, 20 time logs, 4 journals, 4 evaluations, 28 recommendation snapshots, 90 document metadata records and 3 coordinator judgments. Nine older document metadata records still have no physical upload.
- Retrieval workload: five simultaneous authenticated requests, one for each role, repeated for five rounds. Endpoints: enrollment records, coordinator recommendation review, assigned placements, dean program analytics and administrator account listing.
- Recommendation workload: three sequential requests, then five simultaneous requests for the original student's enrollment. Actual generation, eligibility checks, frozen snapshots, audit and notification persistence were included. This appended eight retained generations; it did not reset history.
- Timing: client elapsed time from request dispatch through JSON response parsing. Login/CSRF bootstrap occurred before measurement. No artificial delays or disabled authorization checks.

## Results

| Workload | Requests | Concurrency | Median | p95 | Maximum | Target |
|---|---:|---:|---:|---:|---:|---:|
| Record retrieval | 25 | 5 | 1.237 s | 2.188 s | 2.498 s | ≤5 s |
| Recommendation generation | 3 | 1 | 0.452 s | 0.771 s | 0.771 s | ≤8 s |
| Recommendation generation | 5 | 5 | 1.396 s | 2.274 s | 2.274 s | ≤8 s |

Every measured request succeeded. Both timing targets from NFR-01 in the supplied manuscript were met under these conditions. With this small sample, p95 is descriptive, not an estimate of sustained production reliability. The test does not measure model download, cold embedding generation, Internet latency or higher institutional concurrency.

Raw samples: `.local/oct07-performance.json`. Hardware: `.local/oct07-test-hardware.json`. Dataset snapshot: `.local/oct07-data-counts.json`. Main-database backup before measurements: `.local/oct07-before-performance.sql`. Test driver: `.local/oct07-performance.cjs`.

## Remaining NFR acceptance

| Requirement | Available evidence | Remaining evidence |
|---|---|---|
| Reliability and integrity | 202 backend tests, transactions, constraints, authorization and persisted browser workflows | Sustained approved pilot/recovery behavior |
| Availability | Services and measured requests succeeded during this local session | Scheduled institutional pilot availability record |
| Security | Session/CSRF/account-state checks, private-file and program isolation; TLS still absent locally | Production TLS and deployment security review |
| Usability | All 55 portal routes checked at 1365px and 390px; role redirects and labels tested | Actual approved five-point Likert responses and human UAT |
| Maintainability | Shared services, scoped controllers, typed components, successful build and regression | Team handover/acceptance |
| Compatibility | Bundled Chromium desktop/mobile viewport checks | Current stable Chrome, Edge and Firefox pilot matrix |
| Scalability | Current retained dataset and five-request concurrency | Approved institutional dataset size and concurrency |
| Backup and recovery | Local SQL backup and separate database restore comparison verified | Production storage, schedule, retention, recovery objectives and rehearsal |

Synthetic coordinator judgments remain testing data. They are not approved labels for training or validating Logistic Regression.
