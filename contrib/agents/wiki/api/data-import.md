# Data import & persistence

## `app:update`

The import is a persisted graph of tasks (`DataImport/ImportGraph`, table `import_task`), so several `main`
workers run its steps in parallel. `ImportGraph::start()` creates an `ImportEntity` and one task per
`DataImport\DataImporter` (tagged `app.data_importer`, a service locator keyed by class) with the importers'
`getDependencies()` as task ids, then dispatches a `RunImportTask` message for each task whose dependencies are
done. An importer whose dependencies are missing or circular makes `start()` fail with a `LogicException` naming it,
before anything is written. `ImportTaskRunner` (called by `RunImportTaskMessageHandler`) runs one task:

- Statuses (`ImportTaskStatus`): `pending` → `queued` (dispatched) → `running` → `done` | `failed`; `skipped` when a
  dependency failed or was skipped, or when a new import superseded this one. Every transition two workers could race
  for is one `UPDATE … WHERE id = :id AND status = '<expected>'` checked by its affected-row count, so a dependent
  is dispatched once and a duplicate delivery does nothing.
- An importer adds tasks while it runs with `DataUpdateEvent::spawn(importer, payload, description)`; they get the
  payload as `DataUpdateEvent::$payload` (null for the importer's own task), have no dependencies, and are inserted
  in the transaction marking their parent done. The ZTM schedule importer spawns one task per line+day.
- Any `\Throwable` fails the task with its message in `error`; there is no Messenger retry. A failed spawned task
  (a line+day) doesn't fail the import: it wrote no `schedule_day` row, so the next import plans it again.
- The import is finished when no task is `pending|queued|running`: whichever runner changes `import.finished_at`
  first (`… WHERE finished_at IS NULL`) also sets `import.succeeded` (no failed or skipped task without a payload) and
  closes the Sentry check-in `UpdateDataSourcesMessageHandler` opened (`import.check_in_id`, `maxRuntime` 240 min).
- `start()` supersedes an unfinished import: its pending and queued tasks are skipped and it is finished with
  `succeeded = false`; its running tasks complete, but nothing more runs for it.

`bin/console app:update` runs the same graph in-process (`ImportGraph::runSynchronously()`, no workers) and prints
the task counts by status; `app:update --async` dispatches `UpdateDataSources`, whose handler only starts the
graph.

Memory: `ImportTaskRunner` raises `memory_limit` for each task only and restores it afterwards, because in production
tasks run inside a long-lived messenger worker (`--memory-limit=128M`). `app:update` ends with a `Peak memory: N MB`
line. Measured on 2026-10-28 with a full day of ZTM data (183 lines, 285k scheduled stops, about 8 minutes):
48 MB peak in `prod`, 906 MB in `dev` with debug, because Doctrine keeps every query for the profiler. The limit
(2G) is sized for debug, since `task api:update` runs in `dev`.

Run it with `task api:update` (uses `COJEDZIE_API_MODE=performance` by default).

Scheduling (ADR 0021): Symfony Scheduler in `src/Schedule`. Each schedule is its own environment variable, read by
`ScheduleConfig` (defaults in `config/services.yaml`): a cron expression (`COJEDZIE_SCHEDULE_*`), or an interval
(`COJEDZIE_INTERVAL_*`, a `DateInterval::createFromDateString()` string) for jobs more frequent than a minute:

| Variable | Default | Job | Transport |
|---|---|---|---|
| `COJEDZIE_SCHEDULE_UPDATE_DATA_SOURCES` | `0 5 * * *` | redispatch `UpdateDataSources` to `main` (the import) | `scheduler_default` |
| `COJEDZIE_SCHEDULE_FEDERATION_CHECK` | `* * * * *` | `CheckFederatedConnection` for connections due | `scheduler_hub` |
| `COJEDZIE_SCHEDULE_FEDERATION_CLEANUP` | `0 0 * * *` | `CleanupFederatedConnections` | `scheduler_hub` |
| `COJEDZIE_SCHEDULE_COLLECT_STALE_DATA` | `0 6 * * *` | redispatch `CollectStaleData` to `main` | `scheduler_default` |
| `COJEDZIE_INTERVAL_RECORD_DEPARTURES` | `20 seconds` | `RecordDepartures`, handled in place (see [departure-history.md](departure-history.md)); empty schedule without ClickHouse | `scheduler_recorder` |

A schedule only runs where a worker consumes its transport (`COJEDZIE_WORKER_QUEUES`):

- Production: `worker` consumes `main`; a separate `schedule` service (worker image) consumes
  `scheduler_default scheduler_hub scheduler_recorder`. The dev `docker-compose.yml` mirrors it.
- The `standalone` image consumes `main scheduler_default`: it imports its own data but doesn't check nodes.
- `UpdateDataSourcesMessageHandler` starts the import and opens the Sentry check-in of the configured schedule; the
  task finishing the import closes it.

Use cron syntax for `COJEDZIE_SCHEDULE_*`, not `@daily`: in `services.yaml` a leading `@` is read as a service reference.

`bin/docker-init.sh` runs migrations **and** a synchronous `app:update` on container start whenever migrations
are out of date.

## Provider importers

ZTM Gdańsk importers live in `Provider/ZtmGdansk/DataImporter` (lines, operators, stops, tracks, schedule).
`Service/ProviderDataImporter` registers the providers `ProviderResolver` serves (the dummy provider only in debug).

- Operators, lines, stops and tracks are written with `DataImport\TableUpserter::upsert()`: rows keyed by id, batched
  by 100, existing ids of the provider updated, new ones inserted, every row stamped with `provider_id` and the
  current `import_id`. It opens no transaction; each importer wraps its writes in `Connection::transactional()`.
- Importers never delete rows that disappeared upstream (except track stops, replaced per track);
  `StaleDataCollector` does, see below.
- Track stops: the `stopsInTrip` feed is **not ordered by track** (one day of the 2022 fixture: 21,897 rows of 1,332
  tracks in 21,866 runs), so they are grouped in memory, reduced to track id, stop id and sequence first. Unknown
  stops are filtered after grouping, so a track whose stops are all unknown still has its old stops cleared.
  `tests/Benchmark/Collections/TrackStopsBench` measures this step; its variants test checks the importer matches
  the `compact` variant.
- The schedule is imported per line and day of the window, one transaction each (`importLineDay()`). `plan()`
  lists the line+days without a `schedule_day` row, the only place deciding what is downloaded; the importer's own
  task spawns a task per planned line+day. The row is written also for a day without trips, so it isn't fetched
  again. Trip ids end with the service date, a trip of an unknown track version is skipped with a warning. A line+day
  whose feed can't be opened, parsed or read in time writes nothing and its task fails, so the next run retries it.
  A unique violation (another import wrote the line+day after the check) ends the task as done.
- Upstream feeds are opened by `Parser\FileStringStream` with a 60 s timeout for answering and for each read.

## Stale data (ADR 0010)

`DataImport\StaleDataCollector` (`app:collect-stale-data`, scheduled by `COJEDZIE_SCHEDULE_COLLECT_STALE_DATA`) removes
operators, lines, stops, tracks, track stops, trips and trip stops whose `import_id` points to an import started
more than `COJEDZIE_STALE_DATA_RETENTION` ago (default `7 days`, any `DateInterval::createFromDateString()` string), i.e. that
no import has written since. Tracks, track stops, trips and trip stops also go once their `valid_to` passed (never
with `valid_to IS NULL`), and `schedule_day` rows of days before today in Warsaw. Then it removes the `import` rows
nothing refers to anymore (a `schedule_day` row keeps its import: the row cascades with it). Provider rows stay.

- Children are collected first, and a stale row still referenced by a row that stays is kept (a stop on a current
  track, an operator of a current line), so foreign keys never block and fresh data never loses a relation.
- Brake: unless an import **succeeded** within the retention period, nothing is removed and the handler logs a
  warning, so a stalled or failing import can't wipe the data. Nothing is removed either while an import runs
  (unfinished and started less than `COJEDZIE_IMPORT_TIMEOUT` ago, default `6 hours`).
- One transaction; plain `DELETE … WHERE import_id IN (…) AND NOT EXISTS (…)`, checked on SQLite, MariaDB and
  Postgres.

Tests: `tests/DataImport/ImporterTestCase` runs an importer against the test database inside a transaction rolled
back after each test, with `StubJsonStreamer` serving inline feed rows; `runImporter()` also runs the tasks an
importer spawned, collecting failures in `$failures`. `tests/DataImport/ImportGraphTest` covers the graph. Per ADR 0001, importers are tested there, not
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
