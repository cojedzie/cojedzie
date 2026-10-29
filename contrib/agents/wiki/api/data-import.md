# Data import & persistence

## `app:update`

`bin/console app:update` (`Command/UpdateCommand` → `Service/DataUpdater`) creates an `ImportEntity`, then runs
every `DataImport\DataImporter` (tagged `app.data_importer`) in topological dependency order, reporting progress
through `ProgressReporterFactory` (console + `import` monolog channel). An importer whose dependencies are missing
or circular makes the update fail with a `LogicException` naming it.

Memory: `DataUpdater` raises `memory_limit` for the import only and restores it afterwards, because in production the
import runs inside a long-lived messenger worker (`--memory-limit=128M`). Every run ends with a `Peak memory: N MB`
milestone. Measured on 2026-10-28 with a full day of ZTM data (183 lines, 285k scheduled stops, about 8 minutes):
48 MB peak in `prod`, 906 MB in `dev` with debug, because Doctrine keeps every query for the profiler. The limit
(2G) is sized for debug, since `task api:update` runs in `dev`. `gc_collect_cycles()` between importers made no
difference and was removed.

Run it with `task api:update` (uses `APP_MODE=performance` by default).

Scheduling (ADR 0009): Symfony Scheduler in `src/Schedule`. Each schedule is a cron expression in its own
environment variable, read by `ScheduleConfig` (defaults in `config/services.yaml`):

| Variable | Default | Job | Transport |
|---|---|---|---|
| `SCHEDULE_UPDATE_DATA_SOURCES` | `0 5 * * *` | redispatch `UpdateDataSources` to `main` (the import) | `scheduler_default` |
| `SCHEDULE_FEDERATION_CHECK` | `* * * * *` | `CheckFederatedConnection` for connections due | `scheduler_hub` |
| `SCHEDULE_FEDERATION_CLEANUP` | `0 0 * * *` | `CleanupFederatedConnections` | `scheduler_hub` |
| `SCHEDULE_COLLECT_STALE_DATA` | `0 6 * * *` | redispatch `CollectStaleData` to `main` | `scheduler_default` |

A schedule only runs where a worker consumes its transport (`COJEDZIE_WORKER_QUEUES`):

- Production: `worker` consumes `main`; a separate `schedule` service (worker image) consumes
  `scheduler_default scheduler_hub`. The dev `docker-compose.yml` mirrors it.
- The `standalone` image consumes `main scheduler_default`: it imports its own data but doesn't check nodes.
- `UpdateDataSourcesMessageHandler` handles the import and reports the configured schedule to Sentry.

Use cron syntax, not `@daily`: in `services.yaml` a leading `@` is read as a service reference.

`bin/docker-init.sh` runs migrations **and** a synchronous `app:update` on container start whenever migrations
are out of date.

## Provider importers

ZTM Gdańsk importers live in `Provider/ZtmGdansk/DataImporter` (lines, operators, stops, tracks, schedule).
`Service/ProviderDataImporter` registers the providers `ProviderResolver` serves (the dummy provider only in debug).

- Operators, lines, stops and tracks are written with `DataImport\TableUpserter::upsert()`: rows keyed by id, batched
  by 100, existing ids of the provider updated, new ones inserted, every row stamped with `provider_id` and the
  current `import_id`. It opens no transaction; each importer wraps its writes in `Connection::transactional()`.
- Importers never delete rows that disappeared upstream (except trips and track stops, replaced per line and
  track); `StaleDataCollector` does, see below.
- Track stops: the `stopsInTrip` feed is **not ordered by track** (one day of the 2022 fixture: 21,897 rows of 1,332
  tracks in 21,866 runs), so they are grouped in memory, reduced to track id, stop id and sequence first. Unknown
  stops are filtered after grouping, so a track whose stops are all unknown still has its old stops cleared.
  `tests/Benchmark/Collections/TrackStopsBench` measures this step; its variants test checks the importer matches
  the `compact` variant.
