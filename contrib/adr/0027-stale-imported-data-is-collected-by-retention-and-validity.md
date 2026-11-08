# 0027. Stale imported data is collected by retention and validity

- **Status:** Accepted
- **Supersedes:** [0010](archive/0010-stale-imported-data-is-collected-after-a-retention-period.md)
- **Date:** 2026-11-07
- **Applies to:** `api/src/DataImport/**`, `api/src/Provider/*/DataImporter/**`, `api/config/services.yaml`

## Context

Importers upsert what upstream publishes and stamp every row with the import that wrote it. Rows that disappear
upstream were never removed: a stop ZTM dropped kept showing in search, a line that stopped running kept its old
timetable, and the `import` table grew with every run. Upstream feeds are someone else's infrastructure and
sometimes come back broken or incomplete, so "missing from today's feed" doesn't mean "gone".

[ADR 0010](archive/0010-stale-imported-data-is-collected-after-a-retention-period.md) collected rows by the age
of their import. Since [ADR 0025](0025-imported-schedules-are-versioned-by-validity.md) tracks, track stops, trips
and trip stops have a validity and a window of several days, written by imports of different ages, and imports run
as a graph of messages ([ADR 0026](0026-the-import-is-a-graph-of-messages.md)) that can take long, fail partly or
be superseded. The `import_id` rule alone would delete valid future days when the window is longer than the
retention, and "an import finished" no longer says that the data is complete.

## Considered options

1. **Delete during the import** whatever the feed no longer contains. One broken or partial feed removes real
   data, and every importer needs its own logic.
2. **Soft-delete flags** filtered by every repository. Every query has to know about them, rows pile up.
3. **A separate procedure removing rows by their stamps**: the `import_id` retention for unversioned rows, `valid_to`
   for versioned ones. One place, data survives short upstream outages; removal is delayed.

## Decision

We will collect stale data with a separate procedure (option 3), `App\DataImport\StaleDataCollector`:

- Operators, lines and stops are stale when the import that last wrote them started more than
  `COJEDZIE_STALE_DATA_RETENTION` ago (default `7 days`).
- Tracks, track stops, trips and trip stops are stale when `valid_to < now`, or when `valid_to IS NULL` and the
  `import_id` rule above applies (rows without validity, e.g. written by an old worker after the rollout
  migration of ADR 0025). A future `valid_to` is never stale.
- Tables of one provider's importer aren't the collector's: the ZTM importer deletes its `ztm_gdansk_schedule_day`
  rows once their date has passed. They refer to no import, so removing an import can't take a day of the window
  with it.
- Stale rows still referenced by rows that stay are kept; tables are collected children first.
- Nothing is collected unless an import **succeeded** (status `succeeded`: every node done; failed line jobs of the
  schedule are warnings) within the retention period, so a repeatedly failing feed can't release it; a failed or
  superseded import doesn't.
- Nothing is collected while an import runs: status `running` and started less than `COJEDZIE_IMPORT_TIMEOUT` ago
  (default `1 hour`, an import takes about 2 minutes), so an import that never finishes can't block it forever.
- Imports nothing refers to anymore are removed afterwards.
- It runs as `app:collect-stale-data` and on its own schedule (`COJEDZIE_SCHEDULE_COLLECT_STALE_DATA`, ADR 0021;
  both prefixed by ADR 0011).

## Consequences

- Data that disappears upstream stays visible for up to the retention period (plus a day, with a daily schedule);
  versioned data for as long as its validity.
- Importers must keep stamping every row they write with the current import; an unversioned row that isn't
  restamped is collected once the retention passes.
- Trips and track stops are still replaced by their importers per line and track; the collector also covers lines
  and tracks that vanish entirely, and versioned rows once they expired.

## Compliance

- Importers don't delete rows because they are missing from a feed (replacing a line's trips or a track's stops
  is fine); removal of disappeared data goes through `StaleDataCollector`.
- Every imported table is stamped with `import_id` and is listed in `StaleDataCollector`, with the columns of other
  tables that refer to it; tables with validity are listed as versioned.
- Versioned rows are collected by `valid_to`; the `import_id` rule applies to them only when `valid_to IS NULL`.
- `StaleDataCollector` keeps the succeeded-import brake, the in-progress guard and the reference checks.
