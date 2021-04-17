#!/bin/sh

if [ -f /etc/supervisord.conf.tpl ]; then
  envsubst < /etc/supervisord.conf.tpl > /etc/supervisord.conf
fi

exec "$@"
