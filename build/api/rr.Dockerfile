ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:$BASE_VERSION as base

FROM php:7.4-alpine

LABEL maintainer="Kacper Donat <kacper@kadet.net>"

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

WORKDIR /var/www

RUN install-php-extensions bcmath intl opcache zip sockets;

COPY --from=base /var/www /var/www

ENV APP_ENV=prod
ENV DATABASE_URL="sqlite:////var/db/app.db"
ENV PATH=$PATH:/var/www/bin

VOLUME /var/db

EXPOSE 8080

CMD ["./bin/docker-init.sh", "rr", "serve"]
