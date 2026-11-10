# 0034. Primitive style values are design tokens

- **Status:** Accepted
- **Date:** 2026-11-10
- **Applies to:** `front/styles/**`, `front/src/**/*.vue`

## Context

Every component styled itself with its own literals: spacing (`0.5rem`, `0.75rem`, `0.375rem`, `0.8rem`…), corner
radii, control heights, durations (`150ms`, `200ms`, `0.2s`, `0.5s`), disabled opacities (`0.35` next to `0.3`),
hover tints and the focus ring written out again in each. Near-duplicates crept in, and the UI kit (toggle group,
date picker, calendar) grew its own variables for the same things. Only colours and breakpoints were shared, as Sass
variables mirrored in Tailwind's theme.

## Considered options

1. **Keep literals, share through Sass variables and mixins.** Familiar, but compile-time only, invisible to the
   Tailwind utilities, and Sass is being dropped (ADR 0035).
2. **Design tokens as CSS custom properties defined in Tailwind's theme** (chosen). One place, readable at runtime
   (themes or dark mode stay possible), and Tailwind turns the ones in its namespaces into utilities for `@apply`.
3. **A separate tokens file of custom properties** that the theme references. A second layer for no gain today.

## Decision

We will express every shared primitive style value as a design token, never as a literal in a component's styles.

- **Scales:** radius (`--radius-sm|md|lg|xl|full`), control height (`--control-height-sm|md|lg`), motion
  (`--duration-fast|normal`, `--ease-in-out`), state (`--opacity-disabled`, `--opacity-muted`, `--opacity-faint`,
  `--color-hover`, `--color-hover-primary`), focus ring (`--focus-ring-width|offset|color`), elevation
  (`--shadow-bar`, `--shadow-bar-top`, `--shadow-popup`, `--shadow-sheet`), stacking (`--z-index-bar|popup`), text
  (`--text-xs|sm|base|lg`, `--leading-tight|normal`) and colours. Spacing (padding, margin, gap, insets) is Tailwind's
  spacing scale: `--spacing(n)` or the `p-n`-style utilities through `@apply`.
- **Names:** Tailwind's namespaces where one exists (`--color-*`, `--radius-*`, `--shadow-*`, `--ease-*`, `--text-*`,
  `--leading-*`), project names otherwise.
- **Two tiers:** primitive tokens, and semantic ones (e.g. `--gradient-primary`, `--color-surface`) only for what at
  least two components share. Component custom properties (`--ui-control-*`, `--calendar-*`) read tokens.
- **Snapping:** a value moves to the nearest step of its scale, even when that shifts it slightly; spacing to whole
  steps of `--spacing` (half steps only where whole ones prove too coarse). Hairlines (`1px`/`2px` borders, outlines,
  gaps) and one-off layout dimensions that belong to no scale (a popup's width, a drawing's size) stay literal, each
  with a comment saying why.
- **Placement:** tokens live in the `@theme static` blocks of Tailwind's stylesheet today (`static`, so tokens only
  component styles read are emitted). Moving them elsewhere doesn't need a new ADR.

## Consequences

- Changing a radius, a duration or the focus ring is one edit; near-duplicates are gone.
- The UI shifted slightly where values snapped (listed in the PRs that introduced the tokens).
- Until the global Sass stylesheets are plain CSS, colours and breakpoints are mirrored in Sass, kept equal by
  `test/styles/theme.test.ts`.

## Compliance

- No spacing, radius, size of a control, duration, easing, opacity, shadow, z-index, font size or focus-ring literal
  in a component's styles or a global stylesheet, except commented hairlines and one-off layout dimensions.
- A new shared value is added as a token to the theme, not repeated in components.
- A semantic token exists only when at least two components use it.
