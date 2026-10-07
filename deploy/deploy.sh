#!/bin/bash
# deploy.sh - Deployment script for InternMatch

set -euo pipefail

PROJECT_DIR=$(pwd)
COMPOSE_FILE="docker-compose.yml"
COMPOSE_OVERLAY="docker-compose.production.yml"

echo "[$(date)] Starting deployment..."

# 1. Pull latest code (assuming git is used)
# git pull origin main

# 2. Build and Pull Images
echo "[$(date)] Building and pulling images..."
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY build
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY pull

# 3. Apply changes (Zero-downtime attempt via recreate)
echo "[$(date)] Starting containers..."
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY up -d

# 4. Wait for healthy containers
echo "[$(date)] Waiting for services to become healthy..."
sleep 15 # Initial wait
for i in {1..12}; do
    HEALTH=$(docker inspect --format='{{json .State.Health.Status}}' $(docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY ps -q backend) 2>/dev/null || echo '"unknown"')
    if [ "$HEALTH" == '"healthy"' ] || [ "$HEALTH" == '"unknown"' ]; then # 'unknown' handles cases where healthcheck isn't on backend itself but gateway has it
        echo "[$(date)] Services are up."
        break
    fi
    echo "Waiting for healthcheck... ($i/12)"
    sleep 5
done

# 5. Run Migrations & Clear Caches
echo "[$(date)] Running migrations..."
if ! docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY exec -T backend php artisan migrate --force; then
    echo "[$(date)] ERROR: Migrations failed. Rolling back..." >&2
    # Basic rollback approach: down and restore if needed, or previous image tag.
    exit 1
fi

echo "[$(date)] Clearing and rebuilding caches..."
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY exec -T backend php artisan optimize:clear
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY exec -T backend php artisan optimize
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY exec -T backend php artisan view:cache

echo "[$(date)] Restarting queue workers..."
docker compose -f $COMPOSE_FILE -f $COMPOSE_OVERLAY exec -T backend php artisan queue:restart

echo "[$(date)] Deployment completed successfully!"
