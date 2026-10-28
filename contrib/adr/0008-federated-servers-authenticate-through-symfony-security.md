# 0008. Federated servers authenticate through Symfony Security

- **Status:** Accepted
- **Date:** 2026-10-28
- **Applies to:** `api/src/Controller/Api/v1/FederationController.php`, `api/src/Security/**`,
  `api/src/Entity/Federation/**`, `api/src/Service/FederatedConnectionService.php`,
  `api/config/packages/security.yaml`

## Context

The hub lets federated nodes join the network: a node opens a connection with its URL, the frontend then sends
users there, and the hub health-checks that URL. Both federation endpoints accepted anyone. Any URL could be
registered, which also let anyone make the hub fetch arbitrary URLs, and connection ids are public, so anyone
could close every connection. Each server already had a secret (printed once by `federation:register`, stored as
a bcrypt hash) and an allowed URL, but neither was checked. Everything else in the API is anonymous. User
management is planned and will need authentication too.

## Considered options

1. **Symfony Security with a firewall over the federation endpoints**, the server as the user, HTTP Basic with
   the server id and secret. One more bundle, but it's the mechanism user management will use as well.
2. **A hand-written argument resolver** that checks the credentials and hands the controller the server. Less
   machinery for two endpoints, but a second authentication mechanism to replace when users arrive.
3. **`allowedUrl` as a regex**, as the old help text suggested. Flexible, but a loose or unanchored pattern
   reopens the hole.
4. **`allowedUrl` as an exact URL.** Simplest, but one server could not serve several base paths.
5. **`allowedUrl` as a prefix on a path boundary.** Allows several base paths under one registration while
   refusing lookalike hosts and ports.

## Decision

We will authenticate federated servers through `symfony/security-bundle` (option 1): a stateless firewall over
`^/api/v1/federation` with `App\Security\FederatedServerAuthenticator`, which reads HTTP Basic credentials
(server id, secret) and checks the secret against the stored hash. A server may only open connections at URLs
under its `allowedUrl`, matched as a prefix on a path boundary (option 5), and only close its own connections.

## Consequences

- Nodes need `FEDERATION_SERVER_SECRET`. Nodes from before this change can't connect; there is no transition
  period, as every node is run by the project owner.
- A lost secret can't be recovered: the server is registered again under a new id.
- Public endpoints have no firewall and behave as before. User management can add its own firewall next to this
  one.

## Compliance

- Federation endpoints that change state are under the `federation` firewall and require `ROLE_FEDERATED_SERVER`;
  they take the acting server from the security token, never from the request body.
- A connection's URL is checked with `FederatedServerEntity::allows()` before it is stored.
- Actions on an existing connection go through `FederatedConnectionVoter`, or an equivalent ownership check.
- Server secrets are stored only as password hashes, and the security getters of `FederatedServerEntity` are not
  serialized.
