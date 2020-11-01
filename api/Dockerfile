FROM php:7.3-fpm

ARG XDEBUG_REMOTE_HOST="172.17.0.1"

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip libzip-dev

RUN docker-php-ext-install zip

# XDebug
RUN pecl install xdebug-2.9.0 && docker-php-ext-enable xdebug
RUN echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_host = ${XDEBUG_REMOTE_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

# Blackfire
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

#Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

# Timezone
RUN ln -snf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime
RUN echo "date.timezone = Europe/Warsaw" >> /usr/local/etc/php/conf.d/datetime.ini;

WORKDIR /var/www

EXPOSE 9001
