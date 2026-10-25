# DTOs and Entities

Two parallel models:

- **Entities** (`src/Entity`, `*Entity`) — Doctrine persistence model, filled by data importers. Global across
  providers.
- **DTOs** (`src/Dto`) — the public API model. Repositories return DTOs, controllers serialize DTOs, and
  requirements reference DTOs. Entities never leave the repository layer.

Providers that don't use the DB (live departures, messages) build DTOs directly and never touch entities.

## Identifiers

| | Entity id | DTO id |
|---|---|---|
| Format | `<provider>::<local id>`, e.g. `trojmiasto::1234` | `1234` (provider-local) |
| Source | `ReferableEntityTrait` (`#[ORM\Id]` string) | `Dto\ReferableTrait` |

`Service/IdUtils` converts between them: `generate($provider, $id)` adds the prefix, `strip($id)` / `of($entity)`
removes it. Every boundary crossing has to do this: DB handlers call `generate` on incoming ids and
converters call `of` on outgoing ones. The provider comes from the route, so API URLs never contain the
prefix.

Every entity implements `Entity` (which extends `Dto\Referable`) and gets `ProviderReferenceTrait` (ManyToOne
`ProviderEntity`). Imported entities also use `ImportedTrait` (`import_id` → `ImportEntity`), which is what the
`?import=` filter queries.

## Entity graph

```
OperatorEntity ◀── LineEntity ──1:N──▶ TrackEntity ──1:N──▶ TrackStopEntity ──▶ StopEntity
                                          │  └─ final (1:1 TrackStopEntity, the last stop)
                                          ◀── TripEntity ──1:N──▶ TripStopEntity ──▶ StopEntity
```

`StopEntity` is `readOnly: true`. `TrackStopEntity` and `TripStopEntity` are join entities that carry `order`,
and `TripStopEntity` also carries `arrival`/`departure`.

## Entity → DTO mapping

| Entity | DTO | Notes |
|---|---|---|
| `OperatorEntity` | `Operator` | |
| `LineEntity` | `Line` | `operator`, `tracks` |
| `TrackEntity` | `Track` | `stops` (flattened from `stopsInTrack[].stop`), `line`, `destination` (= `final.stop`) |
| `StopEntity` | `Stop` | lat/lon → `Location`; `destinations` only filled by an embed |
| `TripEntity` | `Trip` | `schedule` (from `stops`), `track` |
| `TrackStopEntity` | `TrackStop` | `stop`, `track`, `order` |
| `TripStopEntity` | `ScheduledStop` | `arrival`, `departure`, `stop`, `order`, `track` (via trip), `trip` |
| `Federation\FederatedConnectionEntity` | `Federation\Node` | `ConnectionConverter` (also deserializes stored `Status\Aggregated`) |
| *(service)* `App\Provider\Provider` | `Dto\Provider` | `ProviderConverter` — converts the provider service, not `ProviderEntity` |

DTOs that have no entity: `Departure`, `Vehicle`, `Message`, `Destination` (stop + lines, built by
`EmbedDestinationsDatabaseHandler`), `StopGroup`, `CollectionResult`, `Links`, `Status\*`.

## Converters (`src/DataConverter`)

`Converter::convert($entity, $type)` / `supports($entity, $type)`; all services tagged `app.converter`.
`AggregateConverter` is the `Converter` alias that services receive. It delegates to the first converter whose
`supports()` matches, and gives `RecursiveConverter`s itself as their parent so they can convert nested values.
Repositories call `$converter->convert($entity, Dto::class)`.

`EntityConverter` handles most entities. Three behaviours matter:

1. **Only loaded data is converted.** An uninitialized Doctrine proxy becomes a *reference DTO* (see below), and
   an uninitialized `PersistentCollection` becomes an empty collection. Lazy loading is never triggered. **So
   the query decides the output shape**, and an `Embed` requirement (`GenericWithDatabaseHandler` joins and
   selects the association) is how a relation gets into the response. If a relation comes back empty or as
   `{id}` only, the query didn't fetch it.
2. **Identity cache** keyed by `class:id`, so shared objects (the same line on many tracks) are converted once.
   It's reset on `kernel.terminate` by `RequestCleanupSubscriber`, which matters with RoadRunner's long-lived
   workers. `EmbedDestinationsDatabaseHandler` clones and resets its own converter for this reason.
3. The DTO is cached **before** its relations are filled, which breaks conversion cycles.

## Reference DTOs (`JustReference`)

A DTO that stands for an object without its data, carrying only `id`:

- `Referable::reference($id)` (`ReferableTrait`) creates a plain DTO with only `id` filled. Bindings use it for
  `RelatedFilter`.
- `Service/Proxy/ReferenceFactory::get($class, $id)` creates a generated subclass (ProxyManager,
  `ReferenceObjectGenerator`) that also implements `Dto\JustReference`. `EntityConverter` uses it for unloaded
  proxies. `JustReferenceNormalizer` serializes these with group `reference`, i.e. just `{ "id": ... }`.

The reverse direction is `Service/EntityReferenceFactory::create($dto|array|Collection, $provider)`. It turns
`Line`/`Stop`/`Track` DTOs into `EntityManager::getReference()` proxies with prefixed ids, which DB handlers
bind as query parameters. Supporting another DTO in `RelatedFilter` requires adding it to its `$mapping`.

## DTO conventions

- Implement `Dto`. Add `Referable` + `ReferableTrait` if the DTO has an id, and `Fillable` + `FillTrait` for
  `createFromArray([...])` / `fill([...])`. `fill()` calls setters (`set<Name>`) or writes public properties,
  and silently ignores unknown keys.
- `#[ContentType('vnd.cojedzie.stop')]` sets the response media type (`application/vnd.cojedzie.stop+json`,
  resolved by `ContentTypeResolver`) and is also used to dispatch refs by type.
- Serialization groups: `Serialization\Serializer` always adds the `default` group, and `MetadataLoader` puts
  every property without explicit `#[Groups]` into `default`. A property in a non-default group (e.g.
  `Stop::$destinations` → `embed:destinations`, `Line` → `full`) is only serialized when a controller passes
  that group in the context. `ApiResponseFactory::extractGroupsFromRequirements()` maps each `Embed(x)` to
  group `embed:x`. Currently only `StopGroupsController` passes these groups. Check this before relying on
  an embed showing up in another endpoint's output.
- `HasRefs` / `Refs` (e.g. `Message` → `MessageRefs { stops, lines }`) serialize as `$refs`. `DtoNormalizer`
  drops empty `$`-prefixed properties. `Embed('$refs.stops')` is resolved by `RefsEmbedHandler`, which replaces
  the reference items with full DTOs loaded from the provider's repositories.
- OpenAPI schemas come from `@OA\Property` annotations on DTO properties.
