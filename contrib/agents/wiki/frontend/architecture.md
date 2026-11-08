# Frontend architecture (`front/`)

Vue 3 (every component is `<script setup lang="ts">`), Pinia, vue-router, Leaflet + MapLibre maps (ADR 0016), Tailwind CSS 4
+ Sass, built with Vite into `front/build/public`.
Path alias `@/` → `front/src/`.

## Serving

`front/server/server.ts` is an Express server (compiled with `npm run build:server` to `build/server/server.js`).
It renders the HTML shell (EJS views in `resources/views`), injects runtime config from env
(`COJEDZIE_API`, `COJEDZIE_API_HUB`, `COJEDZIE_MAPTILER_KEY`, `COJEDZIE_GTM`, Sentry settings) and serves
provider manifests. With `COJEDZIE_MODE=development` it mounts Vite as middleware (HMR on port 3001);
otherwise it serves the built assets using Vite's `build/public/.vite/manifest.json` (in a dot-directory, so
`express.static` doesn't serve it and `/manifest.json` stays the web manifest). Routes use Express 5 (path-to-regexp 8)
syntax: optional parts are braces (`{/:provider}/manifest.json`), wildcards are named (`{/*path}`).

Runtime config reaches components via `composables/useAppConfig`.

Sentry: the browser (`src/sentry.ts`, project `frontend-vue`) is configured from `SENTRY_DSN` and friends passed
through that runtime config; the Express server reports to its own project (`frontend-server`) from
`SENTRY_SERVER_DSN`. `server/instrument.ts` initialises it and must stay the first import of `server.ts`, so Sentry
can instrument Express. Node is 24 in every image and compose service (Sentry SDK 11 needs 20.19+).

## Structure worth knowing

