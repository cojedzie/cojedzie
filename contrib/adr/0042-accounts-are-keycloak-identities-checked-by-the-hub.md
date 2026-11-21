# 0042. Accounts are Keycloak identities, the hub's API only checks their tokens

- **Status:** Accepted
- **Date:** 2026-11-21
- **Applies to:** `api/config/packages/security.yaml`, `api/src/Security/**`, the account endpoints and entities of
  the API, the frontend's sign-in and API client (`front/src/api/**`), `auth/` (the Keycloak theme),
  `docker-compose.yml`, `docker-bake.hcl`

## Context

Riders should be able to sign up, so their stop sets and settings follow them between devices (ADR 0013 planned a
server backend for saved state). Later, accounts will hold more, e.g. alerts the server has to act on.

Running identity ourselves (passwords, email verification, password reset, social sign-in, MFA, brute-force
protection, login pages) is security work a one-person project should not own. The API must get one verified
identity and nothing else. Everything else in the API is anonymous, federated servers authenticate through Symfony
Security (ADR 0008), and the production database is MySQL.

## Considered options

1. **Identity in Symfony** (users, passwords, mailers, login forms). Rejected: it is exactly the security work we
   want to stay out of.
2. **Ory Kratos**: headless, light (one Go binary), MySQL, our own login pages, and session-to-JWT in the open-source
   build. Rejected: no rate limiting or brute-force protection in the open-source build, CAPTCHA and guaranteed
   security patches only in the enterprise build, open-source releases about once a year, and no OIDC without also
   running Ory Hydra. Its direction depends on a commercial edition we don't use.
3. **Zitadel**: full OIDC, configured through an official Terraform provider, riders can delete their own
   accounts, and a login app (Next.js) to customise or replace. Rejected: it isn't lighter than Keycloak (about
   512 MB plus the login container), owning the login pages means forking its monorepo or writing a login UI that
   proxies the OIDC endpoints and keeps the session cookie (security work we want to avoid), brute-force lockout is
   off by default and only an admin can unlock, breaking changes ship even in minor releases, and it runs only on
   PostgreSQL. Moving production to PostgreSQL wouldn't change the choice.
4. **Keycloak** (chosen): full OIDC, brute-force detection, frequent Apache-2.0 releases, CNCF governance, runs on
   MySQL and PostgreSQL. A JVM service, and its theme engine is poor, which **Keycloakify** solves: the login and
   account pages are written as React components and packaged as a theme.

For the token flow, a backend-for-frontend (the Express server keeping tokens behind a cookie) was rejected: the
Express server only renders the page shell and would have to proxy the API. Storing account data on every
federated node was rejected: nodes serve provider data and should stay anonymous.

## Decision

We will use **Keycloak** as the identity provider for accounts:

- **Keycloak owns identity**: sign-up, sign-in, email verification, password reset, social sign-in, MFA and their
  pages. Its login and account pages are a Keycloakify theme in this repository (`auth/`), built into our own
  Keycloak image. The realm configuration lives in the repository and is applied to every environment.
- **The frontend is a public OIDC client** using the Authorization Code flow with PKCE through `oidc-client-ts`
  (provider-agnostic; `react-oidc-context` wraps the same library once the app moves to React), signing in by
  redirect, and sends the access token as a Bearer token.
- **Accounts are optional per deployment**: without an identity provider in the configuration, the frontend shows
  no account entry and the API has no account endpoints.
- **The API is a resource server**: a stateless Symfony Security firewall checks the JWT against Keycloak's JWKS. It
  never handles passwords, sessions or Keycloak's login.
- **The API stores no identity data.** An account is a row keyed by the token's `sub`, created on first use, plus
  the data the account owns. Email, name and credentials stay in Keycloak.
- **Account data lives only on the hub.** Federated nodes never store it; whether they ever see a token is outside
  this decision.
- **The API talks to Keycloak only through standard OIDC**, except for deleting an account: the API deletes the
  account's data, then the identity through Keycloak's admin API. Self-service deletion in Keycloak is off.

## Consequences

- Signing up and signing in need no security code of ours, only a theme and a realm config.
- One more service to run: Keycloak in production (its own database on the production database server, whichever
  engine that is) and in the dev stack, with a realm imported from the repository. It needs SMTP for email
  verification and password reset.
- The theme is React while the app is Vue, until the planned move to React.
- Replacing Keycloak with another OIDC provider touches the realm config, the theme and account deletion, but not
  the API's checks or the frontend's flow; account rows keyed by `sub` would need mapping to the new provider's ids.
- API tests don't run Keycloak: they sign tokens with a test key and serve its JWKS.

## Compliance

- The API has no password, credential or session handling for riders, and no user provider loading identities from
  its own database: riders are authenticated only by checking Keycloak-issued JWTs in a stateless firewall.
- The API stores no email, name or other identity attribute of an account, only `sub` and account-owned data.
- API code calls Keycloak only through OIDC discovery and JWKS, and through the admin API only to delete an
  identity.
- Account data has entities and endpoints only on the hub; federated node code paths don't read or write it.
- The frontend reaches the identity provider only through `oidc-client-ts`, never `keycloak-js` or Keycloak-specific
  endpoints.
- Login, registration and account pages are changed in the Keycloakify theme in `auth/`, not in `front/`.
- Realm settings are changed in the realm config in the repository, not only in a running Keycloak.
