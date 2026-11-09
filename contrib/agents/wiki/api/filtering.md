# Filtering: requirements, bindings and handlers

Repositories never take ad-hoc filter arguments. Every query is described by a list of **requirement** value
objects; HTTP **bindings** create them from the request and **handlers** apply them to a concrete storage
(Doctrine query builder or an in-memory `Ds\Seq`).

```
HTTP request ──(Filter/Binding/Http)──▶ Requirement[] ──▶ Repository::all(...$requirements)
                                                             │ HandlerProvider: Requirement class → handler
                                                             ├─ ModifierHandler::process()          (before query)
                                                             └─ PostProcessingHandler::postProcess() (after query, on DTOs)
```

## Requirements (`src/Filter/Requirement`)

Marker interface `Requirement`; all implementations are immutable.

| Requirement | Meaning |
|---|---|
| `IdConstraint($id)` | id equals a string, or is in an array (`isMultiple()`). Ids are **provider-local** (no `provider::` prefix). |
| `FieldFilter($field, $value, FieldFilterOperator, options)` | Compare a DTO field. Option `case_sensitive` (default `false`). `$field` is the **DTO** field name, mapped to an entity field by the handler. |
| `FieldFilterOperator` (enum) | `Equals`, `NotEquals`, `Less`, `LessOrEqual`, `Greater`, `GreaterOrEqual`, `In`, `NotIn`, `Contains`, `BeginsWith`, `EndsWith`, with `is*Operator()` category helpers. |
| `RelatedFilter($reference, ?$relationship)` | Restrict to records related to one or many `Referable` DTOs (e.g. `Stop::reference('123')`). Relationship defaults to the DTO class of the reference; it can also be a string key such as `'stop'` or `'destination'`. |
| `LimitConstraint($offset, $count)` | Pagination. `LimitConstraint::count(n)` shorthand. |
| `Embed($relationship)` | Load an optional relation into the result (e.g. `destinations`, `track`, `$refs.stops`). |
| `TimetableDay(?$date, DayMode)` | Timetable entries of a day in the provider's time zone (ADR 0029): `Calendar` (departure between the local midnights, so 23 or 25 h over DST), `Service` (`trip.serviceDate`), `Extended` (either). Handled by `TimetableDayDatabaseHandler`, which reads the zone from meta `timezone` (`GenericTimetableRepository::withTimezone()`, given `Provider::getTimezone()`) and today from the clock. |
| `ValidityConstraint($at)` | Only versioned records valid at a moment (`ValidityAwareInterface`). Track repositories add one for now unless the requirements carry one; the `destinations` embed uses tracks valid now. |
| `TrackKindConstraint(...TrackKind)` | Only tracks of the given kinds, or the track stops and timetable entries on them (ADR 0031). `GenericTrackRepository` (tracks and track stops) and `GenericTimetableRepository` (entries and departures) add `TrackKindConstraint::passenger()` (main and side) unless the requirements carry one, so depot runs never reach riders; the `destinations` embed applies it too. `TrackKindDatabaseHandler` reaches the track from meta `entity` with its own joins. |

Requirements are also used **internally** between repositories — e.g. `Database\RealtimeTimetableDepartureRepository` queries the
timetable repository with `RelatedFilter($stop, Stop::class)`, `FieldFilter('departure', $time, GreaterOrEqual)`,
`Embed('track')`, `Embed('destination')`, and doubles the requested `LimitConstraint` before merging realtime data.

## HTTP bindings (`src/Filter/Binding/Http`)

