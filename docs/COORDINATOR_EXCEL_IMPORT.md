# Coordinator Excel import

Import Excel is an inline action in Student Profiles, Host Establishments, Opportunities, and Agreements. Each action imports its own module's records. There is no separate import portal.


## Student account workflow — November 13, 2026

Use **Student Profiles → Import Students → Download Excel template**. The seven columns are Student ID, First Name, Middle Name, Last Name, Program, Academic Term, and Year Level. Student ID is formatted as Text in the template. Keep exactly six digits, including leading zeros. Middle Name is optional. Legacy Full Name columns are also accepted. Import one assigned active program and academic term per batch; values in the file must match the selected program code/name and term code/name. An optional enrollment date may be supplied; otherwise the import date is recorded.

Preview reports new accounts, recognized accounts, new enrollments, unchanged duplicates, and invalid/conflicting rows. For active enrollments, only missing year-level or supplied enrollment-date facts are filled. Existing values and closed enrollment history are preserved; corrections to recorded facts use enrollment management. A repeated identity with conflicting details blocks confirmation until corrected or skipped. Existing database uniqueness constraints cover Student IDs, student/user links, non-null emails, program terms, and student enrollments. Historical non-six-digit IDs are not rewritten; the import requires valid six-digit IDs and flags conflicts for review.

A new account is active and receives a cryptographically random temporary password, stored only as a password hash. An encrypted, coordinator-owned credential handoff expires after 15 minutes. It is atomically consumed on download and its ciphertext cleared; the scheduler also clears expired ciphertext every minute. The downloadable text file preserves leading zeros. Do not share the entire list with students; deliver each student only their own credentials. Import results, workbook history and audit entries never contain generated plaintext passwords. Existing passwords are never reset by reimporting. The same import confirmation is idempotent and cannot recreate a consumed credential handoff.

Students sign in at `/auth` with their six-digit Student ID and password; existing email sign-in continues to work. A temporary-password account can retrieve its session and change its password but cannot access protected portal APIs. The frontend also gates all student pages, including after refresh. The replacement password needs at least 12 characters, mixed case, a number and a symbol, and must differ from the temporary password.

Student Profiles has separate enrollment and placement filters, assigned program and academic term filters, name/ID/email/term/host search, sorting, and 20 records per page. Click a student's name to view their profile and scoped enrollment/placement history. No current placement is labeled **Not Placed**, with **Not assigned** as the host. **Import History** shows owned batches, row outcomes and any remaining credential download. Revoked program access prevents access to scoped student history and credential downloads.

The additive migration `2026_11_12_000001_student_import_credentials` is present in the local database. A fresh local backup was created as `20261112T165841Z-9485cc66` (November 13 in Manila). Verification imports use only `internmatch_testing`. The live database was confirmed as `internmatchlaravel`, with 33 accounts and 10 student records; no live test students were added in this work.

Browser fixture checks passed for pagination, ID search, enrollment filtering, student history, seven-column mapping, confirmation gating, credential download, import history after refresh and mandatory password change after refresh. Backend feature tests cover real database writes, both Excel formats, password hashes, duplicate/reimport/new-term behavior, permission checks, expired/one-time handoffs, and first-login restrictions. A private source-workbook test is skipped unless its file is supplied through `COORDINATOR_REFERENCE_WORKBOOK`.

## User workflow

1. Sign in as an active practicum coordinator and open the relevant module.
2. Click **Import Students** in Student Profiles, or **Import Excel** in the other modules, choose an `.xlsx` or `.xls` file, and click **Upload Excel**.
3. Check the suggested worksheet and column mappings. Select another worksheet or map unfamiliar column names manually.
4. Click **Build editable preview**. Select your assigned program and the actual academic term. Agreements do not require a term.
5. Expand rows to correct missing values or skip a row. Student imports do not assign hosts, opportunities, or placements. Other modules retain their existing host matching and conflict controls.
6. Click **Validate and detect duplicates**. Resolve or skip every row with an error. Any edit invalidates the previous validation.
7. Check the confirmation box and click **Confirm import**. The results report successful, updated, skipped, and failed source rows. A failed row is rolled back.
8. Review the refreshed records in the current module. Student imports create accounts, profiles and enrollments only. Use recommendation and placement approval workflows separately. HTE imports appear in the Accessibility Map list. Only supplied coordinates produce map pins.

## Required information and review

| Module | Required information | Result |
| --- | --- | --- |
| Students | Six-digit Student ID; First Name; optional Middle Name; Last Name; Program; Academic Term; Year Level. Email is optional. | New students receive active accounts and a one-time credential handoff. Returning students retain their accounts and passwords. New terms create new enrollments with program OJT minutes and requirement templates. |
| HTEs | Existing scoped HTE, or HTE name, address, city/location | Existing HTEs are reused; new HTEs are linked to the selected program. Coordinates are optional. Capacity is recorded only when supplied. |
| Opportunities | HTE, title, duties, slots, term, program, confirmed HTE capacity | New opportunities are drafts. Review and publish through the existing opportunity form. |
| Agreements | HTE, actual agreement reference, program; actual dates where available | New agreements are drafts. Attach the executed document and verify coverage/dates before activation through the agreement workflow. |

