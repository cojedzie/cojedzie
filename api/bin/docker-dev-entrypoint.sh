#!/bin/sh

if [ "$APP_MODE" != "debug" ]; then
    rm -f "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"
    rm -f "$PHP_INI_DIR/conf.d/blackfire.ini"
fi

exec "$@"