A `ParameterBinding` has two jobs: `getRequirementsFromRequest(Request)` and `getDocumentation(Route)` (OpenAPI
parameters, collected by `Describer/ParameterBindingRouteDescriber`). So declaring a binding both parses the
request **and** documents the parameter — never document filter parameters by hand. Bindings are also where
input is validated ([ADR 0003](../../../adr/0003-malformed-request-parameters-are-bad-requests.md)): malformed values
throw `InvalidParameterException` (400), required parameters are declared on the binding (`required: true`), and
controllers never check requirements themselves. Requirement objects refuse invalid state in their constructors. A `schema` given as an
array in a binding's `documentation` is turned into `Schema`/`Items` objects and passed to the `Parameter`
constructor (see the swagger-php notes in [architecture.md](architecture.md#api-docs)).

| Binding | Request syntax → requirement |
|---|---|
| `IdConstraintParameterBinding(parameter: 'id', from: ['query'], isMultiple)` | `?id=1` (or `?id=1,2` when `isMultiple`) → `IdConstraint`. `from: ['attributes']` reads a route placeholder. |
| `FieldFilterParameterBinding(parameter, field, defaultOperator, operators, documentation, options)` | `?name=foo` uses `defaultOperator`; `?name:begins=foo` picks an operator by suffix. Suffix sets: `EQUALITY_OPERATORS` (`eq`, `not`), `SET_OPERATORS` (`in`, `not-in`), `STRING_OPERATORS` (+ `contains`, `begins`, `ends`), `ORDINAL_OPERATORS` (+ `le`, `leq`, `ge`, `geq`), `DEFAULT_OPERATORS` (ordinal + string). |
| `RelatedFilterParameterBinding(resource: Stop::class, parameter, relationship, from)` | `?stop=1,2` → `RelatedFilter([Stop::reference('1'), ...])`. With `['attributes', 'query']` the route placeholder and the query merge (`/tracks/a/stops?track=B` also adds B's stops; `/stops/a/timetable?stop=b` → both). |
| `LimitParameterBinding(defaultLimit: 20, maxLimit: 100)` | `?limit=&offset=` → a `LimitConstraint`, limit clamped to `[0, maxLimit]`. The timetable uses `(1000, 1000)`. |
| `EmbedParameterBinding(allowed: [...])` | `?embed=a,b` → one `Embed` per **allowed** value; unknown values are silently dropped. |
| `ImportFilterParameterBinding()` | `FieldFilter` on `import` with equality operators (`?import=<uuid>`). |
| `TimetableScopeParameterBinding()` | `stop`, `line`, `track` and `trip` related filters from the route or the query (lists add up); none of them → 400 `Should specify at least one of stop, line, track, trip.` (`InvalidParameterException::noneOf()`). |
| `TimetableTimeParameterBinding()` | `?date=Y-m-d&day=calendar\|service\|extended` → `TimetableDay` (date null = today), or `?from=&to=` (ISO 8601 with offset, its `+` URL-encoded as `%2B` or `Z`, `from < to`) → two `FieldFilter`s on `departure`. Mixing them, one end of a range or a malformed value → 400. |
| `ParameterBindingGroup(...$bindings)` | Combines bindings. |
| `ParameterBindingProvider($source)` | Lazily builds a binding from a callable (`[self::class, 'getParameterBinding']`) or a class name. |

### Attaching bindings to controller actions

Bindings are PHP attributes, consumed by `Service/RequirementsArgumentResolver` (decorates Symfony's
`argument_resolver`):

- **On a parameter** — requirements from that attribute go to that parameter only:
  ```php
  #[IdConstraintParameterBinding(parameter: 'stop', from: ['attributes'])] IdConstraint $id
  ```
- **On the method** — requirements are distributed over the remaining parameters: a parameter typed
  `array`/`iterable` receives **all** remaining requirements; a parameter typed as a concrete `Requirement`
  class receives the first matching one. Convention for list endpoints:
  ```php
  #[ParameterBindingProvider([self::class, 'getParameterBinding'])]
  public function index(StopRepository $stopRepository, array $requirements): Response

  public static function getParameterBinding(): ParameterBinding
  {
      return new ParameterBindingGroup(new IdConstraintParameterBinding(...), new LimitParameterBinding(), ...);
  }
  ```
  The static method keeps the binding list reusable and is what the OpenAPI describer reads.

## Handlers (`src/Filter/Handler`)

All classes in `src/Filter/Handler` are services tagged `app.handler` and fetched lazily from a service locator.

- `ModifierHandler::process(HandleRequirementEvent)` — modifies the query.
  - DB: `HandleDatabaseRequirementEvent` gives the `QueryBuilder` and `meta` (`alias`, `entity`, `type` = DTO
    class, `provider` = `ProviderEntity`).
  - In-memory: `HandleInMemoryRequirementEvent` collects predicates (`getPredicates()`), combined with AND.
- `PostProcessingHandler::postProcess(PostProcessEvent)` — runs after the query on the `Seq` of
  **converted DTOs** and may replace it with `setData()`. Used by embeds that need a separate query.

A handler may implement both interfaces; handlers ignore event types they don't support (DB handlers `return`
on non-DB events).

### Which handler handles what

Each repository builds a `HandlerProvider` from a `Requirement class → handler` map via
`HandlerProviderFactory`. A map value can be a handler service id or a `callable(Requirement): string` that
picks the handler per instance. **A requirement missing from the map throws `UnsupportedModifierException`** —
that's how a repository declares what it supports.

Defaults:

| Requirement | `Database\DatabaseRepository` | `InMemory\InMemoryRepository` |
|---|---|---|
| `IdConstraint` | `IdFilterDatabaseHandler` | — |
| `LimitConstraint` | `LimitDatabaseHandler` | `LimitInMemoryHandler` |
| `FieldFilter` | `FieldFilterDatabaseHandler` | `FieldFilterInMemoryHandler` |
| `RelatedFilter` | `RelatedFilterDatabaseGenericHandler` | — |
| `Embed` | `GenericWithDatabaseHandler` | `RefsEmbedHandler` |
| `ValidityConstraint` | `ValidityDatabaseHandler` (registered by `GenericTrackRepository`) | — |
| `TrackKindConstraint` | `TrackKindDatabaseHandler` (registered by `GenericTrackRepository` and `GenericTimetableRepository`) | — |

Subclasses extend the map in `protected static function getHandlers()` (always `array_merge(parent::getHandlers(), …)`).
Default requirements are added with `DatabaseRepository::withDefaults($requirements, ...$defaults)`, which skips a
default whose class the requirements already carry.
Example — `GenericStopRepository` routes `Embed('destinations')` to `EmbedDestinationsDatabaseHandler`
(post-processing) and any other `Embed` to `GenericWithDatabaseHandler`.

### Database handlers: mapping tables

The generic DB handlers translate DTO-level names into entity paths using `$mapping` arrays keyed by the DTO
class from `meta['type']`. **Filtering by a new field or relation usually means adding a mapping entry here**,
otherwise the handler throws `InvalidArgumentException`:

- `FieldFilterDatabaseHandler::$mapping` — DTO field → entity field (e.g. `Stop.name`, `TimetableEntry.departure`).
  Case-insensitive comparison uses `LOWER()`; string operators become `LIKE` with `%` wrapping.
- `RelatedFilterDatabaseGenericHandler::$mapping` — relationship → entity association to join, or another
  handler service to delegate to (e.g. `Track` + `'stop'`/`'destination'` → `TrackByStopDatabaseHandler`).
  Referenced DTOs are turned into Doctrine references via `EntityReferenceFactory` (see
  [dto-and-entities.md](dto-and-entities.md)).
- `GenericWithDatabaseHandler::$mapping` — embed name → dotted association path; each segment is joined and
  added to the SELECT (aliases `<alias>_<segment>`), so the relation is fetched in the same query.
- `IdFilterDatabaseHandler` prefixes ids with the provider (`IdUtils::generate`).

## Gotchas

- `DatabaseRepository::allFromQueryBuilder()` sets `setMaxResults(100)` (`DEFAULT_LIMIT`) before handlers run, and
  uses Doctrine's `Paginator`. Without a `LimitConstraint`, results are silently capped at 100.
- Paging with `offset` needs an `ORDER BY` that ends in a unique column. With ties (a line's entries at the same
  minute), MariaDB returns a different subset of the tied rows on each run, so pages overlap and skip rows.
  `GenericTimetableRepository::departures()` ends with `trip_stop.id` for this.
- Two `FieldFilter`s on one field (both ends of a range) get parameters `:alias_field` and `:alias_field_2`.
- `first()` prepends `LimitConstraint::count(1)`.
- `FieldFilterParameterBinding` checks the operator suffix of **every** `name:suffix` query parameter before it
  checks the name. A suffix that this binding doesn't allow throws `InvalidOperatorException`, even when the
  parameter belongs to another binding.
- `ParameterBindingProvider` can resolve a service id only through its container, but attributes are built
  with `ReflectionAttribute::newInstance()` and nothing injects that container. Use a callable or a class name.
- Requirement ordering matters only for post-processing: reducers run in the order of the requirements.
