# 0026. The import is a persisted graph of importers that may split into jobs

- **Status:** Accepted
- **Date:** 2026-11-07
- **Applies to:** `api/src/DataImport/**`, `api/src/Message/**`, `api/src/MessageHandler/**`,
  `api/src/Provider/*/DataImporter/**`, `api/bin/docker-init.sh`

## Context

`app:update` ran every importer one after another in a single process. With a week of schedules the work is
thousands of independent downloads (about 1,300 line+days for ZTM Gdańsk, measured 20 minutes on 3 workers for the
first full window, with the earlier version that ran a task per line+day), and a long job in one process dies with a deploy or the worker's memory limit and starts over.
Workers are separate processes, so any structure describing the work has to be shared.

A first version put every line+day into the graph as a task spawned at runtime. It parallelised the work, but the
schedule's progress was lost (nothing added the tasks up), the end of the import was inferred from a pile of
unrelated tasks, and the importer had no say once it had spawned them.

## Considered options

1. **One sequential job.** Simple, but 13 minutes per day of schedule and no parallelism.
2. **A graph held in memory** by the process that starts the import. Workers are other processes and can't see it.
3. **A persisted graph with a task per line+day**, spawned by the importer while it runs. Parallel, but see above:
   no progress, no control of the importer over its own work.
4. **A persisted graph of importers, an importer splitting its work into jobs it controls** (chosen). Waiting for the
   jobs is one of:
   - **A worker blocking** until its jobs end: holds a worker slot per waiting importer, and with fewer workers than
     waiting importers nothing runs the jobs.
   - **Fibers**: keep the importer suspended in one worker process, so a worker restart or deploy loses it.
   - **Event-driven** (chosen): the importer's node waits in the database holding no worker, and whichever worker
     ends the last job wakes it.

## Decision

We will run the import as a persisted graph of importers whose nodes may split their work into jobs (option 4):

- `UpdateDataSources` only starts the graph (`ImportGraph::start()`): an `import` and an `import_task` node per
  `DataImporter`, dependencies from `getDependencies()`. Nothing is added to the graph at runtime. `RunImportTask`
  messages run the nodes; a node that ends dispatches the dependents whose dependencies are done.
- An importer that implements `SplittingDataImporter` hands out jobs (`DataUpdateEvent::addJob(payload)`) while its
  node runs. They are persisted as `import_job` rows in one transaction with the node becoming `waiting`, then
  dispatched as `RunImportJob` messages on `main`, so any worker runs them (`runJob()`). The node holds no worker
  while it waits. After each job the importer is told the counts of its jobs (`jobEnded()`, for its own progress);
  after the last one it decides whether the node succeeded (`jobsEnded()`). An importer that adds no jobs is a
  plain node. This is generic: any provider's importer can split its work.
- Every transition two workers could race for is a conditional `UPDATE … WHERE status = '<expected>'` checked by its
  affected rows, so a message delivered twice does nothing. A node ends once: the job that ends the last one claims
  it (`waiting → running … AND NOT EXISTS` an unended job), so jobs ending at once end the node once.
- An import is superseded by the next run. `run()` sets a node `waiting` only while its import is unfinished, and
  `runJob()` claims a job only while its import is unfinished; this guard (not `supersede()`) is what makes a job
  queued after a new run started harmless: it is skipped, with its node once no job is left. `supersede()` skips
  pending and queued nodes, queued jobs and waiting nodes with no running job, and ends the import as
  `superseded`; `jobsEnded()` may therefore run for an import superseded meanwhile.
- The importer's own failures are caught and recorded on the node or job, not retried within the run, and there is
  **no reaper**. Messenger's retries of a message whose handler threw (a transient database or transport error in
  the bookkeeping) are harmless and resume it: a redelivered `RunImportTask` of a waiting node dispatches its queued
  jobs again, one of an ended node dispatches its ready dependents and finishes the import if complete; a
  redelivered `RunImportJob` of an ended job tries to end its node. A failed
  node skips its dependents; a failed job is for the importer to judge in `jobsEnded()` (the ZTM schedule fails
  only when no line was imported; otherwise failed lines are a warning, as a failed day writes no `ztm_gdansk_schedule_day` row
  and the next run plans it again). A worker
  killed mid-job leaves that job `running` and its node `waiting`; the next run supersedes the import, and the
  collector's `COJEDZIE_IMPORT_TIMEOUT` stops an unfinished import from blocking it forever. Only a transaction hit
  by a deadlock or lock wait is retried, inside the job, and so is `start()`'s (superseding can deadlock with a job
  claim on MySQL).
