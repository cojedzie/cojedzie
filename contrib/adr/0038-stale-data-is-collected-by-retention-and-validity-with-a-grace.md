# 0038. Stale data is collected by retention and validity, with a grace

- **Status:** Accepted
- **Supersedes:** [0027](archive/0027-stale-imported-data-is-collected-by-retention-and-validity.md)
- **Date:** 2026-11-10
- **Applies to:** `api/src/DataImport/**`, `api/src/Provider/*/DataImporter/**`, `api/config/services.yaml`

## Context

[ADR 0027](archive/0027-stale-imported-data-is-collected-by-retention-and-validity.md) collects disappeared data by the age of the import that wrote it, and versioned
rows (tracks, track stops, trips, trip stops) as soon as `valid_to` passed. Its context still holds: upstream feeds
are someone else's infrastructure, and "missing from today's feed" doesn't mean "gone".

Deleting a row the moment its validity ends leaves no room for anything being off: a validity a bit too short (rows
imported before [ADR 0039](0039-timetable-entries-are-valid-for-the-days-they-belong-to.md) made trip validity whole days were deleted at the end of their run,
#195), a clock or a time zone off, or a collection running late.

## Considered options

1. **Delete at `valid_to`**, as before. Exact, but any error in a validity removes data still shown.
2. **Delete once validity ended more than a grace ago** (chosen), configurable, a day by default. Rows stay a bit
   longer than needed; nothing shown is removed early.

## Decision

We will collect stale data with a separate procedure (option 3), `App\DataImport\StaleDataCollector`:

- Operators, lines and stops are stale when the import that last wrote them started more than
  `COJEDZIE_STALE_DATA_RETENTION` ago (default `7 days`).
- Tracks, track stops, trips and trip stops are stale when their validity ended more than
  `COJEDZIE_STALE_DATA_GRACE` ago (default `1 day`: `valid_to < now - grace`), or when `valid_to IS NULL` and the
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
  versioned data for as long as its validity, plus the grace.
- Importers must keep stamping every row they write with the current import; an unversioned row that isn't
  restamped is collected once the retention passes.
- Trips and track stops are still replaced by their importers per line and track; the collector also covers lines
  and tracks that vanish entirely, and versioned rows once they expired.

## Compliance

- Importers don't delete rows because they are missing from a feed (replacing a line's trips or a track's stops
  is fine); removal of disappeared data goes through `StaleDataCollector`.
- Every imported table is stamped with `import_id` and is listed in `StaleDataCollector`, with the columns of other
  tables that refer to it; tables with validity are listed as versioned.
- Versioned rows are collected by `valid_to` less the grace, never by anything else (service days, time zones):
  what a row is valid for is the importer's to say ([ADR 0039](0039-timetable-entries-are-valid-for-the-days-they-belong-to.md)). The `import_id` rule applies to them
  only when `valid_to IS NULL`.
- `StaleDataCollector` keeps the succeeded-import brake, the in-progress guard and the reference checks.
