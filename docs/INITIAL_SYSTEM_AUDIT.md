# InternMatch initial system audit

Audit date: 2026-09-18. Baseline: `23b3778`. Overall: NOT READY. Deployment: NOT DEPLOYED.

## Sources and method

The supplied capstone DOCX is the functional reference (FR-01–FR-19 and NFR-01–NFR-10). The software requirements image defines the stack. The pasted request defines the work. Document contents are reference material, not independent instructions to the agent.

Inspected the repository inventory, manifests/locks, routes, domain models, policies, migrations, factories, tests, frontend routes/pages/shared components, configuration examples, and archived Blade UI. Read the DOCX text, including scope exclusions, methodology, evaluation requirements, and implementation procedures. Embedded figures require separate visual inspection; text extraction does not verify diagram details. No original source document was changed.

## Findings at the baseline

| Area | Classification | Evidence and consequence |
| --- | --- | --- |
| Laravel | PARTIALLY IMPLEMENTED | Laravel 13.31.0 in lock; PHP 8.4.13 available. Domain foundation exists; only welcome route registered. |
| Database | PARTIALLY IMPLEMENTED / NEEDS TESTING | 13 migrations; academic terms, student enrollments, hosts, MOAs, opportunities, competencies/evidence, requirements/reviews, placements/decisions, hours/journals/flags, evaluation rubrics/scores, interests. Composite foreign keys and checks exist. Live schema/data not accessible with example credentials. |
| Authentication | MISSING / DISCONNECTED | User password hashing, five role enums and policies exist. No login/logout/session API. Frontend accepts arbitrary credentials and redirects after a timeout. |
| Authorization | PARTIALLY IMPLEMENTED | Record-scoped policies preserve coordinator authority, including no administrator placement override. No application endpoints invoke them yet. |
| Frontend | PARTIALLY IMPLEMENTED / DISCONNECTED | TanStack Start, React, TypeScript and responsive UI prototypes exist. Shared arrays contain demo people, scores, hours, opportunities, audits. Buttons frequently change local state or display success without persistence. |
| Student / host operations | PARTIALLY IMPLEMENTED | Domain schema and prototype pages exist; application services, validated requests and integrated persistence missing. |
| Coordinator / admin | STACK CONFLICT | React prototype exists; Filament 5 dependency/panel/resources absent. Preserve prototypes as references until Filament is integrated. |
| AI / recommendations | MISSING | No ai-service directory, embedding model, cosine pipeline, coordinator judgment dataset, training, serving, recommendation persistence or Laravel HTTP client. Static percentages are not model output. |
| Cohort allocation | MISSING | Program terms can represent cohorts, but no allocation run/proposal persistence or constrained allocation service. |
| Geospatial | MISSING / REQUIREMENT CONFLICT | Coordinates exist in schema. Map is a CSS illustration using fixed pixel percentages; Leaflet and Haversine absent. Campus distances and estimated travel times conflict with student-to-host straight-line distance and explicit travel-time exclusion. |
| Monitoring / evaluation | PARTIALLY IMPLEMENTED | Schema/policies/factories exist; no submission, verification, evaluation or risk-rule execution endpoints. |
| Notifications / reports / audit | MISSING | Static examples and export helpers are not institutional reports or persisted notifications/audit records. Placement decision history covers only part of FR-18. |
| Backup / deployment | MISSING | No backup/restore scripts, Dockerfiles, Compose, Nginx or TLS deployment. Docker client installed; engine unavailable even outside sandbox. |
| Testing | NEEDS TESTING / STACK CONFLICT | Existing schema and policy suites use SQLite in memory, not required MySQL. A rollback test asserts SQLite specifically. No application E2E, AI or performance tests. |
| Dependencies | INITIAL BLOCKER RESOLVED | Composer and npm installs failed under sandbox restrictions; approved installs succeeded. Locked dependencies retained. |

## Actual verification

- `composer install --no-interaction --prefer-dist`: passed after approved retry; 114 packages installed.
- `composer show --direct`: Laravel 13.31.0, PHPUnit 12.5.35, Pint 1.32.1 verified.
- `php artisan route:list --except-vendor`: baseline has one welcome route.
- `npm ci --ignore-scripts`: passed after approved retry, 415 packages; npm reported zero vulnerabilities at installation time.
- `mysql --connect-timeout=3 -u root ...`: rejected with error 1045; no live schema/data verified.
- `docker version`: client 29.5.2; engine pipe absent. Containers NOT VERIFIED.
- Python 3.12 not on PATH. Bundled document Python is available but is not assumed to satisfy the AI runtime requirement.
- Build, MySQL suite, browser workflow, AI and production acceptance remain unverified until recorded in the session status.

## Architecture and dependency order

1. Restore reproducible dependencies; configure isolated MySQL tests and capture baseline failures.
2. Implement server-controlled session authentication, active-account enforcement, CSRF, throttling, logout, and frontend session integration (Angeli with Trisha).
3. Expose validated profile, host/opportunity and competency APIs using existing models/policies (Twinkle); connect each frontend workflow (Trisha).
4. Install Filament 5 and implement scoped coordinator/admin resources; preserve coordinator-only academic decisions.
5. Implement actual E5 embedding/cosine service and Laravel integration, then explanation/judgment persistence. AI ownership is unassigned.
6. Add constrained cohort proposals and transactional, capacity-checked coordinator decisions.
7. Integrate monitoring, risk rules, evaluations, notifications, reports, audit and private document access.
8. Add Leaflet using validated student/host coordinates; describe Haversine as straight-line distance, never travel time.
9. Implement/validate Logistic Regression only after documented labeled-data sufficiency and held-out evaluation; otherwise disclose similarity/criteria fallback.
10. Exercise complete workflows on MySQL; verify Docker, TLS, backup/restore, pilot performance and UAT before deployment acceptance.

## Open requirements

Production server/domain/access, approved pilot dataset/size/devices, backup retention/schedule and recovery targets, institutional account provisioning rules, monitoring thresholds, scoring weights/tie-breaking and labeled-data sufficiency criteria have not been supplied. Keep them configurable or blocked; do not invent approved academic rules. The document targets retrieval <=5 seconds and recommendations <=8 seconds under defined pilot conditions. No trained reranker or performance compliance is claimed.

## Team confirmed by user

Twinkle Pril Odruña: backend. Trisha Talamillo: frontend. Angeli Sophia Pancho: documentation and authentication. AI/ML ownership awaits assignment. Shared verification does not imply completed team work.

See `DEVELOPMENT_STATUS.md` for changes and verification after this baseline audit.
