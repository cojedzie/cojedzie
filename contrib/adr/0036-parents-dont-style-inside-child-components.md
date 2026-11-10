# 0036. Parents don't style inside child components

- **Status:** Accepted
- **Date:** 2026-11-10
- **Applies to:** `front/src/**/*.vue`

## Context

With global stylesheets and BEM, a parent could style any element of a child component (`.stop-details-dialog
.ui-icon`, a modifier class passed down to colour a pin). Scoped styles (ADR 0035) stop a parent's selectors at the
child's root element, and Vue's `:deep()` and `:slotted()` can get past that again. Used freely, they bring back the
coupling scoping removed: a child can't change its markup without breaking its parents.

## Considered options

1. **Allow `:deep()` freely.** Easy to migrate, but children's insides become everyone's API.
2. **Never pierce scoping.** Clean, but some cases (styling content a component renders in a slot it owns) have no
   reasonable alternative.
3. **Children expose what parents may change; piercing is a commented exception** (chosen).

## Decision

We will not style the inside of a child component from its parent.

- A parent may style the child's root element (its position, margins, size), as Vue gives the root the parent's
  scope.
- What a parent needs to change inside a child, the child exposes: a prop or variant, a slot, or a custom property
  the child reads. A generic component doesn't learn its parents' domain to do so: `UiPin` reads its colour from
  `--ui-pin-color`, which `StopPin` sets from the stop's line type, instead of knowing line types itself.
- `:deep()` and `:slotted()` are exceptions for cases where that would be overkill (e.g. a component styling the
  figures parents put in its slot, with the class names as the slot's contract), each with a comment saying why.

## Consequences

- A component's markup is its own: changing it can't break a parent's styles.
- Some components gain props or slots where parents used to reach in.

## Compliance

- Every `:deep()` and `:slotted()` has a comment saying why.
- Parents pass props, variants, slots or custom properties to change a child, not classes targeting its inner
  elements.
