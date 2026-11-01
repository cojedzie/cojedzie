# 0021. Schedules are cron expressions or intervals from environment variables

- **Status:** Accepted
- **Supersedes:** [0009](archive/0009-schedules-are-configured-by-environment-variables.md)
- **Date:** 2026-11-01
- **Applies to:** `api/src/Schedule/**`, `api/config/services.yaml`, consumers of schedule times (e.g.
  `api/src/MessageHandler/UpdateDataSourcesMessageHandler.php`, `api/src/MessageHandler/RecordDeparturesMessageHandler.php`),
  `api/Dockerfile`, `worker/**`, `standalone/**`, `docker-bake.hcl`, `docker-compose*.yml`

## Context

ADR 0009 made every schedule a cron expression in its own environment variable, so each deployment can move or
slow down a job without a release. The departure history recorder polls the provider every 20 seconds, matching
how often ZTM Gdańsk updates its data, and cron can't express anything shorter than a minute. What matters is
still that every schedule is controllable per environment, not that it is cron.

## Considered options

1. **Keep cron only** and record once a minute. Loses two of every three updates.
2. **A literal interval in code** for the recorder. Breaks the rule that a deployment controls its schedules.
3. **Two variable families**: cron expressions for jobs at fixed times, intervals for frequent jobs, each still one
   variable per job with a default in `config/services.yaml`.

## Decision

We will configure every schedule with its own environment variable (option 3):

- A job at fixed times reads a cron expression from `COJEDZIE_SCHEDULE_<JOB>` and runs through
  `RecurringMessage::cron()`.
- A job more frequent than a minute reads an interval from `COJEDZIE_INTERVAL_<JOB>`, a
  `DateInterval::createFromDateString()` string such as `20 seconds`, and runs through `RecurringMessage::every()`.
- Both are properties of `App\Schedule\ScheduleConfig`, read with `#[Autowire(env: …)]`, with an `env(…)` default in
  `config/services.yaml` (prefixed per ADR 0011).
- Schedule providers and anything that reports a schedule (Sentry cron monitors) take `ScheduleConfig`.
- Schedules run only through Messenger workers consuming the `scheduler_*` transports; there is no system cron.

## Consequences

- A deployment moves or slows down a job by setting one variable; an invalid expression or interval fails when the
  worker builds the schedule.
- Whether a schedule runs at all is decided by which transports the workers consume (`COJEDZIE_WORKER_QUEUES`),
  not by the variables. A frequent job gets its own schedule and transport (the recorder: `scheduler_recorder`), so
  it can be enabled per deployment and isn't held up by long jobs.
- Defaults are spelled-out cron expressions: in `services.yaml`, a leading `@` (`@daily`) is a service reference.
- The `cron` image is gone; the standalone image consumes `main scheduler_default` instead of running crond.

## Compliance

- No schedule time (cron expression, interval) is written in `api/src/Schedule/**` or passed as a literal to
  `RecurringMessage`; it comes from `ScheduleConfig`.
- Every `ScheduleConfig` property has its own `COJEDZIE_SCHEDULE_*` (cron) or `COJEDZIE_INTERVAL_*` (interval)
  variable and an `env(...)` default in `config/services.yaml`.
- A new schedule adds its variable to the table in `contrib/agents/wiki/api/data-import.md`.
- No image or compose file runs crond or calls `schedule:run`.
