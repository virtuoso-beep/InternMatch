# InternMatch — Master Development, Integration, Testing & Team Checklist
**Project:** InternMatch: An Intelligent Student Internship Placement and Monitoring System for UM Tagum College Using Competency-Based Semantic Matching, Machine Learning, and Geospatial Accessibility Analysis  
**Authority:** `InternMatch_Capstone_with_chatbox.docx` & Live Codebase Verification  
**Status Date:** 2026-10-16  
**Overall Completion:** ~88% Complete

---

## 1. Project Context & Current Baseline

This checklist replaces the preliminary greenfield template to reflect the **actual current working state** of the InternMatch system. The system is **NOT starting from scratch**; core services, database schemas, role portals, and the in-system chatbox have already been developed and locally verified.

### Current Architecture & Stack:
- **Frontend:** TanStack Start + React 19 + TypeScript + Tailwind CSS (Port 8080)
- **Backend API:** Laravel 13 / PHP 8.3 with Sanctum, Gates, and Eloquent (Port 8000)
- **Admin & Governance:** Filament Admin Panel (Port 8000 `/manage`; `/admin/*` redirects there)
- **Messaging:** REST chat endpoints (`/api/v1/chat/*`) with client-side polling in `ChatWorkspace.tsx`. Laravel Reverb/WebSockets is NOT installed (corrected 2026-11-16; earlier text claimed Reverb).
- **AI Recommendation Service:** Python 3.12 + FastAPI + Uvicorn + `intfloat/multilingual-e5-base` sentence embeddings + scikit-learn Logistic Regression
- **Geospatial Engine:** Leaflet Map + Haversine distance calculations
- **Database:** MySQL 8.x (`internmatchlaravel`) with separate guarded `_testing` database

---

## 2. Requirement Traceability Matrix (FR-01 to FR-22, revised manuscript)

> **Numbering (updated 2026-11-19):** rows now follow the revised capstone manuscript (17 Nov 2026), which defines FR-16 Dashboard and Analytics, FR-17 Report Generation, FR-18 Audit Trail, FR-19 Data Backup and Recovery, FR-20 Program-Scoped Messaging, FR-21 Student Roster Import and Account Provisioning, FR-22 Data Export. The older matrix had inserted chat as FR-16 and shifted FR-16 to FR-19; entries in the progress log below written before this date may still use that old numbering (old FR-16 chat = FR-20, old FR-17 = FR-16, old FR-18 = FR-17/FR-22, old FR-19 = FR-18).

| Req ID | Requirement Description | Backend | Frontend | DB | AI/ML | Test Status | Current Status |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :--- |
| **FR-01** | User Authentication & Role-Based Access Control (RBAC) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-02** | Student Profile & Competency Management | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-03** | Host Establishment Registry & Profile Management | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-04** | Internship Opportunity Management (Tasks & Slots) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-05** | Competency & Task Semantic Processing (E5 Embeddings) | [x] | [x] | [x] | [x] | Verified | **[x] COMPLETED** |
| **FR-06** | Internship Recommendation (Cosine Similarity) | [x] | [x] | [x] | [x] | Verified | **[x] COMPLETED** |
| **FR-07** | Placement Suitability Reranking (Logistic Regression / Fallback) | [x] | [x] | [x] | [x] | Verified | **[x] COMPLETED** (Cosine fallback active until coordinator labels gathered) |
| **FR-08** | Recommendation Explanation & Factor Visibility | [x] | [x] | [x] | [x] | Verified | **[x] COMPLETED** |
| **FR-09** | Recommendation Evaluation & Coordinator Judgments | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-10** | Cohort Allocation Support & Group Placement Proposals | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-11** | Internship Assignment Management (Coordinator Approval) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-12** | Internship Hours & Progress Monitoring (Manila TZ, Time Logs) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-13** | Supervisor Student Evaluation (Rubric Scoring) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-14** | Progress Monitoring & Automated Risk Flagging | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-15** | Notification Management (Events, Placements, Messages) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-16** | Dashboard and Analytics (role-specific, live scoped data) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** (2026-11-24: coordinator and dean dashboards and analytics checked at desktop and phone widths with no errors; phone overflow and empty-value display fixed) |
| **FR-17** | Report Generation (7 kinds, frozen snapshots, dean approval gate) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-18** | Audit Trail | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-19** | Data Backup and Recovery | [x] | [x] | — | — | Local verified | **[~] READY FOR SERVER** (backup, retention and isolated restore drill passed in a production-configuration rehearsal on 2026-11-23; `deploy/install-backup-cron.sh` schedules it; to be installed on the real server at deployment) |
| **FR-20** | Program-Scoped Messaging (read-only after deployment ends) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| **FR-21** | Student Roster Import and Account Provisioning (emailed one-time activation links) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** (production needs a real mailer) |
| **FR-22** | Data Export (scoped CSV/XLSX/PDF) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |
| Internal | Messaging performance and privacy guardrails (not a manuscript NFR) | [x] | [x] | [x] | — | Verified | **[x] COMPLETED** |

---

## 3. Verified Progress Checklist by Role

### A. Student Intern Portal — 100% [COMPLETED & VERIFIED]
- [x] Dashboard: Centralized progress, status badges, and active cards.
- [x] Competencies: Proficiency rating sliders/bars, evidence files, add/edit/delete modals.
- [x] Recommendations: Match ranking cards, explanation drawer, distance in kilometers.
- [x] My Internship: Tabbed switcher (Overview, Time Logs, Weekly Journals, Evaluations).
- [x] Requirements Tracking: Document submission table, status pills, upload dialog, version history.
- [x] Accessibility Map: Leaflet interactive map with host markers, filters, distance bands.
- [x] Profile & Notifications: Completeness bar, photo drag-and-drop, filter chips (All/Unread/Read).
- [x] In-System Chatbox: Left rail navigation icon, conversation drawer, message timestamps.

