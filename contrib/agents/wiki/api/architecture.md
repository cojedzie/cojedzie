# API architecture (`api/`)

Symfony 7.4 / PHP 8.3, namespace `App\` → `api/src/`. Global helper functions are autoloaded from
`src/Functions/index.php`. Functional helpers come from `kadet/functional` (`p\`, `t\` namespaces) and
collections from `illuminate/collections` (`collect()`).

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
  departures and messages are fetched live from ZTM Gdańsk open data.
- `Dummy` — fake data for development.

Adding a provider: implement `Provider`, reuse `Generic*Repository` with `withProvider($entity)` for DB-backed
data, and add `DataImporter`s (see [data-import.md](data-import.md)).

## API docs

OpenAPI via NelmioApiDocBundle using `@OA\...` docblock annotations on controllers and DTOs, with custom
describers in `src/Describer`.

## Notable directories

- `src/Parser` + `tests/JsonStreaming*` — a custom streaming JSON tokenizer/parser (commit scope
  `streaming-parser`) used to import large provider JSON files without loading them into memory.
- `src/Service/Proxy` — `friendsofphp/proxy-manager-lts`-generated lazy entity references (`ReferenceFactory`).
