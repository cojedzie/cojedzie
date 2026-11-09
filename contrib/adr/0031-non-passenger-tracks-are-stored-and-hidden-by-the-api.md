# 0031. Non-passenger tracks are stored, and hidden by the API

- **Status:** Accepted
- **Date:** 2026-11-09
- **Applies to:** `api/src/Entity/TrackEntity.php`, `api/src/Dto/Track.php`, `api/src/Provider/**`,
  `api/src/Controller/Api/**`, the importers of tracks and trips

## Context

Providers publish more tracks than riders take. ZTM Gdańsk marks each trip with a `type`: `MAIN` for the track riders
know as the line in one direction, side variants (shortened runs), and `NON_PASSENGER` for runs to and from the
depot. The importer stores every track the same way, so depot runs show up wherever tracks do: in a stop's
destinations, in the line's tracks and in the timetable. The timetable line page (#141) needs a list of a line's
tracks with the main ones first and without depot runs, and every other rider-facing feature should leave depot runs
out as well.

## Considered options

1. **Drop non-passenger trips at import**: simplest, nothing downstream can show them. The data is gone, so the
   departure history and statistics can't tell a depot run from a missing one, and reversing the choice needs a
   re-import.
2. **Store a track kind, and hide non-passenger tracks in the API**: one column; the data stays for internal use, and
   the rule is enforced in one place per endpoint family.
3. **Store a track kind, and let clients filter**: every client (our frontend, federated hub, third parties) has to
   repeat the rule.

## Decision

We will store the kind of every track and hide non-passenger tracks in the API (option 2):

- A track has a kind: `main`, `side` or `non-passenger`. Providers fill it from their data; a provider that can't
  tell leaves it unset, which counts as `side`.
- A line usually has one main track per direction. This is not enforced: data may have none or several.
- Rider-facing endpoints (tracks, stop destinations, timetable, departures and anything added later) leave out
  non-passenger tracks and the trips on them by default.
- The importer keeps importing non-passenger trips and tracks.

## Consequences

- Depot runs disappear from the app without a frontend change, and their data stays available for history and
  diagnostics.
- Every endpoint that returns tracks, trips or entries on them has to apply the filter; a new endpoint that forgets
  it leaks depot runs.
- The Track DTO carries the kind, so clients can order tracks (main first) without guessing from stop counts.

## Compliance

- Tracks have a kind column; importers set it from the provider's data, never from heuristics in controllers.
- Rider-facing endpoints exclude `non-passenger` tracks and their trips by default; the exclusion is a shared
  requirement or repository rule, not code repeated per controller.
- No importer skips non-passenger trips or tracks.
- Nothing assumes exactly one main track per direction.
