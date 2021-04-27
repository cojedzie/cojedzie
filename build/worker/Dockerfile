ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:${BASE_VERSION}-cli

RUN apk add supervisor gettext && \
    { \
        echo '[supervisord]'; \
        echo 'nodaemon=true'; \
        echo 'logfile=/var/log/supervisord.log'; \
        echo 'pidfile=/var/run/supervisord.sock'; \
        echo ; \
        echo '[program:messenger-consumer]'; \
        echo 'command=php /var/www/bin/console messenger:consume $COJEDZIE_WORKER_OPTS $COJEDZIE_WORKER_QUEUES'; \
        echo 'startsecs=0'; \
        echo 'start=true'; \
        echo 'autorestart=true'; \
        echo 'stdout_logfile=/dev/stdout'; \
        echo 'stderr_logfile=/dev/stderr'; \
        echo 'stdout_logfile_maxbytes=0'; \
        echo 'stderr_logfile_maxbytes=0'; \
    } | tee /etc/supervisord.conf.tpl;

COPY ./supervisord-init.sh ./bin/supervisord-init.sh

ENV COJEDZIE_WORKER_QUEUES=main
ENV COJEDZIE_WORKER_OPTS="-vv --time-limit=86400 --limit=10 --memory-limit=128M"

CMD ["./bin/supervisord-init.sh", "supervisord", "-c", "/etc/supervisord.conf"]
