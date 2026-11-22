# UI Changelog

Records every page and component changed by the UI redesign. Colours are unchanged in light mode. Dark mode uses a dedicated palette (see DESIGN_SYSTEM.md). No backend code, route, API response or business rule was changed.

## Foundation (design system)

| File | Change |
|---|---|
| `frontend/package.json`, `package-lock.json` | Added `recharts` 3.10.1 (charts). Approved by the project owner. |
| `frontend/src/styles.css` | Added loading shimmer, reduced-motion rule, dialog sizing cap, header tokens, and the dark-mode palette. Light-mode colour values are unchanged. |
| `frontend/src/lib/theme.ts` | New. Light, dark or match-device preference, stored per browser and applied before first paint. |
| `frontend/src/lib/status.ts` | New. One status-to-colour mapping shared by badges and charts (the existing rules). |
| `frontend/src/routes/__root.tsx` | Applies the saved theme before first paint. |
| `frontend/src/components/im/ui.tsx` | `Modal`, `ConfirmDialog`, `Button` danger variant and small size, status badge with a dot, form inputs, tables; every existing export and prop kept. |
| `frontend/src/components/im/ds.tsx` | New. `Skeleton`, `LoadingBlock`, `EmptyState`, `ErrorState`, `SectionHeading`, `KpiCard`, `ActionList`, `IconButton`. |
| `frontend/src/components/im/charts.tsx` | New. Bar, stacked bar, line and donut charts on Recharts, with a data table for screen readers. |
| `frontend/src/components/im/AppShell.tsx` | Rebuilt: icon navigation that collapses to an icon strip and becomes a drawer on phones, breadcrumb, theme menu, chat button, notifications with unread count, account menu, skip link. |
| `docs/UI_AUDIT.md`, `docs/DESIGN_SYSTEM.md` | New. |

## Practicum Coordinator

| File | Change |
|---|---|
| `components/im/CoordinatorDashboard.tsx` | New dashboard: needs-attention list, key numbers, placement status donut, distance bars, skills in demand, host agreements table. Uses only `/dashboard` and `/program-analytics`. |
| `pages/coordinator.tsx` | Dashboard route uses the new component. |
| `components/im/CoordinatorAnalytics.tsx` | Rebuilt with charts: placement and completion rate, average evaluation, median distance, missing coordinates; outcomes by program, placements by distance band, skills requested by open opportunities. Same `/program-analytics` data; keeps the synthetic-data switch and the report snapshots below. |
| Other coordinator pages (recommendations, approvals, student profiles, hosts, agreements, opportunities, progress, requirements, evaluations, monitoring settings, map, inbox) | Hard-coded light-only colours replaced by theme colours so they work in light and dark: account and agreement status text, import errors and warnings, programs panel, progress bars. No behaviour change. Not viewed in a browser (only real coordinator accounts exist); verified by type-check, lint and build only. |

## Student

| File | Change |
|---|---|
| `components/im/StudentDashboard.tsx` | Rebuilt. Needs-attention list (monitoring flags, returned documents, items still to submit, approved placement), certified hours with progress bar, required-items donut, requirement checklist, top recommendations. Falls back to the student's latest own placement (from `/placements`) when there is no current placement, so a completed internship shows its hours and host. |
| `components/im/ChatWorkspace.tsx` | Theme colours instead of light-only colours; chat availability now asks for confirmation in the shared confirmation dialog instead of the browser's built-in box. |
| `components/im/RequirementWorkspace.tsx` | Status badges use the shared badge (same status colours); table header no longer all capitals. |
| `components/im/ProfileEditor.tsx` | Theme colours for messages; labels in normal case; fixed a 13 px sideways overflow at phone width. |
| `components/im/StudentCompetencies.tsx` | Skills with no level now show "Not rated" instead of a bare percent sign and an empty bar. |
| `components/im/MonitoringWorkspace.tsx`, `AccessibilityMap.tsx` | Theme colours for progress track and map background; labels no longer all capitals. |
| All components using white text on the brand colour (11 places) | Use the brand foreground token, so text stays readable in dark mode. |
| `AppShell.tsx` | Breadcrumb names pages reached from the account menu ("My profile", "Notifications") instead of "Page". |

## Host establishment supervisor

