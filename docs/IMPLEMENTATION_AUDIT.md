# InternMatch implementation audit

Audit checkpoint: 2026-09-23. This is an evidence ledger, not a release certification.

## Authorities and confirmed input

- Architecture and acceptance: `system manu.docx`; the user confirmed that it contains both the revised system specification and the renamed FR/NFR requirements. The separately supplied `InternMatch_System_Spec_Revised.docx` is a supporting architecture reference.
- Build sequence: `InternMatch_Team_Development_Guide.docx`.
- Reference shapes: `InternMatch_Data_Needed_Revised.md`.
- The user supplied 420 hours **unconfirmed**. Live program requirements remain null. Test-only hour values do not constitute department approval.
- The user confirmed no approved evaluation rubrics or monitoring thresholds are available. No defaults are seeded. Configuration requires an approval reference; the application cannot independently authenticate a department's approval.
- Main development database: Docker MySQL, `internmatchlaravel`. Automated tests use a separate local MySQL database ending in `_testing`, guarded in `Tests/TestCase.php`.

## Phase checkpoints

| Phase | Status | Finding / remaining verification |
|---|---|---|
| 0 Preparation | 🔵 In Progress | Laravel, React and MySQL run locally. Docker PHP `intl` was missing and repaired. Filament 5.8.2 is installed; FastAPI remains absent. |
| 1 Database | 🔵 In Progress | Corrected program hours, coordinator scope, per-program capacities, explicit institutional MOA coverage, embedding provenance, recommendation snapshots and audit storage. All 16 programs and starter competencies are seeded without approving unknown hours. Schema and integration tests exist; final suite pending. |
| 2 Authentication | 🔵 In Progress | Existing session, CSRF, active-account and role checks retained. Program-scoped access replaces legacy term assignments. Session-preserving Docker startup repaired. Browser admin sign-in and notification navigation checked. Final multi-role/browser integration remains. |
| 3 Backend/API | 🔵 In Progress | Persisted registry, host, opportunity, competency, document review, monitoring, evaluation, notification and audit APIs implemented. MOA management now has scoped APIs and private document storage; proposal-to-placement decisions and embedding integration remain. |
| 4 Student portal | 🔵 In Progress | Profile, competency, enrollment, eligible opportunities, stored recommendations, documents and notifications connected. Monitoring/evaluation screens connected; full browser workflow verification pending. Recommendation generation and map remain. |
| 5 Host portal | 🔵 In Progress | Host profile, per-program capacity and opportunities connected. Assigned-intern monitoring and evaluations connected. MOA management and persisted dashboard are connected; browser acceptance remains. |
| 6 Filament | 🔵 In Progress | Filament 5.8.2 panel and scoped program registry added. Active admin/coordinator access and audited program edits pass 3 tests / 23 assertions. Native requirement review now shares the API service; 6 targeted review tests / 88 assertions pass. Native student, host, opportunity, recommendation, allocation, decision and reporting workflows remain. |
| 7 Semantic matching | ⬜ Not Started | No E5/FastAPI service, actual embeddings or model provenance generation. Schema alone is not implementation. |
| 8 Cosine recommendations | ⬜ Not Started | Stored snapshot retrieval exists. No actual semantic generation or working fallback pipeline yet. |
| 9 Judgment / ML | 🔴 Blocked | Approved coordinator labels and held-out evaluation evidence are absent. Do not claim trained logistic regression. Judgment collection UI/API and genuine cosine fallback still require implementation. |
| 10 Cohort allocation | ⬜ Not Started | Proposal schema only. Depends on genuine recommendations; no final placement decision API yet. |
| 11 Geospatial | 🔵 In Progress | Haversine computes straight-line kilometers and has boundary tests. Browsing shows real distance when coordinates exist. Leaflet map remains disconnected. |
| 12 Monitoring/evaluation | 🔵 In Progress | Certified time, weekly journals, rubric versions and weighted scoring implemented. Current program hours drive progress. Rules evaluate explicit thresholds and overdue requirements. Browser tests and scheduled flag persistence remain. |
| 13 Notifications | 🔵 In Progress | Database notifications replace shared-shell and notification-page examples. Requirement/journal reviews and submitted evaluations trigger events. Deadline, placement, risk and evaluation-due events remain. |
| 14 Reports/audit | 🟡 Needs Review | Transactional audit covers implemented mutations. Several dashboards and report/export screens still use prototype data; reports are not accepted implementations. |
| 15 Integration testing | 🔵 In Progress | Laravel–React–MySQL integration is partial. AI, allocation, Filament, reports and backup are not integrated. |
| 16 End-to-end testing | 🔵 In Progress | Automated security and workflow tests added. Full role-based browser workflows remain. |
| 17 Performance/NFR testing | 🔴 Blocked | Approved pilot hardware, dataset/concurrency, usability and operational evidence remain unavailable. |
| 18 Dockerization | 🔵 In Progress | Development frontend, Laravel and MySQL containers run. FastAPI and production Nginx remain absent. |
| 19 Production deployment | 🔴 Blocked | Production server/domain, approved settings, pilot/UAT and release acceptance evidence are unavailable. Features remain incomplete. |

## Independent acceptance checks

