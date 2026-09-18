#!/bin/sh

set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="$(php artisan key:generate --show)"
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
