# 0025. Imported schedules are versioned by validity

- **Status:** Accepted
- **Date:** 2026-11-07
- **Applies to:** `api/src/Entity/Validity*.php`, `api/src/Entity/{Track,TrackStop,Trip,TripStop}Entity.php`,
  `api/src/Provider/*/DataImporter/**`, `api/src/DataImport/StaleDataCollector.php`,
  `api/src/Provider/Database/**`, `api/src/Filter/Handler/Database/**`, `api/migrations/**`

## Context

Co Jedzie held one day of the ZTM Gdańsk timetable: each daily import deleted a line's trips and wrote today's.
We want a week of schedules in advance (`ZTM_GDANSK_SCHEDULE_DAYS`, default 7) without downloading a week every
day (one day of `stopTimes` takes about 13 minutes of work). With several days present, the same route can exist
in two topology versions at once (a detour that starts on Thursday), and the same trip runs on several dates.
Track and trip ids carried neither, so the second day or version collided with the first.

Upstream publishes 10 days of lines, stops and tracks at once, keyed by date, but `stopTimes` costs a request per
route and day, and nothing cheap tells that a published day changed: the topology version is per agency and
changes every few days (5 of 11 agencies bumped it on the day it was recorded), and the feeds are streamed, so
hashing them is awkward.

## Considered options

1. **Versions as separate rows with distinct ids.** The id carries the topology version (tracks) or the service
   date (trips); `valid_from` / `valid_to` mark when a row applies. `id` stays the primary key, foreign keys stay
   plain, and a trip points straight at the track version it runs on.
2. **One id with several validity rows.** Stable ids, but `id` stops being the primary key and every foreign key
   and lookup needs a date condition.
3. **Validity on every imported table.** Operators, lines and stops would be versioned too, for little gain: a
   line is a bag of tracks and a stop with stale coordinates for a while is acceptable.
4. **Detecting changed days and refetching them.** Rejected for now, see above; no usable signal exists.

## Decision

We will version only tracks, track stops, trips and trip stops, as separate rows with distinct ids (option 1):

- They implement `ValidityAwareInterface` with `ValidityTrait`: nullable `valid_from` / `valid_to` (UTC);
  `valid_to = NULL` means valid until changed.
- ZTM track ids are `R{routeId}T{tripId}V{topologyVersionId}`, trip ids `R{routeId}T{tripId}S{busServiceName}O{order}D{date}`
  (letters denote the parts, the service date last). Track stops
  inherit the track's validity, trip stops the trip's. A track's validity is the span of days the dated feeds list
  it in, extended by each import; a superseded version stops being extended and expires. Upstream labels every
  published day with an agency's current version, so a version that the import doesn't list while it lists another
  version of the same route and trip is superseded for the whole window and expires at once (`valid_to` = now).
  A trip's validity is its actual run, which can pass midnight.
- Operators, lines and stops stay single rows with stable ids; the latest import wins.
- Track listings return only tracks valid now (a week holds several versions of a route). Departures are
  unaffected, a trip points at its own track.
- A line+day is downloaded only when the dated routes feed lists the line on that day (a line that doesn't run
  has no schedule, upstream answers 404) and it has no `ztm_gdansk_schedule_day` row; a day without trips gets one
  too. The table is the ZTM importer's own (another provider might import by week): it refers to no import, so
  collecting an old import can't drop a day still in the window, and the importer deletes the days that passed. A
  download that fails, a 404 included, is a failure of the line+day (a warning of its line job), retried by
  the next run. A published
  day is assumed not to change within the window. Timetable changes inside the window are therefore **not
  detected** (open problem); the decision which line+days to download is kept in one place (`plan()`), where a
  future signal can be added.
- Expiry of these tables is by `valid_to`, see [ADR 0027](0027-stale-imported-data-is-collected-by-retention-and-validity.md).

## Consequences

- A day is downloaded once, so the daily run fetches one new day per line, not the window.
- Track ids are not stable: a changed route is a different track. A translation to the latest version can be
  added later if needed.
- Until the window has been downloaded for the first time, the import is long (see the wiki).
- Rows imported before validity existed have `valid_to NULL`, undated trip ids and unversioned track ids. A
  one-time migration deletes them on rollout (trip stops, trips, track stops, tracks), so the importers carry no
  code for them. Until the import reaches a line, the line has no timetable departures on the deploy day; live
  departures are unaffected. An old worker importing once more after the migration writes undated rows again,
  which the collector removes by the retention rule for rows without validity (ADR 0027).

## Compliance

- Entities whose rows differ by period use `ValidityTrait`; their ids include what separates the versions, never
  the same id with several validity rows.
- Operators, lines and stops don't get validity columns.
- Queries listing tracks for the API filter on validity now.
- Importers decide what to download in one place, and write the record of an imported line+day
  (`ztm_gdansk_schedule_day`) in the same transaction as its trips.
