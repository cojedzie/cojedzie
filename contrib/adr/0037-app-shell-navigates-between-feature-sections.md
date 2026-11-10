# 0037. App shell: a top bar and a mobile bottom bar navigating between feature sections

- **Status:** Accepted
- **Supersedes:** [0022](archive/0022-app-shell-with-top-bar-and-mobile-bottom-bar.md)
- **Date:** 2026-11-10
- **Applies to:** `front/src/components/Application.vue`, `front/src/components/app/**`, `front/src/navigation.ts`,
  `front/src/routes.ts`, `front/src/pages/**`

## Context

ADR 0022 gave the app a top bar and a mobile bottom bar, both rendered from one navigation list, and made each feature
a route under `/:provider/<feature>` taking the provider as its only param. A feature was one page.

The timetable is the first feature with several pages: a start page (search), a stop's timetable, one line at a stop
and a line's page, each with params of its own (`:stop`, `:line`). Its pages share a layout (the departures page's
two columns, the search or the line page on the left, a stop's timetable on the right), and riders reach them from
outside the menu (a stop's details link to its timetable). The menu marked only the exact route of an entry as the
current page, so on a stop's timetable no entry was marked.

## Considered options

1. **A navigation entry per page.** Every page reachable from the menu, but a stop's timetable needs a stop, so its
   entry would lead nowhere useful, and the bottom bar has room for about four tabs.
2. **Flat routes, with the menu matching entries by a path prefix.** No layout route; each page repeats the layout,
   and the menu needs matching rules of its own.
3. **A feature section: the entry's route is the section's layout, its pages are child routes** (chosen). The router
   already marks a link active on its route's children, so the entry stays marked on every page of its section, and
   the shared layout lives in one component.

## Decision

We keep the shell of ADR 0022: `Application.vue` renders `AppHeader` on every page (logo, provider switcher, and from
768 px the menu with a "Więcej" dropdown), `AppBottomBar` below 768 px on a provider's pages (a tab per entry marked
for it, plus "Więcej" opening a sheet), and `AppFooter` from 768 px. Menu entries come from one list, `navigation` in
`src/navigation.ts`; each entry names its route and says whether it is a bottom bar tab. Only built features are
listed.

A feature is a **section**: a named route under `/:provider/<feature>` taking the provider as its only param, which is
the entry's route. A section with several pages makes that route its layout and its pages child routes under it, which
may add params of their own (`/:provider/timetable/stops/:stop`). The menus mark an entry as current on its route and
on every route under it (`active-class`, not `exact-active-class`).

## Consequences

- Adding a feature is a route plus one `navigation` entry; both bars follow. Adding a page to a feature is a child
  route; the menu needs nothing.
- The bottom bar has room for about four tabs; entries beyond that go to "Więcej".
- Accounts will take the right side of the top bar.
- Moving to another provider remounts the routed page (it is keyed by the provider), and each page clears what
  belonged to the previous provider.
- An entry's route must not be the parent of another feature's routes, or both entries would be marked.
- Routers built by tests and Storybook need a route for every entry, as the menus link to them by name.

## Compliance

- Menus in `AppHeader`, `AppBottomBar` and `AppMoreMenu` are rendered from `navigation`; no component hard-codes a
  feature link.
- Every `navigation` entry's `route` is a named route at `/:provider/<feature>` (or `/:provider` for the departures
  board) whose only param is the provider; `test/navigation.test.ts` checks it exists.
- A feature's other pages are child routes of its entry's route, not routes of their own beside it.
- The menus mark the current entry with `active-class`, so it stays marked on its section's pages.
- No entry, page or placeholder is added for a feature that isn't built; a built feature's start page may be a
  placeholder until its own issue builds it.
- New pages render inside the shell's `<router-view>`; they don't render their own header, menu or footer.
