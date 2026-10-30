# 0013. Frontend state lives in Pinia, saved through `UserStateStorage`

- **Status:** Accepted
- **Date:** 2026-10-30
- **Applies to:** `front/src/stores/**`, `front/src/components/**`, `front/src/pages/**`, `front/src/composables/**`,
  `front/src/api/**`, `front/src/app.ts`

## Context

The frontend kept its state in Vuex modules, written as class components with `vuex-class` decorators, and saved
it with `vuex-persist`.

- All saved state was one `vuex` key in `localStorage`, and the selection another in `sessionStorage`. Every change
  rewrote the whole object, and a store could not be saved or loaded on its own.
- `vuex-persist` and the stores read `window.localStorage` directly, so state could not be saved anywhere else.
  Saving it on the server (so it follows the user between devices) is planned.
- Hydration raced with startup: the API client lived in a store and the migrations needed the client, so the
  store loaded the saved state before the migrations ran, and its next save overwrote what they migrated.
- `vue-class-component` and `vuex-class` are unmaintained and block the typing of templates by `vue-tsc`.

Constraints: the state users already have in their browsers (favourites, settings, history) must survive.

## Considered options

1. **Pinia with `pinia-plugin-persistedstate`.** This is the ready-made option, but its storage interface is
   synchronous (`getItem`/`setItem`), so a backend that talks to the API doesn't fit it.
2. **Pinia with our own plugin over an async `UserStateStorage`** (chosen). A store opts in with `persist` options,
   and the plugin loads, debounces saves and flushes them when the page is hidden.
3. **Keep the single `vuex` key** and have all stores write into it. This was rejected: it keeps stores coupled,
   rewrites everything on every change, and makes per-store backends impossible.
4. **Bridge class components to Pinia** and convert them later. This was rejected: it needs the class decorators
   and shims that the change is meant to remove, and every component is touched anyway.

## Decision

We will keep frontend state in Pinia setup stores, and save it through `UserStateStorage`:

- **Stores** are setup stores in `front/src/stores`, used by components through `useXxxStore()`. The API client
  doesn't depend on a store; it reaches the network and provider state through `bindClientContext`.
- **Saving** goes through the `UserStateStorage` interface (`load`/`save`/`remove`, async, JSON values) with one key
  per store. A store opts in with `persist: { key, storage, pick?, restore? }`, handled by our Pinia plugin in
  `front/src/stores/persistence`. `BrowserStorage` and `MemoryStorage` implement the interface, and a new backend
  must pass the contract suite.
- **Migrations** run through the same interface before any store is created. The `vuex` key is split into the
  per-store keys by a migration.
- **Startup** is: Pinia with the plugin, migrations, client context, saved stores hydrated, mount.
- **Components** are `<script setup lang="ts">`. Shared logic is in composables, and nothing is registered globally
  besides directives.

## Consequences

- **Per-store saving.** Only the store that changed is saved, and a failed load of one store doesn't overwrite its
  data or affect the others.
- **A server backend is an addition.** A future `ApiStorage` would implement the interface and pass the contract
  suite; stores wouldn't change.
- **Our plugin to maintain.** It is small, and covered by its own tests.
- **Users keep their data.** The migration moves it; selected stops, kept only for a tab, start empty once.
- **Every shape change costs a migration** and a format-test update.
- **Tests run in happy-dom** with real stores over `MemoryStorage`.

## Compliance

- **Storage access.** Code in `front/src/stores/**` (outside `persistence/storages.ts`, and `app.ts` for the
  migrations) and components never use `localStorage` or `sessionStorage`. Saved data goes through `UserStateStorage`.
- **Saved stores.** A store whose state is saved is a setup store with a `persist` option, one key per store, and is
  awaited through `$hydrated` in `front/src/app.ts` before mounting.
- **Saved shapes.** A change to a saved shape comes with a migration in `migrations.ts` and an update of
  `front/test/stores/persistence/format.test.ts`. A migration that has run never runs again, so its effects must not
  change: it may only be edited to keep it working on newer code (APIs, libraries), never to change what it does.
- **Backends.** A `UserStateStorage` implementation runs the contract suite
  (`front/test/stores/persistence/storage.contract.ts`).
- **No Vuex.** There is no `vuex`, `vuex-persist` or `vuex-class`, and the API client doesn't import stores.
- **Components.** Components are `<script setup>`, with no class components or `vue-property-decorator`. No
  component is registered globally (`app.component`); directives may be.