- The ZTM schedule importer splits by line: its node plans the missing line+days and hands out a job per line with
  its missing dates. A line job requests all its days at once with Symfony HttpClient (the days download
  concurrently) and writes each day in its own transaction, with its `ztm_gdansk_schedule_day` row. A day that fails
  (a failed download, a response that isn't a `stopTimes` feed, no known track) is a warning and is planned again by
  the next run; the job fails when none of its days was imported, by it or before (an outage upstream), or when a
  day's write fails. A day hitting a unique
  violation counts as done only when `isImported()` is true afterwards (a superseded and a new run can plan the same
  day); otherwise the exception fails the job.
- An import has an explicit status (`ImportStatus`): `running` until it ends, then `succeeded` (every node done),
  `failed` or `superseded`, with `finished_at`. It finishes with its last node (none `pending|queued|running|waiting`).
  Nodes and jobs store their importer's progress (`progress`, `progress_max`, `progress_comment`, at most once a
  second), so the state of an import can be shown without reading logs. `app:update` without `--async` runs the
  same graph, nodes and jobs, in-process.
- The graph knows nothing of monitoring: it dispatches `ImportStartedEvent` once an import is written and
  `ImportFinishedEvent` once when it ends (by its last node or superseded). The Sentry cron monitor is a listener
  (`ImportCronMonitorSubscriber`): it opens an in-progress check-in on start and keeps its id by import id in a
  Redis cache pool, since the worker ending an import is rarely the one that started it, and closes it on finish
  (`ok` when succeeded, `error` otherwise). Rejected: passing the check-in id to `start()` and storing it on the
  import, which tied the graph and its table to Sentry.
- `bin/docker-init.sh` dispatches the import (`app:update --async`) instead of running it, so a first window
  import doesn't block container start. Parallelism comes from `docker compose up -d --scale worker=N`.

## Consequences

- The import is as fast as the workers available, and survives a worker restart: other workers continue. A waiting
  importer costs no worker.
- The graph is state in the database; a lost message or killed worker is visible as a job that stays `running` or
  `queued` and a node that stays `waiting`, and is cleaned up by the next run rather than by a timer.
- The importer reports its own progress (lines done of all) because it sees its jobs' counts.
- A transient upstream error, a 404 included, fails a day until the next daily run. Only days the routes feed lists
  the line on are planned (ADR 0025), so a 404 is a real failure, not a day the line doesn't run.
- Importers must be written so their steps and jobs can run on any worker, in any order the dependencies allow.
- Concurrent downloads are bounded by the HTTP client: curl keeps 6 connections per host, so of 7 days one queues.

## Compliance

- Import steps are `DataImporter`s run as `ImportTaskRunner` nodes, work split by an importer is run as its jobs;
  nothing runs the whole import inline except `ImportGraph::runSynchronously()`.
- Nothing is added to the graph at runtime other than jobs of a running node, and the importer decides how its node
  ends.
- Updates of node, job and import status that workers can race for are conditional on the expected status and check
  the affected row count. They are DQL statements on the entities; raw SQL is left to the importers' batched
  upserts.
- The graph reports an import's start and end only through its events; monitoring and other side effects are
  listeners.
- The importer's failures are recorded on the node or job (`ImportTaskRunner` catches `\Throwable`) and not left
  to Messenger's retries; retrying them belongs to the next run. A redelivered message resumes the bookkeeping its
  first delivery may not have finished, and otherwise does nothing.
- An importer never blocks a worker waiting for its jobs.
- Startup scripts dispatch the import asynchronously rather than waiting for it.