| File | Change |
|---|---|
| `components/im/SupervisorDashboard.tsx` | New dashboard: needs-attention list (time logs awaiting verification, no evaluations yet on active placements), five key numbers, placement status donut, table of the most recent assigned interns. Uses `GET /dashboard` and `GET /placements`, both already limited to the supervisor's own interns. |
| `pages/supervisor.tsx` | Dashboard route uses the new component. |
| Other supervisor pages (assigned interns, attendance, evaluations, company profile, opportunities, agreements) | Restyled through the shared components and theme colours; no behaviour change. Checked at 1440 and 375 px in dark mode: no console errors, no sideways overflow. |

## Dean (program chair)

| File | Change |
|---|---|
| `components/im/DeanDashboard.tsx` | New executive dashboard: program filter and synthetic-data switch, needs-attention list (agreements expiring within 90 days, active hosts with no current agreement, placements missing coordinates), six key numbers, placement and completion rate by program, placement status donut, distance bands, and the latest approved reports. Uses `GET /dashboard`, `GET /program-analytics` and `GET /reports`, all limited to the dean's programs and approved reports. |
| `components/im/ProgramAnalytics.tsx` | Rebuilt for Program performance, Host establishments, Placement equity and Analytics: charts with titles, axis labels, legends and exact-value tooltips, key-number cards, searchable host table with status badges, empty and error states. Similarity bands are now shown as raw scores (for example "0.90 to 1.00") instead of percentages, as the specification requires. |
| `components/im/ReportWorkspace.tsx` | Approved dates use the Manila "Nov 19, 2026" format. |
| `pages/dean.tsx` | Dashboard route uses the new component. |
| Accreditation reports, export data, audit trail, notifications, profile | Restyled through shared components; behaviour unchanged. Checked in dark mode at 1440 px: no console errors, no sideways overflow. |

## System administrator and Filament panel

| File | Change |
|---|---|
| `backend/resources/views/filament/pages/admin-reference-shell.blade.php` | Rebuilt the administration shell to match the portal: persistent icon sidebar with grouped navigation, breadcrumb header, Light / Dark / Match device menu (remembered per browser), notifications link, account menu, drawer on phones, skip link, Public Sans. Same links, groups, sign-out form and embedded workspace as before. View file only. |
| `backend/app/Providers/Filament/ManagementPanelProvider.php` | Styling only: UM maroon primary colour palette, Public Sans font, UMTC seal as the panel logo and favicon, and a stylesheet hook. No routes, permissions or navigation logic changed. |
| `backend/resources/views/filament/brand.blade.php`, `backend/public/css/manage-theme.css` | New. Panel logo block; page background, card and button radius, reduced motion. No build step or new package. |
| `backend/resources/views/admin/workspace.blade.php`, `frontend/src/admin-entry.tsx` | The embedded workspace applies the saved theme before first paint and follows changes made in the shell immediately. |
| `frontend/src/components/im/AdminOverview.tsx` | New overview: needs-attention list (programs without a coordinator, no open academic term), six key numbers, accounts by role, placement status, programs, recent activity and chat status. Uses `/admin/overview` and the system-wide `/dashboard`. |
| `frontend/src/components/im/AdminWorkspace.tsx`, `pages/admin.tsx` | Old overview removed from this file; `AdminLink` exported for reuse. |
| Other administrator pages (accounts, import, hosts, opportunities, agreements, programs, audit, backup, settings) | Restyled through shared components inside the embedded workspace; no behaviour change. |

## Across the app

| Change | Detail |
|---|---|
| All-capitals labels removed | 24 class lists in 13 files (tables, field labels, form labels) no longer force capitals. |
| Table minimum width | Shared table minimum width reduced from 640 to 520 px so it fits side-by-side cards. |
| Bar charts | Switch to horizontal bars automatically when there are four or more long category labels, so labels do not overlap on phones. |
| Donut legends | Labels wrap instead of being cut off. |
| Status colours | "Expired" and "terminated" now use the problem (maroon) badge instead of plain grey. |

## Emojis removed

None were present in the application, frontend code, backend code, Filament resources or seed data. Removed from documentation:

