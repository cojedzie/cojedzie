#!/bin/sh

if ./bin/console doctrine:migrations:up-to-date | grep -q 'Out-of-date!'; then
  # do the migrations
  ./bin/console doctrine:migrations:migrate --no-interaction
  # import data in the workers, a first import of the whole schedule window takes too long to wait for
  ./bin/console app:update --async
fi

exec "$@"