### B. Host Establishment Supervisor Portal — 85% [COMPLETED & VERIFIED]
- [x] Assigned Interns: Dynamic cards with certified hours vs. required hours and risk flags.
- [x] Attendance & Hours: Scrollable table, break tracking, date/status filters, and **Bulk Attendance Review dialog**.
- [x] Student Evaluations: Rubric criteria scoring, percentage calculation, submission history.
- [x] Company Profile: Read-only details with dirty-state editable forms and paired coordinate checks.
- [x] Internship Opportunities: Opportunity postings, competency tags, slot capacity indicators.
- [x] MOA Records: Agreement cards with expiry alerts and document downloads.
- [x] Chatbox: Direct placement messaging with assigned interns and coordinators.

### C. Practicum Coordinator Portal — 80% [CURRENT ACTIVE PROGRESS]
- [x] **Student Profiles** (`PC-Student Profile.png`): Reference-style filters, status chips, search, program filter, sorting, pagination, and refresh. Approved kept distinct from Deployed.
- [x] **Recommendations & Pending Approvals** (`PC-Recommendations.png`, `PC-Pending Approvals.png`):
  - Recommendations: Compact review table, search, program filter, expandable explanations and judgment history.
  - Pending Approvals: Pending filter, student search, keyboard-accessible review/reassignment modal, and date ordering validation.
- [x] **Internship Progress & Evaluations** (`PC-Internship Progress.png`, `PC-Evaluations.png`):
  - Distinct reference-style tables with actual hours, progress, flags, and submitted rubric scores.
  - Working search, filters, pagination, refresh, and detail views. Existing journal review preserved.
- [x] **Monitoring Settings** (`PC-Monitoring Settings.png`):
  - Verified navigation, saved thresholds, validation, and risk flag automation (All 9 monitoring/evaluation tests passed).
- [x] **Requirements Tracking Compliance Dashboard (`PC-Requirements Tracking.png`) — verified 2026-10-18**:
  - Coordinator compliance overview dashboard.
  - Summary metric cards: Complete, Under Review, Missing, Compliance Rate (%).
  - Document compliance progress bars across enrolled students.
- [x] **Analytics / Reports Workspace (`PC-Analytics.png`) — verified 2026-10-18**:
  - Cohort analytics, placement distribution, and host partner metrics.

### D. Program Chair / Dean Portal — 65%
- [x] Executive Dashboard: High-level overview of placements and partner counts.
- [x] Host Establishments & MOAs: Institutional overview.
- [~] Academic Program Performance & Accreditation Reports.
- [~] Data Export: Placement and evaluation CSV/Excel data generation.

### E. System Administrator (Filament Panel) — 90% [COMPLETED & VERIFIED]
- [x] User account management and role configuration.
- [x] MOA registry and legal validity monitoring.
- [x] Academic programs and curriculum settings.
- [x] Chat master toggle (`chat_settings`).
- [x] Backup & recovery interface.
- [x] System audit trail viewer.

---

## 4. Immediate Action Plan (Resuming Directly from Thursday 4:13 PM)

```text
======================================================================
EXACT RESUMPTION POINT
======================================================================
1. PRACTICUM COORDINATOR: REQUIREMENTS TRACKING (NEXT IMMEDIATE TASK)
   - Image Reference: 'PC-Requirements Tracking.png'
   - Status: Needs coordinator compliance overview dashboard (summary cards: Complete, Under Review, Missing, Overall Compliance Rate) + compliance progress bars by document type across assigned students.
   - Files: frontend/src/components/im/RequirementWorkspace.tsx & backend/app/Http/Controllers/RequirementController.php

2. PRACTICUM COORDINATOR: ANALYTICS (SUBSEQUENT TASK)
   - Image Reference: 'PC-Analytics.png'
   - Files: frontend/src/components/im/ReportWorkspace.tsx

3. GIT COMMIT & SYNC
   - Stage, commit, and push all uncommitted progress (Chatbox, Supervisor monitoring, Coordinator profiles/recommendations/progress).
======================================================================
```

---

## 5. Team Task Assignment (UM Tagum College Capstone Team)

| Team Member | Primary Focus Area | Assigned Modules |
| :--- | :--- | :--- |
| **Twinkle Pril Odrunia** | Lead Architect / Backend & AI | AI Recommendation Engine, FastAPI, Reverb Chatbox, Database Schemas, API Security |
| **Angeli Sophia Pancho** | Frontend Lead / UI & UX | TanStack Start UI, Reference Screens Alignment, Responsive Portals, Leaflet Map |
| **Trisha Talamillo** | QA, Evaluation & Documentation | Monitoring Workspaces, Rubric Evaluations, Audit Trails, Capstone Documentation & Test Cases |

---

## 6. Development & Verification Rules
1. **Preserve Completed Work:** Do not touch or refactor already verified screens (Student portal, Supervisor monitoring, Coordinator Profiles/Recommendations/Progress).
2. **Real Data Only:** Preserve existing database accounts, time logs, and enrollment records.
3. **Automated Verification:**
   ```bash
   docker compose exec -T backend php artisan test
   docker compose exec -T frontend npm run build
   ```


---

## 7. Audit & Enhancement Progress Record (2026-11-16)

