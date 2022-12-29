#!/bin/sh

if [ "$APP_MODE" != "debug" ]; then
    rm -f "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"

    {
        echo "opcache.enable_cli=1"
        echo "opcache.jit_buffer_size=100m"
        echo "opcache.jit=tracing"
    } >> "$PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini";
fi

if [ -n "$APP_RUNAS" ]; then
    exec su-exec "$APP_RUNAS" tini -- "$@"
else
    exec tini -- "$@"
fi
