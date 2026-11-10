# 0022. App shell: a top bar and a mobile bottom bar, built from one navigation list

- **Status:** Superseded by [0037](../0037-app-shell-navigates-between-feature-sections.md)
- **Date:** 2026-11-01
- **Applies to:** `front/src/components/Application.vue`, `front/src/components/app/**`, `front/src/navigation.ts`,
  `front/src/routes.ts`

## Context

The frontend was a single departures board per provider (`/:provider`) and a provider chooser (`/`), with no
navigation and a footer rendered by the server. Planned features (vehicle map, vehicle catalog, stop browser, route
planner, alerts, stats, user accounts) need a place in the UI. The departures board stays the main use: people check
departures on a phone before leaving work or home, so it must stay one tap away and the most used features must be
reachable with a thumb.

## Considered options

1. **Top bar with the menu, plus a bottom bar on phones from day one**: the usual web app header on wide screens, tabs
   for the most used features on phones, the rest behind "Więcej". With one feature today the bottom bar looks sparse,
   but the layout doesn't move as features arrive.
2. **Top bar with a menu button, bottom bar only once there is a second feature**: tidier today, but the layout
   changes under users when the second feature ships.
3. **Top bar and a side rail on wide screens**: the bottom bar's tabs turned sideways; scales to many features but
   takes width from the board.
4. **List planned features as "coming soon"**: shows where the app is going, but promises things that may change and
   adds dead ends.

## Decision

We will use option 1. The shell is `Application.vue`: `AppHeader` on every page (logo, provider switcher, and from
768 px the menu with a "Więcej" dropdown), `AppBottomBar` below 768 px on a provider's pages (a tab per entry marked
for it, plus "Więcej" opening a sheet), and `AppFooter` from 768 px. The provider switcher is a chip in the top bar.

Menu entries come from one list, `navigation` in `src/navigation.ts`; each entry names its route and says whether it
is a bottom bar tab. A feature is a route under `/:provider/<feature>` taking the provider as its only param. Only
built features are listed.

## Consequences

- Adding a feature is a route plus one `navigation` entry; both bars follow.
- The bottom bar has room for about four tabs; entries beyond that must go to "Więcej".
- Accounts will take the right side of the top bar.
- Moving to another provider remounts the routed page (it is keyed by the provider), and the board clears what
  belonged to the previous provider.

## Compliance

- Menus in `AppHeader`, `AppBottomBar` and `AppMoreMenu` are rendered from `navigation`; no component hard-codes a
  feature link.
- Every `navigation` entry's `route` is a named route under `/:provider`; `test/navigation.test.ts` checks it exists.
- No entry, page or placeholder is added for a feature that isn't built.
- New pages render inside the shell's `<router-view>`; they don't render their own header, menu or footer.
