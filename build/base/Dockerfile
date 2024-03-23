FROM php:8.3-alpine

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

WORKDIR /var/www

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath intl opcache zip sockets pdo_pgsql pdo_mysql ds amqp redis && \
    echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.jit_buffer_size=100m" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.jit=tracing" >> $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini;

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN apk add git && \
    composer install --no-dev --no-scripts --no-plugins --prefer-dist --no-progress --no-interaction && \
    composer dump-autoload --optimize && \
    composer check-platform-reqs && \
    composer clear-cache && \
    apk del --purge git

COPY . .

ARG COJEDZIE_REVISION
ARG COJEDZIE_VERSION
ENV COJEDZIE_REVISION="${COJEDZIE_REVISION}" COJEDZIE_VERSION="${COJEDZIE_VERSION}"
ENV APP_ENV=prod
ENV DATABASE_URL="sqlite:////var/db/app.db"
ENV PATH=$PATH:/var/www/bin

VOLUME /var/db

RUN mkdir -p /var/db
RUN composer run-script post-install-cmd

# This image is not meant to be run, just to prepare all required files
CMD ["/bin/false"]
