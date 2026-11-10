# 0039. Timetable entries are valid for the days they belong to

- **Status:** Accepted
- **Supersedes:** [0025](archive/0025-imported-schedules-are-versioned-by-validity.md)
- **Date:** 2026-11-10
- **Applies to:** `api/src/Entity/Validity*.php`, `api/src/Entity/{Track,TrackStop,Trip,TripStop}Entity.php`,
  `api/src/Provider/*/DataImporter/**`, `api/src/DataImport/StaleDataCollector.php`,
  `api/src/Provider/Database/**`, `api/src/Filter/Handler/Database/**`, `api/migrations/**`

## Context

[ADR 0025](archive/0025-imported-schedules-are-versioned-by-validity.md) versioned tracks, track stops, trips and trip stops by validity, and made a
trip's validity its actual run, which its stops inherited. Its context still holds: a week of schedules in advance,
versions as rows with distinct ids, and no cheap signal that a published day changed.

A run's validity said nothing true about the timetable: the timetable shows whole days
([ADR 0029](0029-timetables-are-selected-by-calendar-or-service-day.md)), the `extended` day mode also the previous
service day's runs after midnight, yet a row was "no longer valid" a minute after its run, and the collector removed
it then, so today's timetable started at about the last collection (#195). Tracks were already valid for whole days
(the days the feeds list them). Nothing reads a trip's validity as "running now".

## Considered options

1. **Keep run validity, and teach the collector which days a run belongs to** (per provider time zone). The data
   keeps saying a run is over when the timetable still shows it, and the collector carries timetable knowledge.
2. **Validity of the service day only.** Simple, but a run after midnight is shown on the next day's `extended`
   timetable while no longer valid.
3. **From the service day's start to the end of the departure's day** (chosen). An entry is valid on every day a
   timetable can show it; the collector only compares validity with now.

## Decision

We will version only tracks, track stops, trips and trip stops, as separate rows with distinct ids (option 1):

- They implement `ValidityAwareInterface` with `ValidityTrait`: nullable `valid_from` / `valid_to` (UTC);
  `valid_to = NULL` means valid until changed.
- ZTM track ids are `R{routeId}T{tripId}V{topologyVersionId}`, trip ids `R{routeId}T{tripId}S{busServiceName}O{order}D{date}`
  (letters denote the parts, the service date last). Track stops
  inherit the track's validity. A track's validity is the span of days the dated feeds list
  it in, extended by each import; a superseded version stops being extended and expires. Upstream labels every
  published day with an agency's current version, so a version that the import doesn't list while it lists another
  version of the same route and trip is superseded for the whole window and expires at once (`valid_to` = now).
  A timetable entry (trip stop) is valid from the start of its trip's service day to the end of the day it departs
  on, in the provider's time zone; a trip from the start of its service day to the end of its last entry's day. So
  an entry stays valid for every day whose timetable can show it, also a run after midnight on the next day.
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
- Expiry of these tables is by `valid_to`, see [ADR 0038](0038-stale-data-is-collected-by-retention-and-validity-with-a-grace.md).

## Consequences

- A day is downloaded once, so the daily run fetches one new day per line, not the window.
- Track ids are not stable: a changed route is a different track. A translation to the latest version can be
  added later if needed.
- Until the window has been downloaded for the first time, the import is long (see the wiki).
- Rows imported before validity existed have `valid_to NULL`, undated trip ids and unversioned track ids. A
  one-time migration deletes them on rollout (trip stops, trips, track stops, tracks), so the importers carry no
  code for them. Until the import reaches a line, the line has no timetable departures on the deploy day; live
  departures are unaffected. An old worker importing once more after the migration writes undated rows again,
  which the collector removes by the retention rule for rows without validity (ADR 0038).

## Compliance

- Importers give a trip stop the validity of the days whose timetables show it (its service day to the end of
  its departure's day, in the provider's time zone), and a trip the span of its stops'; never the run alone.
- Entities whose rows differ by period use `ValidityTrait`; their ids include what separates the versions, never
  the same id with several validity rows.
- Operators, lines and stops don't get validity columns.
- Queries listing tracks for the API filter on validity now.
- Importers decide what to download in one place, and write the record of an imported line+day
  (`ztm_gdansk_schedule_day`) in the same transaction as its trips.
