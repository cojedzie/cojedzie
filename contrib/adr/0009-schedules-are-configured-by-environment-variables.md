# 0009. Schedules are configured by environment variables

- **Status:** Accepted
- **Date:** 2026-10-29
- **Applies to:** `api/src/Schedule/**`, `api/config/services.yaml`, consumers of schedule times (e.g.
  `api/src/MessageHandler/UpdateDataSourcesMessageHandler.php`), `build/**`, `docker-compose*.yml`

## Context

Scheduled jobs (the daily data import, health checks of federated nodes, cleanup of closed connections) run on
Symfony Scheduler, consumed by Messenger workers. Their times were fixed in code: a constant for the import, a
literal interval and `@daily` for the hub. Changing when the import runs meant a release, although the right time
depends on the deployment (when upstream publishes, the load on a node, a federated node's own provider). A `cron`
image and a crond inside the standalone image ran `schedule:run`, a command that no longer exists, so they did
nothing.

## Considered options

1. **Times in code** (as before). Simple, but every deployment shares them and a change needs a release.
2. **One environment variable per schedule, a cron expression**, with defaults in `config/services.yaml`. Each job
   can be moved or slowed down on its own, with the same mechanism the API uses for upstream addresses (ADR 0004).
3. **One variable for all schedules** (e.g. a JSON map). Fewer variables, but harder to set and to validate.
4. **System cron calling console commands.** Another moving part beside the workers; the scheduler already does
   this.

## Decision

We will configure every schedule with its own environment variable holding a cron expression (option 2):

- Schedule times are properties of `App\Schedule\ScheduleConfig`, each read with `#[Autowire(env: …)]` from a
  variable named `COJEDZIE_SCHEDULE_<JOB>` (prefixed by ADR 0011), with an `env(COJEDZIE_SCHEDULE_<JOB>)` default in `config/services.yaml`.
- Schedule providers and anything that reports a schedule (the Sentry cron monitor) take `ScheduleConfig`.
- Schedules run only through Messenger workers consuming the `scheduler_*` transports; there is no system cron.

## Consequences

- A deployment moves a job by setting one variable; an invalid expression fails when the worker builds the
  schedule.
- Whether a schedule runs at all is decided by which transports the workers consume (`COJEDZIE_WORKER_QUEUES`),
  not by the variables.
- Defaults are spelled-out cron expressions: in `services.yaml`, a leading `@` (`@daily`) is a service reference.
- The `cron` image is gone; the standalone image consumes `main scheduler_default` instead of running crond.

## Compliance

- No schedule time (cron expression, interval) is written in `api/src/Schedule/**` or passed as a literal to
  `RecurringMessage`; it comes from `ScheduleConfig`.
- Every `ScheduleConfig` property has its own `COJEDZIE_SCHEDULE_*` variable and an `env(...)` default in
  `config/services.yaml`.
- A new schedule adds its variable to the table in `contrib/agents/wiki/api/data-import.md`.
- No image or compose file runs crond or calls `schedule:run`.
