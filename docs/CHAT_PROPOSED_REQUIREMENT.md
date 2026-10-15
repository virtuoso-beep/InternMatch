# Proposed addition: internship messaging

This implements the user's approved chat workflow as a proposed addition beyond manuscript FR-01–19. It does not assert that messaging was part of the original manuscript. New-message notifications extend the existing FR-15 notification facility. Incorporate this requirement into the manuscript during its next formal revision.

## Role and conversation rules

- Each program needs one active Practicum Coordinator. One coordinator may manage multiple programs. Duplicate program assignments and conversion of an overlapping Dean account into a Coordinator are rejected by the account APIs. Missing assignments block sending and are shown explicitly; the application does not choose a recipient arbitrarily.
- Each enrollment automatically gets a student–coordinator conversation. Students never choose between coordinators or search arbitrary accounts.
- The implementation's `Placement` model is the deployment record: an approved placement creates a separate conversation between its student, assigned Host Supervisor and program coordinator. Pending or rejected placements do not create a placement conversation. Approval remains in the existing coordinator approval process.
- Coordinator access follows current program assignments. Supervisor access requires the current placement assignment, host assignment, an approved/active placement, no completion timestamp and an end date that has not passed.
- At deployment completion, cancellation or expiry, the placement conversation is read-only for the student and current program coordinator. Supervisor access ends, including access by a previously saved URL. Enrollment conversations also become read-only when the enrollment is closed.
- Dean cannot list, open or send chat. Administrator cannot list, open or send chat and has only an audited availability toggle at `/manage/administration?section=chat-settings`. No administrator content-access exception is implemented.
- Institutional escalation stays outside chat. No Dean participant is automatically added.

## Persistence and privacy

Laravel policies and global query scopes protect both conversation and message reads. Access is rechecked on sending and on every request; UI visibility is not authorization. Messages are plain text, at most 2,000 characters, authenticated as the sender, paginated and protected from accidental retry duplication by a client UUID. Empty/whitespace messages and forged conversation IDs are rejected. All requests use the existing session and CSRF protections.

Only current eligible participants receive a database notification. Notifications contain a generic notice and conversation ID, not message content or a preview that could remain readable after reassignment. Opening a notification rechecks conversation authorization. Read positions are stored per participant. Message audit entries retain event and message IDs, not message bodies.

The inbox and chatbox refresh every ten seconds while open and the browser tab is visible. This is polling, not WebSocket delivery. No external email, SMS or third-party messaging service is used. No synthetic conversation messages are injected into the main database.

## Reference implementation

The UI reference folder contains 69 images across Student, Practicum Coordinator, Host Supervisor, Program Chair/Dean and System Administrator. This update applies its pale-blue shortcut rail, Compose/Inbox navigation, floating chatbox, inbox rows, rounded cards and page breadcrumbs to the existing working screens. The written role rules override screenshot Chat controls for Administrator and Dean; those roles do not get participant chat. Existing feature pages continue to use real APIs and retain their original authorization. Screenshot example counts, support identities and health/backup claims are not fabricated as live records.

The ports remain unchanged: Student, Coordinator, Supervisor and Dean use 8080; Administrator uses Filament on 8000. The old 8080 administrator route continues to redirect.

## Existing data and setup

Richard Misa (`r.misa@example.com`, existing user ID 2) is the BSIT coordinator. The two extra coordinator accounts, `sep28-coordinator@example.test` and `demo.coordinator@example.test`, were disabled, detached from their coordinator assignments and recoverably deleted with audit entries. Their historical IDs and associated QA records remain intact. Richard's password and records were not reset. A pre-change database backup is retained privately in `.local/before-chat-and-coordinator-retirement.sql`.

Other programs currently have no coordinator assignments. Their responsible coordinators must be supplied by the institution; no accounts or assignments are invented. Consequently, the one-coordinator rule is ready for BSIT, but institutional coverage of every program remains incomplete.

Run normal Laravel migrations and `php artisan internmatch:sync-chat` for pre-existing enrollments and approved/completed placements. The command is idempotent and sends no messages. New enrollments and approved placements create their conversations automatically. Rebuild the administrator assets with `./build-management.ps1` after changing its UI.

## Verification

Automated chat coverage: cross-program and cross-student denial; supervisor placement scope; Dean/Admin content denial; automatic conversation creation; persistent messages; authenticated sender identity; duplicate retry handling; blank/overlong input; generic notifications and content-free audit entries; unread/read positions; expired/completed placement restrictions; coordinator detachment; availability toggle; retired accounts; duplicate coordinator assignment and role conversion rejection.

Account-access, Filament authorization and cohort-allocation regression tests are also run on the isolated `internmatch_testing` database. Browser checks use the original five role accounts and do not send messages to live participants.

On 2026-10-14 the combined regression run passed 25 tests with 398 assertions, including a seeder safeguard that preserves existing passwords and retired accounts. The default seeder now uses Richard Misa's confirmed email and does not overwrite existing accounts. TypeScript checking and both frontend and administrator production builds passed. Targeted frontend lint reported no errors and four existing shared-component Fast Refresh warnings.

All five existing role logins passed the final browser run. Student, Coordinator and Supervisor inbox/chat views were checked at desktop and mobile widths; Dean and Administrator conversation access was denied, and the Administrator availability control loaded in Filament. Browser checks sent no live messages; message persistence and notifications were exercised only in the isolated test database.