| File | Removed |
|---|---|
| `docs/IMPLEMENTATION_AUDIT.md` | 35 coloured-circle status markers (blue, red, yellow) before "In Progress", "Blocked" and "Needs Review"; the words were kept. Two check-mark symbols and one blue circle in running text replaced by "complete" and "in progress". |
| `docs/INTERNMATCH_DEVELOPMENT_CHECKLIST.md` | Three menu and close glyphs in a UI note replaced by words. |
| Not changed | `docs/legacy/blade-ui/.../final-report.blade.php` contains one text check mark (U+2713). It is a legacy view that is not served. |


## Modals

Every modal and dialog now uses one shared system in `frontend/src/components/im/ui.tsx` (`Modal`, `ViewModal`, `ConfirmDialog`, `FormModal`, with `FormField`, `FormSection`, `FormGrid`). The three earlier implementations (`ActionDialog`, `AdminFormDialog` and a hand-built overlay in `AccountRegistry.tsx`) and the native `<dialog>` copies were removed. This is a presentation change only: the same API calls, permissions, fields, defaults and validation as before.

### Behaviour common to all modals

- Sizes: small 400 px, medium 560 px, large 880 px. At 375 px every size becomes a full-screen sheet with a fixed header and footer and a scrolling body.
- Header with the title, an optional one-line description and a close button. Footer with actions on the right: primary last, Cancel beside it, destructive actions in red.
- Forms: labels above fields, required fields marked, helper text, section headings, two columns on wider screens only for short fields.
- Server validation (Laravel 422) is shown under each field and the first invalid field receives focus. Errors that do not belong to a field appear in a short summary at the top. The modal stays open and keeps the input.
- Saving disables the submit button and shows a loading label. On success the modal closes, a toast confirms it (the existing sonner library) and the data refreshes without a page reload.
- Esc and the close button ask "Discard changes?" when the form was edited. Clicking the backdrop closes only view and confirmation modals.
- Focus moves into the modal, stays inside it, and returns to the button that opened it. Motion respects reduced-motion settings.
- Titles and buttons are in sentence case and name the item ("Edit MOA for Acme Corp", "Add opportunity"). Buttons use an icon instead of a "+" character.

### Modals changed

| Modal | Where | Role | Size |
|---|---|---|---|
| Add academic term | Administration, Academic terms | Admin | Medium |
| Add host establishment | Host establishments | Admin, coordinator, supervisor | Medium |
| Add MOA, Edit MOA for (host) | Agreements | Admin, coordinator, supervisor | Medium |
| Add opportunity, Edit opportunity | Opportunities | Admin, coordinator, supervisor | Large |
| Add user, Add student | User accounts, Students | Admin | Medium |
| Add competency, Edit competency, Remove competency | My competencies | Student | Small and medium |
| Upload document | Requirements | Student | Medium |
| Requirement details and revision history | Requirements | Student, coordinator | Medium (view) |
| Opportunity details | Student overview | Student | Medium (view) |
| Recommendation details | My recommendations | Student | Medium (view) |
| Review placement | Pending approvals | Coordinator | Medium |
| Academic review | Pending approvals | Coordinator | Small |
| Review recommendation | Recommendations | Coordinator | Large |
| Student profile and history | Students | Coordinator | Large (view) |
| Regenerate recommendation batch | Recommendation batches | Coordinator | Medium |
| Certify pending hours | Monitoring | Supervisor | Medium |
| Disable chat | Chat availability | Admin | Small (already shared) |
| Withdraw enrollment | Filament, Students | Admin | Filament modal; now names the student, program and term |

### Inline forms that became modals

| Form | Admin | Coordinator | Supervisor |
|---|---|---|---|
| Add host | Was already a modal | Was inline, now a modal | Not available to this role |
| Add and edit MOA | Was already a modal | Was inline, now a modal | Was inline, now a modal |
| Add and edit opportunity | Was already a modal | Was inline, now a modal | Was inline, now a modal |

Role differences were kept exactly as they were. The supervisor can only save an MOA with the status "draft"; only the administrator sees the institution-wide coverage option. No role gained a field or an action.

### Not changed

The coordinator-only Filament actions that cannot be reached from the administration panel were left alone. Filament create and edit screens are full pages, not modals.

### Second pass: every action that needs a prompt

