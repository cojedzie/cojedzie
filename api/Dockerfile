FROM php:8.1-fpm-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN install-php-extensions bcmath intl opcache zip sockets pdo_pgsql pdo_mysql ds xdebug amqp redis;
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
