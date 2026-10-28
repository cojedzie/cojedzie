# API architecture (`api/`)

Symfony 7.4 / PHP 8.5, namespace `App\` → `api/src/`. Global helper functions are autoloaded from
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

OpenAPI via NelmioApiDocBundle 5 / swagger-php 5 using `#[OA\...]` attributes (`OpenApi\Attributes as OA`,
`Nelmio\ApiDocBundle\Attribute\Model`) on controllers and DTOs, with custom describers in `src/Describer`.
Docblock annotations are no longer read. Served at `/api/` (UI) and `/api/swagger.json`.

- Nested annotations must be passed to constructors by name (`items:`, `schema:`, `content: [...]`): positional
  nested arguments are a PHP error, and `OA\Response` has no `schema` (use `content: new OA\JsonContent(...)`).
- swagger-php only links an annotation to its parent when it is passed to the parent's constructor. A `Schema`
  attached later (e.g. via `mergeProperties()`) ends up as an unnamed entry in `components.schemas` and fails
  validation, which makes `/api/swagger.json` return 500 in debug.

## Runtime and dependency notes

- Symfony 7.4 / PHP 8.5. Defaults that Symfony 7 changed (`http_method_override`, `handle_all_throwables`,
  UUID version, `property_info.with_constructor_extractor`, Doctrine `controller_resolver.auto_mapping`) are
  pinned to their 6.4 values in `config/packages/`; flip them deliberately. `doctrine.orm.enable_lazy_ghost_objects`
  is still off (belongs with ORM 3).
- `ext-ds` 2.0 is installed with PIE in every PHP image (`build/base/Dockerfile`, `build/base/fpm.Dockerfile`,
  `build/base/cli.Dockerfile`, `api/Dockerfile`, `api/rr.Dockerfile`). amqp and redis are in all of them except
  `api/rr.Dockerfile`, and the dev images (`api/Dockerfile`, `api/rr.Dockerfile`) add xdebug. `php-ds/php-ds` 2.0 is
  required as the polyfill fallback. 2.0 has no `Ds\Collection`, `Ds\Deque` or `Ds\Vector`; the code uses `Ds\Seq`, `Ds\Map` and `Ds\Set`.
- RoadRunner 2025.1 (`ghcr.io/roadrunner-server/roadrunner`) with baldinof/roadrunner-bundle 3; `.rr.yaml` is
  config v3. Workers run with `display_errors=stderr`: anything PHP prints to stdout breaks the worker protocol
  (e.g. a deprecation raised while autoloading, before Symfony's error handler is set up). The runtime images
  set `display_errors` to `Off` (fpm) / `stderr` (cli) for the same reason.
- sentry-symfony 5.14+ needs roadrunner-bundle 3.5+: older bundles decorate Sentry's tracing listener without
  `collectKernelResponseData`, which makes every request 500.
- Sentry (prod only, `config/packages/sentry.yaml`): `App\Service\SentrySampler` samples traces at
  `SENTRY_SAMPLE_RATE`, scaled by a route's `sentry_sample_rate` option or a message's/command's
  `CustomSentrySampleRateInterface`. Logs from `info` up go to Sentry Logs (`sentry_logs` handler in
  `prod/monolog.yaml`, deprecations excluded). `UpdateDataSourcesMessageHandler` sends check-ins to the
  `update-data-sources` cron monitor, its schedule is `DefaultScheduleProvider::UPDATE_SCHEDULE`.
- Mercure hub config pins `protocol_version: '0.x'` (mercure-bundle 0.6 defaults to 1.0). HMAC JWT secrets must be
  at least 256 bits; the dev `!ChangeMe!` secret makes `/api/v1/network/nodes` fail when it creates the cookie.
- The serializer decorator `App\Serialization\Serializer` must implement every interface of Symfony's
  serializer (incl. encoder/decoder): the profiler wraps it in `TraceableSerializer`.
- Kernel tests (`KernelTestCase`) work in the `test` env; `tests/` mirrors `src/`.

## Notable directories

- `src/Parser` + `tests/JsonStreaming*` — a custom streaming JSON tokenizer/parser (commit scope
  `streaming-parser`) used to import large provider JSON files without loading them into memory.
- `src/Service/Proxy` — `friendsofphp/proxy-manager-lts`-generated lazy entity references (`ReferenceFactory`).
