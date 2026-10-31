# 0017. Icons are Tabler icons behind a named registry, ours drawn on Tabler's grid

- **Status:** Accepted
- **Supersedes:** [0015](archive/0015-icons-are-tabler-icons-behind-named-registry.md)
- **Date:** 2026-10-31
- **Applies to:** `front/src/icons.ts`, `front/src/components/ui/UiIcon.vue`, `front/resources/icons/**`,
  `front/package.json`

## Context

The frontend used FontAwesome Pro (light, regular and solid sets) through `@fortawesome/vue-fontawesome`. Pro is
licensed, so the packages could not be installed from npm without a token: they were placed by hand in
`front/resources/fontawesome` for development, and CI and the Release workflow fetched them from S3 with a separate
action and three secrets. A fresh clone did not build without them.

Templates already referred to icons by name (`<ui-icon icon="refresh">`) through a registry in `src/icons.ts`, and
the transport icons (bus, tram, ...) are our own drawings.

ADR 0015 kept those drawings as single filled paths on a 512×512 canvas, the format the icon loader could read. The
transport icons are being redrawn in Tabler's style, and Illustrator or Vectorcraft export such a drawing as many
stroked paths in nested groups, which can't reasonably be merged into one path. This ADR supersedes 0015 to allow that.

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
- Our own icons (the transport types) are SVGs in `front/resources/icons`, wrapped with `custom()` in the registry.
  They are drawn like Tabler's: a 24×24 grid with 2px of padding, 2px round strokes in `currentColor`, any number of
  elements. `svg-icon-loader` keeps the view box and everything drawn, dropping metadata and unused ids. Icons on
  the 24px grid take the same padding crop as Tabler's in `UiIcon`.
- When Tabler has no icon for a combination, the registry composes it as a stacked icon (layers with `scale`, `x`,
  `y`). A ready-made Tabler combination is preferred over a stack.

## Consequences

- **No licensed dependencies.** A fresh clone and CI build without tokens or hand-placed packages; the
  `FONTAWESOME_S3_*` secrets are no longer used.
- **Mixed styles until redrawn.** Train, trolleybus, metro and the unknown vehicle are still filled 512×512 shapes
  drawn to match FontAwesome Light; the loader and `custom()` still read them, without the crop.
- **Icons straight from an editor.** An exported SVG works as is, without merging paths by hand.
- **Our own sizing and stacking.** `UiIcon` provides the 1em sizing, spinning, fixed width and layers that
  FontAwesome's CSS used to.

## Compliance

- **One icon library.** There is no `@fortawesome/*` package or other icon library in `front/package.json`.
- **Icons go through the registry.** Components outside `UiIcon.vue` and `icons.ts` don't import from
  `@tabler/icons-vue`; they use `<ui-icon>` with a name from `icons.ts`.
- **Own icons.** A new or redrawn custom icon is an SVG in `front/resources/icons` with `viewBox="0 0 24 24"`, drawn
  with strokes in `currentColor` (not fixed colours), registered with `custom()`.
