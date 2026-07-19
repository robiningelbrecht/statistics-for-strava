#!/bin/sh

exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
