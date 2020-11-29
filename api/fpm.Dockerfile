FROM php:7.4-fpm-alpine

RUN apk add --no-cache autoconf openssl-dev g++ make pcre-dev icu-dev zlib-dev libzip-dev git && \
    docker-php-ext-install bcmath intl opcache zip sockets;

# XDebug
RUN pecl install xdebug-3.0.0 && docker-php-ext-enable xdebug
RUN echo "xdebug.mode = develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.discover_client_host = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

RUN apk del --purge autoconf g++ make

# Blackfire
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

#Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Timezone
RUN ln -snf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime &&
    echo "date.timezone = Europe/Warsaw" >> /usr/local/etc/php/conf.d/datetime.ini;

WORKDIR /var/www

EXPOSE 9001
