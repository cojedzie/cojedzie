# 0020. Departure history is stored in ClickHouse

- **Status:** Accepted
- **Date:** 2026-11-01
- **Applies to:** `api/src/DepartureHistory/**`, `api/src/ClickHouse/**`, `api/migrations/clickhouse/**`,
  `api/config/services.yaml`, `docker-compose.yml`, `clickhouse/**`

## Context

We want to answer rider questions the timetable can't: when a departure really arrives, how likely a transfer is
to work out, how late a line runs on Friday evenings. That needs history, and history only builds up over time, so
recording has to start long before anything reads it. A provider like ZTM Gdańsk has about 285k stop departures a
day, about 100M rows a year when one row is kept per departure per stop. The questions are aggregations over
months of that data. Everything else (timetables, stops, federation) stays in MySQL/SQLite/Postgres through
Doctrine, and production runs on a single shared server with limited memory and disk.

## Considered options

1. **A table in the existing database** with a cleanup job like ADR 0010's. No new service, but 100M rows a year
   strain MySQL on the shared server, aggregations over months are slow, and all three engines need migrations.
2. **ClickHouse.** Columnar and compressed, built for aggregations over large append-heavy tables, with
   `ReplacingMergeTree` for "last observation wins" and TTL for retention. One more service to run.
3. **A time-series database** (InfluxDB, TimescaleDB). Fits metrics, but the data is rows keyed by stop, line and
   scheduled time, queried by time of day and day of week, which ClickHouse's SQL handles more naturally.

## Decision

We will store departure history in ClickHouse (option 2):

- One table, `departures`, one row per departure per stop, a `ReplacingMergeTree` versioned by `observed_at`, so
  the newest observation of a departure wins. Its columns are provider-neutral (`App\Provider\Live\LiveDeparture`,
  status as an enum); providers map their feeds onto them and their raw entries are not kept.
- Retention is a table TTL on `scheduled_at`, from `COJEDZIE_DEPARTURE_HISTORY_RETENTION_DAYS` (default 365),
  applied to the existing table when the recorder prepares the storage, and only when it differs.
- The app talks to ClickHouse with the `smi2/phpclickhouse` client over HTTP; the connection is
  `COJEDZIE_CLICKHOUSE_URL` (`http://user:password@host:8123/database`). Recording is optional: without the URL,
  nothing touches ClickHouse.
- The schema changes only through versioned migrations in `api/migrations/clickhouse`, applied by
  `App\ClickHouse\Migrator` and recorded in `migration_versions`. Doctrine Migrations was rejected: it needs a DBAL
  driver, and the only ClickHouse one (`friendsofdoctrine/dbal-clickhouse` 3.0) can't create tables on DBAL 4.5,
  its versions table included.
- ClickHouse code lives in `App\ClickHouse`, behind the `App\DepartureHistory\DepartureHistory` port; the recorder in
  `App\DepartureHistory` knows nothing about ClickHouse.

## Consequences

- Readers must deduplicate, because merges collapse rows eventually: `FINAL` or `argMax(…, observed_at)`.
- The live site, tests and deployments without ClickHouse (standalone, federated nodes) keep working as before.
- ClickHouse is a new service in the dev stack and in each production stack, sized as a guest on a shared server.
- There are no backups yet; when added, they go to S3-compatible storage (`BACKUP … TO S3`).

## Compliance

- No HTTP request path, container start or default test run requires ClickHouse; tests that do are in the phpunit
  group `clickhouse`.
- The schema is defined and changed only by migrations in `api/migrations/clickhouse`.
- Only `App\ClickHouse` uses the ClickHouse client.
- Retention comes only from `COJEDZIE_DEPARTURE_HISTORY_RETENTION_DAYS`, never a literal.
- Nothing writes departure history anywhere else (Doctrine entities, files).
