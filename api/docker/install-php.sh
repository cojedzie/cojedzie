#!/bin/sh
# Installs the PHP extensions and settings shared by every PHP image.
# Expects install-php-extensions and pie in /usr/local/bin.
set -eu

apk add --no-cache rabbitmq-c
apk add --no-cache --virtual .pie-deps $PHPIZE_DEPS rabbitmq-c-dev
pie install php-ds/ext-ds:^2.0
pie install php-amqp/php-amqp
pie install phpredis/phpredis
apk del --purge .pie-deps

install-php-extensions bcmath intl opcache zip sockets pdo_pgsql pdo_mysql

{
    echo "opcache.enable_cli=1"
    echo "opcache.jit_buffer_size=100m"
    echo "opcache.jit=tracing"
} >> "$PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini"

# Timezone
ln -snf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime
echo "date.timezone = Europe/Warsaw" >> "$PHP_INI_DIR/conf.d/datetime.ini"
