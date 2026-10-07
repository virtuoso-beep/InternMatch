#!/bin/bash
# backup.sh - Automated backup script for InternMatch

set -euo pipefail

# Configuration
BACKUP_DIR="/var/backups/internmatch"
PROJECT_DIR="/opt/internmatch" # Adjust to production path
COMPOSE_FILE="$PROJECT_DIR/docker-compose.yml"
COMPOSE_OVERLAY="$PROJECT_DIR/docker-compose.production.yml"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_CONTAINER_NAME=$(docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY ps -q mysql)
DB_USER=$(grep DB_USERNAME $PROJECT_DIR/.env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD $PROJECT_DIR/.env | cut -d '=' -f2)
DB_NAME=$(grep DB_DATABASE $PROJECT_DIR/.env | cut -d '=' -f2)

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR/db"
mkdir -p "$BACKUP_DIR/uploads"

echo "[$(date)] Starting backup process..."

# 1. Database Backup
DB_BACKUP_FILE="$BACKUP_DIR/db/db_backup_$TIMESTAMP.sql.gz"
echo "[$(date)] Dumping database to $DB_BACKUP_FILE..."
if docker exec "$DB_CONTAINER_NAME" mysqldump -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | gzip > "$DB_BACKUP_FILE"; then
    echo "[$(date)] Database backup successful."
else
    echo "[$(date)] ERROR: Database backup failed." >&2
    exit 1
fi

# 2. Uploads Directory Backup
# Assuming uploads are in backend/storage/app/public
UPLOADS_DIR="$PROJECT_DIR/backend/storage/app/public"
UPLOADS_BACKUP_FILE="$BACKUP_DIR/uploads/uploads_backup_$TIMESTAMP.tar.gz"
if [ -d "$UPLOADS_DIR" ]; then
    echo "[$(date)] Archiving uploads to $UPLOADS_BACKUP_FILE..."
    tar -czf "$UPLOADS_BACKUP_FILE" -C "$UPLOADS_DIR" .
    echo "[$(date)] Uploads backup successful."
else
    echo "[$(date)] Warning: Uploads directory not found at $UPLOADS_DIR. Skipping."
fi

# 3. Rotation (Keep last 7 daily, 4 weekly)
echo "[$(date)] Rotating old backups..."
# Keep 7 most recent backups
ls -1t $BACKUP_DIR/db/db_backup_*.sql.gz | tail -n +8 | xargs -r rm --
ls -1t $BACKUP_DIR/uploads/uploads_backup_*.tar.gz | tail -n +8 | xargs -r rm --

# TODO: Add logic for weekly rotation if required (e.g. move to a weekly folder)

echo "[$(date)] Backup process completed successfully."