**Completed (build/syntax verified only):**
- `DatabaseSeeder` is create-only and skipped in production; it no longer resets demo-account passwords/status on every container start. Tests updated/added in `AccountSeederTest` (not executed: no `*_testing` DB configured).
- Homepage (`frontend/src/routes/index.tsx`): removed stale "16 programs" and "planned/not yet implemented" copy, replaced glyph icons with inline SVG, added focus outlines. Frontend production build passed; not visually reviewed.
- Checklist corrected (Laravel 13, polling chat, FR numbering note).

**BSCS (decided 2026-11-16: out of scope):** deactivated, not deleted. `ProgramSeeder` seeds it `is_active=false`; migration `2026_11_16_000001_deactivate_out_of_scope_bscs_program` deactivates it on existing databases (reversible; enrollments/history preserved). Selectors/imports already filter on `is_active`. Back up (`deploy/backup.sh`) before the next backend restart applies it.

**Test run (disposable `internmatch_docker_testing`, needs `-e APP_ENV=testing -e DB_DATABASE=... -e ALLOW_TEST_DATABASE_RESET=true`):** AccountSeeder/ProgramArchitecture/DepartmentRoster 15 passed. Pre-existing failures unrelated to these changes: `ConfirmedCatalogCleanupTest`, `SyntheticDemoTest` (assume programs seeded by `DatabaseSeeder`), `CoordinatorExcelImportTest::permissions_owner_revision...` (admin gets 200 on reference endpoint).

**Pending:** `lucide-react` install; rotate any existing `@example.com` passwords.

**Also done (2026-11-17):** removed ~385 lines of unused static sample data (HOSTS, INTERNS, STUDENTS, etc., incl. BSCS rows) from `frontend/src/lib/internmatch.ts`; only `ROLES`/`ROLE_ORDER`/types were imported anywhere. Frontend build passes. Report CSV export already neutralizes formula cells (`ReportController`); judgment export is numeric-only.

**Not yet audited:** page-by-page controls per role, dashboard KPI accuracy, upload/import hardening, chat authorization tests.

