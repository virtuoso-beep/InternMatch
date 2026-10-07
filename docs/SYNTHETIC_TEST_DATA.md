# Synthetic testing data

Open http://localhost:8080/auth and use your **original accounts**. The October 6 correction links synthetic records to those accounts without creating accounts or changing account records or passwords.

| Portal / scenario | Email |
|---|---|
| Student: active internship, 40 certified hours, 14 checklist items, journal, sample evaluation and real AI recommendations | student@example.com |
| Coordinator: BSIT cohort, hosts, review, allocation, reports | coordinator@example.com |
| Supervisor: assigned synthetic hosts and original student's placement | supervisor@example.com |
| Dean: BSIT statistics; reports appear after coordinator approval | dean@example.com |
| Administrator: account and academic registries | admin@example.com |

The existing local password verified on October 6 is `password` for these five accounts. It was not reset. If you subsequently change it, use your changed password; rerunning the seed does not reset it. Native management: http://localhost:8000/manage (coordinator or administrator; academic decisions belong to the coordinator).

## Reproduce

```powershell
docker compose exec -T backend php artisan migrate --force
docker compose exec -T backend php artisan internmatch:seed-demo --recommendations
```

The opt-in seed refuses environments other than local/testing. It requires all five active original accounts with their expected roles and fails before seeding if an account is missing. It never creates replacement accounts. Ordinary `db:seed` only seeds reference catalogs. Back up the database before adding fixtures. Never run `migrate:fresh` on the application or retained browser QA databases.

## Included records

For the original student: one fictional BSIT enrollment, one active placement, five verified time logs totaling 40 hours, one journal and one sample evaluation. Shared fixtures include four fictional hosts including one expired agreement, four opportunities, separate BSIT and BSCS capacities, and seven initial report snapshots generated for the original coordinator. Real E5 recommendations are computed with the optional flag, not assigned invented percentages. Expired agreements and full opportunities are excluded from new recommendations.

All 10 BSIT documents and four attendance events from the supplied data document are included. Each seeded submission has an actual PDF labeled SYNTHETIC TEST DOCUMENT. PDOS remains unscheduled in the catalog; synthetic past attendance is not a real schedule announcement. The original student receives a populated testing scenario. The prior empty-checklist scenario remains preserved in historical QA records.

BSIT retains the confirmed 486 hours. The other 15 programs retain their existing settings. No synthetic rubric is made the BSIT institutional default. Sample evaluations explicitly reference a test-only rubric; a real approved rubric is still needed for new institutional evaluations. Synthetic judgments and approvals must not be used as genuine training labels, department approvals or UAT evidence.

The original coordinator and dean are linked to BSIT, and the original supervisor is linked to the four synthetic hosts and the original student's placement. The student sees only their own enrollment. Existing scope links and all prior QA history are retained. Earlier demo accounts were preserved as history; they are not needed for these logins, and the current seeder creates no accounts.

Verification: all five original logins passed at localhost:8080. The student enrollment count changed from zero to one; the coordinator now sees ten retained enrollment records; the supervisor sees the assigned original student. All 31 existing user rows were compared before and after the transactional update and remained identical. Backup: `.local/oct06-before-original-links.sql`. Seeder regression: three tests, 44 assertions passed.

The supplied document's real names, emails and phone numbers were not copied into fictional login accounts. See DATA_DOCUMENT_REFERENCE.md for its program-head reference list and unresolved input distinctions.
