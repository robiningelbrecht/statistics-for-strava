# "We moved to Dreeve" tombstone image.
#
# Based on caddy:alpine (not nginx) because v4's app-container healthcheck is
# `curl -f http://localhost:2019/metrics` — Caddy's admin endpoint, which
# caddy:alpine exposes for free.
FROM caddy:alpine

# The healthcheck command itself must exist inside the container, or the app
# container sits permanently `unhealthy`.
RUN apk add --no-cache curl

COPY Caddyfile /etc/caddy/Caddyfile
COPY index.html /srv/index.html
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080 2019

# ENTRYPOINT ignores any `command:` v4's compose passes and always runs Caddy.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
