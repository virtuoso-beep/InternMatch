# Bugs and blockers

| ID | Issue | Severity | Area | Assigned To | Status | Solution / evidence |
| --- | --- | --- | --- | --- | --- | --- |
| BUG-001 | Login accepts arbitrary credentials; portals are unguarded | Critical | Authentication | Angeli / Trisha | In progress | Replace timeout redirect with server session; deny inactive/wrong-role access; verify MySQL + browser |
| BUG-002 | Demo state and success messages imply persisted operations | High | Frontend | Trisha | Open | Integrate each operation; clearly disclose remaining prototype views |
| BUG-003 | MySQL example credentials rejected | High | Database | Twinkle | Blocked | Error 1045. Configure local credentials; isolated disposable test database authorized |
| BUG-004 | SQLite test config conflicts with MySQL-only requirement | High | QA | Twinkle | In progress | Dedicated MySQL connection and destructive-test guard |
| BUG-005 | Docker engine not running | High | Infrastructure | Shared | Blocked | Docker Desktop engine pipe absent outside sandbox too |
| BUG-006 | FastAPI/E5/ML integration absent | High | AI | Unassigned | Open | Implement service, real embedding tests and authenticated Laravel client |
| BUG-007 | Filament 5 absent | High | Coordinator/admin | Twinkle | Open | Implement documented panel, scoped resources and actions |
| BUG-008 | Illustration claims geographic and travel-time results | High | Geospatial | Trisha | Open | Leaflet + Haversine, actual student origin; remove travel-time claims |
| BUG-009 | Training data and approved sufficiency rules absent | High | ML | Unassigned | Blocked | Gather coordinator suitable/not-suitable pair judgments; split evaluation data; disclose fallback |
| BUG-010 | Production host/domain/TLS and access unspecified | High | Deployment | Shared | Blocked | User must identify target before deployment |
| BUG-011 | Monitoring thresholds/pilot conditions/backup schedule unspecified | Medium | Requirements | Angeli | Needs review | Obtain approved settings; do not infer from prototype numbers |
| BUG-012 | Composer/npm sandbox installation failures | High | Environment | Shared | Resolved | Approved retries installed lockfiles; no dependency upgrades |
| BUG-013 | No real endpoint/E2E/performance evidence | High | QA | Shared | Open | Add tests and retain actual command outcomes |
| BUG-014 | Program chair represented by combined dean role | Medium | RBAC | Angeli | Needs review | Document uses chair/dean; confirm whether separate institutional scopes needed |

No production data was reset. Refer to DEVELOPMENT_STATUS.md for latest test evidence.
