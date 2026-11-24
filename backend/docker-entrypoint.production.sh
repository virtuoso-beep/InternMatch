#!/bin/sh
# Production start (docker-compose.production.yml): install production dependencies, migrate, cache, then
# serve with Apache. Never seeds demo data and never generates an application key: APP_KEY must come from
# the server's .env (see docs/PRODUCTION_DEPLOYMENT.md), because a new key would make encrypted data unreadable.

set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set. Add it to the server's .env (docs/PRODUCTION_DEPLOYMENT.md, step 2)." >&2
    exit 1
fi

# Settings come from the container environment; an empty .env keeps Laravel from loading local values.
[ -f .env ] || : > .env

# The bind-mounted code may be newer than the vendor volume (after git pull).
composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

# Only the config cache before migrating: optimize:clear would also empty the database cache store, whose
# table does not exist yet on a new database. "php artisan optimize" below rebuilds every cache.
php artisan config:clear

echo "Waiting for MySQL..."
for attempt in $(seq 1 30); do
    if php artisan migrate --force; then
        break
    fi
    if [ "$attempt" -eq 30 ]; then
        echo "Database did not become available in time." >&2
        exit 1
    fi
    sleep 2
done

php artisan filament:cache-components
php artisan optimize

# Apache runs as www-data; the queue worker and scheduler run as www-data too (docker-compose.production.yml).
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
