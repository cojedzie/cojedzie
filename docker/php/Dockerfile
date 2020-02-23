FROM php:7.3-fpm

ARG XDEBUG_REMOTE_HOST="172.17.0.1"

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip libzip-dev

RUN docker-php-ext-install zip

RUN pecl install xdebug-2.9.0 && docker-php-ext-enable xdebug

RUN echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_host = ${XDEBUG_REMOTE_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;
RUN echo "date.timezone = Europe/Warsaw" >> /usr/local/etc/php/conf.d/datetime.ini;

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN ln -snf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime

WORKDIR /var/www

EXPOSE 9001
