# InternMatch UI Audit

Date: 19 November 2026. Scope: the React portal (`frontend/`, TanStack Start) and the Filament management panel (`backend/app/Filament`, served at `/manage`). This audit records the current state before the redesign. Nothing has been changed yet.

## 1. Roles

The code defines five roles (`backend/app/Enums/Role.php`): `student`, `coordinator`, `supervisor`, `dean`, `admin`.

| Role in the brief | Role in the code | Notes |
|---|---|---|
| Student | `student` | React portal only (`/student/...`). |
| Practicum Coordinator | `coordinator` | React portal only (`/coordinator/...`). Assigned to exactly one program; a program may have several coordinators (`CoordinatorAssignment`). |
| Supervisor | `supervisor` | One role. It is the host establishment supervisor: linked to establishments through `host_establishment_user` and to interns through `placements.supervisor_id`. |
| Host Establishment Supervisor | `supervisor` (same role) | There is no separate faculty supervisor role. No new role will be created. |
| Dean | `dean` | React portal only (`/dean/...`). Scoped to assigned programs; sees only coordinator-approved reports. |
| System Administrator | `admin` | Filament panel only. Signing in to the portal as admin redirects to `/manage`. The Filament "Administration" page embeds the React admin workspace (`frontend/src/admin-entry.tsx`, built into `backend/public/admin-ui`) in an iframe. |

Filament access: `User::canAccessPanel()` admits active admins only.

## 2. Screens by role

### Student (`frontend/src/pages/student.tsx`)

| Section | Component | Purpose |
|---|---|---|
| Dashboard | `StudentDashboard` | Placement, hours, requirements and recommendations summary |
| Competencies | `StudentCompetencies` | Own skills and levels |
| Opportunities | `StudentOverview opportunities` | Browse eligible opportunities, express interest |
| Recommendations | `StudentOverview recommendations` / `StoredRecommendations` | Stored ranked matches (rank, similarity score, distance) |
| My Internship | `MonitoringWorkspace` | Time logs, weekly journals, evaluations |
| Requirements | `RequirementWorkspace` | Document submissions and event attendance |
| Accessibility Map | `AccessibilityMap` | Leaflet map, straight-line distances |
| Inbox | `ChatWorkspace` | Placement chat |
| Notifications | `NotificationList` | |
| Profile | `ProfileEditor role="student"` | |

### Supervisor (host establishment) (`pages/supervisor.tsx`)

Dashboard (`LiveDashboard`), Assigned Interns, Attendance & Hours, Evaluations (all `MonitoringWorkspace` views), Company Profile (`HostWorkspace`), Internship Opportunities (`OpportunityWorkspace`), Memorandum of Agreement (`MoaWorkspace`), Inbox, Notifications, Profile.

### Practicum Coordinator (`pages/coordinator.tsx`)

Dashboard (`LiveDashboard`), Recommendations (`CoordinatorRecommendations` + `RecommendationBatch`), Pending Approvals (`AllocationWorkspace`), Student Profiles (`CoordinatorStudents`), Host Establishments, Agreements (MOA), Opportunities, Internship Progress (`CoordinatorMonitoring mode="progress"`), Monitoring Settings (`ProgramMonitoringSettings`), Requirements, Evaluations (`CoordinatorMonitoring mode="evaluations"`), Analytics (`CoordinatorAnalytics`, includes reports), Accessibility Map, Inbox, Notifications, Profile.

### Dean (`pages/dean.tsx`)

Executive Dashboard (`LiveDashboard`), Program Performance, Host Establishments, Placement Equity, Analytics (all `ProgramAnalytics`), Accreditation Reports and Export Data (`ReportWorkspace`), Audit Trail (`AuditLog`), Notifications, Profile.

### System Administrator

React admin workspace (`pages/admin.tsx`, rendered inside Filament): Dashboard (`AdminOverview`), Students, User Accounts (`AccountRegistry`), Roles & Permissions (static table), Student Import (`ExcelImportWorkspace` + `ImportHistory`), Host Establishments, Opportunities, MOA Records, Programs & Curriculum (`ProgramRegistry`), Monitoring Settings, Academic Terms, Chat Availability, Notifications, Audit Trail, Backup & Recovery, System Settings, Profile.

