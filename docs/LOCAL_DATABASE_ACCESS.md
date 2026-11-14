# Local database access and account cleanup

SQLyog's Windows MySQL connection on port 3306 is a separate, older database. The running Docker application uses database `internmatchlaravel` in the `mysql` service. It is now published on **127.0.0.1:3307**, bound to loopback only. Use the Docker MySQL credentials from the local Compose configuration.

On November 13, 2026, the user confirmed retaining both BSIT coordinators and the four original role logins. Backup: `20261113T044922Z-bfc57b0b`.

24 additional synthetic/QA or pending factory accounts were disabled and soft-deleted with audit records. Nine retained accounts remain: student@example.com, supervisor@example.com, dean@example.com, admin@example.com, and the five named coordinators. Existing password hashes were verified unchanged. Student (10), enrollment (10), placement (6), time-log (20), evaluation (4), and document (90) counts were preserved. Two older coordinator test accounts were already archived.

Coordinator assignments: BSA — Joe Mari Flores; BSCRIM — Jun Mark Asis; BSIT — Richard Vincent Misa and Benjamin Mahinay; BSCS — Iris Mae Mendoza.

To inspect current accounts without password hashes:

```sql
SELECT id, name, email, role, status
FROM users
WHERE deleted_at IS NULL
ORDER BY role, name;
```

Archived rows remain in the underlying table for history and foreign-key integrity. They cannot sign in and do not appear in active account lists. Do not run the synthetic/demo seeders to populate the live database again.
