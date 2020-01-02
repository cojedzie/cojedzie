FROM php:7.3-fpm

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip libzip-dev

RUN docker-php-ext-install zip

RUN pecl install xdebug-2.9.0 && docker-php-ext-enable xdebug

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN ln -snf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime

WORKDIR /var/www