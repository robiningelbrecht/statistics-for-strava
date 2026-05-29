#!/bin/sh
set -e

echo "date.timezone=\"${TZ:-UTC}\"" > "${PHP_INI_DIR}/conf.d/timezone.ini"

# Symfony requires APP_SECRET in prod (CSRF tokens, signed URIs, ...).
if [ -z "$APP_SECRET" ]; then
    export APP_SECRET="$(php -r 'echo bin2hex(random_bytes(16));')"
fi

if [ -n "$PUID" ] && [ -n "$PGID" ] && [ "$(id -u)" = "0" ]; then
    echo "Setting permissions for PUID=$PUID PGID=$PGID..."

    chown -R "$PUID:$PGID" \
        /var/www \
        /config/caddy \
        /data/caddy || true

    echo "Permissions have been set"
fi

exec "$@"