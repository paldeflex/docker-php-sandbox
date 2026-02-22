#!/bin/sh
set -e

# Sync www-data UID/GID with host user to avoid permission issues on mounted volumes
if [ -n "$WWWDATA_UID" ] && [ "$WWWDATA_UID" != "$(id -u www-data)" ]; then
    usermod -u "$WWWDATA_UID" www-data 2>/dev/null || true
fi

if [ -n "$WWWDATA_GID" ] && [ "$WWWDATA_GID" != "$(id -g www-data)" ]; then
    groupmod -g "$WWWDATA_GID" www-data 2>/dev/null || true
fi

exec docker-php-entrypoint "$@"