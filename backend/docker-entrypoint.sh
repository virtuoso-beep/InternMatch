#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="$(php artisan key:generate --show)"
fi

php artisan config:clear

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
exec php -S 0.0.0.0:8000 -t public
