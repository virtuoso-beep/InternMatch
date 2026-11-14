# Local Windows backup and recovery

The operator tool is `deploy/recovery.py` (Python 3.12+ and Docker Compose). Backups are stored in `backend/storage/app/backups`, outside public web storage and ignored by Git. They contain sensitive records and private documents: retain Windows account access restrictions and keep any additional copies in restricted storage.

Run `python deploy/recovery.py backup` from the project. It creates a unique backup folder containing a compressed SQL snapshot, an archive of **both private and public uploads**, and an atomic JSON manifest with sizes and SHA-256 checksums. Failed jobs are marked failed; they are not accepted for restore verification. Database snapshots and documents are captured sequentially: stop application writes in a planned maintenance window for coordinated production consistency.

Run `python deploy/recovery.py verify BACKUP_ID` for a recovery drill. It validates checksums, creates a **new uniquely named recovery testing database**, imports SQL, checks that tables exist, and extracts documents into a separate folder. It never overwrites the application database or documents. It retains the recovery database and extracted files for inspection. It does not run migrations or change application configuration. Operator application-level acceptance (login, permissions, placements, reports and downloads) is separate from this storage recovery check.

`deploy/backup.sh` and `deploy/restore.sh` delegate to these commands. The old overwrite-live-data behavior has been removed. On a production Docker Compose installation, explicitly set `INTERNMATCH_PRODUCTION=1`; the local Windows installation does not use that overlay.

## Local schedule

`deploy/install-local-backup.ps1 -PythonPath ABSOLUTE_PYTHON_PATH` registers **InternMatch Local Daily Backup**, daily at **18:00 Windows local time**. It runs as the signed-in user with limited privileges. Docker Desktop must be running. Missed runs start when available; overlapping instances are ignored. Re-run the installer if Python or the project moves. Check Windows Task Scheduler for live task state and exit results; Filament displays the recorded configuration and actual manifests, not a live scheduler heartbeat.

No automatic deletion is enabled. Review disk usage and retain/remove old backups through an operator process after confirming recovery needs. No off-device protection is implied by this local setup. Preserve the application version, encryption key and deployment configuration separately with restricted access; secrets are deliberately not embedded in UI responses or manifests.

## Verified on 2026-10-26

Backup `20261026T063234Z-721c978f` passed checksum validation, SQL import (60 tables) and document extraction. Its isolated database is `internmatch_recovery_314f9ad403fd_testing`. Live accounts and records were not restored or replaced.
