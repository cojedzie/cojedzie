# 0030. The timetable is enriched from departure history

- **Status:** Proposed
- **Date:** 2026-11-08
- **Applies to:** `api/src/DepartureHistory/**`, `api/src/ClickHouse/**`, `api/src/Provider/Database/**`,
  `api/src/Dto/Departure.php`

## Context

The timetable lists planned entries for a day or a range, for whole lines (about 30 stops) or several stops. Riders
also want to know whether an entry is running late or has left. Live estimates from TRISTAR cost one request per
stop and only cover the next hour. Departure history ([ADR 0020](0020-departure-history-is-stored-in-clickhouse.md))
already polls every stop every 20 seconds and keeps the last observation of each departure, including the outcome of
past ones. It is optional: no request path may require ClickHouse.

## Considered options

1. **TRISTAR per stop**, as `/departures` does: one request per stop, no past outcomes, slow for a line.
2. **Departure history**: one query, covers upcoming and past entries; estimates are as fresh as the last poll.
3. **Planned data only**: simplest, ignores what we already know.

## Decision

We will enrich timetable entries from departure history (option 2):

- `DepartureHistory` gets a read method; only `App\ClickHouse` implements it against ClickHouse.
- Entries are paired with recorded departures by stop + line + scheduled minute, using the pairing shared with
  `RealtimeTimetableDepartureRepository`.
- `Departure.state` is `departed` when the estimated (else scheduled) time has passed: the last recorded state is
  the outcome, shown whatever its age. An entry that hasn't departed is `realtime` only if observed within
  `COJEDZIE_TIMETABLE_ESTIMATE_MAX_AGE`; otherwise it is `planned` and the stale estimate is dropped.
- Without ClickHouse, or when the read fails (logged as a warning), entries are planned only.
- Recorded departures without a timetable entry are not added to the timetable.
- `/departures` is not changed by this: it stays rooted in live data for one stop.

## Consequences

- A line timetable costs one ClickHouse query instead of a request per stop, and shows past outcomes.
- Estimates lag the provider by up to one recorder poll; a stopped recorder degrades to the plan within the age limit.
- The history, built for statistics, becomes read on a request path, so the read has to stay cheap (stop/line and
  time-bounded, matching the table's ordering key).

## Compliance

- Timetable code reads live data only through the `DepartureHistory` port, never ClickHouse or TRISTAR directly.
- A timetable request succeeds without ClickHouse.
- The freshness limit comes only from `COJEDZIE_TIMETABLE_ESTIMATE_MAX_AGE`, never a literal.
- Pairing of planned and live departures exists once, shared by the timetable and `/departures`.
