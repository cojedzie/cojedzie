FROM php:8.1-alpine

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

WORKDIR /var/www

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath intl opcache zip sockets pdo_pgsql pdo_mysql;

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN apk add git && \
    composer install --no-dev --no-scripts --no-plugins --prefer-dist --no-progress --no-interaction && \
    composer dump-autoload --optimize && \
    composer check-platform-reqs && \
    composer clear-cache && \
    apk del --purge git

COPY . .

ENV APP_ENV=prod
ENV DATABASE_URL="sqlite:////var/db/app.db"
ENV PATH=$PATH:/var/www/bin

RUN composer run-script post-install-cmd

VOLUME /var/db

# This image is not meant to be run, just to prepare all required files
CMD ["/bin/false"]
