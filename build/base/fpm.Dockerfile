ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:$BASE_VERSION as base
RUN mv /var/www/vendor /opt/vendor

FROM php:8.1-fpm-alpine

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath intl opcache zip sockets pdo_pgsql pdo_mysql ds && \
    echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.jit_buffer_size=100m" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.jit=tracing" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini;

WORKDIR /var/www

# this is split into two becasue of layer caching
COPY --from=base /opt/vendor /var/www/vendor
COPY --from=base /var/www /var/www

ENV APP_ENV=prod
ENV DATABASE_URL="sqlite:////var/db/app.db"
ENV PATH=$PATH:/var/www/bin

VOLUME /var/db

# This image is not meant to be run, just to prepare all required files
CMD ["/bin/false"]
