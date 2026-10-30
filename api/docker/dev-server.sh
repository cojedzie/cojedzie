#!/bin/sh
# Starts the server selected by COJEDZIE_DEV_SERVER: fpm (default) or rr.
case "${COJEDZIE_DEV_SERVER:-fpm}" in
    fpm) exec php-fpm ;;
    rr) exec rr serve -c .rr.yaml ;;
    *)
        echo "Unknown COJEDZIE_DEV_SERVER '$COJEDZIE_DEV_SERVER', expected fpm or rr" >&2
        exit 1
        ;;
esac
