# API architecture (`api/`)

Symfony 8.1 / PHP 8.5, namespace `App\` → `api/src/`. Global helper functions are autoloaded from
`src/Functions/index.php`. Functional helpers come from `kadet/functional` (`p\`, `t\` namespaces). Collections
are php-ds values (`Ds\Seq`, `Ds\Map`, `Ds\Set`) transformed with `kadet/functional`'s `c\` functions and `|>`;
see [collections.md](collections.md).

## Request flow

1. **Routes** — `config/routes.yaml` loads `src/Controller/Api/v1` with prefix `/api/v1` and route-name prefix `v1_`.
   Most routes are `/{provider}/...`.
2. **Provider resolution** — `Service/RepositoryValueResolver` (a controller argument value resolver) reads the
   `{provider}` route attribute, resolves it via `Service/ProviderResolver` (all services tagged `app.provider`,
   keyed by `getIdentifier()`), and injects the matching repository (`StopRepository`, `LineRepository`, ...)
   into the controller action. Unknown providers give a 404. The `provider` attribute stays a string, because
   `Context/ProviderContext` resolves it again.
   `DummyProvider` is only available when `kernel.debug` is on.
3. **Requirements** — query/route parameters are turned into `Filter\Requirement\*` objects
   (`IdConstraint`, `FieldFilter`, `RelatedFilter`, `LimitConstraint`, `Embed`). Bindings are declared with
   attributes from `Filter/Binding/Http` either per-argument (e.g. `#[IdConstraintParameterBinding(...)]`) or
   per-action via `#[ParameterBindingProvider([self::class, 'getParameterBinding'])]`, which fills an
   `array $requirements` argument. `Service/RequirementsArgumentResolver` decorates Symfony's argument resolver
   to do this.
4. **Repositories** — `FluentRepository::all(Requirement ...)` / `first(...)`. Each requirement is dispatched to
   a **handler** through `HandlerProvider` (handlers in `Filter/Handler`, tagged `app.handler`):
   - `ModifierHandler` — mutates the query before execution (`HandleDatabaseRequirementEvent` /
     `HandleInMemoryRequirementEvent`).
   - `PostProcessingHandler` — transforms results afterwards (`PostProcessEvent`).
   Details: [filtering.md](filtering.md).
5. **Response** — entities are converted to DTOs (`App\Dto\*`) by `DataConverter\AggregateConverter`
   (all `app.converter` services), then serialized by the decorated `Serialization\Serializer` /
   `Normalizer\DtoNormalizer`. `Service/ApiResponseFactory` builds responses
   (`createResponse`, `createCollectionResponse`). Details: [dto-and-entities.md](dto-and-entities.md).

## Providers

`Provider\Provider` is the contract a data source implements (repositories + metadata). Providers are
autoconfigured and tagged by `_instanceof` in `config/services.yaml`.

- `ZtmGdansk` (identifier `trojmiasto`) — the real provider. Static data (lines, stops, tracks, schedule,
  operators) lives in the DB via the `Database\Generic*Repository` classes bound with `withProvider()`;
  live departures (`ZtmGdanskRealtimeDepartureRepository`, TRISTAR `departures` API) and messages are fetched
  from ZTM Gdańsk open data. `REALTIME` entries carry estimates; `SCHEDULED` ones are the API's own timetable and fill gaps in the
  imported timetable. The legacy `delays` endpoint is no longer used.
- `Dummy` — fake data for development. `test` — fake provider for Behat (see [testing.md](testing.md)).