| Requirement | Status | Evidence / gap |
|---|---|---|
| FR-01 Authentication/RBAC | 🔵 In Progress | Session/CSRF/access tests and program-scoped authorization; final multi-role browser check pending. |
| FR-02 Student profile | 🔵 In Progress | Persisted profile, coordinates, competencies and academic enrollment. Full free-text/evidence workflow and browser acceptance pending. |
| FR-03 Host management | 🔵 In Progress | Scoped CRUD and per-program host capacities tested; live browser workflow pending. |
| FR-04 Opportunities | 🔵 In Progress | Persisted tasks, competencies and per-program slots with eligibility constraints; browser acceptance pending. |
| FR-05 NLP embeddings | ⬜ Not Started | No actual model service or embedding generation. |
| FR-06 Similarity ranking | ⬜ Not Started | No actual cosine ranking pipeline. |
| FR-07 Supervised ranking/fallback | ⬜ Not Started | Neither a validated supervised model nor functioning cosine fallback is running. |
| FR-08 Frozen explanations | 🔵 In Progress | Stored supporting values and model-level update/delete guard tested. Generation integration absent; direct query-builder writes can bypass model guards. |
| FR-09 Coordinator judgments | ⬜ Not Started | Storage only; collection/approval/evaluation evidence absent. |
| FR-10 Cohort allocation | ⬜ Not Started | Storage only. |
| FR-11 Coordinator decisions | ⬜ Not Started | Authorization policy exists; proposal review/modify/override/reject workflow absent. |
| FR-12 Internship tracking | 🔵 In Progress | Time-log/journal/document workflows implemented and tested. End-to-end placement dependency remains. |
| FR-13 Supervisor evaluation | 🔵 In Progress | Versioned rubrics, draft/final scoring and student notification tests pass. No approved live rubric; browser verification pending. |
| FR-14 Rules-based monitoring | 🔵 In Progress | Current program hours, certified minutes, remaining days and latest document revision checked; inclusive hour/day and strict overdue boundaries tested. No approved thresholds, scheduled persistence or full browser proof. |
| FR-15 Notifications | 🔵 In Progress | Real event storage and recipient isolation tested; required event coverage incomplete. |
| FR-16 Role dashboards | 🔵 In Progress | Root dashboards now query persisted, role-scoped counts; two tests verify scope and certified hours. Specialized analytics/report pages remain prototypes. Browser acceptance remains. |
| FR-17 Reports | ⬜ Not Started | Approved scoped reports and verified exports absent. |
| FR-18 Audit trail | 🔵 In Progress | Implemented mutations logged with scoped UI; incomplete feature coverage. |
| FR-19 Backup/restore | ⬜ Not Started | Prototype UI does not execute or verify backups/restores. |
| NFR-01 Performance | ⬜ Not Started | No approved-pilot timed retrieval/recommendation evidence. |
| NFR-02 Reliability | 🔵 In Progress | Transactions, constraints and negative tests exist; full workflow/concurrency acceptance pending. |
| NFR-03 Availability | ⬜ Not Started | No approved pilot uptime measurements. |
| NFR-04 Security | 🔵 In Progress | Server authorization, hashing, CSRF, private documents and isolation tests; production TLS/security verification pending. |
| NFR-05 Usability | 🔴 Blocked | Human Likert/UAT responses required and not supplied. |
| NFR-06 Maintainability | 🔵 In Progress | Services/controllers/components separated; architecture still incomplete. |
| NFR-07 Compatibility | ⬜ Not Started | No complete Chrome/Edge/Firefox desktop/mobile matrix. |
| NFR-08 Scalability | 🔴 Blocked | Approved pilot dataset/scale absent; load tests not run. |
| NFR-09 Integrity | 🔵 In Progress | MySQL constraints, scoped APIs and validation tests; final suite and concurrency verification pending. |
| NFR-10 Recovery | 🔴 Blocked | No approved backup schedule or demonstrated authorized restore. |

## Recorded checks

- Baseline: 110 tests, 681 assertions.
- Earlier expanded suite: 133 tests, 827 assertions.
- Host/opportunity targeted suite: 5 tests, 24 assertions.
- Requirements targeted suite: 4 tests, 61 assertions; later configuration-scope assertions added.
- Academic provisioning targeted suite: 3 tests, 27 assertions.
- Monitoring targeted suite: 4 tests, 42 assertions.
- Evaluation targeted suite: 3 tests, 28 assertions.
- Latest full backend suite: **160 tests, 1,079 assertions passed** (2026-09-21).
- Frontend TypeScript check and production build passed after MOA/dashboard integration (2026-09-21).
- Main database migration through `2026_09_19_010003_add_requirement_review_state` applied.
- Browser: administrator sign-in, real notification empty state and notification navigation verified at `localhost:8080`.

Transient command output is retained under ignored `.local/`. Never interpret a targeted test pass as proof that an entire phase is complete.


- Browser (2026-09-21): Filament coordinator login and assigned-program-only registry verified against isolated QA records. Registry exposed no edit control to the coordinator. Local web-server intl inheritance repaired using --no-reload; cold page rendering remains slow and is not performance acceptance.


- Requirement review refactor/native panel: 6 targeted tests, 88 assertions passed after the full-suite checkpoint. Browser upload selection returned no attached file twice; browser upload remains unverified.


## Backend recovery and notification check — 2026-09-23

- Docker startup failed because its named vendor volume lacked Filament installed in the Windows environment. Installed the locked dependencies in that volume. The entrypoint now synchronizes Composer dependencies before Laravel boots; Compose uses the current bind-mounted entrypoint.
- Docker backend /up, isolated QA backend /up, and the main frontend API proxy returned HTTP 200 after recovery. No database reset was performed.
- Student browser showed the persisted coordinator review notification: QA Signed Form approved. Found and repaired a stale header unread count after marking the notification read. Browser verified the badge clears without navigation.
- TypeScript and frontend production build passed after the badge fix.