Shared helpers in `frontend/src/components/im/actions.tsx`: `ConfirmButton` (opener and confirmation modal for an action with no input) and `ReasonButton` (opener and form modal with a required reason, for reject, return and withdraw). Both close on success, show a toast, refresh the page data and keep server errors inside the modal. `formUtils.ts` holds `inputClass`, `FormError` and `toFormError`.

| Role | Page | Action | Now opens |
|---|---|---|---|
| All | Header account menu | Sign out | Confirmation "Sign out?" |
| All | Header account menu, Profile | Change password | Form modal (new endpoint) |
| Admin | Administration shell (Blade) | Sign out | Confirmation "Sign out?" |
| Student | Recommendations | Express interest, Withdraw interest | Confirmation |
| Student | Recommendations | Generate or refresh recommendations | Confirmation |
| Student | Logs | Add time log, Edit time log | Form modal |
| Student | Logs | Correct and resubmit a returned log | Form modal showing the return reason |
| Student | Logs | Add journal, Edit or revise journal | Large form modal showing the return reason |
| Student | Logs | Submit journal | Confirmation |
| Student | Requirements | Record event attendance | Form inside the requirement details modal |
| Student | Profile | Upload photo | Confirmation with preview, file type and size |
| Supervisor | Attendance | Certify hours (one log) | Confirmation |
| Supervisor | Attendance | Return for correction | Form modal, reason required |
| Supervisor | Evaluations | Add evaluation, Edit draft | Large form modal |
| Supervisor | Evaluations | Finalize evaluation | Confirmation |
| Supervisor, coordinator, admin | Company profile, Hosts | Edit profile | Large form modal |
| Supervisor, coordinator, admin | Company profile, Hosts | Set program capacity | Form modal |
| Supervisor, coordinator, admin | Agreements | Upload executed agreement | Form modal with type and size |
| Coordinator, admin | Hosts, Agreements, Opportunities | Archive, Restore, Delete | Confirmation (delete only when never used) |
| Coordinator | Students | Send or re-send activation link | Confirmation |
| Coordinator | Students | Edit enrollment | Form modal (new UI on the existing endpoint) |
| Coordinator | Students | Withdraw | Form modal, reason required (new UI on the existing endpoint) |
| Coordinator | Pending approvals | Create proposal batch | Confirmation |
| Coordinator | Requirements | Start review, Approve | Confirmation |
| Coordinator | Requirements | Return for correction | Form modal, reason required |
| Coordinator | Requirements | Confirm event attendance | Confirmation |
| Coordinator | Requirements | Configure requirement | Form modal |
| Coordinator | Monitoring | Approve journal | Confirmation |
| Coordinator | Monitoring | Return journal | Form modal, reason required |
| Coordinator, admin | Monitoring | Certify hours, End deployment | Confirmation (new endpoints) |
| Coordinator | Monitoring settings | New rubric version, Edit monitoring rules | Large form modals |
| Coordinator | Reports | Generate report, Approve report for dean | Confirmation naming the program, term and type |
| Coordinator | Excel import | Confirm import | Confirmation with row counts |
| Admin | Academic terms | Set active, Deactivate, Archive, Restore, Delete | Confirmation |
| Admin | Users | Manage access (role, status, programs, hosts) | Large modal |
| Admin | Users | Disable, Enable, Send activation link | Confirmation |
| Admin | Programs | Edit requirements | Form modal |
| Admin | Programs, academic setup | Create academic term, Enroll student | Form modals |
| Admin | Chat availability | Enable chat (disable was already a confirmation) | Confirmation |

Kept as they are, by decision: report exports stay direct download links, admin settings stay inline, Filament create and edit stay full pages, and the coordinator-only Filament screens (Allocations, Recommendations, Reports) are unchanged.

Backend added for this pass (each with feature tests): archive, restore and delete for hosts, MOAs, opportunities and academic terms; certify hours and end deployment; the stored reason on returned journals; password change for every role.

Also added in this pass: Start deployment (coordinator), which moves an approved placement to active so time logs can begin.

## Page load performance

Measured on the local Docker stack (PHP built-in server with 4 workers) with the current synthetic data. Target: list pages under 2 seconds.

