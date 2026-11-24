#!/bin/bash
# ssl-setup.sh - obtain the Let's Encrypt certificate (first deployment, before deploy.sh) and set up renewal.
#
# Usage: sudo ./deploy/ssl-setup.sh <domain> <email> [--with-www]
#
# Uses certbot's standalone mode, which answers the challenge itself on port 80. That avoids the
# chicken-and-egg problem of the webroot mode (Nginx cannot start before the certificate exists). For renewals,
# certbot stops the Nginx container for a few seconds and starts it again; the hooks are stored in
# /etc/letsencrypt/renewal/<domain>.conf, so the certbot timer (or cron) can renew unattended.

set -euo pipefail

DOMAIN=${1:-""}
EMAIL=${2:-""}
WITH_WWW=${3:-""}

if [ -z "$DOMAIN" ] || [ -z "$EMAIL" ]; then
    echo "Usage: $0 <domain> <email> [--with-www]"
    echo "Example: $0 internmatch.example.edu it-office@example.edu"
    exit 1
fi

REPO=$(cd "$(dirname "$0")/.." && pwd)
COMPOSE="docker compose -f $REPO/docker-compose.yml -f $REPO/docker-compose.production.yml"

if ! command -v certbot >/dev/null 2>&1; then
    echo "Installing certbot..."
    apt-get update
    apt-get install -y certbot
fi

mkdir -p /var/www/certbot

DOMAINS=(-d "$DOMAIN")
[ "$WITH_WWW" = "--with-www" ] && DOMAINS+=(-d "www.$DOMAIN")

echo "Requesting a certificate for $DOMAIN (port 80 must be free and reachable from the internet)..."
certbot certonly --standalone "${DOMAINS[@]}" \
    --email "$EMAIL" --agree-tos --no-eff-email --non-interactive \
    --pre-hook "cd $REPO && $COMPOSE stop nginx || true" \
    --post-hook "cd $REPO && $COMPOSE start nginx || true"

# Renewal: Ubuntu's certbot package ships a systemd timer. Add a cron entry only if there is none.
if systemctl list-timers 2>/dev/null | grep -q certbot; then
    echo "Renewal: certbot.timer is active."
else
    (crontab -l 2>/dev/null | grep -v "certbot renew"; echo "0 3 * * * certbot renew --quiet") | crontab -
    echo "Renewal: daily cron entry added (03:00)."
fi

echo "Certificate ready: /etc/letsencrypt/live/$DOMAIN/. Set DOMAIN_NAME=$DOMAIN in .env, then run ./deploy/deploy.sh."
