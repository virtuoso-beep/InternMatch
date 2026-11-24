#!/bin/bash
# deploy.sh - first deployment and every update of InternMatch on the production server.
# Run from anywhere inside the repository, after `git pull`. See docs/PRODUCTION_DEPLOYMENT.md.
#
#   1. checks Docker Compose and the server's .env (every required setting),
#   2. backs up the database and uploaded documents (skipped only on the very first deployment),
#   3. builds the images and starts the stack; the backend migrates and caches on start
#      (backend/docker-entrypoint.production.sh), the frontend builds itself and the admin bundle,
#   4. restarts the services that run the code (so a git pull takes effect) and waits until the database,
#      Laravel, the frontend and Nginx are healthy.

set -euo pipefail

cd "$(dirname "$0")/.."
COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.production.yml)
# Optional extra override file, for a staging copy or a local rehearsal (other ports or certificates).
if [ -n "${INTERNMATCH_COMPOSE_EXTRA:-}" ]; then COMPOSE+=(-f "$INTERNMATCH_COMPOSE_EXTRA"); fi
log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }
fail() { log "ERROR: $*" >&2; exit 1; }

# 1. Preconditions
[ -f .env ] || fail ".env is missing next to docker-compose.yml (docs/PRODUCTION_DEPLOYMENT.md, step 2)."
version=$(docker compose version --short 2>/dev/null | sed 's/^v//') || fail "Docker Compose v2 is not installed."
if [ "$(printf '%s\n' "2.24.0" "$version" | sort -V | head -n1)" != "2.24.0" ]; then
    fail "Docker Compose $version is too old; 2.24 or newer is required."
fi
"${COMPOSE[@]}" config --quiet || fail "The production configuration is incomplete; the message above names the missing setting."
command -v python3 >/dev/null || fail "python3 is required for backups (deploy/recovery.py)."

# 2. Backup before anything changes (database and documents, with checksums)
if [ -n "$("${COMPOSE[@]}" ps --status running -q mysql 2>/dev/null)" ] && [ -n "$("${COMPOSE[@]}" ps --status running -q backend 2>/dev/null)" ]; then
    log "Backing up the database and documents..."
    INTERNMATCH_PRODUCTION=1 bash deploy/backup.sh || fail "Backup failed; nothing was changed."
else
    log "No running installation found (first deployment): skipping the backup."
fi

# 3. Build and start
log "Building images..."
"${COMPOSE[@]}" build
# No "pull" here: MySQL and Nginx stay on the image already on the server, so a code update never upgrades or
# restarts the database by surprise. Upgrade them deliberately (docs/PRODUCTION_DEPLOYMENT.md, section 6).
log "Starting containers..."
"${COMPOSE[@]}" up -d --remove-orphans
# Code is bind-mounted: a git pull alone does not recreate containers. Restart the services that run the code,
# so Laravel re-caches its routes and config and the frontend rebuilds (both happen on start).
"${COMPOSE[@]}" restart backend worker scheduler frontend

# 4. Wait for the stack (the frontend builds on start, which takes a few minutes)
log "Waiting for the services to become healthy..."
for attempt in $(seq 1 90); do
    unhealthy=""
    for service in mysql backend frontend nginx; do
        id=$("${COMPOSE[@]}" ps -q "$service")
        status=$([ -n "$id" ] && docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$id" || echo missing)
        [ "$status" = "healthy" ] || unhealthy="$unhealthy $service=$status"
    done
    [ -z "$unhealthy" ] && break
    [ "$attempt" -eq 90 ] && fail "Services did not become healthy:$unhealthy. Check: ${COMPOSE[*]} logs <service>"
    sleep 10
done

log "Deployment completed. Health: $("${COMPOSE[@]}" ps --format '{{.Service}}={{.Health}}' | tr '\n' ' ')"
