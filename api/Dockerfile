# Every PHP image of Co Jedzie is built from this file; docker-bake.hcl maps its targets to the published images:
#
#   php-cli ── vendor ── app        composer dependencies, then the application code
#      ├── rr    (+ app)            cojedzie/api, cojedzie/api:*-rr, and the base of standalone/
#      └── cli   (+ app)            the base of worker/
#   php-fpm
#      ├── fpm   (+ app)            cojedzie/api:*-fpm
#      └── dev                      the dev compose stack, the code is bind-mounted
#
# dev is the last stage, so building without a target gives the dev image.

ARG PHP_VERSION=8.5
ARG ROADRUNNER_VERSION=2025.1.15

# ---- Tools ------------------------------------------------------------------------------------------------------------
#
# Binaries copied into the images below.

FROM composer:latest AS composer
FROM ghcr.io/roadrunner-server/roadrunner:${ROADRUNNER_VERSION} AS roadrunner
FROM mlocati/php-extension-installer AS php-extension-installer
FROM ghcr.io/php/pie:bin AS pie

# ---- PHP --------------------------------------------------------------------------------------------------------------
#
# The cli and fpm flavours of PHP with the extensions and settings every image shares, see docker/install-php.sh.

FROM php:${PHP_VERSION}-cli-alpine AS php-cli
LABEL maintainer="Kacper Donat <kacper@kadet.net>"
COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=pie /pie /usr/local/bin/pie

RUN --mount=type=bind,source=docker/install-php.sh,target=/tmp/install-php.sh sh /tmp/install-php.sh

WORKDIR /var/www

FROM php:${PHP_VERSION}-fpm-alpine AS php-fpm
LABEL maintainer="Kacper Donat <kacper@kadet.net>"
COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=pie /pie /usr/local/bin/pie

RUN --mount=type=bind,source=docker/install-php.sh,target=/tmp/install-php.sh sh /tmp/install-php.sh

WORKDIR /var/www

# ---- Application ------------------------------------------------------------------------------------------------------
#
# vendor installs the composer dependencies alone, so that a code change doesn't reinstall them. app adds the code and
# runs Symfony's post-install scripts (cache warmup, assets) for prod. Composer runs as root during the build.

FROM php-cli AS vendor
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN apk add git \
 && composer install --no-dev --no-scripts --no-plugins --prefer-dist --no-progress --no-interaction \
 && composer dump-autoload --optimize \
 && composer check-platform-reqs \
 && composer clear-cache \
 && apk del --purge git

FROM vendor AS app
ENV APP_ENV=prod

COPY . .
RUN mkdir -p /var/db && composer run-script post-install-cmd

# ---- Production images ------------------------------------------------------------------------------------------------
#
# Each one copies the application from app: vendor/ first, as its own layer that survives a code change, then the rest.
# The version args come last, because they change on every build.
#
# rr leaves display_errors alone, .rr.yaml starts the workers with display_errors=stderr. cli is not run on its own,
# worker/Dockerfile builds on it.

FROM php-cli AS rr
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=roadrunner /usr/bin/rr /usr/bin/rr

COPY --from=app /var/www/vendor ./vendor
COPY --from=app /var/www ./

ENV APP_ENV=prod PATH=$PATH:/var/www/bin COMPOSER_ALLOW_SUPERUSER=1
VOLUME /var/db

EXPOSE 8080
ENTRYPOINT ["./bin/docker-entrypoint.sh"]
CMD ["./bin/docker-init.sh", "rr", "serve"]

ARG COJEDZIE_VERSION
ARG COJEDZIE_REVISION
ENV COJEDZIE_VERSION=${COJEDZIE_VERSION} COJEDZIE_REVISION=${COJEDZIE_REVISION}

FROM php-cli AS cli
RUN echo "display_errors=stderr" >> $PHP_INI_DIR/conf.d/zz-errors.ini

COPY --from=app /var/www/vendor ./vendor
COPY --from=app /var/www ./

ENV APP_ENV=prod PATH=$PATH:/var/www/bin
VOLUME /var/db

CMD ["/bin/false"]

ARG COJEDZIE_VERSION
ARG COJEDZIE_REVISION
ENV COJEDZIE_VERSION=${COJEDZIE_VERSION} COJEDZIE_REVISION=${COJEDZIE_REVISION}

FROM php-fpm AS fpm
RUN echo "display_errors=Off" >> $PHP_INI_DIR/conf.d/zz-errors.ini

COPY --from=app /var/www/vendor ./vendor
COPY --from=app /var/www ./

ENV APP_ENV=prod PATH=$PATH:/var/www/bin
VOLUME /var/db

ENTRYPOINT ["./bin/docker-entrypoint.sh"]
CMD ["./bin/docker-init.sh", "php-fpm"]

ARG COJEDZIE_VERSION
ARG COJEDZIE_REVISION
ENV COJEDZIE_VERSION=${COJEDZIE_VERSION} COJEDZIE_REVISION=${COJEDZIE_REVISION}

# ---- Development image ------------------------------------------------------------------------------------------------
#
# Has no application code: the dev compose stack mounts ./api at /var/www. It runs php-fpm or RoadRunner, see
# docker/dev-server.sh, and bin/docker-dev-entrypoint.sh switches xdebug off outside of debug mode.

FROM php-fpm AS dev
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=roadrunner /usr/bin/rr /usr/bin/rr

RUN apk add --no-cache git su-exec tini \
 && apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers \
 && pie install xdebug/xdebug \
 && apk del --purge .xdebug-deps

RUN { \
        echo "xdebug.mode=debug"; \
        echo "xdebug.discover_client_host=On"; \
    } >> $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

COPY docker/fpm-log.conf /usr/local/etc/php-fpm.d/zz-log.conf
COPY docker/dev-server.sh /usr/local/bin/dev-server

EXPOSE 9000 8080
ENTRYPOINT ["./bin/docker-dev-entrypoint.sh"]
CMD ["dev-server"]
