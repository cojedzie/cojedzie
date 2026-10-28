# Federation

Co Jedzie can run as a network of API nodes: a **hub** (default `https://cojedzie.pl`, env `FEDERATION_HUB`)
plus independently hosted **federated nodes** that serve the same API.

## Node side (api)

- Configuration via env: `FEDERATION_SERVER_ID`, `FEDERATION_URL` (advertised URL), `FEDERATION_HUB`,
  `FEDERATION_CONNECTION_ID` — wired into `Context/FederationContext`.
- `FEDERATION_SERVER_SECRET`: the secret `federation:register` printed for the server. The node sends the server
  id and secret as HTTP Basic credentials (`FederatedConnectionService`). Only its bcrypt hash is stored on the
  hub, so a lost secret means registering the server again, under a new id.
- `bin/docker-entrypoint.sh`: when `FEDERATION_SERVER_ID` is set, runs `federation:connect` against the hub,
  exports the returned `FEDERATION_CONNECTION_ID`, and calls `federation:disconnect` on SIGINT/SIGQUIT/SIGTERM.
- Hub side: `Controller/Api/v1/FederationController` (`/api/v1/federation/connections`), entities in
  `Entity/Federation`, health checks via `Message/CheckFederatedConnection` and cleanup via
  `Message/CleanupFederatedConnections`. `federation:*` console commands live in `src/Command`.
- `docker-compose.federated.yml` runs a local federated node (RoadRunner) against the local nginx as hub.

## Authentication (hub side)

`/api/v1/federation` is the only path behind a Symfony Security firewall (`config/packages/security.yaml`,
stateless). Everything else stays anonymous. ADR 0008 records the decision.

- `FederatedServerEntity` is the security user: identifier = server id, password = the bcrypt secret hash, role
  `ROLE_FEDERATED_SERVER`. Its security getters are `#[Ignore]`d, so the hash never gets serialized.
- `Security/FederatedServerAuthenticator` reads the Basic credentials (`PHP_AUTH_USER`/`PHP_AUTH_PW`) and is also
  the entry point. Missing or wrong credentials, or a non-UUID id, get a 401 with
  `WWW-Authenticate: Basic realm="Co Jedzie federation"` and the problem-shaped body other errors have. The body
  is serialized directly, because the firewall runs before `JSONFormatSubscriber` sets `_format`.
- **Connect:** the connection belongs to the authenticated server (no `server_id` in the body). Its `url` must be
  an http(s) URL (otherwise 400) under the server's `allowedUrl` (otherwise 403). `allowedUrl` is a prefix on a
  path boundary (`FederatedServerEntity::allows()`): `https://node.example` allows `https://node.example/v2` but
  not `https://node.example:8080` or `https://node.example.evil.com`. This also limits the URLs the hub's health
  checker fetches to ones an admin registered.
- **Disconnect:** `Security/FederatedConnectionVoter` (`FEDERATED_CONNECTION_CLOSE`) lets only the owning server
  close a connection; others get 403.

## Endpoint versioning

Routes declare `options: ['version' => 'x.y']`. `Service/StatusService` publishes every route that has a version
(name, version, methods, path template). Nodes therefore advertise which endpoint versions they support.

## Client side

The frontend picks nodes by endpoint name and version — see
[../frontend/architecture.md](../frontend/architecture.md#api-nodes-and-load-balancing).
