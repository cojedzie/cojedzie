# Departure history (`api/src/DepartureHistory`)

Co Jedzie records what really happened to every departure, so that later features can answer what the timetable
can't: when a departure a rider takes routinely really arrives, how likely a transfer is to work out, how late a line
runs at a given time of day. Decided in [ADR 0020](../../../adr/0020-departure-history-is-stored-in-clickhouse.md).

Roadmap: (1) the recorder, done; (2) live departures read from the recorded data instead of one provider call per
stop per request; (3) the answers (statistics, expected arrival, transfer odds) and their UI. Later: cross-checking
with the provider's GPS feed, trip planning, backups to S3-compatible storage.

## Layers

| Namespace | What |
|---|---|
| `App\Provider\Live` | provider-neutral live departures: `LiveDeparture`, `DepartureStatus` (`scheduled`, `realtime`), `LiveDepartureSource` (all stops of a provider at once, tagged `app.live_departure_source`) |
| `App\DepartureHistory` | the business side: `DepartureRecorder` and the storage port `DepartureHistory`; no ClickHouse here |
| `App\ClickHouse` | `ClickHouseConnection` (smi2/phpclickhouse client from `COJEDZIE_CLICKHOUSE_URL`), `Migrator`, `ClickHouseDepartureHistory` |

## What is recorded

One row per departure per stop: the **last observation** before the departure left, in provider-neutral columns.
Intermediate estimates and the provider's raw entries are not kept (storage). For public transport, arrival is taken
to equal departure.

`RecordDepartures` (schedule `recorder`, transport `scheduler_recorder`, every `COJEDZIE_INTERVAL_RECORD_DEPARTURES`,
default `20 seconds`, matching how often ZTM updates) is handled in place by `RecordDeparturesMessageHandler`, which
calls `DepartureRecorder`. The recorder asks every `LiveDepartureSource` for all its stops and records them as
observed now. Each poll re-inserts every departure still on the boards; ClickHouse keeps the newest.

- A failing source or storage is a `warning`; nothing throws, so Messenger doesn't retry stale polls. One failing
  source doesn't stop the others.
- Polls closer than half the interval to the previous one are skipped: the scheduler yields every run missed while a
  poll was slow.
- The storage is prepared once per worker process (and again after a failure): pending migrations run, then the
  retention is applied.
- Sentry cron monitor `record-departures`: one `ok` check-in at most once a minute, after a poll where every source
  succeeded (interval monitor, 1 minute, 5 minutes margin). Failures show up as logged warnings. Handlers check in
  through `App\Utility\SentryCronMonitorTrait` (`monitored()` around a job, `heartbeat()` for frequent ones).
- `bin/console app:record-departures` runs one poll now.
- Without `COJEDZIE_CLICKHOUSE_URL` the schedule is empty and nothing touches ClickHouse; `tests/bootstrap.php` pins it
  empty.

## Migrations

The schema changes only through migrations in `api/migrations/clickhouse` (namespace `ClickHouseMigrations`): classes
named `VersionYYYYMMDDHHMMSS` implementing `App\ClickHouse\Migration`, autoconfigured as services. `Migrator` applies
the ones missing from `migration_versions`, in name order, and records each. Migrations only go up; write them so a
rerun after a half-applied one fails loudly rather than silently. `bin/console clickhouse:migrate` applies them by
hand; the recorder does it on its own when it starts.

## The table

