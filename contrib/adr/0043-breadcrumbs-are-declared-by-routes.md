# 0043. Breadcrumbs are declared by routes, named from what the pages loaded

- **Status:** Accepted
- **Date:** 2026-11-22
- **Applies to:** `front/src/breadcrumbs.ts`, `front/src/routes.ts`, `front/src/composables/useBreadcrumbs.ts`,
  `front/src/components/ui/UiBreadcrumbs.vue`, `front/src/pages/**`

## Context

The timetable section (ADR 0037) has pages several levels deep: a stop's timetable, a line's page, one line at a stop.
They are reached from deep links and from outside the menu, and on a phone an open stop's timetable stands alone, so
riders need a trail back up ("Rozkład › Linia 8 › Brama Wyżynna 01"). Its crumbs name a line and a stop, which only
the pages load. The router doesn't nest the pages the way riders see them: a line's page and a line at a stop are
siblings under the section's layout, because the same line page instance has to stay next to the stops it opens.
Other sections may want a trail later.

## Considered options

1. **From the browser history.** Rejected: a deep link has no history, and the trail would describe how the rider got
   there, not where the page is.
2. **From the router's nesting (`route.matched`).** Rejected: the line's page isn't a parent of a line at a stop in
   the router, and nesting it would remount the line page or give up its layout.
3. **Each page renders its own trail.** Rejected: every page repeats the trail of the pages above it, and a new page
   means editing its children.
4. **Routes declare their crumbs, with a logical parent** (chosen). One declaration per route, independent of the
   router's nesting; the layout renders the trail.

For the names, fetching the line and stop for the trail was rejected: the pages already load them.

## Decision

- A route's crumb is declared in its `meta.breadcrumb`: a `label` of the route and the section's loaded data, the
  name of its `parent` crumb's route (a logical parent, not necessarily the router's), the `query` keys its link
  keeps, and optionally `content`, a component and its props showing the loaded entity as elsewhere in the app (a
  stop with its code in a badge); the label stays its text and accessible name.
- A label falls back to what the crumb is ("Linia", "Przystanek") until the data holds the entity its route's params
  name; it never names another line or stop from the data.
- Between two pages, a crumb whose new data is loading keeps showing what it showed, for at most a second, rather
  than flashing its generic word between two names; a load that never arrives shows the generic word after that.
- `buildTrail` turns a route into crumbs by walking the parents; it knows no section. `UiBreadcrumbs` only renders
  crumbs.
- Nothing is fetched for the trail: pages emit what they loaded and the section's layout passes it to the trail.
- The trail stays on one line at every width: crumbs truncate, they don't wrap.

## Consequences

- Adding a page to a trail is one meta entry; adding a trail to a section is declaring its routes' crumbs and
  rendering `UiBreadcrumbs` in its layout.
- A page whose crumb needs a name has to emit what it loaded, and its layout has to keep it.
- Crumbs show generic labels while a deep-linked page loads; moving between pages briefly shows the previous name
  instead.
- A move to another router (the planned React port) keeps the declarations and the parent walk, only the meta's
  home changes.

## Compliance

- Trails are built only by `buildTrail` from `meta.breadcrumb` declarations; no page or component hard-codes a trail
  or reads the browser history for it.
- A crumb's parent is named by `parent`, never inferred from `route.matched`.
- Labels use loaded data only when its id matches the route's params, and have a fallback without it; holding a
  previous crumb while the next one loads happens only in `useBreadcrumbs`, bounded in time.
- No request is made for the sake of a crumb's label.
- `UiBreadcrumbs` takes crumbs as a prop and doesn't read the route or its meta.
