ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:${BASE_VERSION}-cli

RUN apk add tini && \
    echo "* * * * *     cd /var/www && ./bin/console schedule:run" >> /etc/crontabs/root;

ENTRYPOINT ["/sbin/tini", "--"]
CMD ["crond", "-l", "2", "-f"]
