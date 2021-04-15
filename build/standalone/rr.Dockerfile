ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM ${REGISTRY}/cojedzie/api:${BASE_VERSION}-rr

RUN apk add supervisor && \
    { \
        echo '[supervisord]'; \
        echo 'nodaemon=true'; \
        echo ; \
        echo '[program:roadrunner]'; \
        echo 'command=rr serve'; \
        echo 'startsecs=0'; \
        echo 'start=true'; \
        echo 'autorestart=true'; \
        echo 'stdout_logfile=/dev/stdout'; \
        echo 'stderr_logfile=/dev/stderr'; \
        echo 'stdout_logfile_maxbytes=0'; \
        echo 'stderr_logfile_maxbytes=0'; \
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

CMD ["./bin/docker-init.sh", "supervisord", "-c", "/etc/supervisord.conf"]