**Departures** of DB-backed providers come from `Database\RealtimeScheduleDepartureRepository`, built by the
provider from its schedule repository, a live source and the clock. The live source is any `DepartureRepository`
returning departures with estimates for the given stops; there is no separate realtime interface. The class joins
the two: a live departure replaces the timetable entry with the same line and scheduled minute (keeping the
timetable's destination, track and trip), live departures without a timetable entry are kept, departures that
left are dropped, then ordering and `limit`/`offset`. Override `pair()`/`merge()` in a subclass to change matching.
Entries are matched per stop (`stop::line::HH:MM`), the timetable is read from now or from the earliest late live
departure, and `offset` is applied once to the joined list. A live source that fails returns no estimates, so
departures fall back to the timetable. Behaviour is pinned by `tests/ZtmGdansk/ZtmGdanskDepartureRepositoryTest`
and the departures scenarios.

**Upstream addresses** are configuration ([ADR 0004](../../../adr/0004-upstream-data-addresses-are-configuration.md)), grouped in `ZtmGdansk\ZtmGdanskConfig` (injected where needed, one
environment variable each, defaults in `config/services.yaml`): `ZTM_GDANSK_API_URL` is the TRISTAR API base
(classes add `/departures`, `/displayMessages`, `/stopTimes`); every dataset file has its own full URL, because its
resource id changes over time (`ZTM_GDANSK_STOPS_URL`, `_ROUTES_URL`, `_TRIPS_URL`, `_STOPS_IN_TRIPS_URL`,
`_AGENCIES_URL`, `_DISPLAYS_URL`).

Adding a provider: implement `Provider`, reuse `Generic*Repository` with `withProvider($entity)` for DB-backed
data, and add `DataImporter`s (see [data-import.md](data-import.md)).

## API docs

OpenAPI via NelmioApiDocBundle 5.13 / swagger-php 6 using `#[OA\...]` attributes (`OpenApi\Attributes as OA`,
`Nelmio\ApiDocBundle\Attribute\Model`) on controllers and DTOs, with custom describers in `src/Describer`.
Docblock annotations are no longer read. Served at `/api/` (UI) and `/api/swagger.json`.

- Nested annotations must be passed to constructors by name (`items:`, `schema:`, `content: [...]`): positional
  nested arguments are a PHP error, and `OA\Response` has no `schema` (use `content: new OA\JsonContent(...)`).
- swagger-php only links an annotation to its parent when it is passed to the parent's constructor. A `Schema`
  attached later (e.g. via `mergeProperties()`) ends up as an unnamed entry in `components.schemas` and fails
  validation, which makes `/api/swagger.json` return 500 in debug.
- Nelmio uses `symfony/type-info` (Symfony 8 has no `PropertyInfo\Type`): custom describers read
  `Model::getTypeInfo()`, never the legacy `Model::getType()`, which fails on Symfony 8.

## Runtime and dependency notes

- Symfony 8.1 / PHP 8.5, Doctrine ORM 3 / DBAL 4 / DoctrineBundle 3, PHPUnit 13. Defaults that Symfony 7
  changed (`http_method_override`, `handle_all_throwables`, UUID version,
  `property_info.with_constructor_extractor`) are pinned to their 6.4 values in `config/packages/`; flip them
  deliberately.
- DoctrineBundle 3 forbids `controller_resolver.auto_mapping`: an entity controller argument whose route
  parameter isn't `{id}` needs `#[MapEntity(id: '<param>')]` (see `FederationController::disconnect`).
- ORM 3 uses PHP native lazy objects, so there are no proxy subclasses: test for an unloaded entity with
  `new \ReflectionClass($entity)->isUninitializedLazyObject($entity)`. Reading the id doesn't load it.
- Symfony attributes live in `...\Attribute\` namespaces (`Serializer\Attribute\Groups`, ...). The old
  `...\Annotation\` classes are gone, and PHP silently ignores an attribute whose class doesn't exist, so a stale
  import drops serialization groups without any error.
- DBAL 4 has no default `VARCHAR` length on MySQL/MariaDB: string columns declare `length: 255` explicitly
  (which also keeps the existing schema). Migrations check the platform with `instanceof` (e.g.
  `SQLitePlatform`), since `AbstractPlatform::getName()` is gone. DBAL 4 no longer writes `(DC2Type:...)`
  column comments, so a schema diff against a database migrated earlier lists those columns.
- `ext-ds` 2.0 is installed with PIE by `api/docker/install-php.sh`, which the `php-cli` and `php-fpm` stages of
  `api/Dockerfile` run, so every PHP image has it, together with amqp and redis. Only the `dev` stage adds xdebug.
  `php-ds/php-ds` 2.0 is
  required as the polyfill fallback. 2.0 has no `Ds\Collection`, `Ds\Deque` or `Ds\Vector`; the code uses
  `Ds\Seq`, `Ds\Map` and `Ds\Set`.
- RoadRunner 2025.1 (`ghcr.io/roadrunner-server/roadrunner`) with baldinof/roadrunner-bundle 3; `.rr.yaml` is
  config v3. Workers run with `display_errors=stderr`: anything PHP prints to stdout breaks the worker protocol
  (e.g. a deprecation raised while autoloading, before Symfony's error handler is set up). The runtime images
  set `display_errors` to `Off` (fpm) / `stderr` (cli) for the same reason.
- sentry-symfony 5.14+ needs roadrunner-bundle 3.5+: older bundles decorate Sentry's tracing listener without
  `collectKernelResponseData`, which makes every request 500.
- Sentry (prod only, `config/packages/sentry.yaml`): `App\Service\SentrySampler` samples traces at
  `SENTRY_SAMPLE_RATE`, scaled by a route's `sentry_sample_rate` option or a message's/command's
  `CustomSentrySampleRateInterface`. Sentry Logs get `info` and up from our channels (`app`, `import`) and
  `warning` and up from the rest (`sentry_logs_*` handlers in `prod/monolog.yaml`, `php` and `deprecation`
  excluded): framework channels log every request ("Matched route") and message at `info`, which tracing
  already covers. Add a channel to `sentry_logs_app` to get its `info` logs. `UpdateDataSourcesMessageHandler`
  sends check-ins to the `update-data-sources` cron monitor, its schedule is `COJEDZIE_SCHEDULE_UPDATE_DATA_SOURCES`
  (`ScheduleConfig::$updateDataSources`).
- Mercure hub config pins `protocol_version: '0.x'` (mercure-bundle 0.6 defaults to 1.0). HMAC JWT secrets must be
  at least 256 bits; the dev `!ChangeMe!` secret makes `/api/v1/network/nodes` fail when it creates the cookie.
- The serializer decorator `App\Serialization\Serializer` must implement every interface of Symfony's
  serializer (incl. encoder/decoder): the profiler wraps it in `TraceableSerializer`.
- Kernel tests (`KernelTestCase`) work in the `test` env; `tests/` mirrors `src/`.

## Notable directories

- `src/Parser` + `tests/JsonStreaming*` — a custom streaming JSON tokenizer/parser (commit scope
  `streaming-parser`) used to import large provider JSON files without loading them into memory.

## Docker

- `api/Dockerfile` is the only PHP Dockerfile. Stages: `php-cli` and `php-fpm` (base images with the extensions
  from `api/docker/install-php.sh`), `vendor` (composer dependencies only, cached apart from the code), `app` (code
  plus vendor), and the runtime images `rr` (RoadRunner), `cli`, `fpm` and `dev` (php-fpm plus xdebug, composer and
  the `rr` binary; the default target).
- `docker-bake.hcl` maps stages to images: `api` = `rr` (tags `api` and `api-rr`), `api-fpm` = `fpm`, and `api-cli`
  = `cli` (not published, only the base of the worker). `worker/Dockerfile` and `standalone/Dockerfile` start
  `FROM api`, a named build context that bake points at `target:api-cli` for the worker and `target:api` for
  standalone. `front/Dockerfile` has `dev` and `prod`.
- Groups: `ci` (api, worker, front) and `release` (adds api-fpm and standalone). Variables: `REGISTRIES`, `TAGS`
  (the first is the main tag), `COJEDZIE_VERSION`, `COJEDZIE_REVISION`, `GHA_CACHE` and `SENTRY_AUTH_TOKEN`. Always
  call `docker buildx bake -f docker-bake.hcl ...`: otherwise bake also reads `docker-compose.yml`, whose service
  names collide with the targets. `contrib/bake-digests.sh` writes `digests.txt` for the deploy workflow and
  `contrib/release-notes.sh` the release notes.
- The dev stack (`docker-compose.yml`, commands in `AGENTS.md`) builds the `dev` targets and bind-mounts `api/` and
  `front/`. `api-setup` and `front-setup` install dependencies before the other services start. The `dev` image's
  `dev-server` picks php-fpm or RoadRunner from `COJEDZIE_DEV_SERVER`.
