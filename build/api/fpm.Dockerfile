ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:${BASE_VERSION}-fpm as base

ENTRYPOINT ["./bin/docker-entrypoint.sh"]
CMD ["./bin/docker-init.sh", "php-fpm"]
