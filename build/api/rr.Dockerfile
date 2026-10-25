ARG BASE_VERSION=latest
ARG REGISTRY=docker.io

FROM $REGISTRY/cojedzie/base:${BASE_VERSION} as base

COPY --from=ghcr.io/roadrunner-server/roadrunner:2025.1.15 /usr/bin/rr /usr/bin/rr

EXPOSE 8080

ENTRYPOINT ["./bin/docker-entrypoint.sh"]
CMD ["./bin/docker-init.sh", "rr", "serve"]
