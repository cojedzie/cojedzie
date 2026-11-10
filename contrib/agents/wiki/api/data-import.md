# Data import & persistence

## `app:update`

The import is a persisted graph of importers (`DataImport/ImportGraph`, table `import_task`, one row per node), so
several `main` workers run its steps in parallel. `ImportGraph::start()` creates an `ImportEntity` and one node per
`DataImport\DataImporter` (tagged `app.data_importer`, a service locator keyed by class) with the importers'
`getDependencies()` as node ids, then dispatches a `RunImportTask` message for each node whose dependencies are
done. Nothing is added to the graph at runtime. An importer whose dependencies are missing or circular makes
`start()` fail with a `LogicException` naming it, before anything is written. `ImportTaskRunner` (called by
`RunImportTaskMessageHandler` and `RunImportJobMessageHandler`) runs one node or job:

- Node statuses (`ImportTaskStatus`): `pending` → `queued` (dispatched) → `running` → `done` | `failed`, or
  `running` → `waiting` → `running` → `done` | `failed` for a node with jobs; `skipped` when a dependency failed or
  was skipped, or when a new import superseded this one. Job statuses (`ImportJobStatus`, table `import_job`):
  `queued` → `running` → `done` | `failed` | `skipped`. Import statuses (`ImportStatus`, column `import.status`):
  `running` → `succeeded` | `failed` (by its last node) | `superseded` (by a new import). Every transition two
  workers could race for is one DQL `UPDATE … WHERE id = :id AND status = :expected` checked by its affected-row
  count, so a dependent is dispatched once and a duplicate delivery does nothing.
- `ImportGraph` and `ImportTaskRunner` work on the entities (`ImportEntity`, `ImportTaskEntity`, `ImportJobEntity`):
  reads are DQL queries with `Query::HINT_REFRESH`, since other workers change the rows with UPDATE statements and
  the identity map would return stale objects (in `app:update` one entity manager holds everything); jobs are
  persisted and flushed. Uuid parameters are bound with the type `uuid` explicitly (DQL infers a string, which
  misses the binary column on SQLite and MySQL). Raw SQL is only in importers, for batching and upserts.
- Any `\Throwable` of the importer fails the node (or job) with its message in `error`; it is not retried within
  the run. Messenger's default retries still apply to a handler that throws outside that (a transient database or
  transport error in the bookkeeping), and a redelivery resumes it: when the claim fails, `run()` re-dispatches the
  queued jobs of a `waiting` node, or calls `ended()` (`dispatchReady()` + `finishIfComplete()`) for an ended one;
  `runJob()` of an ended job calls `endTask()`, which ends a waiting node or, for an ended one, calls `ended()`.
  Duplicates of those are no-ops by their claims. A `running` node or job is left to the delivery running it.
- The import is finished when no node is `pending|queued|running|waiting`: whichever runner ends it first
  (`… WHERE status = 'running'`) sets `succeeded` (no failed or skipped node) or `failed`, and `finished_at`.
