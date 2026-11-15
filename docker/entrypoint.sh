#!/bin/sh
set -eu
umask 077

: "${APP_KEY:?Configure APP_KEY as a private environment variable}"
export PORT="${PORT:-10000}"
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:$PORT}}"

case "$PORT" in ''|*[!0-9]*) echo 'PORT must be numeric' >&2; exit 1 ;; esac
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then echo 'Invalid PORT' >&2; exit 1; fi

mkdir -p storage/data storage/app/private storage/app/public \
    storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
if [ "${DB_CONNECTION:-sqlite}" = sqlite ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    if [ ! -f "$DB_DATABASE" ]; then touch "$DB_DATABASE"; fi
fi
chown -R www-data:www-data storage bootstrap/cache

su -s /bin/sh www-data -c 'php artisan config:clear && php artisan migrate --force'
if [ "${DEMO_MODE:-false}" = true ]; then
    su -s /bin/sh www-data -c 'php artisan app:bootstrap-demo'
fi
# The bootstrap password is not needed by Apache or cached web configuration.
unset DEMO_PASSWORD
su -s /bin/sh www-data -c 'php artisan config:cache && php artisan route:cache && php artisan view:cache'
exec "$@"
