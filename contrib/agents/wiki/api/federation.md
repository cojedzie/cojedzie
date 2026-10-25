# Federation

Co Jedzie can run as a network of API nodes: a **hub** (default `https://cojedzie.pl`, env `FEDERATION_HUB`)
plus independently hosted **federated nodes** that serve the same API.

## Node side (api)

- Configuration via env: `FEDERATION_SERVER_ID`, `FEDERATION_URL` (advertised URL), `FEDERATION_HUB`,
  `FEDERATION_CONNECTION_ID` — wired into `Context/FederationContext`.
- `bin/docker-entrypoint.sh`: when `FEDERATION_SERVER_ID` is set, runs `federation:connect` against the hub,
  exports the returned `FEDERATION_CONNECTION_ID`, and calls `federation:disconnect` on SIGINT/SIGQUIT/SIGTERM.
- Hub side: `Controller/Api/v1/FederationController` (`/api/v1/federation/connections`), entities in
  `Entity/Federation`, health checks via `Message/CheckFederatedConnection` and cleanup via
  `Message/CleanupFederatedConnections`. `federation:*` console commands live in `src/Command`.
- `docker-compose.federated.yml` runs a local federated node (RoadRunner) against the local nginx as hub.

## Endpoint versioning

Routes declare `options: ['version' => 'x.y']`. `Service/StatusService` publishes every route that has a version
(name, version, methods, path template). Nodes therefore advertise which endpoint versions they support.

## Client side

The frontend picks nodes by endpoint name and version — see
[../frontend/architecture.md](../frontend/architecture.md#api-nodes-and-load-balancing).
