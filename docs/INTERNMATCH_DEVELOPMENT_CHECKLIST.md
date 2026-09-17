# InternMatch development checklist

Updated 2026-09-18. Status: [ ] NOT STARTED; [~] IN PROGRESS; [x] COMPLETED and verified; [!] BLOCKED; [R] NEEDS REVIEW. Baseline code presence is not completion. Owners follow the user-confirmed roles. All acceptance items below require recorded test evidence.

## PHASE 0 — PROJECT PREPARATION

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P00-01 | Read capstone documentation | Angeli | [~] | Source documents and repository | NOT VERIFIED; execute acceptance test and record result |
| P00-02 | Analyze system architecture | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-03 | Analyze provided diagrams/images | Angeli | [~] | Source documents and repository | NOT VERIFIED; execute acceptance test and record result |
| P00-04 | Inspect existing repository | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-05 | Inspect existing database | Angeli | [!] | Source documents and repository | NOT VERIFIED; execute acceptance test and record result |
| P00-06 | Inspect existing frontend | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-07 | Inspect existing backend | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-08 | Inspect existing AI service | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-09 | Inspect Docker configuration | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-10 | Identify missing features | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-11 | Identify broken features | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-12 | Identify integration problems | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-13 | Create initial audit | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |
| P00-14 | Create development checklist | Angeli | [x] | Source documents and repository | Static audit: INITIAL_SYSTEM_AUDIT.md |

## PHASE 1 — DEVELOPMENT ENVIRONMENT

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P01-01 | Install PHP 8.3+ | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-02 | Install Composer | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-03 | Install Node.js | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-04 | Install npm | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-05 | Install Python 3.12 | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-06 | Install Docker | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-07 | Configure Git | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-08 | Configure GitHub | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-09 | Configure VS Code | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-10 | Configure Laravel | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-11 | Configure MySQL | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-12 | Configure FastAPI | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-13 | Configure frontend | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |
| P01-14 | Verify all tools | Twinkle | [ ] | Phase 0 | NOT VERIFIED; execute acceptance test and record result |

## PHASE 2 — DATABASE

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P02-01 | Database creation | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-02 | Users | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-03 | Roles | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-04 | Students | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-05 | Host establishments | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-06 | Internship opportunities | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-07 | Competencies | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-08 | Tasks | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-09 | Applications/placements | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-10 | Recommendations | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-11 | Recommendation evaluations | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-12 | Cohorts | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-13 | Assignments | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-14 | Monitoring records | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-15 | Supervisor evaluations | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-16 | Notifications | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-17 | Audit logs | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-18 | Required geographic data | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-19 | Other entities required by the documentation | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-20 | Foreign keys verified | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-21 | Relationships verified | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-22 | Constraints verified | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-23 | Indexes reviewed | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-24 | Seed data created | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-25 | Database migrations tested | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |
| P02-26 | Database reset tested | Twinkle | [~] | MySQL test environment | NOT VERIFIED; execute acceptance test and record result |

