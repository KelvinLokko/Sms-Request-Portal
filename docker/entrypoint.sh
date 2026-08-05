#!/usr/bin/env sh
set -eu

cd /var/www/html

# Ensure writable runtime directories exist in volume-mounted environments.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Waiting for database..."
    attempts=0
    until php artisan db:show >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            echo "Database is not ready after waiting."
            exit 1
        fi
        sleep 2
    done

    php artisan migrate --force --no-interaction
fi

if [ "${APP_ENV:-production}" != "local" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"
