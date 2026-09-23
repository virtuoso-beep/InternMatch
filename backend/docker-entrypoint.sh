#!/bin/sh

set -eu

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

# The named vendor volume survives image rebuilds and can lag behind the
# bind-mounted lockfile. Synchronize it before booting any Laravel command.
composer install --no-interaction --prefer-dist --no-progress

# Preserve the application key across restarts; rotating it invalidates sessions
# and makes previously encrypted application data unreadable.
if [ -z "${APP_KEY:-}" ] && ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --force
fi

php artisan config:clear

echo "Waiting for MySQL..."

for attempt in $(seq 1 30); do
    if php artisan migrate --force; then
        break
    fi

    if [ "$attempt" -eq 30 ]; then
        echo "Database did not become available in time."
        exit 1
    fi

    sleep 2
done

php artisan db:seed --force

echo "Starting Laravel development server..."

exec php artisan serve --host=0.0.0.0 --port=8000