## PHASE 3 — AUTHENTICATION & RBAC

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P03-01 | Student login | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-02 | Host Supervisor login | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-03 | Practicum Coordinator login | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-04 | Program Chair/Dean access | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-05 | System Administrator access | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-06 | Registration where required | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-07 | Password handling | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-08 | Session handling | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-09 | Role-based permissions | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-10 | Unauthorized-access protection | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-11 | Logout | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P03-12 | Route protection | Angeli | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 4 — STUDENT PORTAL

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P04-01 | Student dashboard | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-02 | Student profile | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-03 | Personal information | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-04 | Program information | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-05 | Competencies | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-06 | Tasks/skills | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-07 | Internship requirements | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-08 | Recommendation results | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-09 | Recommendation explanations | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-10 | Placement status | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-11 | Hours/progress monitoring | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-12 | Notifications | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P04-13 | Relevant reports | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 5 — HOST ESTABLISHMENT PORTAL

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P05-01 | Host profile | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-02 | Establishment information | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-03 | Location | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-04 | Internship opportunities | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-05 | Capacity | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-06 | Required competencies | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-07 | Required tasks | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-08 | Agreement/status information | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-09 | Assigned interns | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-10 | Supervisor evaluation | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-11 | Monitoring information | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P05-12 | Relevant notifications | Trisha | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 6 — COORDINATOR / ADMIN

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P06-01 | Coordinator dashboard | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-02 | Student management | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-03 | Host management | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-04 | Internship opportunity management | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-05 | Competency/task management | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-06 | Recommendation management | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-07 | Recommendation review | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-08 | Recommendation evaluation | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-09 | Cohort allocation | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-10 | Assignment management | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-11 | Approval | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-12 | Modification | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-13 | Override | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-14 | Rejection | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-15 | Monitoring | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-16 | Reports | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-17 | Analytics | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-18 | Notifications | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P06-19 | Audit trail | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 7 — SEMANTIC MATCHING

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P07-01 | Input preprocessing | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-02 | Embedding generation | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-03 | Student embedding | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-04 | Host requirement embedding | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-05 | Cosine similarity calculation | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-06 | Similarity score storage/handling | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-07 | Recommendation ranking | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-08 | Error handling | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P07-09 | API integration | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 8 — LOGISTIC REGRESSION

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P08-01 | Training dataset exists | Unassigned (AI); Twinkle (API) | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-02 | Labels are defined | Unassigned (AI); Twinkle (API) | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-03 | Data preprocessing exists | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-04 | Training process works | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-05 | Model validation works | Unassigned (AI); Twinkle (API) | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-06 | Model can be saved/loaded | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-07 | Prediction endpoint works | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-08 | Laravel can call the model | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-09 | Results are explainable | Unassigned (AI); Twinkle (API) | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P08-10 | Fallback exists when insufficient labeled data is available | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 9 — GEOSPATIAL ANALYSIS

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P09-01 | Coordinates validated | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P09-02 | Haversine calculation tested | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P09-03 | Distance stored/calculated correctly | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P09-04 | Invalid coordinates handled | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P09-05 | Distance shown where required | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P09-06 | Map visualization works | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P09-07 | Leaflet integration works | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 10 — RECOMMENDATION ENGINE

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |

