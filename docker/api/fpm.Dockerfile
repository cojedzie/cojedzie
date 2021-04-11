FROM php:7.4-fpm-alpine

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN install-php-extensions bcmath intl opcache zip sockets;

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

CMD ["./bin/docker-init.sh", "php-fpm"]
