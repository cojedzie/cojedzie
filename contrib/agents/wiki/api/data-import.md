# Data import & persistence

## `app:update`

`bin/console app:update` (`Command/UpdateCommand` → `Service/DataUpdater`) creates an `ImportEntity`, then runs
every `DataImport\DataImporter` (tagged `app.data_importer`) in topological dependency order, reporting progress
through `ProgressReporterFactory` (console + `import` monolog channel). Memory limit is raised to 4G and the SQL
logger is disabled during import.

Run it with `task api:update` (uses `APP_MODE=performance` by default).

Scheduling: `src/Schedule` (Symfony Scheduler) plus `Message/UpdateDataSources` handled by
`MessageHandler/UpdateDataSourcesMessageHandler`; the `worker` and `cron` containers run these in docker.

`bin/docker-init.sh` runs migrations **and** a synchronous `app:update` on container start whenever migrations
are out of date.

## Provider importers

ZTM Gdańsk importers live in `Provider/ZtmGdansk/DataImporter` (lines, operators, stops, tracks, schedule).
`Service/ProviderDataImporter` handles provider entity registration.

## Database

Doctrine ORM; entities in `src/Entity` (`*Entity` suffix). Three engines are supported, each with its own
migration set: `migrations/sqlite`, `migrations/mysql`, `migrations/postgres`. Default local DSN is SQLite
(`api/.env`).

When changing entities, generate migrations for **all three** engines with `task api:migrations:generate`
(requires the `postgres` and `mysql` compose services to be running, since it uses `docker compose exec`).