## PHASE 11 — COHORT ALLOCATION

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P11-01 | Student grouping/cohort handling | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-02 | Host capacity | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-03 | Program requirements | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-04 | Recommendation scores | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-05 | Geographic distance | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-06 | Agreement/status | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-07 | Allocation logic | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-08 | Conflict checking | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-09 | Coordinator review | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-10 | Coordinator modification | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-11 | Coordinator override | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-12 | Approval | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P11-13 | Rejection | Unassigned (AI); Twinkle (API) | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 12 — MONITORING

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P12-01 | Internship hours | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P12-02 | Requirements | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P12-03 | Progress | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P12-04 | Supervisor evaluation | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P12-05 | Risk/progress flags | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P12-06 | Monitoring dashboard | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P12-07 | Reports | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P12-08 | Notifications | Twinkle | [~] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 13 — NOTIFICATIONS

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P13-01 | Recommendation notification | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P13-02 | Placement/assignment notification | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P13-03 | Approval notification | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P13-04 | Rejection notification | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P13-05 | Monitoring notification | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P13-06 | Relevant status updates | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 14 — REPORTS & ANALYTICS

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P14-01 | Student reports | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P14-02 | Placement reports | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P14-03 | Internship monitoring reports | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P14-04 | Recommendation statistics | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P14-05 | Cohort/allocation information | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P14-06 | Relevant coordinator analytics | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P14-07 | Export functionality if required by the documentation | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 15 — AUDIT TRAIL

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P15-01 | Login/security events | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-02 | Student changes | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-03 | Host changes | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-04 | Recommendation actions | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-05 | Placement decisions | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-06 | Assignment changes | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-07 | Overrides | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-08 | Approvals | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-09 | Rejections | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P15-10 | Administrative actions | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 16 — FRONTEND ↔ BACKEND INTEGRATION

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P16-01 | GET requests | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-02 | POST requests | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-03 | PUT/PATCH requests | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-04 | DELETE requests where applicable | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-05 | Authentication | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-06 | Validation | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-07 | Error responses | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-08 | Loading states | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-09 | Empty states | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-10 | Success states | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P16-11 | Error states | Trisha | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 17 — LARAVEL ↔ FASTAPI INTEGRATION

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P17-01 | FastAPI starts | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-02 | Health endpoint works | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-03 | Laravel can reach FastAPI | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-04 | Authentication/security where required | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-05 | Request validation | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-06 | Embedding request | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-07 | Similarity calculation | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-08 | Logistic Regression request | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-09 | Error handling | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-10 | Timeout handling | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P17-11 | Response validation | Twinkle | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 18 — DOCKER

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P18-01 | Frontend | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-02 | Laravel | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-03 | MySQL | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-04 | FastAPI | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-05 | Nginx | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-06 | Required supporting services | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-07 | Environment variables | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-08 | Volumes | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-09 | Networks | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-10 | Health checks | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P18-11 | Service dependencies | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 19 — TESTING

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P19-01 | Haversine | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-02 | Cosine similarity | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-03 | Recommendation calculations | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-04 | Validation | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-05 | Important backend services | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-06 | Frontend ↔ Laravel | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-07 | Laravel ↔ MySQL | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-08 | Laravel ↔ FastAPI | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-09 | FastAPI ↔ ML model | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-10 | Leaflet/geospatial integration | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-11 | Student workflow | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-12 | Host workflow | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-13 | Coordinator workflow | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-14 | Recommendation workflow | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-15 | Placement workflow | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-16 | Monitoring workflow | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-17 | Unauthorized access | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-18 | Role restrictions | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-19 | Input validation | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-20 | Authentication | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P19-21 | Sensitive configuration | Shared | [ ] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 20 — END-TO-END TEST

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |

## PHASE 21 — DEPLOYMENT

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P21-01 | Production environment variables | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-02 | APP_ENV | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-03 | APP_KEY | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-04 | Database credentials | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-05 | FastAPI URL | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-06 | Frontend configuration | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-07 | API configuration | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-08 | CORS | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-09 | Nginx | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-10 | HTTPS/TLS | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-11 | Database migration | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-12 | Database seed requirements | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-13 | Storage | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-14 | Logs | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-15 | Backups | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-16 | Health checks | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-17 | Restart behavior | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P21-18 | Docker production configuration | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## PHASE 22 — DEPLOYMENT VERIFICATION

| ID | Task | Responsible | Status | Dependency | Verification |
| --- | --- | --- | --- | --- | --- |
| P22-01 | Website loads | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-02 | Frontend loads | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-03 | Login works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-04 | Student portal works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-05 | Host portal works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-06 | Coordinator portal works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-07 | MySQL connection works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-08 | Laravel API works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-09 | FastAPI works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-10 | AI endpoint works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-11 | Recommendation works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-12 | Cosine similarity works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-13 | Logistic Regression works when applicable | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-14 | Haversine calculation works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-15 | Leaflet map works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-16 | Notifications work | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-17 | Reports work | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-18 | Audit trail works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-19 | No critical console errors | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-20 | No critical backend errors | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-21 | No failed API requests | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-22 | Docker containers are healthy | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |
| P22-23 | HTTPS works | Shared | [!] | Prior foundations; see audit sequence | NOT VERIFIED; execute acceptance test and record result |

## Release rule

No FR is complete until implementation, API/UI integration, MySQL tests and relevant browser verification pass. See REQUIREMENT_TRACEABILITY_MATRIX.md and DEVELOPMENT_STATUS.md. Training, institutional UAT, production TLS and recovery rehearsal are independent acceptance gates.
