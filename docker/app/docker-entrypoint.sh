#!/bin/sh
set -e

if [ -n "$PUID" ] && [ -n "$PGID" ] && [ "$(id -u)" = "0" ]; then
    echo "Fixing permissions for PUID=$PUID PGID=$PGID..."

    chown -R "$PUID:$PGID" \
        /var/www \
        /config/caddy \
        /data/caddy || true
fi

exec "$@"