# 0015. Icons are Tabler icons behind a named registry

- **Status:** Accepted
- **Date:** 2026-10-30
- **Applies to:** `front/src/icons.ts`, `front/src/components/ui/UiIcon.vue`, `front/resources/icons/**`,
  `front/package.json`

## Context

The frontend used FontAwesome Pro (light, regular and solid sets) through `@fortawesome/vue-fontawesome`. Pro is
licensed, so the packages could not be installed from npm without a token: they were placed by hand in
`front/resources/fontawesome` for development, and CI and the Release workflow fetched them from S3 with a separate
action and three secrets. A fresh clone did not build without them.

Templates already referred to icons by name (`<ui-icon icon="refresh">`) through a registry in `src/icons.ts`, and
the transport icons (bus, tram, ...) are our own drawings.

## Considered options

1. **Keep FontAwesome Pro.** It has the widest set, but keeps the licence, the token and the manual setup.
2. **FontAwesome Free.** No licence, but no light style, and fewer icons than we used.
3. **Tabler icons through `@tabler/icons-vue`** (chosen). MIT, installed from npm, one Vue component per icon
   (so unused icons are tree-shaken), and an outline style we prefer. It has no tram, trolleybus or metro, and
   lacks some combinations.
4. **Iconify (`unplugin-icons`).** Any icon set, but another build plugin and a less direct import.

## Decision

We will take icons from `@tabler/icons-vue`, used only through the named registry:

- Components render icons with `<ui-icon icon="name">`. Names are defined in `front/src/icons.ts`, which maps them
  to Tabler components.
- Our own icons (the transport types) stay in `front/resources/icons` as single filled paths and are wrapped with
  `custom()` in the registry.
- When Tabler has no icon for a combination, the registry composes it as a stacked icon (layers with `scale`, `x`,
  `y`). A ready-made Tabler combination is preferred over a stack.

## Consequences

- **No licensed dependencies.** A fresh clone and CI build without tokens or hand-placed packages; the
  `FONTAWESOME_S3_*` secrets are no longer used.
- **Mixed styles until redrawn.** The transport icons were drawn as filled shapes to match FontAwesome Light; they
  are planned to be redrawn in Tabler's stroke style.
- **Our own sizing and stacking.** `UiIcon` provides the 1em sizing, spinning, fixed width and layers that
  FontAwesome's CSS used to.

## Compliance

- **One icon library.** There is no `@fortawesome/*` package or other icon library in `front/package.json`.
- **Icons go through the registry.** Components outside `UiIcon.vue` and `icons.ts` don't import from
  `@tabler/icons-vue`; they use `<ui-icon>` with a name from `icons.ts`.
- **Own icons.** A custom icon is an SVG in `front/resources/icons` with a single path on a 512×512 canvas, registered
  with `custom()`.
