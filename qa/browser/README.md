# Browser checks and end-to-end flows

Playwright scripts that drive the running app the way a person would. They need the local Docker stack
(`docker compose up -d`): portal on http://localhost:8080, management panel on http://localhost:8000/manage,
Mailpit on http://localhost:8025. They are not part of CI, because they need the stack, seeded data and
real browser sessions.

## Setup

```bash
cd qa/browser
npm install                 # installs Playwright 1.62.1
npx playwright install msedge   # or set the channel in harness.cjs; any Chromium works
```

To reuse a Playwright copy installed elsewhere, set `PLAYWRIGHT_MODULE` to its folder.

## Accounts

Credentials are never stored in these files. Each script reads them from environment variables at run time:

| Variable | Account |
|---|---|
| `QA_ADMIN_EMAIL`, `QA_ADMIN_PW` | administrator |
| `QA_DEAN_EMAIL`, `QA_DEAN_PW` | dean |
| `QA_COORDINATOR_EMAIL`, `QA_COORDINATOR_PW` | practicum coordinator |
| `QA_SUPERVISOR_EMAIL`, `QA_SUPERVISOR_PW` | host supervisor (synthetic) |
| `QA_STUDENT_EMAIL`, `QA_STUDENT_PW` | student (synthetic) |
| `QA_CONSENT_EMAIL` | the one synthetic `@students.internmatch.test` account whose privacy notice may be acknowledged |

Use synthetic accounts and records only. On Windows Git Bash, run with `MSYS_NO_PATHCONV=1` so arguments such
as `/coordinator` are not rewritten into Windows paths.

## Read-only checks

| Script | What it checks |
|---|---|
| `modal-sweep.cjs <role> [names]` | Every modal of a role (see `plans.cjs`) at 1440 and 375 px, light and dark: title, footer layout, focus trap, Escape, focus return, discard prompt, field errors, full-screen sheet on phones. Role is `admin`, `coordinator`, `supervisor`, `dean` or `student`. |
| `page-times.cjs <role>` | Load time and API request count per list page (each page twice; the second visit is the warm one). `PT_PAGES=/a,/b` limits the pages. |
| `sign-in-states.cjs` | Sign-in validation, wrong credentials, throttle countdown (uses a made-up address), server unreachable, return to the requested page. |
| `sign-in-redirect.cjs` | A signed-out visit to a student page returns there after sign-in. |
| `password-reset.cjs` | Forgot-password answer is the same for any address, the e-mail arrives in Mailpit, the reset form validates. It never completes a reset, so no password changes. |

## End-to-end flows (these change synthetic data)

Run in order; each continues from the previous one. They use the synthetic term `SYN-QA-2026` (BSA) and the
roster in `fixtures/qa-roster.xlsx`.

| Script | Flow |
|---|---|
| `flow1-roster-activation.cjs` | Coordinator imports the roster; activation e-mails arrive; one account is activated (`activate.cjs`). |
| `flow2-requirements.cjs` | Requirement configured; student uploads; coordinator approves and returns with a reason; student re-uploads. |
| `flow3-matching-placement.cjs` | Capacity, MOA and opportunity created; student competencies and recommendations; academic review, proposal batch, placement approval, start of deployment. |
| `flow4-logs-journals.cjs` | Time logs and journals; supervisor certifies and returns; student resubmits. |
| `flow5-completion.cjs` | Final evaluation; certify hours; end deployment; supervisor chat becomes read-only (backend 403). Seeds earlier certified logs to reach the required hours. |
| `flow6-dashboards.cjs` | Dean and admin dashboards with the synthetic switch off and on. |

Screenshots and JSON results are written to `out/` (ignored by git).
