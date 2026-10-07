# Synthetic and QA data retention

User instruction confirmed October 1, 2026: retain synthetic and QA records created and successfully tested during InternMatch development. Do not remove these records during cleanup, reseeding, or subsequent development.

- Preserve application records in `internmatchlaravel` and persistent browser fixtures in `internmatch_browser_testing`, including related uploads, recommendations, notifications, decisions, attendance, reports, and audit history.
- Use the main InternMatch frontend on port **8080** and backend on port **8000** for all ongoing development and browser verification. On October 3, 2026, retained browser QA records were copied additively into `internmatchlaravel`, preserving its five original accounts. The extra 8082/8002 containers are stopped; their database remains preserved as a historical source, not the active application.
- Run destructive automated test isolation only against `internmatch_docker_testing`; never point RefreshDatabase, migrate:fresh, truncation, or cleanup commands at either persistent database.
- Keep synthetic records clearly identified. Synthetic approvals, rubrics, and training examples do not establish department approval or production acceptance.
- Preserve existing backups and fixture scripts. The September 28 cleanup log states accounts and operational records were preserved; it removed unused requirement vocabulary and unconfirmed BSIT competency links. Do not restore unconfirmed curriculum entries as approved requirements.
- Records already discarded by isolated automated tests cannot be claimed as retained or recovered. Investigate available backups before making recovery claims.

The October 1 retention snapshot is stored locally in `.local/sep28-audit/retained-system-and-qa-2026-10-01.sql`. Database dumps contain account and application data and must remain excluded from Git.

The October 3 consolidation backup is `.local/sep28-audit/before-port-consolidation.sql`; the committed ID mappings and copied counts are in `promotion-applied.json` in that directory. User IDs and reference IDs were remapped, original credentials remained unchanged, and MySQL-generated columns were recalculated. Nine historical synthetic document metadata records lack corresponding uploaded files; they were retained as-is and are not evidence of verified uploads. The actual browser-uploaded document remains available in shared storage.