| Page | Cause | Fix | Before | After |
|---|---|---|---|---|
| Coordinator Requirements | One request per enrollment (about 204 requests in sequence) | `GET /api/v1/requirement-tracking`: one paged request (20 per page) with requirements, submissions, attendance and a scope-wide summary | about 2 minutes | 1.4 s, 13 to 17 queries |
| Pending approvals | `PlacementReadiness` ran 6 queries per student (632 for 102 students) | `reasonsForMany()` loads requirements, submissions, attendance and placements once | 3.5 s page, 632 queries | 1.4 s page, 19 queries |
| Coordinator dashboard | Unmet requirements checked per waiting student | `unmetRequirementsForMany()` | grew with students | 16 queries, fixed |
| Internship Progress, Evaluations, Assigned Interns | One request per placement for progress and evaluations | `GET /placements?include=progress,evaluations` | 20 requests | 8 requests, 1.6 s |
| Chat inbox | Placement and supervisor loaded per conversation | Eager loading | 122 queries | 13 queries |

Tests that fail if the query count grows with the number of records: `RequirementTrackingTest`, `PlacementListPerformanceTest`, `AllocationListPerformanceTest`.

The first visit to a page after the frontend container starts can take longer while the Vite dev server compiles modules; this does not happen in the production build. Duplicate requests visible in development come from React StrictMode.

## Pending approvals layout

The page listed every student as a tall card, and the eligibility section showed all 100 students of a cohort at once, each with up to three full sentences of reasons.

- Proposals and eligibility are compact rows, 15 per page, with Previous and Next.
- Status tabs and search sit on one line above the proposals.
- Reasons that apply to every student in the program and term are shown once in a notice instead of on every row.
- Remaining reasons are short labels (for example "Already placed"); the full sentence is the tooltip and is read by screen readers.
- Eligibility filters with counts: All, Ready, Program-wide items only, and one per reason.
- Decided proposals open a "View decision" details modal.

## Landing page and sign-in

Landing page redesigned in an editorial direction ("The Gazette"): full-screen hero, numbered sections with fine rules, alternating split layouts, one maroon colour block, a photo mosaic and a maroon closing band. Components live in `src/components/landing/`; copy and photo slots in `landing/content.ts`.

- Colours: existing tokens only (`--header`, `--warn`, `--brand-foreground`, neutrals). Two shades of the existing gold were added for small text that meets AA contrast: `--warn-ink` (light surfaces) and `--warn-light` (maroon surfaces).
- Fonts: Public Sans stays for body text; Newsreader (self-hosted, `@fontsource-variable/newsreader`) is the display serif.
- Photos: seven UM Tagum College photographs supplied by the project team, in `public/images/campus/` as WebP and JPEG at their original size (about 550 to 680 px wide; AVIF was not produced). They were taken from public posts, several carry "The Collegium" watermark, and the footer credits them; permission and credit still need confirming, and full-resolution originals should replace them. A slot without a photo shows a sized "Photo: ..." placeholder. `src/assets/hero-interns.jpg` is no longer used by the landing page because it does not appear to be a real campus photograph.
- Motion: Ken Burns zoom on the hero photo, staggered reveals on scroll, light parallax; all off with reduced motion.

Sign-in page: same layout, copy and colours, refined spacing and states.

- Lucide icons, 48 px inputs, focus and press transitions, fade-in on load.
- zod validation that mirrors `LoginRequest`, with inline field errors.
- States: signing in (spinner), invalid credentials (generic message plus "If you received an activation email, use its link first."), too many attempts (countdown from `retry_after`), server unreachable (retry), session expired.
- "Forgot password?" explains that the administrator resets passwords (no self-service reset exists).
- The page a signed-out person tried to open is restored after sign-in, only inside their own portal (`signInDestination`, unit-tested with `npm run test:unit`).
- Backend: the 429 response is `{message, retry_after}` with `Retry-After`, for both the per-email and the per-IP limit; session cookies default to Secure when `APP_ENV=production`.

## Missing features completed (2026-11-22)