Table `departures`, `ReplacingMergeTree(observed_at)`, partitioned by month of `scheduled_at`, ordered by
`(provider, stop_id, line_id, scheduled_at, trip_key)`, `TTL scheduled_at + INTERVAL
COJEDZIE_DEPARTURE_HISTORY_RETENTION_DAYS DAY` (default 365; next uses 30). The migration creates it with 365 days;
`ClickHouseDepartureHistory::prepare()` changes the TTL when the configured retention differs (changing it rewrites
the parts, so it isn't repeated).

| Column | Meaning |
|---|---|
| `provider` | provider identifier (`trojmiasto`) |
| `stop_id`, `line_id` | the provider's ids, as the API exposes them |
| `line_symbol` | what riders see (`171`, `C10`) |
| `trip_key` | tells apart departures of the same line, stop and minute within a day; ZTM: `<tripId>@<scheduledTripStartTime>` |
| `scheduled_at`, `estimated_at` | UTC; `estimated_at` is null without a live estimate |
| `delay` | `MATERIALIZED` column, seconds `estimated_at - scheduled_at`, negative when early; computed on insert and stored, left out of `SELECT *` |
| `status` | `Enum8('scheduled', 'realtime')`: only the provider's timetable, or estimated from a tracked vehicle |
| `headsign`, `vehicle` | as reported |
| `duty` | the vehicle's planned work for the day (a block, Polish *brygada*), the nearest the feeds come to a driver: drivers may change mid-duty unseen. No driver ids are stored, they would be personal data. ZTM: `vehicleService` (`171-01`), set for scheduled entries too |
| `observed_at` | our poll time, the version: the newest wins |

Reading: merges collapse duplicates only eventually, so use `FINAL` or `argMax(…, observed_at)`. To compare "the same"
departure across days (and across timetable changes) group by line + stop + scheduled **time of day in the provider's
time zone** (`toTimeZone(scheduled_at, 'Europe/Warsaw')`), not by `trip_key` or our trip ids, which every import
regenerates.

```sql
SELECT status, count(), avg(delay) FROM departures FINAL GROUP BY status
```

## ZTM Gdańsk

`ZtmGdanskLiveDepartures` (HttpClient) reads `{ZTM_GDANSK_API_URL}/departures`, and maps TRISTAR entries to
`LiveDeparture` once for both uses: `all()` asks **without** `stopId`, and TRISTAR answers for every stop at once, an
object keyed by stop id (on 2026-11-01: 2,622 stops, ~8.8k entries at noon, 89% realtime, 3.5 MB, under a second);
`forStop()` asks for one stop and serves `ZtmGdanskRealtimeDepartureRepository`, the live departures of the API. Only
`REALTIME` entries with a delay are `realtime` and have an estimate. Tested with `MockHttpClient` on a trimmed
recorded response in `tests/ZtmGdansk/Resources/live/`.

## Adding a provider

Implement `App\Provider\Live\LiveDepartureSource` (`getProviderIdentifier()`, `all(): Seq<LiveDeparture>`, throwing
`LiveDepartureException` when the feed can't be read). Autoconfiguration tags it; nothing else changes. One request
for all stops if the provider allows it.

## Testing

Unit tests use `tests/DepartureHistory/Support` (`InMemoryDepartureHistory`, `FakeLiveDepartureSource`). Tests that
need a ClickHouse server are in the phpunit group `clickhouse`, excluded by default: `task test:clickhouse` runs them
against the compose service in a throwaway database `cojedzie_test` (`COJEDZIE_CLICKHOUSE_TEST_URL` overrides it);
CI runs them with a ClickHouse service.

## Running it

- Dev: the `clickhouse` service (`clickhouse/clickhouse-server:25.8`, `cojedzie`/`cojedzie`, HTTP on `:8123`) with
  `clickhouse/low-memory.xml`; the `schedule` service consumes `scheduler_recorder`.
- Production and next: a `clickhouse` service in each swarm stack (server repo), same config, 2 GB memory limit, its
  own volume; `schedule` adds `scheduler_recorder` to `COJEDZIE_WORKER_QUEUES`.
- `low-memory.xml` makes ClickHouse a guest on a shared box: ~1.5 GB server memory cap, small mark cache, no
  uncompressed cache, and the internal system logs (`query_log`, `trace_log`, ...) removed, as they grow without
  limit.
- Size (2026-11-01, one ZTM snapshot of 8.4k rows after `OPTIMIZE … FINAL`, a small sample): ~20 compressed bytes a
  row, ~3 of them the stored `delay`. At ~285k departures a day that is ~6 MB a day, ~2.1 GB a year for production,
  ~0.17 GB for next's 30 days.
  Re-measure once a few days are recorded:
  `SELECT formatReadableSize(sum(data_compressed_bytes)), sum(rows) FROM system.parts WHERE table = 'departures' AND active`.
- Checked in dev on 2026-11-01: polls every 20 s for minutes without warnings; with ClickHouse stopped, each poll
  logged a warning and the worker kept going, and recording resumed after it came back.
