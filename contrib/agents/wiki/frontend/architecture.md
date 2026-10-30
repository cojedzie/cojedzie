# Frontend architecture (`front/`)

Vue 3 (every component is `<script setup lang="ts">`), Pinia, vue-router, Leaflet + mapbox-gl maps, Bootstrap +
Sass, built with Vite into `front/build/public`.
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
- `src/stores` — Pinia setup stores and the persistence layer, see [State](#state-stores-and-persistence).
- `src/composables` — shared logic of components (`useRemoved`, `useIsTouch`, `useService`, `useAppConfig`, ...).
  Nothing is registered globally on the app except directives: those in `src/filters.ts`, and `dragscroll` (from
  `vue-dragscroll`) registered in `src/app.ts`. Components are imported where they are used.
- `src/utils/container.ts` — small service container, accessed via `composables/useService`; tested in
  `front/test/container.test.ts`.
- `src/model` — domain types mirroring the API DTOs.
- `src/stories` + `.storybook` — Storybook, served at http://storybook.cojedzie.localhost through nginx in docker
  (`nginx/storybook.conf`; also published on port 6006). It gets its own host because Storybook injects preview
  scripts at root paths that ignore a Vite `base`, so it can't live under a path of the app's host. The `storybook`
  service sets `COJEDZIE_MAPTILER_KEY` and `COJEDZIE_API`, and runs with `--host 0.0.0.0` (Storybook listens on
  127.0.0.1 by default) and `--no-version-updates` (the version check crashes on Node 24).
- Docker: `front/Dockerfile` has a `dev` target (used by the compose `frontend`, `storybook` and `front-setup`
  services, run as `COJEDZIE_UID`) and a `prod` target (the published image).

## Style

Prettier (`printWidth: 120`, `arrowParens: avoid`) + ESLint 10 (flat config, `eslint.config.mjs`). Source files
carry the AGPL license header.

## Checks

`task lint:front` runs `yarn check:format` (Prettier), `yarn lint` (ESLint, errors fail, warnings don't) and
`yarn check:types` (`vue-tsc` for the app including `.vue` templates, plain `tsc` for `server/`); `task test:front`
runs Vitest. CI runs the same scripts in the `test-front` job, which the Release workflow requires next to `test`.
The type check needs the FontAwesome Pro packages in `resources/fontawesome` (fetched in CI by
`.github/actions/fontawesome`).

Vite does not type check, so a broken type only shows up in these checks (`vue-tsc` 3 also checks templates).

## State: stores and persistence

State lives in Pinia **setup stores** (`defineStore("id", () => {...})`) in `src/stores`. Components use them through
`useXxxStore()` and `storeToRefs`. Ids: `favourites`, `departures-settings`, `messages-settings`, `history`,
`selection`, `network`, `departures`, `messages`. `fetching.ts` holds the fetching state shared by `departures` and
`messages`.

The API client (`src/api/client/shared.ts`) is store-free. It reaches the network and provider state through
`bindClientContext` (`src/api/context.ts`); binding triggers one node update. Importing the client creates no store.

### Persistence (`src/stores/persistence`)

- `storage.ts`: `UserStateStorage` (`load` / `save` / `remove`, all async, JSON values, `load` returns a copy).
- `browser.ts` `BrowserStorage` (over `localStorage` / `sessionStorage`) and `memory.ts` `MemoryStorage` (tests).
- `storages.ts`: `storages.local` and `storages.session`, which touch `window` on first use. `replaceStorages()`
  swaps them in tests.
- `plugin.ts`: `persistence()` Pinia plugin. A store opts in with `persist: { key, storage, pick?, restore? }` and
  gets `$hydrated`, a promise that resolves once the saved state is applied (default `pick` is the whole state,
  default `restore` is `$patch(saved)`). Saving is debounced (500 ms) and starts only after loading succeeded; a
  failed load is logged and leaves that store unsaved for the session, so defaults never overwrite unreadable
  data. Pending saves are flushed when the page gets hidden (`flushPersistence()`).
- `migrations.ts`: key-agnostic runner over a `UserStateStorage`. Applied names are kept in
  `localStorage["migrations"]`, recorded only after a migration succeeds. A failure stops the runner, goes to
  Sentry, and the app still starts. Migrations run before the client context is bound, so an API request in one
  passes bound params like `provider` itself, and sets a low `maxRetries` so a failure doesn't hold up startup (the
  balanced client otherwise retries for about 30 s). A migration may reach `storages.session` too (the old
  `sessionStorage["vuex"]` is removed that way).

Saved keys: `favourites`, `departures-settings`, `messages-settings`, `history` in `localStorage`; `selection` in
`sessionStorage`. `test/stores/persistence/format.test.ts` pins the exact JSON of each.

### Startup order (`src/app.ts`)

1. Create Pinia with `persistence()`.
2. `migrate(...)` over a `BrowserStorage(localStorage)` (before any store exists).
3. Bind the client context to the `network` and `selection` stores.
4. Create the saved stores and await their `$hydrated`.
5. Mount.

### How to add

- **A store**: `src/stores/<id>.ts` with `defineStore("<id>", () => ...)`, state as refs, getters as computeds,
  actions as functions; add `test/stores/<id>.test.ts` with a fresh `createPinia()`.
- **A saved store**: pass `persist: { key, storage: storages.local | storages.session }` in the store options
  (third argument of `defineStore`), use `pick` / `restore` when the saved shape differs from the state (e.g.
  dates), add the store to the awaited list in `app.ts` and its key to `format.test.ts`. Stores never touch
  `localStorage` directly.
- **A storage backend**: implement `UserStateStorage` and run `describeUserStateStorage` from
  `test/stores/persistence/storage.contract.ts` against it (see `browser.test.ts`). The backend must pass the
  whole contract.
- **A migration**: append `{ name: "<yyyymmddhhmm>_<what>", up(storage) }` to `migrations` in `migrations.ts`
  (never reorder or edit applied ones) and cover it in `migrations.test.ts`. Any change to a saved shape needs one,
  and an update of `format.test.ts`.

### Component tests

Vitest with `// @vitest-environment happy-dom` and `@vue/test-utils`, in `test/components/**` and `test/pages/**`.
Create a store with `createPinia()` + `setActivePinia()`, set up the state through the store, and mount with
`global.plugins: [pinia]`. Stub icons and library wrappers (`stubs: { UiIcon: true }`, maps, popper). Assert on what
is rendered, what is emitted and which store state changes. For saved stores, use `replaceStorages` with
`MemoryStorage` instead of the browser. API calls are stubbed at the client level (see `test/api/client.test.ts`).

## API nodes and load balancing

`src/api/loadbalancer.ts` keeps a list of available nodes (the `network` store, refreshed every 60s) and picks
a node that exposes the requested endpoint and satisfies a `require` predicate (e.g. minimum version), falling
back to the default API. Endpoint names in `src/api/endpoints.ts` mirror the Symfony route names (`v1_stop_list`,
...), so renaming a route or changing its version is a cross-stack change.

Node side and endpoint versioning: [../api/federation.md](../api/federation.md).
