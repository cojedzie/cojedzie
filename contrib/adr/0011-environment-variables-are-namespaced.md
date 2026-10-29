# 0011. Environment variables controlling the app are namespaced

- **Status:** Accepted
- **Date:** 2026-10-29
- **Applies to:** `api/config/**`, `api/src/**` (`#[Autowire(env: …)]`, `$_ENV`/`$_SERVER`), `api/.env`, `front/server/**`,
  `front/src/**`, `build/**`, `api/bin/**`, `docker-compose*.yml`, `Taskfile.yml`

## Context

The API and frontend read their settings from environment variables. Some had a prefix (`COJEDZIE_VERSION`,
`COJEDZIE_WORKER_QUEUES`, the frontend's `COJEDZIE_API`), others had generic names (`SCHEDULE_UPDATE_DATA_SOURCES`,
`STALE_DATA_RETENTION`, `APP_EVENT_QUEUE`, `APP_MODE`). Generic names are easy to collide with variables of the
platform, other services in the same stack, or tools, and it's hard to tell from a deployment's environment which
variables configure Co Jedzie.

## Considered options

1. **Everything under `COJEDZIE_`**, including standard names like `DATABASE_URL` or `SENTRY_DSN`. One namespace,
   but it fights Symfony, recipes and SDKs that know the standard names.
2. **Variables that control the product are namespaced**: `COJEDZIE_` for general settings, or the prefix of the
   subsystem they belong to (`FEDERATION_`, `SENTRY_`, a provider's `ZTM_GDANSK_`). Standard names stay.
3. **No convention** (as before).

## Decision

We will namespace every environment variable that controls the product (option 2):

- General settings use `COJEDZIE_` (`COJEDZIE_SCHEDULE_UPDATE_DATA_SOURCES`, `COJEDZIE_STALE_DATA_RETENTION`,
  `COJEDZIE_EVENT_QUEUE`, `COJEDZIE_API_MODE`).
- Settings of a subsystem or integration may use its own prefix: `FEDERATION_`, `SENTRY_`, a provider's identifier
  (`ZTM_GDANSK_`).
- Names defined by Symfony, its recipes and other tools keep their standard names: `APP_ENV`, `APP_DEBUG`,
  `APP_SECRET`, `DATABASE_URL`, `MERCURE_*`, `CORS_ALLOW_ORIGIN`, `TRUSTED_PROXIES`, `SENTRY_DSN`, ….
- Renamed variables keep working under their previous names for a transition period: the API copies them in
  `App\Utility\Environment` (with a deprecation notice), shell scripts use `${NEW:-$OLD}`.

## Consequences

- Deployments can see at a glance which variables configure Co Jedzie.
- The fallbacks in `App\Utility\Environment::LEGACY_NAMES` and the dev entrypoint are temporary; once the deployments
  use the new names they are removed.

## Compliance

- A new environment variable read by the app is prefixed with `COJEDZIE_` or with the prefix of the subsystem it
  configures (`FEDERATION_`, `SENTRY_`, a provider's identifier), unless it is a standard name of Symfony, a recipe
  or a third-party tool.
- Renaming a variable adds its old name to `App\Utility\Environment::LEGACY_NAMES` (API) or a `${NEW:-$OLD}`
  fallback (shell), and the wiki and ADRs refer to the new name.
