#!/bin/sh
# Starts the server selected by COJEDZIE_DEV_SERVER: fpm (default) or rr.
case "${COJEDZIE_DEV_SERVER:-fpm}" in
    fpm)
        # php-fpm can't open /proc/self/fd/2 when it doesn't run as root, so its log goes through a fifo
        rm -f /tmp/fpm-error
        mkfifo /tmp/fpm-error
        cat /tmp/fpm-error >&2 &
        exec php-fpm
        ;;
    rr) exec rr serve -c .rr.yaml ;;
    *)
        echo "Unknown COJEDZIE_DEV_SERVER '$COJEDZIE_DEV_SERVER', expected fpm or rr" >&2
        exit 1
        ;;
esac
