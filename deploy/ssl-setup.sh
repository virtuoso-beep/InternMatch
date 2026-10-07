#!/bin/bash
# ssl-setup.sh - Setup Let's Encrypt SSL via Certbot

set -euo pipefail

DOMAIN=${1:-""}
EMAIL=${2:-""}

if [ -z "$DOMAIN" ] || [ -z "$EMAIL" ]; then
    echo "Usage: $0 <domain> <email>"
    echo "Example: $0 example.com admin@example.com"
    exit 1
fi

echo "Setting up SSL for $DOMAIN..."

# Ensure certbot is installed
if ! command -v certbot &> /dev/null; then
    echo "Installing certbot..."
    sudo apt-get update
    sudo apt-get install -y certbot
fi

# Create certbot webroot directory
mkdir -p /var/www/certbot

# Request certificate
sudo certbot certonly --webroot -w /var/www/certbot -d "$DOMAIN" -d "www.$DOMAIN" --email "$EMAIL" --agree-tos --no-eff-email --non-interactive

# Setup Cron for Renewal
CRON_JOB="0 3 * * * certbot renew --quiet && docker compose -f /opt/internmatch/docker-compose.yml -f /opt/internmatch/docker-compose.production.yml exec nginx nginx -s reload"
(crontab -l 2>/dev/null | grep -v "certbot renew" ; echo "$CRON_JOB") | crontab -

echo "SSL setup complete! Cron job added for auto-renewal."
echo "Make sure your nginx.production.conf is updated with the correct domain paths."