**Route sweep (2026-11-17, `.local/oct07-navigation-browser.cjs` adapted for Edge):** 43 student/coordinator/supervisor/dean routes passed at desktop width (admin routes not swept: admin login now redirects to Filament `/manage`, which the script doesn't expect). Fixed two defects it found: (1) `ProgramAnalyticsController` 500 on `/dean/performance` when a placement's student has no linked user (now null-safe); (2) duplicate `<h1>` on `/coordinator/analytics` (embedded `ReportWorkspace` now renders an `<h2>` via new `headingLevel` prop on `PageHeader`). Mobile width and admin/Filament pages not re-swept.

**Full backend suite (2026-11-17, disposable DB):** 231 passed, 1 skipped, 13 failed. Failures: 10 in `Filament{Host,Opportunity,Requirement,Student}Test` (Livewire/Filament test-harness errors such as `getDefaultTestingSchemaName() on null`, and records not resolved; these tests use no seeders and none of the files changed this session), plus `ConfirmedCatalogCleanupTest`, `SyntheticDemoTest`, `CoordinatorExcelImportTest::permissions_owner...` (see above). No clean-HEAD baseline run was done, so "pre-existing" is by inspection, not proof. The 7 Oct audit recorded 202/202 passing, so these regressed since (likely the later commits or Filament vendor drift).

**Filament test failures explained (2026-11-17):** the 10 `Filament*Test` failures are stale tests, not regressions from this session. Commit `b097815` (2026-10-13) made `User::canAccessPanel()` admin-only (coordinators use the React portal), so tests that act as a coordinator in the `manage` panel get no records. Filament 5.8.2 / Livewire 4.4.5 match `composer.lock` (no vendor drift). Decision needed: rewrite these tests to act as admin (and keep program-scope checks via the API), or restore coordinator panel access (a permission change; not done).

**Stale Filament tests updated (2026-11-17, option 1):** `Filament{Student,Host,Opportunity,Requirement}Test` now act as admin in the panel (coordinator denied `/manage`); coordinator program-scope and review rules are asserted through the API instead. Admin is asserted to have no `review` action. 15 tests / 150 assertions pass. `CoordinatorExcelImportTest` no longer expects admin 403 on the reference endpoint. Still failing, not fixed: `ConfirmedCatalogCleanupTest`, `SyntheticDemoTest` (need reference/program data seeded; simple `ProgramSeeder` calls were not enough). `npm run lint`: 2000 errors / 7 warnings repo-wide, none in files edited this session; not triaged.

**Full backend suite green (2026-11-17):** 243 passed, 1 skipped, 0 failed (2040 assertions) in `internmatch_docker_testing`. `ConfirmedCatalogCleanupTest` / `SyntheticDemoTest` updated to current seeders (4 programs incl. inactive BSCS, five demo accounts, BSCS 350 h). Supersedes the failure lists above. Still open: repo-wide `npm run lint` (2000 errors, untriaged), mobile/Filament browser sweep.

**Sweeps (2026-11-17):** 43 portal routes pass at 1365 and 390 px (no overflow/page errors). Filament admin: 9 of 12 pages load without overflow at both widths; `/manage/recommendations`, `/reports`, `/allocations` return 403 for admin, consistent with the spec (admin is not a placement/academic decision maker); not verified that the nav hides those links. Lint: `.prettierrc` `endOfLine: auto` clears the 2000 CRLF errors; `routes/index.tsx` formatted.

**Filament nav check (2026-11-17):** admin sidebar links only to students and Administration sections; no links to the 403 pages (recommendations, reports, allocations). Full-repo lint now 49 problems (42 auto-fixable prettier errors in untouched files, 7 fast-refresh warnings); `--fix` not run.

**Upload/chat security review (2026-11-17, code read, not pen-tested):** all 5 upload endpoints (requirements, MOA, import workbook, profile photo) validate type (`mimes`/`image`) and size (10 MB / 2 MB, photo dimension cap); files go to the private `local` disk under generated names; downloads are policy-gated (`Gate::authorize`), `Cache-Control: no-store`, `nosniff`, with SHA-256 recorded and orphan cleanup on failure. Chat endpoints exclude Admin from conversation access and use `ConversationPolicy`; `ChatTest` covers cross-program and admin-read denial (passes). No findings.

**Dashboard KPI review (2026-11-17, code read):** `DashboardController` is database-driven, program-scoped, filterable (program/term/year). Counts: enrollments, active/pending/completed placements, certified hours, time logs and submissions awaiting review, submitted evaluations, recommendation generations, plus system-wide admin figures. Gaps vs the spec coordinator example (§11): no explicit "For Placement", "Pending Requirements" (students, not submissions), "Available Slots" or "Students Needing Attention" tiles on this endpoint; some may live on Analytics/Compliance pages (not verified). P2. Not changed: adding metrics needs approval.

**Coordinator dashboard tiles added (2026-11-17, approved):** `DashboardController` now returns, for coordinators only, "Students awaiting placement", "Awaiting students with incomplete requirements", "Available placement slots" (reuses `OpportunityEligibility`; host capacity counted once) and "Students needing attention (open monitoring flags)". `PlacementReadiness::unmetRequirements()` extracted (behavior unchanged). Rendered under the dashboard "additional" metrics (not the main card row). Test: `DashboardTest` (slots asserted as integer only, no exact-figure fixture). Full backend suite: 244 passed, 1 skipped, 0 failed (2046 assertions).

**Updated spec received (2026-11-17):** Final Defense folder now holds `*_revised.docx` files and `InternMatch_What_the_System_Actually_Does_Revised.md` (replaces the 16-program spec). Not yet implemented (backend gap list): one program per coordinator with many coordinators per program (+ `removed_at` history), BSA hours 400, MOA-per-HTE covered-program list, pace-based risk rules, evaluation rubric areas/weights, privacy consent + retention jobs (§17B), 72 h activation links, no "% match" display, labeled-data plan. Open per §22: BSCRIM hours, retention approval, DPO contact, email domain, core-values indicators, similarity-label cutoffs.

**Administrator UI refinement, phase 1 (2026-11-17):** Blade shell rail (`admin-reference-shell.blade.php`): Lucide-style icons, correct active/`aria-current` state (Dashboard no longer always highlighted), "Chat" relabeled "Chat settings". Overview (`AdminWorkspace.tsx`): icon KPI cards (4 across from lg), alert for active programs with no active coordinator (`programs_without_coordinator` in `/admin/overview`, tested), readable timestamps (`formatDateTime` in `ui.tsx`) on overview and backup history. Admin screens render inside an iframe, so breakpoints follow the iframe width. Rebuild with `./build-management.ps1`. Backup taken before restart: `20261117T051452Z-0c419830`. Admin tests 22 passed; build OK; lint 0 errors (warnings pre-existing). Not yet refined: Users, Programs, other sections.

**Administrator UI refinement, phase 2 (2026-11-17):** audit trail and dashboard activity show readable action names (`formatAction` in `ui.tsx`; export keeps raw codes); Hosts/Opportunities/Programs "Manage existing … records" link restyled as a quiet right-aligned "Open full … manager" link with icon (`admin-entry.tsx`); Chat availability shows an on/off status pill and asks for confirmation before disabling chat (`ChatSettings`). Sweep: admin pages load at 1365/390 px; `/manage/{recommendations,reports,allocations}` remain 403 by design. Remaining admin sections (Users, Programs card layout, Student import, Opportunities, MOA, Monitoring settings, Terms, Notifications) not yet individually refined.

**Administrator UI refinement, phase 3 (2026-11-17):** User accounts on phones: role and status folded under the name, Action column always visible (`AccountRegistry.tsx`); Student import file chooser styled (`ExcelImportWorkspace.tsx`). Type-check note: `npm run build` (Vite) does NOT type-check; use `npx tsc --noEmit` (now 0 errors; two earlier type errors in `AdminWorkspace.tsx`/`ui.tsx` fixed). Lint 0 errors.

**Administrator UI refinement, phase 4 (2026-11-17):** Monitoring settings shows guidance until a program is chosen; Academic Terms availability uses status pills; MOA search field widened so its placeholder is not cut off. tsc 0 errors, lint 0 errors, admin sweep 18/24 (6 = by-design 403). Opportunities and Notifications screens were captured but not reviewed; Programs card layout unchanged.

**Administrator UI refinement, phase 5 (2026-11-17):** Opportunities: "1 slot" pluralization, capitalized posting-status filter ("All statuses"). Emoji/glyph icons replaced with Lucide across the frontend: notifications empty state and per-item icons (`NotificationList.tsx`), header menu the menu glyph (`AppShell.tsx`, all portals), requirements dialog close the close glyph (`RequirementWorkspace.tsx`). tsc 0 errors, lint 0 errors, builds pass. Portal sweep 43/43 routes at 1365/390 px (admin step expected to land on Filament); admin sweep 18/24 (6 = by-design 403). Administrator pass complete.

**Recommendation fixes (2026-11-17):** (1) `GET /recommendation-reviews` crashed (500) when a recommendation belonged to an archived (soft-deleted) student account; the same unsafe `student->user` access was made null-safe in batch reports, allocation, map, report builder, generation and the Filament student editor ("Archived student account" label). Regression test added to `RecommendationReviewTest`. (2) Spec §8 / decision #10: similarity is now shown as rank + raw score to two decimals ("Similarity score 0.83"), never "% match"; `matchTone` (arbitrary 85/78 colour tiers) removed; neutral styling; sort renamed "Highest similarity". Files: `ui.tsx` (`formatSimilarity`), `StoredRecommendations`, `StudentDashboard`, `AllocationWorkspace`, `RecommendationBatch`, `CoordinatorRecords`. Verified: tsc 0, lint 0, builds OK, student generation end-to-end with the AI service (E5 ready) shows "Rank 1 · Similarity score 0.83", no console/API errors; coordinator pages error-free but the demo coordinator has no program so rows were not visually checked; backend 246 passed / 1 skipped / 0 failed; portal sweep 43/43.

**Demo coordinator removed (2026-11-17, owner request):** `coordinator@example.com` (id 36, no program, no history) was first archived (audited as `account.demo_account_archived`, actor admin), then permanently deleted at the owner request (backup `20261117T071349Z-c82a1fe3`); the audit entry recording its removal is kept. Backup before change: `20261117T070901Z-3153150d`. Removed from `DatabaseSeeder` so it is not recreated; `AccountSeederTest`/`ConfirmedCatalogCleanupTest` updated (10 seeder tests pass). The two older demo coordinators (`demo.coordinator@example.test`, `sep28-coordinator@example.test`) were already archived on 2026-10-13 and are kept for their 13 audit entries each. Known effect: the opt-in `internmatch:seed-demo` command (`SeedDemo.php`, `SyntheticDemoSeeder`) requires the coordinator demo account and will now refuse to run.

**All-roles UI pass — step 1, dashboards (2026-11-17):** `DashboardUI.tsx`: KPI icons chosen by meaning (label keyword map, Lucide) instead of card position; optional in-card progress bar; link arrows as icons; new `StatusBreakdown` stacked-bar chart (reserved status colours warn/info/success, validated with the dataviz palette validator — all checks pass, tritan 7.9 covered by text labels; legend with counts and %, hover titles, screen-reader table). Coordinator/supervisor/dean dashboards gain a "Placement pipeline" card (pending/active/completed from existing stats). Student dashboard: progress bars on requirements and certified-hours cards. Shared rail `▦` glyph and workspace text arrows replaced with Lucide icons. tsc 0, lint 0, builds OK; student and dean dashboards verified in browser with no console/API errors. Coordinator portal cannot be checked visually: no coordinator test login (demo account deleted; real coordinator passwords unknown).

**All-roles UI pass — step 2, tables, dialogs, coordinator (2026-11-17):** Coordinator verified with a real coordinator login (local only). Shared `Table`/`Row` (`ui.tsx`): header cells now padded like body cells (fixed run-together headers such as "PROGRAMRECOMMENDED HOST"), `scope="col"`, top-aligned multi-line rows, row hover. Recommendation review button shows "Review" with the full name as `aria-label`. `Button` gains `aria-label` support and `relative` positioning — fixes a page-wide horizontal scroll at 390 px caused by sr-only text inside buttons escaping scrolled tables. Dialogs: shared `ActionDialog` gets max-height + scroll; dialogs use `dvh`; admin dialogs use `bg-card` (theme) instead of `bg-white`; "Add User" dialog takes focus on open so Escape closes it (was broken). Admin shell the menu glyph glyph replaced with an SVG icon. Browser dialog audit (open → inside viewport, has heading, no horizontal overflow, Escape closes) at 390 and 1365 px: student 3/3, coordinator 4/4, admin 5/5 pass; supervisor bulk dialog not testable (0 pending logs). Portal sweep 43/43 incl. real coordinator data; admin pages 18/24 (6 = by-design 403); admin panel tests 9 passed; tsc 0, lint 0, builds OK.

**All-roles UI pass — step 3, page review (2026-11-18):** Fixed a JavaScript crash on `/coordinator/progress` ("Cannot read properties of null (reading 'name')") for archived student accounts; added shared `studentName()` (`ui.tsx`) and applied it wherever `student.user.name/email` was read (CoordinatorMonitoring, CoordinatorRecords, MonitoringWorkspace, RecommendationBatch, StudentHistory, RequirementWorkspace). Native `<progress>` bars (analytics, progress, requirements, supervisor, dean) now use design tokens via `styles.css` instead of browser green/grey. Shared `Button` shows a disabled state (`opacity-50`, not-allowed cursor). Tablet width (768 px) added to the route sweep. Results: routes pass at 1365, 768 and 390 px (43 each, incl. real coordinator data); no console/API errors on reviewed pages; backend 246 passed / 1 skipped / 0 failed; tsc 0, lint 0.

**All-roles UI pass — step 4, status labels (2026-11-18):** `Pill` now displays plain lowercase status codes as labels ("active" → "Active", "under_review" → "Under review") via new `formatStatus()` in `ui.tsx`; any other badge content (sentences, codes like BSIT, rank/score text) is unchanged. Raw statuses in backup history, batch results and import-row tables, and the supervisor time-log status filter, use `formatStatus`. Status words inside running text are intentionally left lowercase. tsc 0, lint 0, builds OK.

**Coordinator assignment rule — spec §2 (2026-11-18, approved):** Backup `20261118T015350Z-c379ae25` before change. Migration `2026_11_18_000001_add_coordinator_assignment_history`: `coordinator_program` gains `assigned_at`/`removed_at`; the old `(user_id, program_id)` unique key is replaced by a unique virtual column allowing at most one ACTIVE row per pair (history rows may repeat). `User::programs()`/`Program::coordinators()` return active rows only. New `CoordinatorAssignment::set()` ends rows (sets `removed_at`) instead of deleting, opens new rows, and rejects more than one program for a coordinator (deans may hold several); used by `ProgramAccessController` and `DepartmentRoster`. Changing a user's role to coordinator is refused while they hold more than one program. Placement decisions: the existing lock/409 now says "This case was already decided by <name>, <date time>". Admin User accounts: coordinators get a single-choice program picker (incl. "No program") with an explanation; deans keep checkboxes. Local DB migrated: all 8 existing assignments preserved and active with `assigned_at` backfilled. Tests: new `CoordinatorAssignmentTest` (3 tests / 27 assertions); full backend suite 249 passed / 1 skipped / 0 failed; tsc 0, lint 0, admin bundle rebuilt; coordinator pages load without errors. Follow-up (same day): `CohortAllocationTest` now asserts a second coordinator on the same program gets 409 "This case was already decided by <first coordinator>" (4 tests / 33 assertions pass); browser check (no save) shows a coordinator gets 4 single-choice options incl. "No program" with the current program selected, a dean keeps checkboxes, no page errors.

**Supervisor bulk-review dialog verified (2026-11-18):** A synthetic pending time log (placement 6, 2026-11-17 08:00–12:00) was submitted through the student API so the dialog could open; dialog audit passes at 390 and 1365 px (inside viewport, heading, no horizontal overflow, Escape closes). Nothing was certified; the synthetic log was then deleted (its `time_log.submitted` audit entry remains as an accurate record). All dialogs found in the app have now passed the audit.

**Spec alignment round (2026-11-18, revised spec 17 Nov):** Backups `20261118T044133Z-2ace2ece` (before rubric) and `20261118T044909Z-3e6e34aa` (before privacy migration).
- FR-14 risk flags (§13): `ProgressSummary` now applies the adopted defaults, active for every program and overridable per term — rule 1 behind schedule (<85% of expected pace after 10 working days), rule 2 cannot finish (critical), rule 3 no hours logged 5 working days, rule 4 log uncertified > 5 working days, rule 5 weekly report missing after 3-day grace, rule 6 requirement incomplete 2 working days after deadline. Legacy threshold rule kept. Flags carry severity. Fixed a crash in `internmatch:refresh-monitoring` when notifying an archived student. New `SpecMonitoringRulesTest` (boundary tests per rule); three older tests updated to the spec. Local run raised 12 flags on the 4 active synthetic placements; progress page shows them. NOT done: rule 7 (evaluation pending — evaluation periods not tracked), holidays (Mon–Fri only), per-rule recipient differences (all flags notify coordinators, student and assigned supervisor), settings UI for the new rule values.
- FR-13 rubric (§13A): `internmatch:configure-spec-rubrics` (idempotent) applied the three-area rubric (50/30/20, 14 indicators, program-specific career indicators, interim core values) to BSA, BSIT, BSCRIM via new `EvaluationRubricConfiguration` service (controller refactored to use it). Results display as "x.xx / 5 · descriptor (n%)" (`formatRating`). `SpecRubricTest` proves the weighted score equals the spec formula.
- MOA (§5): new institution-wide MOAs refused; only one active MOA per establishment (overlapping active dates refused; renewals after expiry allowed). Existing data already compliant. NOT done: status still stored (date-derived states are displayed in the UI), "expiring" notifications.
- Privacy (§17B): `privacy_consents` table; notice config `config/privacy.php` (DPO contact and retention periods shown as marked placeholders/proposals); `GET privacy-notice`, `POST privacy-consent` (audited, idempotent, re-asked per notice version); portal consent screen after first-password. `internmatch:retention-report` is report-only. Consent acknowledged only for the synthetic student/supervisor/dean demo accounts — NOT for the real coordinator account. NOT done: admin (Filament) sign-in path does not show the notice.
- NOT done (needs approval/infrastructure): §17A-B emailed 72-hour activation links (current approved flow: temporary credential hand-off + forced first-password change).
- Verification: backend 265 passed / 1 skipped / 0 failed (2143 assertions); tsc 0 errors; route sweeps student/supervisor/dean 28/28 at 1365, 768, 390 px (coordinator routes excluded because the real coordinator account now shows the consent screen).

**Lint (2026-11-18):** whole-repo `npm run lint` reported 11 errors, all in the generated admin bundle `dist-admin/` (not ignored, unlike `dist`/`.output`). Added `dist-admin` to the ESLint ignore list. Source tree (`eslint src`): 0 errors, 12 warnings (react-refresh "only export components", pre-existing pattern).

**Activation links — spec §17A-B decision #9 (2026-11-18, approved):** Backup `20261118T052625Z-bf5c7a23`. New `account_activations` table (only SHA-256 token hashes stored); `AccountActivation` service; `AccountActivationLink` mail notification; `config/internmatch.php` `activation_hours` (default 72, env `ACCOUNT_ACTIVATION_HOURS`) and `portal_url` (env `PORTAL_URL`). Endpoints: `POST users/{user}/activation` (admin: any account; coordinator: only students in assigned programs; disabled/no-email refused; throttled), public `GET activation/{token}` and `POST activation` (password rules as first login; throttled). Links are single-use; re-issuing revokes earlier unused links; expired links refused; pending accounts become active; audited (`account.activation_issued`, `account.activated`). Portal: public `/activate` page; admin account editor "Send activation link". Existing temporary-credential hand-off kept unchanged. Tests: `AccountActivationTest` 3 / 28 assertions. End-to-end (synthetic `qa.activation@example.test`, archived afterwards): issue → link read from local mail log → `/activate` sets password → reuse refused → sign-in works and lands on the privacy notice. Full backend suite 268 passed / 1 skipped / 0 failed. NOTE: local `MAIL_MAILER=log` writes links into `storage/logs`; production needs a real mailer (and `PORTAL_URL`). No coordinator-side "send link" button yet (API supports it).

**Spec alignment round 2 (2026-11-18):**
- §13 rules complete: rule 7 (evaluation pending: midterm opens at half the required certified hours, final at completion; flag after 10 open + 5 grace working days; cleared by a submitted evaluation), configured holidays (`internmatch.holidays`), per-rule recipients as listed in §13. Settings screen ("Monitoring rules" card) edits all nine rule values per program term with an approval reference; out-of-range values rejected (422); saved values used by `ProgressSummary`; changes audited (`monitoring_rules.configured`). Legacy days/hours pair now optional (both or neither). Tests: `SpecMonitoringRulesTest` 14.
- MOA §5: `Moa::statusOn()` / appended `lifecycle_status` computed from dates — pending, active, expiring (≤ 90 days), expired (terminated stays terminated). MOA workspace and monitoring show it. New daily `internmatch:notify-expiring-moas` (07:00) notifies coordinators of the covered programs and every administrator once per agreement. Tests: `MoaLifecycleTest` 2 (boundary 90/91 days, recipients, no duplicates).
- Privacy §17B on the admin sign-in path: Filament panel and admin workspace redirect to `/manage/privacy-notice` until the current notice version is acknowledged (same `privacy_consents` table and audit). Tests: `ManagementPrivacyNoticeTest` 3. Panel test classes use `Tests\Concerns\AcknowledgesPrivacyNotice`. Note: real admin/coordinator accounts will see the notice on next panel visit (not acknowledged on their behalf).
- Roster import §17A-B (approved): new student accounts are created PENDING with no usable password; e-mail required for new accounts; an activation link is e-mailed after the import commits; the temporary-password download is no longer produced (old endpoint returns 410). Template gains an Email column. Import results and history show activation per row: link sent / activated / expired / email failed. Coordinator student list shows "Not activated" with "Send / Re-send activation link". Tests: `StudentAccountImportTest`, `CoordinatorExcelImportTest` updated (Pint also reformatted the latter; whitespace/import style only).
- FR-22 export: reports download as CSV (formula prefixes neutralised, existing) or XLSX (`?format=xlsx`, new; same scope-checked frozen snapshot; text written as explicit string cells so it is never evaluated; "Export details" sheet with report, program, term, approval, content hash, export time and user; audited with the format). Report screen has "Download Excel". PDF not built (needs a new PDF library — awaiting approval). `ReportTest` covers XLSX.
- Verification: backend 277 passed / 1 skipped / 0 failed (2218 assertions); tsc 0; lint 0 on changed files; frontend and admin bundles rebuilt. No commit, no deploy.

**Placement chat after deployment end — FR-20 / spec §17A-A (2026-11-19, owner decision):** When a deployment ends (end date passed or completed), the placement conversation is read-only for everyone and the assigned supervisor keeps read access to that intern's history (previously the supervisor lost access entirely). Sending still requires an active deployment; cancelled placements give the supervisor no access. Other interns' conversations stay hidden. `ChatAccess::supervisedPlacement()`; `ChatTest` updated (6 tests / 83 assertions). Full backend suite 277 passed / 1 skipped / 0 failed.

**PDF export — FR-22 (2026-11-19, approved):** Added `dompdf/dompdf` ^3.1 (no advisories against it or its dependencies; `composer audit` lists 16 pre-existing advisories in laravel/framework, filament, livewire, league/commonmark and symfony packages — to review separately). Reports download as PDF (`?format=pdf`) from the same scope-checked frozen snapshot: title, export details (program, term, generated, approval, content hash, exported at/by), column headers repeated per page, landscape when more than six columns. Values are HTML-escaped by Blade; dompdf runs with remote resources, PHP and JavaScript disabled. Report screen has "Download PDF". `ReportTest` checks the PDF response, audit format and escaping; a synthetic sample was rendered and inspected (accents and HTML text shown correctly). Full backend suite 277 passed / 1 skipped / 0 failed; tsc 0, lint 0; bundles rebuilt.

**Synthetic BSA data set + synthetic-data filter (2026-11-19, approved):**
- Filter: `is_synthetic` + `source_id` on 18 tables (additive migration `2026_11_19_090000_add_synthetic_data_support`). Dashboard, program analytics and reports leave synthetic records out by default; coordinators, deans and admins can switch on "Include synthetic data". Reports generated with synthetic data are stored `contains_synthetic`, get a per-row `synthetic_record` column and are labelled "Contains synthetic data" on screen, in the Excel export-details sheet, in the PDF banner and in every export file name. The Filament overview widget never counts synthetic data. `ml:export-judgments --source=real|synthetic|all` (real by default); synthetic labels skip "For Review" rows; every exported row has `is_synthetic`.
- New: placement status `incomplete`, weekly-log status `not_submitted`, optional competency level, exact source `time_logs.hours`; tables `coordinator_labels`, `monitoring_records`, `attendance_absences`, `role_permission_references` (reference only; access is still enforced by `Role`/`Permission` enums), `synthetic_source_rows` (untouched copy of every imported row). Completing an activation link now also sets `email_verified_at`.
- Commands: `internmatch:import-synthetic` (validates everything first, stops on any value that does not fit, one transaction, no model events or e-mail except the `--activate` student's link), `internmatch:purge-synthetic` (dry run unless `--force`; keeps accounts in `SYNTHETIC_KEEP_STUDENT_NUMBERS`, default 150001), `internmatch:set-local-password` (refuses production).
- Imported BSA synthetic set into the live local DB after backup `20261118T221104Z-c8171246` (rehearsed first on the isolated restore `internmatch_recovery_3c3c63088671_testing`, including a full purge). Term "AY 2025–2026 First Semester" 2025-08-01..2025-12-31 (inactive). All counts match the sheets (124 users, 20 HTEs/MOAs/opportunities, 100 students, 600 labels, 66 placements, 5,166 time logs + 194 absences, 1,104 weekly logs, 264 monitoring, 66 evaluations, 132 notifications, 198 chat messages, 132 audit logs, 8,220 source rows). Real records unchanged. Synthetic embeddings not yet generated (run `internmatch:refresh-embeddings` before testing recommendations).
- Tests: `SyntheticDataTest` 6; `AccountActivationTest` asserts e-mail verification. Full backend suite 283 passed / 1 skipped / 0 failed (2290 assertions); tsc 0; lint 0 on changed files; bundles rebuilt. No commit, no deploy.

**End-to-end verification on the synthetic BSA set (2026-11-19):** backup `20261119T023109Z-de048769` first. Embeddings refreshed (768-dim; 100 synthetic students + 20 opportunities). Recommendations verified in a rolled-back transaction (synthetic term is inactive, so live generation correctly returns none): 30 recommendations for 5 unplaced students, cosine recomputed from stored vectors (max diff 3e-15), Haversine recomputed (max diff < 0.0001 km; matches the sheet's distance_km within 0.005 km), order = similarity then distance, only eligible opportunities (6 of 20: closed, zero-slot, full, expired-status and date-expired MOAs excluded; non-BSA opportunity excluded). Dashboards (admin/dean/coordinator/supervisor/student), analytics and reports checked over HTTP with synthetic data hidden by default and the switch on; CSV/XLSX/PDF exports carry the label and file-name suffix and are audited. Training export: real 2 rows, synthetic 532 (68 For Review skipped), all 534, `is_synthetic` on every row. Activation tested on a throwaway synthetic account (link, weak password rejected, password set, `email_verified_at` set, link single-use, login); 150001's link untouched. Purge dry run changed nothing.
- Fixed: `ml:export-judgments` message and the ML pipeline default input pointed at `storage/app/ml`, but Laravel's local disk writes `storage/app/private/ml` (pipeline defaults could not find the export). Tests: `SyntheticDataTest` asserts the reported path; `ai-service/tests/test_ml_pipeline.py::test_default_input_points_at_laravel_local_disk` (written; pytest is not installed in this environment, path checked directly instead).
- Open: recommendation batch reports (`/recommendation-batch-reports`) run over synthetic enrollments and store their names in the results JSON; no synthetic filter there yet and purge does not remove those report rows (decision needed).

**PDF approved, batch reports, test tooling, local logins (2026-11-19):** backup `20261119T031433Z-ad90c137`.
- PDF export (dompdf) approved by the owner. Banner checked visually (page rendered to PNG in a disposable container): amber "Contains synthetic data" box under the title, details and `synthetic_record` column visible. Regression test `SyntheticDataTest::test_pdf_download_prints_the_synthetic_banner_only_when_included` extracts the drawn text from the downloaded PDF.
- Recommendation batch reports: `contains_synthetic` column (migration `2026_11_19_100000`, backfilled from referenced enrollments; batches #2 and #3 flagged). List hides synthetic batches unless "Include synthetic data"; rows marked synthetic; the batch run skips synthetic students unless the switch is on; label and export file name on screen. Purge deletes synthetic-only batches and removes only synthetic rows from mixed batches (dry run on live: 2 synthetic-only batches would be deleted). Test: `test_batch_reports_hide_synthetic_by_default_and_purge_strips_synthetic_rows`.
- `ai-service/requirements-dev.txt` (pytest 8.3.5, pandas 2.2.3; test-only, not in the Docker image). AI tests 8 passed in a disposable container.
- `internmatch:set-local-password --all-synthetic` (skips purge-kept and pending accounts; never real ones; refuses production). Test added. Applied locally: 124 synthetic accounts share one local test password; real account hashes and 150001 unchanged.
- Not done: Gmail SMTP for 150001 — the backend still loads mailer `log` (default host/port, no username) after restart, so no new link was issued (the existing link stays valid).

**Fixed staff accounts (2026-11-19):** backup `20261119T035922Z-63ce79f5`. Admin, dean and coordinator accounts are real and fixed; only supervisors and students are synthetic.
- `internmatch:reassign-synthetic-staff` (dry run unless --force) moved every reference to synthetic U-0001..U-0004 to the real accounts, keeping `is_synthetic`: admin@example.com (2 activation-link issuers), dean@example.com (BSA added to its programs, BSIT kept, audited), jm.flores@umindanao.edu.ph (600 labels, 264 monitoring records, 132 synthetic audit rows, 66 chat messages, 2 generated / 1 approved reports). 12 real audit rows by those accounts were not re-attributed (actor cleared on removal). The 4 synthetic accounts were removed; real accounts' names, e-mails, passwords, status and other programs unchanged (verified by diff). Purge dry run still removes all synthetic records and no real account.
- `internmatch:import-synthetic` now requires --admin/--dean/--coordinator (existing active real accounts; dean and coordinator must already hold the program) and never creates or changes those accounts or programs.
- `internmatch:update-account-identity` changes only name/e-mail (unique across all accounts, including archived), audited as `account.identity_updated` (not used; owner chose a name-only change). Dean #4 renamed "Dean Demo" -> "Gina Fe G. Israel" (name only; dean@example.com, password, status and BSA+BSIT unchanged; audited by admin@example.com; backup `20261119T083043Z-c436c032`). Admin Demo / admin@example.com unchanged.
- Commands now reset per-run state (Laravel reuses command instances in one process). Tests: `SyntheticStaffAccountsTest` 3. Full backend suite 289 passed / 1 skipped / 0 failed; tsc 0; lint 0 errors; bundles built.
