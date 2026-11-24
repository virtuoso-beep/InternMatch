#!/bin/bash
# install-backup-cron.sh - schedule the nightly production backup (FR-19) for the current user.
#
# Usage: ./deploy/install-backup-cron.sh [HH:MM]   (default 02:00, server time)
#
# Runs deploy/backup.sh (database and uploaded documents, with checksums; keeps the newest
# INTERNMATCH_BACKUP_KEEP backups, default 14) and appends the output to backend/storage/logs/backup-cron.log.
# Copy backend/storage/app/backups to another machine regularly: a backup on the same disk does not survive
# the loss of that disk. Check a backup with: INTERNMATCH_PRODUCTION=1 bash deploy/restore.sh <backup-id>

set -euo pipefail

AT=${1:-"02:00"}
if ! [[ "$AT" =~ ^([01][0-9]|2[0-3]):([0-5][0-9])$ ]]; then
    echo "Give the time as HH:MM, for example 02:00." >&2
    exit 1
fi
HOUR=$((10#${AT%%:*}))
MINUTE=$((10#${AT##*:}))
REPO=$(cd "$(dirname "$0")/.." && pwd)
LINE="$MINUTE $HOUR * * * cd $REPO && INTERNMATCH_PRODUCTION=1 bash deploy/backup.sh >> $REPO/backend/storage/logs/backup-cron.log 2>&1"

(crontab -l 2>/dev/null | grep -v "deploy/backup.sh"; echo "$LINE") | crontab -
echo "Nightly backup scheduled at $AT:"
crontab -l | grep "deploy/backup.sh"