Filament panel (`/manage`): Dashboard (Filament `AccountWidget` + `PlacementStatistics` widget), Students resource (list/create/edit), Administration page (embeds the React workspace by section). Hosts, Opportunities and Programs resources exist and are reachable by admins through `canAccessPanel`; the admin navigation shows Dashboard, Students and Administration only.

Shared/public: `/` landing, `/auth` sign-in, `/activate` account activation, first-password screen (`FirstPassword`), privacy notice (`PrivacyConsent`), and `/manage/privacy-notice` (Blade).

## 3. Current styling and shared components

- Tailwind CSS 4 with tokens in `frontend/src/styles.css` (`@theme inline`, oklch values). Brand colour: deep maroon `--brand: oklch(0.35 0.11 18)`, with success, warn and info tones. Light and `.dark` token sets exist; no dark-mode switch in the UI.
- Font: Public Sans from Google Fonts (`routes/__root.tsx`). Logo and favicon: `umtc-seal.png`.
- Icons: `lucide-react` 0.468 is installed and used in 10 components.
- Toasts: `sonner`. Animations: `tw-animate-css`. Maps: `leaflet` 1.9.4.
- Charts: **no chart library is installed.** Current "charts" are CSS/SVG bars and meters (`ui.tsx` `Bars`, `Meter`; `DashboardUI.tsx` `StatusBreakdown`, `DashboardProgress`).
- Shared building blocks: `AppShell` (sidebar, header, role navigation from `lib/internmatch.ts`), `ui.tsx` (`PageHeader`, `Card`, `Pill`/`statusTone`, `StatCard`, `Button`, `ActionDialog`, `FilterChips`, `Table`/`Row`, `Meter`, `Field`, `Bars`), `DashboardUI.tsx` (dashboard metric, heading, empty, progress, status breakdown).
- Filament: default theme with `Color::Rose` primary; no custom Filament theme stylesheet. The backend has its own Vite setup (`backend/package.json`, `vite.config.js`), which a Filament custom theme would use.

## 4. Inconsistencies and likely defects

Found by reading the code. Items marked (browser) still need confirming at 375, 768, 1024 and 1440 px before the redesign changes them.

1. **Three modal implementations.** Native `<dialog>` in 10 components (`AdminFormDialog`, `AllocationWorkspace`, `CoordinatorRecords`, `RecommendationBatch`, `RequirementWorkspace`, `StoredRecommendations`, `StudentCompetencies`, `StudentHistory`, `StudentOverview`, `SupervisorMonitoring`), the shared `ActionDialog`, and custom fixed overlays (`AccountRegistry`, `AppShell`). Sizing, internal scrolling and focus handling differ. (browser: overflow on small screens)
2. **Two table approaches.** The shared `Table` is used in 4 components; 14 components render raw `<table>` with their own classes. Horizontal scrolling is applied in some (15 files use `overflow-auto`/`overflow-x-auto`) but not consistently. (browser: cut-off tables at 375 px)
3. **Native confirm dialog.** `ChatWorkspace.tsx` uses `window.confirm(...)` instead of an in-app confirmation dialog.
4. **Unreachable Filament resources.** `AllocationResource`, `RecommendationResource` and `ReportResource` require the coordinator role, but the panel admits admins only, so no one can open them. Coordinators use the React equivalents. Leave as is unless you decide otherwise.
5. **Duplicate dashboard primitives.** `ui.tsx` `StatCard`/`Bars`/`Meter` and `DashboardUI.tsx` `DashboardMetric`/`StatusBreakdown`/`DashboardProgress` overlap.
6. **Lint warnings (pre-existing).** 12 `react-refresh/only-export-components` warnings in `admin-entry.tsx`, `SessionGuard.tsx` and `ui.tsx`.
7. **Admin experience split.** Admins see Filament chrome around an iframe of the React workspace; the two look different (Rose theme versus maroon brand).

