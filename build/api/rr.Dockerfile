ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:${BASE_VERSION} as base

COPY --from=spiralscout/roadrunner:1.9.2 /usr/bin/rr /usr/bin/rr

EXPOSE 8080

CMD ["./bin/docker-init.sh", "rr", "serve", "-v"]
