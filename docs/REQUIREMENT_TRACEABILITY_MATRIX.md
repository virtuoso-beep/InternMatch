# Requirement traceability matrix

Source: supplied capstone DOCX, functional and non-functional requirements. Updated 2026-09-18. No end-to-end FR is accepted yet. `Partial` identifies existing implementation, not verification. Test execution evidence belongs in DEVELOPMENT_STATUS.md.

| Requirement | Feature | Backend | Frontend | Database | AI/ML | Test | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| FR-01 | Authentication / authorization | User, Role, policies; session implementation in progress | Login integration in progress | users, sessions | — | Existing policy suite; new HTTP tests planned | [~] PARTIALLY IMPLEMENTED |
| FR-02 | Student profiles | Student, UserProfile, enrollment relations | Prototype profile editor | students, user_profiles, student_enrollments | — | Schema relations only | [~] DISCONNECTED |
| FR-03 | Host management | HostEstablishment, Moa, scoped policy | Prototype company/host views | hosts, MOAs, supervisor membership | — | Policy/schema tests | [~] DISCONNECTED |
| FR-04 | Opportunities | Opportunity model; CRUD absent | Local-state opportunity form | opportunities, competency pivots, interests | — | Schema tests only | [~] DISCONNECTED |
| FR-05 | Competency/task embeddings | Competency/evidence models only | Prototype competencies | Text/competency records; embedding provenance absent | Missing E5 encoder | Missing | [ ] MISSING |
| FR-06 | Cosine recommendations | No recommendation endpoint/service | Static percentages | Recommendation runs/results absent | Missing | Missing | [ ] MISSING |
| FR-07 | Ranking / learned reranking | No ranking integration | Static ranking | No labeled judgment dataset | No trained model or validated fallback | Missing | [!] BLOCKED: labels/rules |
| FR-08 | Explanation | No factor snapshot | Prototype explanation | Explanation provenance absent | Missing | Missing | [ ] MISSING |
| FR-09 | Coordinator judgment capture | No judgment workflow | Missing | Placement decisions are not training judgments | Missing training/evaluation | Missing | [ ] MISSING |
| FR-10 | Cohort allocation / geography | Program terms only | CSS map/proposal prototype | No proposal/run entities; coordinates exist | Allocation/Haversine absent | Missing | [ ] MISSING |
| FR-11 | Assignment decisions | PlacementPolicy; no transactional action | Local approval/override state | placements, placement_decisions | — | Scoped policy/schema tests | [~] PARTIALLY IMPLEMENTED |
| FR-12 | Monitoring | Models/policies; no actions | Static hours/reports/requirements | time_logs, journals, submissions/reviews | — | Schema/policy tests | [~] DISCONNECTED |
| FR-13 | Supervisor evaluations | EvaluationPolicy; no submission service | Prototype evaluation forms | rubrics, criteria, evaluations, scores | — | Schema/policy tests | [~] DISCONNECTED |
| FR-14 | Progress/risk flags | No rule execution | Static risk summaries | monitoring_flags, configurable rules column | Rule-based, not predictive ML | Missing boundary tests | [~] PARTIALLY IMPLEMENTED |
| FR-15 | Notifications | No event delivery | Static notifications | No database notifications migration | — | Missing | [ ] MISSING |
| FR-16 | Dashboards / analytics | No aggregations | Five role prototypes | Underlying domain data partial | No recommendation metrics | Missing scope/aggregate tests | [~] DISCONNECTED |
| FR-17 | Reports | No scoped report queries/export service | Prototype exports | Domain records partial | No recommendation report data | Missing | [~] DISCONNECTED |
| FR-18 | Audit trail | Placement decision records only | Static audit list | No general audit table | Judgment/model provenance absent | Missing append-only/access tests | [~] PARTIALLY IMPLEMENTED |
| FR-19 | Backup/recovery | No operational procedure/automation | Prototype backup notice | No verified backup/restore | Model artifacts also need recovery | Missing restore rehearsal | [ ] MISSING |

| Requirement | Acceptance | Evidence / dependency | Status |
| --- | --- | --- | --- |
| NFR-01 | Standard retrieval <=5s; recommendations <=8s under defined pilot conditions | Pilot size/concurrency/hardware and cold/warm model conditions pending | [!] BLOCKED |
| NFR-02 | Errors preserve consistent records | Transactions, rollback tests and failure injection required | [~] NEEDS TESTING |
| NFR-03 | Available during scheduled pilot sessions | Schedule and uptime monitoring pending | [ ] NOT VERIFIED |
| NFR-04 | Auth, roles, hashed passwords, TLS | Password casts/policies present; sessions in progress; TLS absent | [~] PARTIAL |
| NFR-05 | Role-appropriate UI and approved five-point usability evaluation | UI prototype exists; approved instrument/UAT pending | [R] NEEDS REVIEW |
| NFR-06 | Identifiable independent components | Laravel/frontend separated; AI service missing | [~] PARTIAL |
| NFR-07 | Pilot Chrome/Edge/Firefox and mobile/desktop coverage | Browser/screen matrix and actual execution required | [ ] NOT VERIFIED |
| NFR-08 | Approved pilot dataset without schema changes | Dataset not supplied | [!] BLOCKED |
| NFR-09 | Validated information and record relationships | FK/check schema exists; HTTP validation incomplete | [~] NEEDS TESTING |
| NFR-10 | Approved schedule and authorized restoration | Schedule/retention/restore evidence absent | [!] BLOCKED |

Critical invariants: recommendations do not assign students; only scoped coordinators decide placements; administrator access does not confer academic decision authority; distance is student-to-host straight-line kilometers; no travel-time claim; no learned ranking claim without sufficient reviewed labels and held-out evaluation.
