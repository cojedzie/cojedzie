# 0007. Imported rows are upserted portably, importers are tested on recorded feeds

- **Status:** Accepted
- **Date:** 2026-10-28
- **Applies to:** `api/src/DataImport/**`, `api/src/Provider/*/DataImporter/**`, `api/tests/DataImport/**`,
  `api/tests/*/DataImporter/**`, `api/tests/*/Resources/**`

## Context

Provider importers write upstream data (operators, lines, stops, tracks) into the database on every `app:update`.
Each row may already exist from an earlier import, so it has to be inserted or updated. Every importer had its own
copy of that loop, and the pipeline had almost no tests, so several bugs in it went unnoticed for years.

The API supports SQLite, MySQL and Postgres, and CI only exercises SQLite. Upstream (ZTM Gdańsk) is someone else's
infrastructure, publishes data for ten days ahead and changes its format without notice.

## Considered options

1. **Select the existing ids of a batch, then update or insert each row** (what the importers did). Works the same
   on every platform with plain DBAL calls; costs one extra query per 100 rows.
2. **Native upserts** (`INSERT … ON CONFLICT` on SQLite and Postgres, `ON DUPLICATE KEY UPDATE` on MySQL). Fewer
   round trips, but three dialects, and only one of them tested in CI.
3. **Tests against live upstream feeds.** Always current, but slow, flaky, dependent on another party's
   infrastructure, and a load on it.
4. **Tests on feeds recorded from upstream and kept in the repository.** Deterministic and offline; they show a
   format change only once a newer day is recorded.

## Decision

We will write imported rows that are updated in place through one helper, `App\DataImport\TableUpserter`, which
selects the existing ids of each batch and then updates or inserts (option 1). Rows replaced wholesale on every import
(track stops, trips and their stops) are deleted and inserted again, so they need no upsert. Native upserts can
replace its internals later, behind the same method, if import time ever matters.

We will test importers in PHPUnit against the test database, with feed rows served by a stub streamer, and check them
against what upstream sends with one day of feeds recorded from it and kept as test resources (option 4). Tests never
call upstream.

## Consequences

- The importers contain only what is specific to their feed; batching, stamping `provider_id` and `import_id`, and
  the insert-or-update choice live in one place.
- An import costs one extra `SELECT` per 100 rows.
- A change in what ZTM publishes is caught when a newer day is recorded (see the README next to the recording), not
  automatically.
- The recorded day adds about 5.5 MB to the repository.

## Compliance

- Rows an import updates in place (operators, lines, stops, tracks) are written through `TableUpserter::upsert()`,
  not an importer's own insert-or-update loop. Rows an importer deletes and writes again on every import (track
  stops, trips, trip stops) may be inserted directly, and so may the provider registry (`ProviderDataImporter`),
  which isn't provider data.
- Importers wrap their writes in `Connection::transactional()`.
- No platform-specific SQL (`ON CONFLICT`, `ON DUPLICATE KEY`, `INSERT OR REPLACE`) in importers or the helper.
- No test opens a provider's real upstream URL; feeds come from a stub streamer or from recorded files under
  `api/tests/<Provider>/Resources/`.