- The schedule is imported one line per transaction; a line whose feed can't be opened, parsed or read in time
  (`StreamOpenException`, `UnexpectedTokenException`, `EndOfStreamException`, `StreamReadException`) is skipped with
  a warning.
- Upstream feeds are opened by `Parser\FileStringStream` with a 60 s timeout for answering and for each read.

## Stale data (ADR 0010)

`DataImport\StaleDataCollector` (`app:collect-stale-data`, scheduled by `SCHEDULE_COLLECT_STALE_DATA`) removes
operators, lines, stops, tracks, track stops, trips and trip stops whose `import_id` points to an import started
more than `STALE_DATA_RETENTION` ago (default `7 days`, any `DateInterval::createFromDateString()` string), i.e. that
no import has written since. Then it removes the `import` rows nothing refers to anymore. Provider rows stay.

- Children are collected first, and a stale row still referenced by a row that stays is kept (a stop on a current
  track, an operator of a current line), so foreign keys never block and fresh data never loses a relation.
- Brake: unless an import **finished** within the retention period, nothing is removed and the handler logs a
  warning, so a stalled or failing import can't wipe the data.
- One transaction; plain `DELETE … WHERE import_id IN (…) AND NOT EXISTS (…)`, checked on SQLite, MariaDB and
  Postgres.

Tests: `tests/DataImport/ImporterTestCase` runs an importer against the test database inside a transaction rolled
back after each test, with `StubJsonStreamer` serving inline feed rows. Per ADR 0001, importers are tested there, not
in Behat. Tests never call upstream: `ZtmGdanskRecordedFeedsTest` imports one day of feeds recorded from ZTM
(`tests/ZtmGdansk/Resources/<date>/`, with a README on how to record a newer day) through the real parser, to catch
changes in what ZTM publishes.

ZTM data notes from the 2026-10-28 recording: most routes have `routeType` `UNKNOWN` (only buses and trams are
typed), so the line type still comes from the agency (2 = tram, 5 = trolleybus); agency 5 (PKT Gdynia) also runs a
few other lines (e.g. 181) that end up as trolleybuses. `stopTimes` rows carry `passenger`/`nonpassenger` flags the
schedule importer doesn't use.

## Database

Doctrine ORM; entities in `src/Entity` (`*Entity` suffix). Three engines are supported, each with its own
migration set: `migrations/sqlite`, `migrations/mysql`, `migrations/postgres`. Default local DSN is SQLite
(`api/.env`).

When changing entities, generate migrations for **all three** engines with `task api:migrations:generate`
(requires the `postgres` and `mysql` compose services to be running, since it uses `docker compose exec`).

The CI job `schema` runs each engine's migrations on an empty database (MariaDB 10.7 and Postgres 14 as service
containers, SQLite in a file) and then `doctrine:schema:validate`, so a migration missing on one engine fails the
build. Production runs on MySQL: its migrations must also apply to a database with data. Things learned bringing the
three in line (2026-10-28, after DBAL 4 dropped the `DC2Type` column comments):

- After generating, apply the migration and run `doctrine:schema:update --dump-sql` again; it must say nothing is
  left. All three engines converge.
- `messenger_messages` belongs to the Doctrine Messenger transport, which declares it to the schema
  (`MessengerTransportDoctrineSchemaListener`) and also creates it on first use (`auto_setup`). Migrations create it
  only when `$schema->hasTable()` says it is missing.
- SQLite applies schema changes by rebuilding tables (copy to a temporary table, drop, recreate, copy back); the
  rows survive, including `track.final_id`.
- Postgres: the 2021 migrations used `SERIAL` for `track_stop.id` and `trip_stop.id`; DBAL 4 maps them to identity
  columns. The switch drops the old sequence and moves the identity past the existing ids, or the next insert
  collides. Postgres also lacked most foreign keys until then.
