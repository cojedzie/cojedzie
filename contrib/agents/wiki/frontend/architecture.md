# Frontend architecture (`front/`)

Vue 3 (mix of `vue-class-component`/`vue-property-decorator` class components and composables), Vuex with
`vuex-persist`, vue-router, Leaflet + mapbox-gl maps, Bootstrap + Sass, built with Vite into `front/build/public`.
Path alias `@/` → `front/src/`.

## Serving

`front/server/server.ts` is an Express server (compiled with `yarn build:server` to `build/server/server.js`).
It renders the HTML shell (EJS views in `resources/views`), injects runtime config from env
(`COJEDZIE_API`, `COJEDZIE_API_HUB`, `COJEDZIE_MAPTILER_KEY`, `COJEDZIE_GTM`, Sentry settings) and serves
provider manifests. With `COJEDZIE_MODE=development` it mounts Vite as middleware (HMR on port 3001);
otherwise it serves the built assets using the Vite `manifest.json`.

Runtime config reaches components via `composables/useAppConfig`.

Sentry: the browser (`src/sentry.ts`, project `frontend-vue`) is configured from `SENTRY_DSN` and friends passed
through that runtime config; the Express server reports to its own project (`frontend-server`) from
`SENTRY_SERVER_DSN`. `server/instrument.ts` initialises it and must stay the first import of `server.ts`, so Sentry
can instrument Express. Node is 24 in every image and compose service (Sentry SDK 11 needs 20.19+).

## Structure worth knowing

- `src/api` — typed endpoint map (`endpoints.ts`), HTTP / static / load-balanced clients, load balancer
  (see [API nodes and load balancing](#api-nodes-and-load-balancing)).
- `src/store` — root store, modules (`departures`, `messages`, `favourites`, `history`, `network`, `settings`),
  and `migrations.ts` for persisted-state schema migrations.
- `src/utils/container.ts` — small service container, accessed via `composables/useService`; tested in
  `front/test/container.test.ts`.
- `src/model` — domain types mirroring the API DTOs.
- `src/stories` + `.storybook` — Storybook (served at `/storybook/` through nginx in docker). The `storybook`
  service sets `STORYBOOK_BASE_HREF`, `COJEDZIE_MAPTILER_KEY` and `COJEDZIE_API`, and runs with
  `--no-version-updates` (the version check crashes on Node 24).
- Docker: `front/Dockerfile` has a `dev` target (used by the compose `frontend`, `storybook` and `front-setup`
  services, run as `COJEDZIE_UID`) and a `prod` target (the published image).

## Style

Prettier (`printWidth: 120`, `arrowParens: avoid`) + ESLint (`.eslintrc.js`). Source files carry the AGPL
license header.

## API nodes and load balancing

`src/api/loadbalancer.ts` keeps a list of available nodes (Vuex `network` module, refreshed every 60s) and picks
a node that exposes the requested endpoint and satisfies a `require` predicate (e.g. minimum version), falling
back to the default API. Endpoint names in `src/api/endpoints.ts` mirror the Symfony route names (`v1_stop_list`,
...), so renaming a route or changing its version is a cross-stack change.

Node side and endpoint versioning: [../api/federation.md](../api/federation.md).
