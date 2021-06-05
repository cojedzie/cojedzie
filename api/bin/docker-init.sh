#!/bin/sh

if ./bin/console doctrine:migrations:up-to-date | grep -q 'Out-of-date!'; then
  # do the migrations
  ./bin/console doctrine:migrations:migrate --no-interaction
  # update data synchronously
  ./bin/console app:update
fi

exec "$@"
