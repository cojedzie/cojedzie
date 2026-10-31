# 0018. Component styles live in their components

- **Status:** Accepted
- **Date:** 2026-10-31
- **Applies to:** `front/src/components/**`, `front/styles/**`

## Context

Most component styles are in `front/styles/*.scss` (`_departure`, `_line`, `_message`, `_stop`, ...), imported once
by `styles/main.scss`, and away from the components they style. Some components already have a `<style lang="scss">`
block. Finding, changing or removing a component's styles means searching both. Global stylesheets also `@extend`
each other's classes (a section header extends the alert classes), so their dependencies don't show.

## Considered options

1. **Keep styles in `styles/`.** Nothing to move, but the split stays.
2. **Component styles in their `<style lang="scss">` blocks** (chosen). A component and its styles change and go
   away together; Sass (variables, mixins, loops) stays available through `_variables.scss`, which emits no CSS.
3. **Scoped styles (`<style scoped>`).** Stronger isolation, but the BEM class names already prevent clashes, and
   parents style children's elements in places.

## Decision

We will keep the styles of a component in its own `<style lang="scss">` block, not in a file in `styles/`. `styles/`
keeps only what is global: the base (reboot), the former Bootstrap classes used in templates (`btn`, `badge`,
`alert`, form controls), layout helpers, and `_variables.scss`. A look shared between a component and other styles
is a mixin in `_variables.scss`.

Existing stylesheets move when their component is next worked on, or in a dedicated pass.

## Consequences

- **Styles next to markup.** Deleting a component deletes its styles.
- **No `@extend` across components.** Each `<style>` block compiles alone, so shared rules become mixins.
- **Two places for a while.** Until the remaining stylesheets move, some components are still styled from `styles/`.

## Compliance

- A new component, or one whose styles are reworked, keeps its styles in its `<style lang="scss">` block.
- No new file in `front/styles/` styles a single component.
- A component `<style>` block doesn't `@extend` classes defined elsewhere; it uses mixins from `_variables.scss`.