## 5. Dashboard metrics: what existing data supports

Sources: `GET /api/v1/dashboard` (role-scoped counts; filters `program_id`, `academic_term_id`, `year_level`; synthetic switch), `GET /api/v1/program-analytics` (coordinator and dean only), `GET /api/v1/admin/overview` (admin), `GET /api/v1/placements` (paginated, role-scoped), `GET /api/v1/placements/{id}/monitoring` (one placement's progress summary, time logs, journals, evaluations, flags), `GET /api/v1/enrollments` and the student's own requirement, recommendation and monitoring endpoints, `GET /api/v1/notifications`, `GET /api/v1/reports`.

Legend: **Yes** = available from an existing endpoint with backend aggregation. **Partial** = available, but only as listed. **No** = needs a new endpoint or query (not built; listed in section 6).

### Student
| Metric | Status | Source |
|---|---|---|
| Matching status (stored recommendations, rank, similarity score) | Yes | `/enrollments/{id}/recommendations` |
| Deployment details (host, supervisor, dates, status) | Yes | `/enrollments`, `/placements/{id}/monitoring` |
| Certified vs required OJT hours (progress bar) | Yes | `ProgressSummary` via `/placements/{id}/monitoring`; required hours from `program_terms.required_minutes` |
| Requirement progress | Yes | `/enrollments/{id}/requirements` |
| Evaluation progress | Yes | monitoring payload (evaluations) |
| Next steps / alerts | Yes | rule-based from monitoring flags, unmet requirements and pending items |
| Hours logged per week (trend chart) | No | needs a weekly aggregate for the student's own placement |

### Supervisor (host establishment)
| Metric | Status | Source |
|---|---|---|
| Assigned interns; active, pending and completed placements | Yes | `/dashboard` |
| Certified hours; time logs awaiting verification | Yes | `/dashboard` |
| Submitted evaluations | Yes | `/dashboard` |
| Accessible active hosts | Yes | `/dashboard` |
| Per-intern progress list | Partial | `/placements` lists interns; progress needs one `/monitoring` call per intern |
| Pending (due) evaluations count | No | needs a role-scoped "evaluations due" count |
| Attendance trend over time | No | needs a weekly aggregate |

### Practicum Coordinator
| Metric | Status | Source |
|---|---|---|
| Enrollments; active, pending and completed placements; certified hours; logs awaiting verification; submissions awaiting review; submitted evaluations; recommendation generations | Yes | `/dashboard` |
| Students awaiting placement; awaiting with incomplete requirements; available slots; students with open monitoring flags | Yes | `/dashboard` coordinator tiles |
| Placement and completion rate per program; evaluation average | Yes | `/program-analytics` |
| Host inventory (current and expiring agreements) | Yes | `/program-analytics` |
| Distance bands, similarity score bands, competency demand | Yes | `/program-analytics` |
| Pending approvals list | Yes | existing approvals/allocation endpoints (page level) |
| Deployment status by program (stacked) | Partial | `/dashboard?program_id=` per program (one call per assigned program; a coordinator has one program) |
| OJT hours monitoring trend | No | needs a weekly aggregate per program |

### Dean
| Metric | Status | Source |
|---|---|---|
| Program-scoped enrollment and placement counts | Yes | `/dashboard` (filters: program, term, year level) |
| Program comparison (placement, completion, evaluation) | Yes | `/program-analytics` |
| Host inventory, distance equity, per-term summary | Yes | `/program-analytics` |
| Approved reports | Yes | `/reports` |
| Overall practicum progress over time | No | needs a time series |

### System Administrator
| Metric | Status | Source |
|---|---|---|
| Users by role, total users | Yes | `/admin/overview` |
| Programs, programs without an active coordinator, terms | Yes | `/admin/overview` |
| Recent system activity (last 5 audit entries) | Yes | `/admin/overview` |
| System-wide placement counts, active hosts, published opportunities, active accounts | Yes | `/dashboard` (admin) |
| Placement statistics per program | No | `/program-analytics` is coordinator and dean only |
| Host establishment statistics by industry or city | No | needs an aggregate query |

### Filters and trends
- `/dashboard` supports program, academic term and year level filters. **No endpoint supports a date range.**
- No endpoint returns a time series, so no percentage change or trend can be shown honestly today. KPI cards will show plain numbers.

## 6. Metrics that need new endpoints (not built; awaiting approval)

| Metric | Would show | Needs |
|---|---|---|
| Weekly certified hours (student) | Line chart of certified hours per week for the student's placement | `GET /placements/{id}/hours-by-week`, grouped by `work_date` week in Asia/Manila, verified logs only |
| Weekly certified hours (program) | Line chart for coordinator/dean | Same aggregate grouped by program, scoped like `/dashboard` |
| Evaluations due (supervisor, coordinator) | Count and list of evaluations whose window is open and not submitted | Count from the existing evaluation-window rules in `ProgressSummary` |
| Deployment status by program (admin, dean) | Stacked bar of pending/approved/active/completed per program | Group-by on placements joined to program terms, role-scoped |
| Placement statistics per program (admin) | Bar chart | Admin access to the `/program-analytics` aggregates or a new admin aggregate |
| Host statistics by industry/city (admin) | Bar chart | Group-by on `host_establishments` |
| Date-range filtering | Date pickers on dashboards | `from`/`to` parameters on `/dashboard` and `/program-analytics` |

## 7. Emojis found

The live UI, frontend code, backend code, Filament resources and seeders contain no emojis. Found only in documentation:

| File | Lines | Characters |
|---|---|---|
| `docs/IMPLEMENTATION_AUDIT.md` | 38 lines from L24 | U+1F535 blue circle, U+1F534 red circle, U+1F7E1 yellow circle (status markers) |
| `docs/INTERNMATCH_DEVELOPMENT_CHECKLIST.md` | L201, L209 | U+2630 menu symbol, U+2715 multiplication X (describing UI controls) |
| `docs/legacy/blade-ui/resources/views/student/final-report.blade.php` | L15 | U+2713 check mark (legacy view, not served) |

These will be replaced with plain text during the redesign and listed in `CHANGELOG_UI.md`.

## 8. Decisions needed before section 2

1. **Charts.** No chart library is installed. Options: (a) build a small set of SVG chart components in-house (line, bar, stacked bar, donut) with tooltips, axes and legends, no new dependency; (b) add Recharts (about 100 kB gzipped plus dependencies). Recommendation: (a).
2. **Filament theme.** Matching Filament to the React design needs a Filament custom theme compiled by the backend's Vite build (`backend/resources/css/filament/manage/theme.css` and a `->viteTheme(...)` line in `ManagementPanelProvider`). This is a styling/build change only, but it touches a backend file. Approve or decline.
3. **Supervisor role.** Confirm there is no separate faculty supervisor; "Supervisor" and "Host Establishment Supervisor" will be treated as one role and one dashboard.
4. **New metrics in section 6.** Approve any you want built (each is a small read-only aggregate endpoint with tests), or keep dashboards to the metrics marked Yes/Partial.
5. **Unreachable Filament coordinator resources (section 4, item 4).** Leave untouched (recommended) or remove in a later, separate change.
6. **Dark mode.** Dark tokens exist but there is no toggle. Keep light only (recommended) or add a toggle.

## 9. Modals (inventory before the modal redesign)

Searched the React app (`frontend/src`) and the Filament panel (`backend/app/Filament`) for every modal, dialog, drawer, popover form and confirmation. Create and edit screens in Filament (Students, Hosts, Opportunities, Programs) are full pages, not modals, and are not listed. No `window.confirm` boxes remain. The toast library (`sonner`) is installed but never called, so no modal shows a success toast today.

### 9.1 React app

| # | Modal | Opens from | Role | What it does | Component | Current problems |
|---|---|---|---|---|---|---|
| 1 | Confirm disable chat | Chat availability, "Disable chat" | Admin | Confirms turning chat off for everyone | `ConfirmDialog` (`ui.tsx`) | Already on the shared `Modal`; this is the pattern to keep. |
| 2 | Action dialog (generic form) | Nowhere | None | Generic title, fields and Save | `ActionDialog` (`ui.tsx`) | Unused; every field is `required`; no validation messages. |
| 3 | Add term | Academic terms, "Add term" | Admin | Create an academic term (code, name, year, dates) | `AdminFormDialog` | Title case "Add Term"; close is a bordered "x" text button; no footer, so submit sits in the body; backdrop click closes and discards typed values; no unsaved-changes check; no toast. |
| 4 | Add host | Host establishments, "Add host" | Admin (dialog); coordinator and supervisor see the same form inline on the page, not as a modal | Create a host establishment | `AdminFormDialog` | Same as 3; long two-column form with all fields in one block and no section headings; cramped on mobile. |
| 5 | Add MOA / Edit MOA | Agreements, "Add MOA" or "Edit" | Admin (dialog); coordinator and supervisor inline | Create or edit a memorandum of agreement | `AdminFormDialog` | Same as 3; the title "Edit MOA" does not name the host. |
| 6 | Add opportunity / Edit opportunity | Opportunities, "Add" or "Edit" | Admin (dialog); coordinator and supervisor inline | Create or edit an opportunity | `AdminFormDialog` | Same as 3; long form with competency and program capacity rows and no sections. |
| 7 | Add user / Add student | User accounts, "+ Add User" or "+ Add Student" | Admin | Provision an account | Custom overlay in `AccountRegistry.tsx` | Third implementation: hand-built overlay with "Cancel" in the header and no footer; no focus trap and focus is not returned to the opener; Esc handled by hand; backdrop click discards input; "+" in the button text. |
| 8 | Add / edit / remove competency | Competencies, "Add competency", "Edit", "Remove" | Student | Add a competency with a level, change the level, or remove it | native `<dialog>` in `StudentCompetencies.tsx` | Remove is the same dialog with a different button label and no confirmation text naming the competency; header scrolls away with the body; no fixed footer. |
| 9 | Upload document / requirement details | Requirements, "Upload document" or a row | Student | Submit a requirement document or read its details | native `<dialog>` in `RequirementWorkspace.tsx` | One title ("Requirement details") for two purposes; close is an icon without a footer; file errors appear inside the body. |
| 10 | Opportunity details | Opportunities, "View details" | Student | Read an opportunity and express interest | native `<dialog>` in `StudentOverview.tsx` | View-only content with the action inside the body; slightly different width from 11. |
| 11 | Recommendation details | Recommendations, "View details" | Student | Read a stored recommendation, its rank and similarity score | native `<dialog>` in `StoredRecommendations.tsx` | Duplicate of 10 with different markup and spacing. |
| 12 | Placement decision / academic eligibility | Pending approvals, "Review" or the eligibility link | Coordinator | Approve or reject a proposed placement (decision, opportunity, supervisor, dates, reason), or confirm academic eligibility | native `<dialog>` with an inner `role="dialog"` box in `AllocationWorkspace.tsx` | Two dialog elements nested; two purposes with one heading (the student's name); long form without sections; no sticky footer. |
| 13 | Recommendation review | Recommendations, "Review" | Coordinator | Record a suitable, unsuitable or uncertain judgment with a reason | side sheet (native `<dialog>` on the right edge) in `CoordinatorRecords.tsx` | The only sheet-style modal; header is sticky but the footer is not; a different pattern from all the others. |
| 14 | Student profile and history | Student profiles, "View history" | Coordinator | Read a student's profile and history | native `<dialog>` in `StudentHistory.tsx` | Heading "Student profile and history" with a bordered Close button; plain paragraphs instead of a field layout; 800 px; read-only. |
| 15 | Regenerate recommendation batch | Recommendations, "Regenerate batch" | Coordinator | Run recommendations for a program's students and show progress | native `<dialog>` in `RecommendationBatch.tsx` | Long-running action; buttons are not in a fixed footer; the closing rule while running is correct. |
| 16 | Certify pending hours | Attendance and hours, "Review pending logs" | Supervisor | Review the pending time logs and certify them in one step | native `<dialog>` in `SupervisorMonitoring.tsx` | Long list of logs inside the body; the Certify and Cancel buttons scroll away with the list. |

### 9.2 Panels and popovers (menus, not forms)

| # | Item | Opens from | Role | Notes |
|---|---|---|---|---|
| 17 | Notifications popover | Header bell | All portal roles | Own `role="dialog"` popover; closes on outside click and Esc. Already themed. |
| 18 | Account menu | Header avatar | All | Menu with sign-out. Already themed. |
| 19 | Theme menu | Header sun or moon icon | All | Menu. Already themed. |
| 20 | Chat panel | Header chat icon | Student, coordinator, supervisor | Floating, non-modal panel. Already themed. |
| 21 | Navigation drawer | Header menu button on phones | All | Drawer with scrim. Already themed. |
| 22 | Map marker popups | Accessibility map markers | Student, coordinator, dean | Leaflet popups; not forms. |

### 9.3 Filament panel (administrator only)

The Filament panel admits administrators only. Rows marked "not reachable" belong to resources that require the coordinator role, so no one can open them today (see section 4, item 4).

| # | Modal | Opens from | Role | What it does | Current problems |
|---|---|---|---|---|---|
| 23 | Withdraw enrollment | Student Enrollments table, "Withdraw enrollment" | Admin | Confirmation plus a required reason; keeps the records | Filament default layout (now maroon); the confirmation text does not name the student. |
| 24 | Review (requirements) | Requirements table, "Review" | Needs a review permission the administrator does not hold | Set status and comments | No heading set (default "Review"). |
| 25 | Placement decision | Allocations table | Not reachable (coordinator resource) | Approve or reject a proposal | Long form without sections. |
| 26 | Generate cohort proposal | Allocations header | Not reachable | Choose a cohort | None found. |
| 27 | Review recommendation | Recommendations table | Not reachable | Record a judgment | Heading and description already set. |
| 28 | Generate report | Reports header | Not reachable | Choose cohort and report type | Report kinds shown as raw codes. |
| 29 | View snapshot | Reports table | Not reachable | Read a report snapshot | Snapshot shown as pipe-separated text in a disabled box. |
| 30 | Approve report | Reports table | Not reachable | Confirmation | Does not name the report. |
| 31 | Filament built-ins | Table filter, column toggle, user menu | Admin | Dropdown panels | Follow the Filament theme; not changed. |

### 9.4 Common problems found

1. **Four implementations:** the shared `Modal`, `AdminFormDialog`, a hand-built overlay, and native `<dialog>` in ten places. Widths range from about 400 to 800 px with no rule.
2. **No consistent structure:** most put the title and close control in the body, so the header scrolls away; none has a fixed footer; the primary button is not always on the right.
3. **Data loss:** three forms close on a backdrop click, and none asks "Discard changes?" when closed with edits.
4. **No success feedback:** `sonner` is installed but unused.
5. **Focus:** native dialogs trap and restore focus; the custom overlay (7) does not.
6. **Role difference:** the same form is a modal for administrators and an inline block for coordinators and supervisors (items 4 to 6).
7. **Titles and wording:** Title Case ("Add Term"), bare labels ("Edit MOA", "Requirement details"), "+" in button text, a bordered "x" text close button.
8. **Destructive actions:** only chat disable, competency removal and Filament withdraw use a confirmation, and competency removal and withdraw do not name the item.
9. **Long dialogs** (12, 15, 16) have no sticky footer, so actions scroll out of view.
10. **Validation:** forms rely on browser `required` and show server errors at the top of the modal rather than under each field.

### 9.5 Cannot be fully tested without submitting

Everything that writes data: items 3 to 9, 12, 13, 15, 16 and 23. Items 1 (disable chat) and 8 (remove competency) are destructive. I will open each one, check layout and Cancel only, and list these for you to submit yourself.
