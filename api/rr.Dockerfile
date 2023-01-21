FROM php:8.1-cli-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN install-php-extensions bcmath intl opcache zip sockets pdo_pgsql pdo_mysql ds xdebug;
RUN apk add git su-exec tini;

# XDebug
RUN echo "xdebug.mode=debug" >> $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.discover_client_host=On" >> $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini;

# Timezone
RUN ln -snf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime && \
    echo "date.timezone = Europe/Warsaw" >> /usr/local/etc/php/conf.d/datetime.ini;

WORKDIR /var/www

COPY --from=spiralscout/roadrunner:2.8.0 /usr/bin/rr /usr/bin/rr

EXPOSE 8080

ENTRYPOINT ["./bin/docker-dev-entrypoint.sh"]
CMD ["./bin/docker-init.sh", "rr", "serve"]
