#!/bin/sh

# APP_MODE and APP_RUNAS are the previous names (ADR 0011)
COJEDZIE_API_MODE="${COJEDZIE_API_MODE:-${APP_MODE:-debug}}"
COJEDZIE_RUNAS="${COJEDZIE_RUNAS:-$APP_RUNAS}"

if [ "$COJEDZIE_API_MODE" != "debug" ]; then
    rm -f "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"

    {
        echo "opcache.enable_cli=1"
        echo "opcache.jit_buffer_size=100m"
        echo "opcache.jit=tracing"
    } >> "$PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini";
fi

# su-exec can only drop privileges from root; with -u the container already runs as that user
if [ -n "$COJEDZIE_RUNAS" ] && [ "$(id -u)" = 0 ]; then
    exec su-exec "$COJEDZIE_RUNAS" tini -- "$@"
else
    exec tini -- "$@"
fi
