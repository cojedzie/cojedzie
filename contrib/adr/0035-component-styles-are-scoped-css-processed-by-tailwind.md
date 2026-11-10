# 0035. Component styles are scoped CSS processed by Tailwind

- **Status:** Accepted
- **Supersedes:** [0014](archive/0014-frontend-styles-tailwind-utilities-and-scss.md),
  [0018](archive/0018-component-styles-live-in-their-components.md)
- **Date:** 2026-11-10
- **Applies to:** `front/src/**/*.vue`, `front/styles/**`, `front/vite.config.ts`, `front/package.json`,
  `front/.storybook/preview.js`

## Context

ADR 0014 put Tailwind under the app for base styles and template utilities, keeping SCSS for the rest; ADR 0018 moved
each component's styles into its `<style lang="scss">` block. Components were kept apart by BEM names
(`.ui-calendar__day--today`), which existed only to stop styles from clashing, and shared looks were Sass mixins.
Templates carried layout as utilities (`flex items-center gap-2 md:flex`), so a component's look was split between
its template and its styles.

Tailwind 4 doesn't process Sass: `@apply` in an SCSS block passes through unexpanded. In a plain-CSS block that
references the Tailwind stylesheet, `@apply`, `@utility` and `@variant` work, and native CSS nesting replaces most of
what Sass nesting did. Vite's esbuild minification lowers nesting and adds prefixes for the build target (Safari
16.4 included), so no other CSS processor is needed.

## Considered options

1. **Keep SCSS, move template utilities into plain properties.** Semantic templates, but no `@apply`, and BEM stays
   for scoping.
2. **Scoped plain CSS processed by Tailwind** (chosen). Vue's `<style scoped>` keeps components apart, so class names
   can be short; Tailwind's `@apply`, `@utility` and `@variant` replace Sass mixins and breakpoint helpers.
3. **CSS Modules.** Scoped by hashing and portable to React, but templates go through `$style.x`, and Tailwind's Vite
   plugin doesn't process `<style module>` blocks in our setup. A later React port can convert scoped CSS to modules
   mechanically.
4. **PostCSS** on top. Tailwind (through Lightning CSS) and Vite's esbuild already cover what it would do here.

## Decision

We will style components with `<style scoped>` plain CSS processed by Tailwind.

- **A component's styles live in the component** (as in 0018): a `<style scoped>` block, no `lang`, starting with
  `@reference "@styles/tailwind.css"`, using `@apply`, design tokens (ADR 0034) through `var(--…)` and
  `--spacing(n)`, `@variant md` / `@variant max-md` for breakpoints, `color-mix()` for colour math and native nesting
  (`& .child`, `&.state`, `&:hover`).
- **Short semantic class names** (`.day`, `.selected`), not BEM. A component class must not reuse the name of a
  global class (`btn`, `badge`, `alert`, `form-control`, the reset and helpers) or of a Tailwind utility (`hidden`,
  `grid`, `outline`…), which would apply too; a variant whose natural name is a utility goes into a `data-` attribute.
- **Templates carry semantic class names only**: no Tailwind utilities. Layout and spacing are in the component's
  styles.
- **Shared looks are `@utility` definitions** in Tailwind's stylesheet (e.g. `ui-control`, `app-nav-item`,
  `ui-section-header`, `focus-ring`), applied with `@apply`.
- **Global CSS** is only the base (Tailwind's preflight, the reset, animations), layout helpers (`page-container`,
  `hstack`, `visually-hidden`) and the classes kept from Bootstrap (`btn`, `badge`, `alert`, `form-control`) until they
  become components (#181).
- **No Sass, no PostCSS.** Tailwind supports the browsers its version 4 does, and Vite's `build.target` says so (as in
  0014).

## Consequences

- A component's look is in one place, and class names read as what they are.
- Parents can't reach inside child components with their styles (ADR 0036).
- While the global Sass stylesheets are converted, they keep their own classes, colours and breakpoints, mirrored in
  Sass.
- Tailwind scans the sources for utilities only while templates still have some; once none do, it needs no sources.

## Compliance

- Every `.vue` style block is `<style scoped>` without `lang`; no `.scss` file or `lang="scss"` in `front/` (once the
  global stylesheets are converted); `sass` is not a dependency.
- Templates contain no Tailwind utility classes.
- Component class names don't reuse global class names or Tailwind utility names.
- Shared looks are `@utility` definitions, not copied declarations.
- No PostCSS configuration.
