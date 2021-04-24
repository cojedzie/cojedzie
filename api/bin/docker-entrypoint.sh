#!/bin/sh

cleanup() {
    echo "Leaving the federation"
    ./bin/console federation:disconnect
    exit $?
}

if [ -n "$FEDERATION_SERVER_ID" ]; then
  # fixme: this whole script probably should ran inside some init process like tini

  if [ -z "$FEDERATION_URL" ]; then
    echo "You have to specify base URL for this federated node by the \$FEDERATION_URL environment variable." >&2
    exit 1
  fi

  echo "Running in federation mode as ${FEDERATION_URL} (${FEDERATION_SERVER_ID})."
  FEDERATION_CONNECTION_ID=$(./bin/console federation:connect)

  # If connection not succeeded terminate
  if [ $? -ne 0 ]; then
    echo "$FEDERATION_CONNECTION_ID"
    exit 1
  fi

  echo "Joined the federation with connection id: ${FEDERATION_CONNECTION_ID}"

  # make connection id available to the server
  export FEDERATION_CONNECTION_ID

  trap cleanup 2 3 15 # SIGINT SIGQUIT SIGTERM
  "$@" &
  wait $!
  cleanup
else
  exec "$@"
fi
