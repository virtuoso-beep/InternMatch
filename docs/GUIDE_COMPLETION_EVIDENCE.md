# Development guide completion evidence

Checkpoint: **7 October 2026**. The updated Word guide contains **149 task rows: 131 Fully Done and 18 Blocked**. This continuation completed **42 previously unfinished rows**. Completion applies to the recorded local development/test conditions, not a production release.

The workspace guide is `C:/InternMatch/InternMatch_Team_Development_Guide.docx`. The source guide is in the user's `Videos/ubuntu/Outline defense/internmatch` folder. Pre-update copies are retained in `.local/oct07-guide/`.

## Verified work

| Guide area | Implementation and verification |
|---|---|
| Authentication and role access | All five original accounts sign in. Twenty cross-role portal visits redirect to the correct portal. Restricted APIs deny unauthorized roles. Session, CSRF, account-state, ownership and program policies passed regression. |
| Laravel APIs | Student/enrollment, host, opportunity, competency and placement persistence, validation, scope and capacity checks passed. |
| Student portal | Original student has linked synthetic data, checklist PDFs and attendance, real E5 recommendations and explanations, an active placement, 40 certified hours and 446 remaining out of 486. Competency updates survived reload; original level restored. |
| Host portal | Assigned host edit, persisted opportunity creation, required competency relationship and intern access verified. Capacity violations are rejected. |
| Coordinator portal | Removed reachable static student/recommendation lists and simulated approval actions. Student list now reads scoped enrollments. Judgment forms use the existing service, append history, preserve frozen facts and create no placement. Browser persistence passed. |
| Dean portal | Replaced fixed performance, host, equity and analytics values with scoped database aggregates. Individual student identities are excluded. Missing coordinates/ratings stay unknown. Accreditation uses approved report snapshots; audit uses authorized stored records. |
| Native Filament | Cohort proposal generation/review, final placement decisions, statistics, reports, approval and CSV export passed service/native/browser checks. Administrator access does not grant academic approval authority. |
| Notifications and audit | Submitted-evaluation notification tests passed; drafts send none. Scoped audit reads exclude other programs and private change payloads. Mutation history is retained. |
| Integration and E2E | Listed React/Laravel/MySQL/E5/Leaflet/placement/report workflows passed. Full navigation sweep: 55 routes at widths 1365 and 390, with no page errors, failed API requests or page-wide overflow. |
| Local performance | Defined hardware, dataset and concurrency; measured retrieval and real recommendation generation meet five/eight-second targets. See `LOCAL_PERFORMANCE_RESULTS.md`. |
| Docker and Nginx | Existing stack restarted. Frontend 8080/backend 8000 remain main endpoints. Optional Nginx gateway 8081 passed five original logins, role redirects and API authorization checks. |

## Final checks

- Backend: **202 tests / 1,566 assertions passed**, isolated `internmatch_docker_testing` database.
- Frontend: TypeScript and production build passed.
- New analytics and recommendation review: five targeted tests / 90 assertions passed before final regression.
- Local retrieval: 25 requests at concurrency five; maximum **2.498 seconds**.
- Actual recommendation generation: three sequential plus five concurrent requests; maximum **2.274 seconds**.
- Original accounts/passwords remain in place. The current seeder creates no accounts. All 31 existing user records and prior QA accounts/history are retained.
- Main database and retained QA databases were not reset. The only destructive test resets occurred in the guarded disposable database.

## Exact remaining task rows

| Phase | Tasks still blocked | Required evidence or access |
|---|---|---|
| 9 | Collect approved judgments; clean dataset; split training/evaluation data; train Logistic Regression; evaluate model; integrate validated model | Genuine coordinator-approved training/validation labels with provenance and a suitable held-out evaluation set. Synthetic test judgments cannot satisfy this requirement. The working cosine/placement-criteria fallback remains in use. |
| 17 | Verify remaining NFRs | Approved institutional scale, scheduled pilot availability/reliability evidence, current stable Chrome/Edge/Firefox matrix and actual approved Likert/UAT responses. Local measurements do not establish these. |
| 19 | Select production server; configure domain/DNS; configure HTTPS/TLS; deploy containers; configure production MySQL; configure FastAPI; configure frontend; configure Laravel; backup system; restore rehearsal; final UAT | A selected production target with deployment/DNS access, production configuration and backup/recovery requirements, followed by actual human acceptance results. Local backup/restore evidence does not count as production recovery. |

Department-approved evaluation rubrics, monitoring thresholds, other programs' required hours and the PDOS schedule also remain unprovided. Configuration workflows are implemented, but test values are not institutional approvals. Nine historical document metadata records still lack files; they remain preserved and explicitly unverified. New synthetic submissions have actual labeled PDFs.

## Evidence locations

| Evidence | Retained file |
|---|---|
| Final backend output | `.local/oct07-guide-full-suite.txt` |
| TypeScript/build | `.local/oct07-guide-build.txt` |
| Scoped analytics/review tests | `.local/oct06-live-portals-tests.txt` |
| Main role/forbidden-page checks | `.local/oct06-guide-browser.json` |
| Persisted student/host/coordinator checks | `.local/oct06-workflows-browser.json` |
| Final dean/mobile checks after layout repair | `.local/oct07-remaining-browser.json` |
| Complete route/viewport sweep | `.local/oct07-navigation-browser.json` |
| Authenticated Nginx routing | `.local/oct07-nginx-browser.json` |
| Performance samples and conditions | `.local/oct07-performance.json` |
| Native placement/report workflows | `.local/oct03-browser-results.json` |
| Local restore comparison | `.local/oct04-restore-verification.txt` |
| Fresh main database backup | `.local/oct07-before-performance.sql` |
| Guide task status inventory | `.local/oct07-guide/status-counts.json` |

Earlier browser runs include a hidden-option locator failure and an analytics mobile overflow failure. The locator was corrected, the layout repaired, and the remaining-page checks and complete navigation sweep passed. The failed intermediate outputs are retained rather than represented as successful runs.