- **Logistic Regression reranking, wired end to end.** Laravel calls the AI service's `/recommendations/ml` (setting `MATCHING_RERANKER=auto|off`). The service ranks by suitability probability only when a model card shows the model passed the F1 threshold, matches the files on disk (SHA-256) and was built without synthetic labels; otherwise it returns cosine order and the reason. Each recommendation stores `ranking_method`, and with the model active also the probability and model identity. A "Ranking method" panel on the coordinator's Recommendations page and the admin System Settings shows which method is active, why, and how many coordinator judgments exist. Fixed in the pipeline: missing distances are filled with the training median at ranking time (previously 0), and a failed evaluation removes any earlier passing card. Training still needs real approved judgments (4 exist).
- **Password reset by e-mail.** "Forgot password?" opens `/forgot-password`; the answer is the same for every address; only active accounts get a single-use link valid 60 minutes; `/reset-password` applies the activation password rules with a live checklist, signs out other sessions and is audited. Requests are rate-limited per address.
- **Weekly activity charts** (coordinator Analytics, dean Analytics): certified hours, time logs and interns logging per week for the last 16 weeks. Similarity bands are now labelled as raw scores (0.90 to 1.00) at the source.
- **Production frontend.** The build now targets a Node server (`node .output/server/index.mjs`) to match `docker-compose.production.yml`; previously the compose file served a `dist` folder the build never produced. Production Nginx now also compresses `text/javascript` (the main bundle was sent uncompressed). Public Sans is self-hosted, the 868 KB seal is replaced by 11 KB copies, and the landing page preloads its display font. Production-build Lighthouse (through gzip): sign-in 94 mobile / 100 desktop, landing 84 mobile / 99 desktop; accessibility and SEO 100.
- **Smaller fixes:** landing page hides the page scrollbar over the hero; Edge's duplicate password-reveal button is hidden; maps use darkened tiles in dark mode; supervisor Attendance & Hours pages 20 logs at a time.

## Recommendation learning, revised to the documented rule (2026-11-22)

Follows spec §9 and Development Guide Phase 9; replaces the earlier F1 0.70 random-split gate.

- **Eligibility (spec §6) is unchanged and verified:** only opportunities that pass the eligibility check (published, valid agreement covering the program, remaining capacity) are sent for ranking. A regression test proves expired agreements, full and closed opportunities never get a score. The institution-wide agreement option is removed (spec §5: every agreement lists its programs).
- **Model:** one shared Logistic Regression with three features: similarity score, straight-line distance and program. Agreement status and capacity are hard constraints, not features.
- **Rule:** train only with at least 30 suitable and 30 not-suitable approved coordinator judgments on real students (latest judgment per pair; uncertain excluded). Evaluate with leave-one-student-out; report accuracy, precision, recall and F1, overall and per program. Install only if the model's F1 is better than similarity alone and every program has both labels. Otherwise the current model stays.
- **Fallback evaluation:** Precision@3, Recall@3 and NDCG@3 for the current ranking against keyword overlap and distance only, shown under Technical details ("Not enough judgments yet" until a student has both labels).
- **One action:** the administrator's "Update recommendations" button (confirmation modal) and `php artisan ml:retrain` run the same check, train, evaluate and atomic install with reload (no image rebuild or restart). Every run is kept in `ranker_runs` (date, judgments used, result). Coordinators see live counts for their own programs, read only.
- **Test data:** synthetic labels are used only in local test mode (`ML_ALLOW_SYNTHETIC_MODEL` in `docker-compose.override.yml`, never loaded in production), never count toward the real 30/30 rule, and a test run takes up to 60 per program (30 of each) so the same rule applies.
- **Wording:** no technical terms on normal screens. Admin card "Recommendation learning"; ranking line "Ranked by skills match and distance" (or "... and past coordinator judgments"); similarity score with a plain help tip; student page intro; landing page matching section in plain words. "Synthetic" reads "test data" on all screens and exports (reports saved earlier are relabelled when shown or exported; stored content is unchanged). Monitoring "thresholds" read "alert levels".
- **Live check (local):** real judgments 1 suitable, 0 not suitable, so "not enough judgments". A practice run on 30/30 test labels was evaluated (17 students) and not installed: model F1 0.765 was not better than similarity alone (0.787).

## Known issues and suggestions not implemented

- `LiveDashboard.tsx` was no longer used by any role and was deleted after confirming nothing imports it.
- Tables on phones scroll sideways inside their card rather than switching to a card layout.
- 12 existing lint warnings (`react-refresh/only-export-components`) remain; `npm audit` reports 9 existing vulnerabilities in TanStack Start, vite, zod and two smaller packages.
