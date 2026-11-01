# 0010. Stale imported data is collected after a retention period

- **Status:** Accepted
- **Date:** 2026-10-29
- **Applies to:** `api/src/DataImport/**`, `api/src/Provider/*/DataImporter/**`, `api/config/services.yaml`

## Context

Importers upsert what upstream publishes and stamp every row with the import that wrote it. Rows that disappear
upstream were never removed: a stop ZTM dropped kept showing in search, a line that stopped running kept its old
timetable (and so its departures), and the `import` table grew with every run. Upstream feeds are someone else's
infrastructure and sometimes come back broken or incomplete, so "missing from today's feed" doesn't mean "gone".

## Considered options

1. **Delete during the import** whatever the feed no longer contains. Immediate, but one broken or partial feed
   removes real data, and every importer needs its own deletion logic.
2. **Soft-delete flags** filtered by every repository. Reversible, but every query has to know about them, and the
   rows still pile up.
3. **A separate procedure removing rows no import has written within a retention period**, using the existing
   `import_id` stamps. One place, no schema change; data survives short upstream outages; removal is delayed.

## Decision

We will collect stale data with a separate procedure (option 3), `App\DataImport\StaleDataCollector`:

- A row is stale when the import that last wrote it started more than `COJEDZIE_STALE_DATA_RETENTION` ago (default
  `7 days`), configurable by environment.
- Stale rows still referenced by rows that stay are kept; tables are collected children first.
- Nothing is collected unless an import finished within the retention period.
- Imports nothing refers to anymore are removed afterwards.
- It runs as `app:collect-stale-data` and on its own schedule (`COJEDZIE_SCHEDULE_COLLECT_STALE_DATA`, ADR 0021; both prefixed by ADR 0011).

## Consequences

- Data that disappears upstream stays visible for up to the retention period (plus a day, with a daily schedule).
- Importers must keep stamping every row they write with the current import; a row that isn't restamped is
  collected once the retention passes.
- Trips and track stops are still replaced by their importers per line and track; the collector also covers lines
  and tracks that vanish entirely.

## Compliance

- Importers don't delete rows because they are missing from a feed (replacing a line's trips or a track's stops
  is fine); removal of disappeared data goes through `StaleDataCollector`.
- Every imported table is stamped with `import_id` and is listed in `StaleDataCollector`, with the columns of other
  tables that refer to it.
- `StaleDataCollector` keeps the finished-import brake and the reference checks.
