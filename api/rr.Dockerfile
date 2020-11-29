FROM php:7.4-alpine

RUN apk add --no-cache autoconf openssl-dev g++ make pcre-dev icu-dev zlib-dev libzip-dev git && \
    docker-php-ext-install bcmath intl opcache zip sockets && \
    apk del --purge autoconf g++ make

WORKDIR /usr/src/app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install --no-dev --no-scripts --no-plugins --prefer-dist --no-progress --no-interaction
RUN ./vendor/bin/rr get-binary --location /usr/local/bin

ENV APP_ENV=prod
ENV DATABASE_URL="sqlite:///var/db/app.db"

RUN composer dump-autoload --optimize && \
    composer check-platform-reqs && \
    php bin/console cache:warmup

EXPOSE 8080

CMD ["rr", "serve"]
