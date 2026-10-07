#!/bin/bash
# restore.sh - Restore database and uploads for InternMatch

set -euo pipefail

if [ "$#" -lt 1 ]; then
    echo "Usage: $0 <timestamp_or_file_prefix>"
    echo "Example: $0 20261007_120000"
    exit 1
fi

PREFIX=$1
BACKUP_DIR="/var/backups/internmatch"
PROJECT_DIR="/opt/internmatch" # Adjust to production path
COMPOSE_FILE="$PROJECT_DIR/docker-compose.yml"
COMPOSE_OVERLAY="$PROJECT_DIR/docker-compose.production.yml"

DB_BACKUP_FILE="$BACKUP_DIR/db/db_backup_$PREFIX.sql.gz"
UPLOADS_BACKUP_FILE="$BACKUP_DIR/uploads/uploads_backup_$PREFIX.tar.gz"

if [ ! -f "$DB_BACKUP_FILE" ]; then
    echo "ERROR: Database backup file not found: $DB_BACKUP_FILE" >&2
    exit 1
fi

echo "WARNING: This will overwrite the current database and uploads!"
read -p "Are you sure you want to proceed? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Restore cancelled."
    exit 1
fi

DB_CONTAINER_NAME=$(docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY ps -q mysql)
DB_USER=$(grep DB_USERNAME $PROJECT_DIR/.env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD $PROJECT_DIR/.env | cut -d '=' -f2)
DB_NAME=$(grep DB_DATABASE $PROJECT_DIR/.env | cut -d '=' -f2)

echo "[$(date)] Restoring database from $DB_BACKUP_FILE..."
zcat "$DB_BACKUP_FILE" | docker exec -i "$DB_CONTAINER_NAME" mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"
echo "[$(date)] Database restore successful."

if [ -f "$UPLOADS_BACKUP_FILE" ]; then
    UPLOADS_DIR="$PROJECT_DIR/backend/storage/app/public"
    echo "[$(date)] Restoring uploads from $UPLOADS_BACKUP_FILE to $UPLOADS_DIR..."
    mkdir -p "$UPLOADS_DIR"
    tar -xzf "$UPLOADS_BACKUP_FILE" -C "$UPLOADS_DIR"
    echo "[$(date)] Uploads restore successful."
fi

echo "[$(date)] Running migrations..."
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY exec backend php artisan migrate --force

echo "[$(date)] Clearing caches..."
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY exec backend php artisan optimize:clear

echo "[$(date)] Restore process complete. Please verify the application."
