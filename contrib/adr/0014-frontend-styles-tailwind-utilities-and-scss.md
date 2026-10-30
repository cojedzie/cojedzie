# 0014. Frontend styles: Tailwind utilities, components in SCSS

- **Status:** Accepted
- **Date:** 2026-10-30
- **Applies to:** `front/styles/**`, `front/src/**/*.vue`, `front/resources/views/**`, `front/vite.config.ts`,
  `front/.storybook/preview.js`

## Context

The frontend was styled with Bootstrap 4 plus about 2,200 lines of project SCSS. Bootstrap 4 is end of life, and its
Sass was the main reason the build silenced Sass deprecations. The app used only a small part of it: about 50 classes
in templates (buttons, form controls, input groups, badges, alerts, a few spacing and flex utilities and one grid
layout), about 30 Sass variables and the breakpoint mixins. The project SCSS also `@extend`s several Bootstrap
classes.

Tailwind CSS 4 doesn't run through Sass, puts its styles into cascade layers (which lose to any unlayered rule) and
needs Chrome/Edge 111, Safari 16.4 and Firefox 128.

## Considered options

1. **Tailwind for base styles and utilities, the rest stays SCSS** (chosen). Templates get a maintained utility
   set; the SCSS keeps working and can move to utilities file by file later.
2. **Own minimal Sass** with Bootstrap's class names. It is the smallest change, but it leaves the project
   maintaining a utility framework of its own.
3. **Bootstrap 5.** It keeps the dependency, renames most utilities anyway (`ml-*` → `ms-*`, `sr-only` →
   `visually-hidden`) and still uses Sass `@import`.
4. **Everything in Tailwind now**, dropping Sass. It means rewriting 2,200 lines at once, with much more visual risk.

## Decision

We will use Tailwind CSS 4 for the base styles (preflight) and utility classes, and plain SCSS for everything else.

- `styles/tailwind.css` imports Tailwind with `important`, so utilities beat the unlayered SCSS as Bootstrap's
  `!important` utilities did. It scans only `src/` and `resources/views/`.
- The components the app took from Bootstrap live in `styles/components/` under their old names, with only the
  variants in use, because the SCSS `@extend`s them.
- Breakpoints keep Bootstrap 4's values in both the Tailwind theme and `_variables.scss`. The Sass variables are the
  values the SCSS computes with; the `@theme` block mirrors those Tailwind needs, and a test keeps them equal.
- The app supports the browsers Tailwind 4 does, and Vite's `build.target` says so.

## Consequences

- No Bootstrap in the dependency tree, and Sass no longer has to quiet dependency warnings. Our own `@import`s still
  need the silences until the SCSS moves to `@use`.
- New layout and spacing in templates is written with Tailwind utilities. Tailwind's spacing scale differs from
  Bootstrap's above `*-2`.
- The project helper that was `.flex` (flex plus centred items) is now `.hstack`, so Tailwind's `flex` means only
  `display: flex`.
- Browsers older than the floor (iOS before 16.4, from March 2023) get a broken layout.
- Colours and breakpoints exist in two files, so a change must touch both; the theme test fails otherwise.

## Compliance

- No `bootstrap` package, import or Bootstrap-only class (`d-flex`, `col-*`, `row`, `container`, `ml-auto`...) in
  `front/`.
- Utilities in templates are Tailwind's. Project classes don't reuse a Tailwind utility name.
- A colour or breakpoint added to `@theme` in `styles/tailwind.css` has the same value in `styles/_variables.scss`,
  and `test/styles/theme.test.ts` covers it.
- `vite.config.ts` keeps `build.target` at or above Tailwind's browser floor.
