FROM php:7.4-cli-alpine

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

RUN apk add supervisor && \
    { \
        echo '[supervisord]'; \
        echo 'nodaemon=true'; \
        echo ; \
        echo '[program:messenger-consumer]'; \
        echo 'command=php /var/www/bin/console messenger:consume main -vv --time-limit=86400 --limit=10'; \
        echo 'startsecs=0'; \
        echo 'start=true'; \
        echo 'autorestart=true'; \
        echo 'stdout_logfile=/dev/stdout'; \
        echo 'stderr_logfile=/dev/stderr'; \
        echo 'stdout_logfile_maxbytes=0'; \
        echo 'stderr_logfile_maxbytes=0'; \
    } | tee /etc/supervisord.conf;

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