- `src/components/app` — the app shell, see [App shell and navigation](#app-shell-and-navigation).
- `src/api` — typed endpoint map (`endpoints.ts`), HTTP / static / load-balanced clients, load balancer
  (see [API nodes and load balancing](#api-nodes-and-load-balancing)).
- `src/stores` — Pinia setup stores and the persistence layer, see [State](#state-stores-and-persistence).
- `src/composables` — shared logic of components (`useRemoved`, `useIsTouch`, `useService`, `useAppConfig`, ...).
  Nothing is registered globally on the app except directives: those in `src/filters.ts`, and `dragscroll` (from
  `vue-dragscroll`) registered in `src/app.ts`. Components are imported where they are used.
- `src/utils/container.ts` — small service container, accessed via `composables/useService`; tested in
  `front/test/container.test.ts`.
- `src/model` — domain types mirroring the API DTOs.
- `src/timetable` — the timetable's logic, framework-free so the views can be ported to another framework.
  `fetchTimetableDay(client, { stop, line?, date })`
  takes the API client as an argument, reads every page of a stop's day (`v1_stop_timetable`, pages of 1000) and fills
  in the lines from the stop's tracks, because timetable entries carry only a reference of their line.
  `TIMETABLE_DAY_MODE` (`extended`, provisional) is the only place the day mode is set. Pure builders turn the
  entries into the list (`buildTimetableList`), hour rows per line with headsign footnotes (`buildLineTimetables`) and
  combined hour rows (`buildCombinedTimetable`). They take the requested date, to mark entries of another service day,
  and the provider's time zone, which the hours are shown in. Hour rows group consecutive entries of the same hour,
  so with `extended` the runs after midnight get their own rows at the end of the day. `todayIn(timeZone)` and
  `nearestEntry(entries, now)` pick the default date and the entry to scroll to. Tests: `test/timetable/`.
- `components/ui/UiSection.vue` + `UiSectionSettings.vue` — the page's titled blocks. A section has an optional
  `icon` and `title` (or a `#title` slot getting both), an `#actions` slot on the right of the header and the
  content in the default slot; `collapsible` adds a fold button (state in `v-model:collapsed`, or internal).
  `UiSectionSettings` is the gear button for `#actions`: it opens its slot in a `UiDialog` anchored to itself,
  teleported to `#popups`. Stories: `Ui/Section`, `Ui/Section/Settings`.
- `components/ui/UiMenu.vue` + `UiMenuItem.vue` — the list of actions in a popup or sheet (`AppMoreMenu`,
  `ProviderSwitcher`). The menu reaches the popup's edges (negative `$popup-padding` margins), so item hovers do too.
  An item renders a `<button type="button">` inside an `<li>`, or the `tag` it is given (`tag="a"`,
  `tag="router-link"`: the globally registered name, as vue-tsc rejects `RouterLink` itself). Classes, attributes and
  listeners go to the item, not the `<li>`. To restyle, put your own class on `ui-menu` / `ui-menu-item`: a
  single-class rule there beats the base one, because a component's CSS is emitted after the CSS of the components it
  imports. Story: `Ui/Menu`.
- `src/components/map.ts` + `components/ui/UiMap.vue` — maps: `@vue-leaflet/vue-leaflet` with a vector base layer
  (`LVectorLayer`) drawn by MapLibre through `@maplibre/maplibre-gl-leaflet`, style from MapTiler
  (`COJEDZIE_MAPTILER_KEY`; without it MapTiler answers 403 and the map stays grey). MapLibre finds its web worker
  next to its own module, which breaks once Vite pre-bundles it, so `map.ts` passes the worker's URL
  (`maplibre-gl-worker.mjs?url`) to `setWorkerUrl`. The attribution goes in `attributionControl.customAttribution`,
  which is where the plugin reads it from.
- `src/icons.ts` + `components/ui/UiIcon.vue` — icons, see ADR 0017. Templates use names (`<ui-icon icon="refresh">`),
  the registry maps them to `@tabler/icons-vue` components, or to the transport icons in `resources/icons/light`,
  wrapped with `custom()`. `src/svg-icon-loader.ts` (a Vite plugin) turns each icon SVG into `{ viewBox, content }`:
  the markup inside the root `<svg>`, without `<title>`/`<desc>`/`<metadata>`, comments, `data-name` and ids
  nothing refers to (they would repeat for every rendered icon). Any number of paths and groups works, so editor
  exports load as they are; `custom()` renders the content as the svg's `innerHTML`. It returns an empty source map
  (`{ mappings: "" }`), as the generated module has no source to map to. An entry is `simple` (optionally `spin`,
  `fixedWidth`) or `stacked`: layers placed with `scale` and `x`/`y` in em, for combinations Tabler lacks. `UiIcon`
  sizes icons to 1em and crops the 2px padding of Tabler's grid off the view box (`2 2 20 20`), so they fill the box;
  our icons are drawn on the same 24×24 grid and take it too. The Storybook `Ui/Icon` page shows the whole catalog.
- `src/stories` + `.storybook` — Storybook, served at http://storybook.cojedzie.localhost through nginx in docker
  (`nginx/storybook.conf`; also published on port 6006). It gets its own host because Storybook injects preview
  scripts at root paths that ignore a Vite `base`, so it can't live under a path of the app's host. The `storybook`
  service sets `COJEDZIE_MAPTILER_KEY` and `COJEDZIE_API`, and runs with `--host 0.0.0.0` (Storybook listens on
  127.0.0.1 by default) and `--no-version-updates` (the version check crashes on Node 24).
  In MDX docs, a story block is `<Canvas of={Stories.Name} />`: `Canvas` ignores children, so the old
  `<Canvas><Story of={...} /></Canvas>` renders the primary story in every block.
- Docker: `front/Dockerfile` has a `dev` target (used by the compose `frontend`, `storybook` and `front-setup`
  services, run as `COJEDZIE_UID`) and a `prod` target (the published image).

## App shell and navigation

`components/Application.vue` renders the shell around the routed page:

- `AppHeader` (every page, sticky): logo linking to the provider's board (or `/` on the chooser), the
  `ProviderSwitcher` chip and, from 768 px (`md`), the menu with a "Więcej" dropdown (`UiDialog`). The chip and the
  menu are shown only on routes with a `provider` param. The chip shows `selection.provider.name` once the details of
  the route's provider are loaded, the id from the URL until then. It gets a shadow (`app-header--stuck`) once it
  sticks: an `IntersectionObserver` watches a 1px marker placed just above it, which scrolls away while the header
  stays. CSS can't do this in every browser yet (scroll-driven animations and `scroll-state` queries lack Firefox).
- `AppBottomBar` (below 768 px, only on a provider's routes): fixed to the bottom, the tabs (one per navigation entry
  with `bottomBar: true`) centred and "Więcej" at the right end, which opens `AppMoreMenu` in a `UiSheet`. Tabs show
  their icon, named by `aria-label`; only the current page's tab shows its label too. Its height is the
  `--app-bottom-bar-height` variable (`styles/main.scss`) plus `env(safe-area-inset-bottom)`; `main` gets the same
  bottom padding, so the bar never covers the end of the page.
- `AppFooter` (from 768 px): the former server-rendered footer of `index.ejs`, now in Vue.
- `AppMoreMenu`: the navigation entries it is given, then the about links from `useAboutLinks()` (API, Storybook
  only when `AppConfig.production` is false, GitHub), version and copyright.

Routes are named: `providers` (`/`) and `departures` (`/:provider`). `src/navigation.ts` lists the menu entries
(`{ id, label, icon, route, bottomBar }`); the route takes the provider as its only param. **Adding a feature** is a
route under `/:provider/<feature>` plus an entry there; it appears in the desktop menu, and in the bottom bar or the
"Więcej" sheet depending on `bottomBar`. Nothing unbuilt is listed.

Switching the provider only pushes the new route. `<router-view>` is keyed by the provider, so `MainPage` mounts
anew: it clears the previous provider's messages and departures, and `selection.loadProvider` clears the stops and
the old provider when the id differs (so Back/Forward clears them too) and ignores details of a provider it was
switched away from. Stops restored from the session on the first load are kept.

The shell components lay themselves out with Tailwind utilities in their templates (ADR 0014); their `<style>`
blocks only hold the look, mostly from mixins and variables in `_variables.scss`. `app-bar-control` is a borderless
button or link tinted with `$nav-hover-background` when hovered or open (the provider chip). `app-nav-item` adds the
pill of the items of both bars (header links, bottom bar tabs, both "Więcej" buttons), filled with
`$primary-gradient` when it is the current page (its `--active` modifier) and `$primary-gradient-light` when that
one is hovered. Icon-only buttons carry an `aria-label`.

The bottom bar names only the current tab until it is 576 px wide: it takes the `size-*` classes of `v-responsive`
(`src/filters.ts`), and component tests stub the directive (`directives: { responsive: {} }`).

The phone/desktop split is 768 px everywhere: Tailwind `md:` in templates, `useMediaQuery("(min-width: 768px)")` in
scripts.

The header and bottom bar use `$app-bar-z-index` (90): `UiDialog` puts a popup at 100 over its reference's stacking context, so
board popups stay above the bars while the header's own dropdowns (90 + 100) stay above the header.

`UiSheet` is the phone counterpart of `UiDialog`: teleported to `#popups`, slides up from the bottom, emits `leave` on
a backdrop click or Escape. `ProviderSwitcher` picks between the two with `useMediaQuery("(min-width: 768px)")`.

Images in templates go through `@resources/images/...`: a root-relative `src="/images/..."` is turned into a module
import by `@vitejs/plugin-vue`, which the dev server answers with the PNG and the page fails to load.

Storybook's preview installs Pinia and a memory router with stub `providers` and `departures` routes (at
`/trojmiasto`), so shell components using `RouterLink` render; `.storybook/setup.js` sets `version` and `production`.

## Style

Prettier (`printWidth: 120`, `arrowParens: avoid`) + ESLint 10 (flat config, `eslint.config.mjs`). Source files
carry the AGPL license header.

## Stylesheets

Two entry points, imported in this order by `src/app.ts` and `.storybook/preview.js` (ADR 0014):

- `styles/tailwind.css`: Tailwind CSS 4 (`@tailwindcss/vite`) with its preflight and the utilities used in templates.
  It is imported with `important`, so utilities are `!important` and beat the SCSS. Tailwind cascade layers lose to
  unlayered CSS, so without the flag every SCSS rule would win over every utility. Classes are collected only from
  `src/` and `resources/views/` (`source(none)` + `@source`). The `@theme` block sets Bootstrap 4's breakpoints
  (sm 576, md 768, lg 992, xl 1200 px) and the project colours (`text-primary`, `bg-dark`, `text-muted`...).
- `styles/main.scss`: everything else, in Sass with `@import`. `_variables.scss` holds the colours, sizes and
  breakpoints the SCSS computes with, the `media-breakpoint-up`/`-down` mixins (Bootstrap 4 semantics:
  `down("md")` is below 992 px) and `theme-color-level()`/`color-yiq()`. It emits no CSS, so component `<style>`
  blocks import it too. `base/_reboot.scss` adds back what the styles expect on top of preflight (body font,
  heading sizes, block margins, links, inline images). `components/` has the former Bootstrap components, kept under
  their names: `btn` (`-primary`, `-danger`, `-sm`, `-link`, `-outline-dark`), `form-control(-sm)`, `form-group`,
  `input-group(-sm|-append|-text)`, `badge` (`-pill`, `-warning`, `-danger`, `-dark`), `alert`
  (`-info`, `-warning`, `-danger`, `-dark`), `list-unstyled`, `stretched-link` and `page-container`. Only the
  variants in use exist; add one when a template needs it.

`test/styles/theme.test.ts` checks that the `@theme` values equal the Sass variables. Change both together.

A component's own styles live in its `<style lang="scss">` block (ADR 0018), which `@import`s `_variables.scss`
for variables, mixins and loops. `UiSection` is the first one moved; the files in `styles/` that style a single
component (`_departure`, `_line`, `_message`, ...) are still to move. `@extend` doesn't reach across separately
compiled `<style>` blocks, so a look shared with a global stylesheet is a mixin in `_variables.scss` instead
(`ui-section-header`, used by `UiSection` and `.stop-group__header`).

Popups (`UiDialog`, `UiTooltip`, `UiSelect`) are positioned by `positionFloating()`/`useFloating()` in
`src/composables/useFloating.ts`, over `@floating-ui/dom`. It sets `data-placement` (the arrow styles in
`styles/ui/_popup.scss` key on it) and, for responsive dialogs below 560 px, `data-narrow` with a full-width layout.
`UiDialog` adds `--ui-popup-arrow-size` to the distance from the reference, so the arrow has room.

Gotchas:

- The project's "row of centred items" helper is `hstack`, not `flex`: Tailwind's `flex` is only `display: flex`.
- A word in a template or script that matches a utility (`hidden`, `border`, `transition`) generates it. That's
  harmless unless an element really has that class.
- Spacing follows Tailwind's scale (`*-4` is 1rem, `*-6` is 1.5rem), not Bootstrap's.
- Tailwind 4 needs Chrome/Edge 111, Safari 16.4 and Firefox 128, which is the app's `build.target`.

## Checks

`task lint:front` runs `npm run check:format` (Prettier), `npm run lint` (ESLint, errors fail, warnings don't) and
`npm run check:types` (`vue-tsc` for the app including `.vue` templates, plain `tsc` for `server/`); `task test:front`
runs Vitest. CI runs the same scripts in the `test-front` job, which the Release workflow requires next to `test`.

Vite does not type check, so a broken type only shows up in these checks (`vue-tsc` 3 also checks templates).

TypeScript is 6: version 7 (the native compiler) is not supported by typescript-eslint and has no API for `vue-tsc`
yet. TypeScript 6 turns `strict` on by default; the code was written without it, so both `tsconfig.json` files set
`"strict": false` (turning it on reports about 170 errors). Module declarations for untyped imports, including
side-effect ones like moment's locales, are in `src/types/webpack.d.ts`.

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
`global.plugins: [pinia]`. Stub icons and library wrappers (`stubs: { UiIcon: true }`, maps, `autoUpdate` of
`@floating-ui/dom`). Assert on what is rendered, what is emitted and which store state changes. For saved stores,
use `replaceStorages` with `MemoryStorage` instead of the browser. API calls are stubbed at the client level (see
`test/api/client.test.ts`).

## API nodes and load balancing

`src/api/loadbalancer.ts` keeps a list of available nodes (the `network` store, refreshed every 60s) and picks
a node that exposes the requested endpoint and satisfies a `require` predicate (e.g. minimum version), falling
back to the default API. Endpoint names in `src/api/endpoints.ts` mirror the Symfony route names (`v1_stop_list`,
...), so renaming a route or changing its version is a cross-stack change.

Node side and endpoint versioning: [../api/federation.md](../api/federation.md).