Student imports never create placements or placement proposals. Existing placements and historical completed/withdrawn enrollments remain untouched.

Missing student IDs require review. Identical names with different IDs are separate students. Repeated IDs with conflicting identity or placement data are flagged. Distinct terms are imported separately. Existing account, enrollment, HTE, opportunity and agreement details are preserved; conflicts require an explicit keep-existing selection or corrected input. Only supported blank address/coordinate fields can be filled with the explicit fill-missing option.

Hosts already belonging outside the coordinator's access scope need administrator program assignment before reuse. An existing student with records outside the coordinator's assigned programs also requires administrator review.

## Accounting Education workbook

The supplied `Copy of InternMatch_Accountancy_Practicum_Coordinator_Data_Template.xlsx` has twelve sheets with preformatted blank rows. Blank formatting is excluded from record counts. Student, HTE, opportunity, and placement sheets each contain ten source rows.

Verify student names and six-digit Student IDs before account creation; email is optional. Historical placement status is not evidence of coordinator approval in this system. Source opportunity codes are not database IDs; the coordinator must resolve the supplied opportunity-to-HTE links. Agreement references/documents and coordinates must not be invented. Unique reference rows are joined only by Student ID or source HTE ID, never by names.

The archived BSA HTEs are linked to BSA only when both their prefixed source code and name match the archived source row. This adds program visibility without changing existing HTE details or inventing capacity.

## Implementation and operations

- Endpoints: `GET /api/v1/coordinator-imports/reference`, `POST /api/v1/coordinator-imports`, and `POST /api/v1/coordinator-imports/{id}/map`, `/preview`, `/confirm`, `/credentials`; `GET /api/v1/coordinator-imports/history` and `/student-template`.
- Every endpoint requires an active coordinator; batches are private to their uploader. Imports are restricted to the coordinator’s assigned active programs.
- Confirmation rechecks ownership, program assignment, preview revision and current database conflicts. Confirming a completed batch returns its original result.
- Imports are serialized with a database advisory lock; each row saves transactionally. Successful results are audited. No outgoing emails are sent by import.
- Limits: 10 MB upload, 25 sheets, 100 columns, 10,000 nonempty workbook rows, 10,000 physical rows per sheet, 25,000 physical rows total, 1,000 records per confirmed batch, 50 MB expanded XLSX content. Split larger workbooks/batches.
- Formula cells must be replaced with verified values; import does not evaluate formulas. Date formats and zero-padded Student IDs are retained.
- PHPSpreadsheet is declared in Composer. `backend/Dockerfile` installs GD and loads `imports.ini` (10 MB upload, 12 MB POST, 256 MB memory). Rebuild backend/worker/scheduler images for fresh deployments, then run the additive migrations.
- Database tests use `internmatch_docker_testing`. Never run reset migrations against the live database.

## Verification

Feature tests cover XLSX/XLS reading, formatted templates, ID/date preservation, formula handling, module-specific records, duplicate prevention, identity conflicts, ownership, revoked scope, unchanged passwords, rollback, module visibility, and coordinator-only placement decisions. Existing host, opportunity, agreement, allocation and map regression tests were also run. Frontend TypeScript and production build checks passed during implementation.

Live application migrations were applied after backup `20261103T220212Z-0790dafb`. Domain-record import writes were exercised in the isolated test database. The supplied workbook was used for parsing and preview checks; unresolved historical records were not confirmed into live accounts or placements.

On November 4, 2026, the expanded import suite passed **13 tests / 227 assertions**, including the actual private coordinator workbook through upload, mapping, preview and rejection of unresolved rows in all four modules. Generated XLSX and XLS fixtures also completed upload through database save and repeat import. The four allocation tests passed, including updating an imported pending placement in place. Frontend production build, TypeScript validation, and HTTP checks for the backend health and sign-in page passed.

This run found and fixed excess memory use from creating absent spreadsheet cells while scanning formatted rows. Four consecutive reads of the actual twelve-sheet workbook now peak at 16 MB in the standalone parser check, within the 256 MB PHP limit. Final browser interaction verification remains incomplete because computer-use could not reliably determine the Windows browser URL; automated API and database checks do not substitute for that visual acceptance check.

Final November 13 checks: StudentAccountImportTest passed 7 tests / 130 assertions; AuthenticationTest and AuthControllerTest passed 36 tests / 177 assertions. The revised coordinator import suite passed its 12 available tests; the private-source workbook case remains conditional. StudentPortalTest also passed. TypeScript, targeted ESLint, production build, and browser fixture workflow checks passed.
