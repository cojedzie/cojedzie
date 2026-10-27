FROM php:8.5-fpm-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=ghcr.io/php/pie:bin /pie /usr/local/bin/pie
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN install-php-extensions bcmath intl opcache zip sockets pdo_pgsql pdo_mysql;
RUN apk add --no-cache rabbitmq-c && \
    apk add --no-cache --virtual .pie-deps $PHPIZE_DEPS linux-headers rabbitmq-c-dev && \
    pie install php-ds/ext-ds:^2.0 && \
    pie install xdebug/xdebug && \
    pie install php-amqp/php-amqp && \
    pie install phpredis/phpredis && \
    apk del --purge .pie-deps;
RUN apk add git su-exec tini;

# XDebug
RUN echo "xdebug.mode=debug" >> $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.discover_client_host=On" >> $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini;

# Timezone
RUN ln -snf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime && \
    echo "date.timezone = Europe/Warsaw" >> /usr/local/etc/php/conf.d/datetime.ini;

WORKDIR /var/www

EXPOSE 9001

ENV APP_MODE="debug"

ENTRYPOINT ["./bin/docker-dev-entrypoint.sh"]
CMD ["php-fpm"]
