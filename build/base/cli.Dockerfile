ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:$BASE_VERSION as base

FROM php:7.4-cli-alpine

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

WORKDIR /var/www

RUN install-php-extensions bcmath intl opcache zip sockets;

COPY composer.json composer.lock ./

COPY --from=base /var/www /var/www

ENV APP_ENV=prod
ENV DATABASE_URL="sqlite:////var/db/app.db"
ENV PATH=$PATH:/var/www/bin

VOLUME /var/db

# This image is not meant to be run, just to prepare all required files
CMD ["/bin/false"]