- `start()` supersedes the unfinished imports, each loaded as an entity: its pending and queued nodes, queued jobs
  and waiting nodes with no running job are skipped and it ends as `superseded` (that transaction is retried up to 3
  times by `Utility\Retry::onLockFailure()` on a `RetryableException`, as it can deadlock with a job claim on MySQL;
  it is a DBAL transaction because the entity manager's closes the manager on failure); its running nodes and jobs
  complete, but nothing more runs for it. What keeps jobs queued *after* that from running is not `supersede()` but a guard: `run()`
  sets a node `waiting` only `WHERE` its import is unfinished (else the node is skipped with no jobs), and `runJob()`
  claims a job only while its import is unfinished (else the job is skipped, and its node once no job is left).
  `jobsEnded()` can therefore still run for an import superseded meanwhile.
- No reaper: a worker killed mid-job leaves that job `running` and its node `waiting`. The next run supersedes the
  import, and the collector ignores an import older than `COJEDZIE_IMPORT_TIMEOUT`.

### Events, check-ins and progress

- `ImportGraph` dispatches `App\Event\ImportStartedEvent` once an import and its nodes are committed, and
  `App\Event\ImportFinishedEvent` once when it ends: from the runner whose claim ended it, or from `start()` for each
  import it superseded (before the new import's started event). Both carry the `ImportEntity` with its status.
  `app:update` dispatches them too.
- `App\Subscriber\ImportCronMonitorSubscriber` reports them to the Sentry cron monitor `update-data-sources`
  (schedule `COJEDZIE_SCHEDULE_UPDATE_DATA_SOURCES`, `maxRuntime` 240 min): an in-progress check-in on start, whose id
  it stores by import id in the cache pool `cache.import_check_ins` (Redis at `COJEDZIE_REDIS_URL`, a day's
  lifetime; `cache.adapter.array` in the test env), and `ok` (succeeded) or `error` (failed, superseded) on finish,
  then it deletes the key. Without a Sentry DSN the hub returns no check-in id and nothing is stored. An unreachable
  Redis only logs cache warnings: the import doesn't depend on it. An import that fails to start opens no check-in;
  the monitor reports the run as missed, and the handler logs the error as critical.
- Nodes and jobs store their progress (`progress`, `progress_max`, `progress_comment`, integers and 255 characters)
  through `DataImport\PersistingProgressReporter`, which wraps the reporter the runner gives a node (its import,
  `jobEnded()`, `jobsEnded()`) or a job (`runJob()`): one DQL UPDATE by id at most once a second, and always when
  `finished: true`. Subtask reporters aren't stored. Progress reported inside an importer's own transaction (lines,
  stops) is written in that transaction, so it shows once it commits. A job's progress is still shown where its
  node's is (console bar or log).

### Importers that split their work into jobs

An importer that implements `DataImport\SplittingDataImporter` stays in control of work it hands out to any worker:

1. Its `import()` runs as the node and calls `$event->addJob(array $payload)` for each piece (JSON-serializable).
   `ImportTaskRunner` persists the jobs as `import_job` rows in one transaction with the node becoming `waiting`,
   then dispatches a `RunImportJob` for each. A node that adds no jobs ends as a plain one; a plain importer adding
   jobs is a `LogicException`.
2. `runJob($reporter, $event, $payload)` runs on whichever worker takes the job; throwing fails the job only, and
   so does adding jobs from it (a `LogicException`: only the node adds jobs).
3. `jobEnded($reporter, ImportJobCounts $counts)` runs after each job (counts of its jobs so far: `total`, `done`,
   `failed`, with `ended()` and `remaining()`), for progress. It runs on the worker that ran the job.
4. When no job is `queued` or `running`, one worker claims the node (`waiting → running … AND NOT EXISTS` an
   unended job, so jobs ending at once end it once) and calls `jobsEnded($reporter, $counts)`, which returns whether
   the node succeeded. Only then are its dependents dispatched.

Waiting is event-driven: the waiting node holds no worker. Rejected: a worker blocking and polling for its jobs
(a worker per waiting importer, a deadlock when jobs have no free worker), and fibers (the importer would stay
suspended in one process and die with a worker restart or deploy). In tests, `ImporterTestCase::runImporter()`
runs the importer's jobs as well, collecting failed ones in `$failures`.

`bin/console app:update` runs the same graph, nodes and jobs, in-process (`ImportGraph::runSynchronously()`, no
workers) and prints the node counts by status; `app:update --async` dispatches `UpdateDataSources`, whose handler only starts the
graph.

Memory: `ImportTaskRunner` raises `memory_limit` for each node and job only and restores it afterwards
(`App\Utility\MemoryLimit::raisedWhile()`), because in
production they run inside a long-lived messenger worker (`--memory-limit=128M`). `app:update` ends with a `Peak memory: N MB`
line. Measured on 2026-10-28 with a full day of ZTM data (183 lines, 285k scheduled stops, about 8 minutes):
48 MB peak in `prod`, 906 MB in `dev` with debug, because Doctrine keeps every query for the profiler. The limit
(2G) is sized for debug, since `task api:update` runs in `dev`. An in-process `app:update` in `dev` on a full date
reaches ~1 GB and grows with the jobs: the profiler also wraps `http_client` in `TraceableHttpClient`, which keeps
the response bodies. Workers reset services between messages and `prod` traces nothing, so this is a dev-only effect
of running everything in one process.

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
- `UpdateDataSourcesMessageHandler` only starts the import; its Sentry check-ins come from the import events, see
  [Events, check-ins and progress](#events-check-ins-and-progress).

Use cron syntax for `COJEDZIE_SCHEDULE_*`, not `@daily`: in `services.yaml` a leading `@` is read as a service reference.

`bin/docker-init.sh` runs migrations **and** `app:update --async` on container start whenever migrations
are out of date: a first import of the whole window takes too long to block the start, so the workers run it.

## Schedule window (ADRs 0039, 0026)

`ZTM_GDANSK_SCHEDULE_DAYS` (default 7, at least 1) days of schedules are kept, today first (Warsaw). Upstream's dated
feeds (stops, routes, trips, stopsInTrip) hold 10 days, so lines, stops and tracks are imported for the whole
window without extra downloads; only `stopTimes` costs a request per route and day. The stops, lines and tracks bars
advance one step per day of the window, with the window as their total (they used to count only rows not seen on an
earlier day, so after day one they barely moved and had no total).

`JsonStreamer::streamDated()` reads those feeds in 1 MB chunks, cuts them into days at the top-level date keys and
`json_decode`s each requested day lazily, once its rows are read (a generator keeps its last yielded value, so
yielding decoded rows directly kept two days alive). Measured on the 2026-11-07 feeds without xdebug, 7 days of
stopsInTrip: streaming parser 15.7 s / ~4 MB, whole-file `json_decode` 0.14 s / 238 MB, per day 0.13 s / 35 MB.
Xdebug (the dev containers' default `debug` mode) makes the streaming parser about 4.7× slower again; measure
imports with `task api:update` (performance mode). Single-day reads (`stream()`, e.g. `stopTimes`) still stream.

- Tracks, track stops, trips and trip stops carry `valid_from` / `valid_to` (`ValidityTrait`, `NULL` = until
  changed). Track ids are `R{routeId}T{tripId}V{topologyVersionId}`, trip ids
  `R{routeId}T{tripId}S{busServiceName}O{order}D{date}` (unique per run and service day). A track
  is valid for the days the dated feeds list it in. A trip stop (a timetable entry) is valid from the start of its
  trip's service day to the end of the day it departs on (Warsaw), so a run after midnight stays valid on the next day,
  whose `extended` timetable shows it; a trip from its service day's start to the end of its last stop's day (#195).
  Track listings return tracks valid now.
- ZTM labels every published day with an agency's current topology version, so after a bump the old version is
  missing from the whole window while keeping the `valid_to` an earlier import gave it (up to today + 6). The track
  importer therefore sets `valid_to` = now on the tracks (and their stops) that share `R{routeId}T{tripId}` with a
  track listed by this import, have another version and weren't listed. Tracks whose route+trip this import doesn't
  list keep their validity, so a partial feed expires nothing it doesn't cover.
- `track.kind` (`TrackKind`, ADR 0031) comes from the trip's `type` in the trips feed: `MAIN` → `main`,
  `NON_PASSENGER` (depot runs) → `non-passenger`, anything else → `side`. `SIDE` is ZTM's shortened runs; other
  agencies send `UNKNOWN`, so about half the routes (90 of 183 on 2026-10-28) have no main track. Providers that
  can't tell leave the default `side`. Non-passenger tracks and their trips are imported like any other; the API
  hides them (see the filtering page).
- `trip.service_date` (not null, indexed) is the service day of the trip: **importers must set it**
  (`TripEntity::setServiceDate()`, or the `service_date` column in bulk inserts). The ZTM importer uses the date of the
  `stopTimes` request that listed the trip, so a run after midnight stays on its day. The rollout migration deleted
  trips without a date suffix in their id (and their stops) and backfilled the rest from the id's last 10 characters.
  The timetable's `service` day mode filters on it ([filtering.md](filtering.md)). Deploying it: stop the `worker`
  and `schedule` services before the API migrates, since an import running on the old image writes trips without a
  `service_date` between the column's `ADD` and `MODIFY` (MySQL DDL isn't transactional) and fails the migration.
- `ztm_gdansk_schedule_day` (line, date, imported at; entity `Entity\ZtmGdansk\ZtmGdanskScheduleDayEntity`) records
  the imported line+days, also those without trips. It is the ZTM importer's own table: no other code names it, the
  importer deletes the days before today (Warsaw) when its node runs, and it has no `import_id`, so the collector
  removing an old import can't take a day of the window with it (it used to cascade). A row goes with its line
  (`ON DELETE CASCADE`). A line+day is downloaded only if it has no row, so a daily run fetches the day that entered the window. The first
  run after deploy downloads everything: about 1,300 line+days, measured 20 minutes on 3 workers with the earlier version
  that ran a task per line+day (not measured since the job per line).
- Workers scale with `docker compose up -d --scale worker=N` (production: replicas of the `worker` service); the dev
  stack runs 3 (`scale: 3` in `docker-compose.yml`).

Known behaviour:

- Upstream answers 404 for `stopTimes` of a route on a day it doesn't run (weekends, holidays; 138 such line+days
  on one run). `plan()` therefore plans only the dates the dated `routes` feed lists the line on; a 404 of a planned
  line+day is a real failure: it is a warning of its line job, writes no `ztm_gdansk_schedule_day` row and the next run retries
  it. A failed day doesn't fail the import, unless no day of any line was imported.
- **Open problem:** a published day is assumed not to change. Timetable changes inside the window are not
  detected, as the topology version is per agency and changes every few days and the feeds are streamed. Where a
  signal could be added: `ZtmGdanskScheduleDataImporter::plan()`.

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
- The schedule importer is a `SplittingDataImporter` with a job per line. Its node plans (`plan()` lists the
  line+days without a `ztm_gdansk_schedule_day` row whose line the dated `routes` feed lists on that date, the only place
  deciding what is downloaded) and hands out a job per line with that line's missing dates. A job
  (`importLine()`) requests all its days at once with Symfony HttpClient and reads the responses in order, so the
  days download concurrently (a line has at most 10 days; curl's default is 6 connections per host, so of 7 days one
  queues). Each response is decoded with `json_decode` and written in its own transaction (`importLineDay()`),
  with its `ztm_gdansk_schedule_day` row, also for a day without trips, so it isn't fetched again. A day that can't be
  downloaded (404, timeout) or whose response isn't a `stopTimes` feed (not JSON, no `stopTimes`) is a warning and
  writes nothing, so the next run retries it. A job that imported none of its days, nor found any imported before
  (`isImported()`), fails, so an outage upstream shows; the days' warnings stay. Trip ids end with the service
  date, a trip of an unknown track version is skipped with a warning; when every trip of a day with trips is
  skipped so, the day throws `UnknownTrackVersionException`: nothing is written, a warning, the next run retries it.
  A unique violation counts the day as imported only when it is imported now (another import wrote it after the
  check); otherwise the job fails. A transaction failing on a deadlock or lock wait timeout (`RetryableException`,
  workers inserting trips at once) is retried as a whole without downloading again, by `Utility\Retry::onLockFailure()`
  (up to 3 attempts with a 50-250 ms random pause, the same helper as `start()`'s). Progress is counted in lines
  (`jobEnded()`: "lines 74/183, 3 failed"); `jobsEnded()` fails the node only when every line failed
  (`total === 0 || done > 0` succeeds), as failed lines are retried by the next run; its summary is a warning when
  any line failed.
- Upstream feeds are opened by `Parser\FileStringStream` with a 60 s timeout for answering and for each read.

## Stale data (ADR 0038)

`DataImport\StaleDataCollector` (`app:collect-stale-data`, scheduled by `COJEDZIE_SCHEDULE_COLLECT_STALE_DATA`) removes
operators, lines, stops, tracks, track stops, trips and trip stops whose `import_id` points to an import started
more than `COJEDZIE_STALE_DATA_RETENTION` ago (default `7 days`, any `DateInterval::createFromDateString()` string), i.e. that
no import has written since. Tracks, track stops, trips and trip stops go once their validity ended more than
`COJEDZIE_STALE_DATA_GRACE` ago (default `1 day`), slack for a late run or a validity a bit short (rows imported
before whole-day validity, which ends with the run, stay a day after their run thanks to it); the retention applies to them only with `valid_to IS NULL` (rows from before validity), so a window longer than the
retention keeps its future days. Then it removes the `import` rows nothing refers to anymore. Provider rows stay.
Tables of one provider (`ztm_gdansk_schedule_day`) are left to its importer.

- Children are collected first, and a stale row still referenced by a row that stays is kept (a stop on a current
  track, an operator of a current line), so foreign keys never block and fresh data never loses a relation.
- Brake: unless an import **succeeded** (`status = 'succeeded'`) within the retention period, nothing is removed and
  the handler logs a warning, so a stalled, failing or superseded import can't wipe the data. Nothing is removed
  either while an import runs (`running` and started less than `COJEDZIE_IMPORT_TIMEOUT` ago, default `1 hour`).
- One transaction; plain `DELETE … WHERE import_id IN (…) AND NOT EXISTS (…)`, checked on SQLite, MariaDB and
  Postgres.
- Track stops stay while a trip of their track exists, as the track does, so a kept track never loses its stops.
- Rows from before validity (`valid_to IS NULL`: undated trip ids `{route}-{service}-{trip}-{order}`, track ids
  `R{route}T{trip}` without a version) are deleted by the one-time migration `Version20261107173924` (per engine,
  children first, `track.final_id` cleared before the track stops go; a few seconds for a day of data on MySQL).
  The importers have no code for them. On the deploy day a line has no timetable departures until the import
  reaches it; live departures are unaffected. An old worker importing once more after the migration writes undated
  rows again; the `import_id` retention above removes them.

Tests: `tests/DataImport/ImporterTestCase` runs an importer against the test database inside a transaction rolled
back after each test, with `StubJsonStreamer` serving inline feed rows; `runImporter()` also runs the jobs an
importer handed out, collecting failures in `$failures`. `tests/DataImport/ImportGraphTest` covers the graph, its
statuses, events and stored progress, `PersistingProgressReporterTest` the throttling and
`tests/Subscriber/ImportCronMonitorSubscriberTest` the check-ins. Per ADR 0001, importers are tested there, not
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
