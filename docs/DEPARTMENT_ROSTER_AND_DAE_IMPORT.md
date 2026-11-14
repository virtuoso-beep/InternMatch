# Department roster and Accountancy source import

Confirmed by the user on November 2, 2026. The active catalog includes only:

| Department | Program | Coordinator | Email | Required hours |
| --- | --- | --- | --- | --- |
| DAE — Accounting Education | BSA — BS Accountancy | Joe Mari Flores | jm.flores@umindanao.edu.ph | 400 |
| DCJE — Criminal Justice Education | BSCRIM | Jun Mark Asis | jm.asis@umindanao.edu.ph | Awaiting confirmation |
| DCE — Computing Education | BSIT | Richard Vincent Misa | r.misa@umindanao.edu.ph | 486, previously confirmed |
| DCE — Computing Education | BSIT | Benjamin Mahinay | b.mahinay@umindanao.edu.ph | Same BSIT program record |
| DCE — Computing Education | BSCS | Iris Mae Mendoza | i.mendoza@umindanao.edu.ph | 350, user confirmed November 2, 2026 |

Hours belong to each program. Enrollment copies that program's confirmed hours into its own required minutes; changing a program does not rewrite historical enrollment requirements. Importing BSA hours never assigns them to other programs.

The user confirmed BSCS requires 350 hours on November 2, 2026. BSIT remains 486 hours. The courses were described as almost similar; this does not specify which competency, document, or evaluation settings should be copied, so those settings remain independent.

`internmatch:reconcile-departments --apply` retires excluded programs by setting them inactive and recoverably retires excluded coordinator accounts. Historical records remain intact. Richard's existing account ID and password are preserved while his email is corrected. New accounts receive distinct random passwords saved in private local storage, never committed or emailed automatically. The ordinary account seeder no longer recreates Richard's former example.com account.

Both assigned BSIT coordinators can access their program's conversations and receive notifications. Program assignment and role changes now support multiple coordinators.

Student/coordinator conversations stay within the enrollment's program. BSIT students and coordinators cannot read or send messages in BSCS/BSA/BSCRIM conversations, and other-program users receive no notifications. Students see only their own conversations, not classmates' conversations. Existing placement-supervisor access remains limited to that supervisor's assigned active placement.

## Supplied Accountancy workbook

Source: Copy of InternMatch_Accountancy_Practicum_Coordinator_Data_Template.xlsx, supplied by the user as the export of the linked Google Sheet.

The source identifies the degree as **BS Accountancy**, so the existing BSA code is retained. References to Management Accounting in host acceptance lists do not add BSMA to the active catalog.

Extraction: `deploy/extract-accountancy.py SOURCE.xlsx OUTPUT.json`. The extractor reads the workbook without changing it, excludes template legends, and retains source sheet/row references and the file SHA-256. Keep its output private because it contains student identifiers.

Import: `php artisan internmatch:import-accountancy OUTPUT.json --apply`. Without `--apply`, the command only previews counts and hours. Repeating the import does not duplicate source rows, hosts or requirement templates.

Imported operational data:

- BSA required internship hours: 400.
- Ten host establishments, with source-local codes prefixed `DAE-`.
- Three requirement templates, retaining the supplied compound requirement wording and before/during internship timing.

Archived source data: 10 students, 10 hosts, 10 opportunities, 10 placements, 10 monitoring records, 10 evaluations, 3 requirements, 3 placement criteria, 7 procedures, and 16 availability entries (89 rows). Stored in `coordinator_source_rows` with provenance and review notes.

Historical records need reconciliation before operational promotion:

- Student names and login emails are absent. No student identities or credentials were invented.
- Several opportunity IDs are blank. Placement records repeatedly reference OPP-001 even where the host differs from OPP-001's host.
- Academic term boundaries and opportunity publication periods are not supplied precisely enough to create a new live opportunity cohort.
- Monitoring contains aggregate hours, not dated daily time logs or uploaded evidence.
- Evaluations contain qualitative results, without a numeric rating scale or named evaluator. No scores, approvals, or training labels were invented.
- MOA status and expiry are reported, but agreement references, effective dates and files are absent. Host descriptions retain the report; the import does not fabricate verified agreements.

The source remains available for completing these mappings. The original workbook is unchanged. A pre-change database backup is retained privately in `.local/before-dae-roster-2026-11-02.sql`.
