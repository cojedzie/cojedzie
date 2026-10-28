# 0006. Collections are php-ds values with kadet/functional pipelines

- **Status:** Accepted
- **Date:** 2026-10-28
- **Applies to:** `api/src/**`, `api/tests/**`, `api/composer.json`

## Context

The API had three ways to express collections:

- php-ds in the importers, the streaming parser and `CollectionUtils`;
- `illuminate/collections` in repositories, DTOs, converters, filter handlers and controllers;
- plain arrays.

`kadet/functional` predicates and transforms were mixed into all three. Illuminate was picked in PHP 7.4 days for its
fluent, functional feel. With PHP 8.5's pipe operator (`|>`) the same feel is available without a wrapper object, as
long as every step is a function of one argument (data last).

php-ds was chosen originally for speed and memory on large data sets. Imports still need that: they must not get
slower or use more memory. The request path works on small sets (one page of results), so there readability matters
more than raw speed, as long as it doesn't regress noticeably.

## Considered options

1. **Keep `illuminate/collections`.** Fluent and familiar. But it is a second model next to php-ds, which the
   importers keep for speed. It also pulls in Laravel packages for one feature, and its `filter` keeps keys, so the
   code needed `->values()` everywhere.
2. **A wrapper object over Ds types** (a fluent class of our own). Every step allocates a wrapper, the importers would
   bypass it for speed, and the codebase would be back to two models.
3. **A standalone collection library** (loophp/collection, tiny-blocks/collection, ramsey/collection,
   noctud/collection, ...). Each brings its own collection type, which is again a second model next to php-ds. None
   is a fluent layer over php-ds 2.0.
4. **kuaukutsu/ds-collection**, a fluent layer over php-ds. It supports only php-ds `^1.3`, while the API runs php-ds
   2.0 (`Seq`, `Map`, `Set`; no `Vector`, `Deque` or `Collection`).
5. **Curried, data-last functions over php-ds values, chained with `|>`**, added to `kadet/functional` (the owner's
   library, already a dependency) as `Kadet\Functional\Collections`. One model: the data stays a plain `Ds\Seq`,
   `Map` or `Set`, and the functions return the same kind of container they get. The library offers an eager
   (`c\`) and a lazy (`Collections\Lazy`) flavour, and the benchmark below picked the default.

## Decision

We will use option 5. Collections in the API are php-ds 2.0 values (`Ds\Seq` for lists, `Ds\Map` for keyed data,
`Ds\Set` for sets), transformed with the eager `Kadet\Functional\Collections` functions (`use ... as c;`) chained
with `|>`. `illuminate/collections` is removed.

Rules that come with it:

- **Callbacks get `($value, $key)`**, for every input type, `Ds\Map` included. Builtins are passed as first-class
  callables (`trim(...)`), which the library calls with the value only.
- **Missing is `null`.** `c\first()`/`c\last()` return `null` on empty input, and every repository `first()` returns
  `null` when nothing matches. `required: true` is used where absence is an error.
- **Lazy values stay local.** Repository return types, DTO properties, converter outputs and anything serialized are
  materialized `Seq`/`Map`/`Set`, never generators or `iterable`. Importers may still pass generators between their
  own steps (`yield`, the JSON streamer, Doctrine `iterateColumn`); that is I/O streaming, not a collection pipeline.
- **Received collections are never mutated.** `Seq::sort()`, `push()`, `unshift()` and `apply()` mutate in place, so
  code transforms through `c\` functions, which return new collections.
- **Eager is the default.** `Collections\Lazy` is used only where a benchmark shows a gain.

### Benchmark verdict

The gate (phpbench, `api/tests/Benchmark/Collections`, PHP 8.5 with ext-ds 2.0, opcache on, Xdebug off) compared
the old code, the eager pipeline and the lazy pipeline on two paths. Each number is the median of three runs.

**Import** (`ZtmGdanskTrackDataImporter`: stream `stopsInTrip`, group by track, filter known stops, sort, renumber).
"Improvement" was the larger relative gain in time or own memory (peak minus a no-op baseline), provided the other
did not get worse by more than 5%. Lazy would have needed more than 5% to be chosen, and above 15% it would have
been the default.

| Variant | Time | Own memory |
|---|---:|---:|
| old code (`Ds\Seq` methods) | 2.555 s | 26,637,832 B |
| eager `c\` | 2.548 s | 26,760,952 B |
| lazy | 2.558 s | 26,642,320 B |

Lazy saved 0.44% of memory and was 0.39% slower, so the improvement was 0.44% and **eager won**. The run is dominated
by about 2.55 s of JSON streaming. `groupBy` has to hold every streamed record (about 26.7 MB), and laziness can't
avoid that. On the collection step alone (no parsing), lazy saved about 124 KB (7.8% of that step's small footprint)
at a time gain within the ~4% run-to-run noise. The eager pipeline was about 27% slower than the old `Ds\Seq` code on
that step alone (15.4 ms vs 19.5 ms), which is invisible end to end. A final check before Illuminate was removed
measured the eager importer 1.6–2.0% faster than the old code end to end, with +0.47% own memory.

**Request path** (`RealtimeScheduleDepartureRepository::pair()`, eager `c\` against Illuminate). The limit was 5%
slower. The first run was over it (+13.4% at production size, +32.4% at stress size), and most of the gap came from
`c\sortBy` calling a userland comparator. kadet/functional 1.0.1 sorts natively. After that:

| Set | Eager vs Illuminate | Lazy vs Illuminate |
|---|---:|---:|
| production (32 scheduled / 16 realtime) | +25.5…+26.1% (~72 μs vs ~57 μs) | +13.9…+14.6% |
| stress (512 / 256) | +7.0…+7.4% | +10.2…+10.8% |

The production-size percentage rose because Illuminate's own time dropped from 63.0 to 57.4 μs between sessions
(about 9% drift, with no change to that code). In absolute terms the cost is about 10–15 μs per departures request.
The rest of the gap is building php-ds structures (`keyBy`, `map`, `concat`, `Map::remove`), not sorting. **The owner
accepted this cost** for having one collection model.

## Consequences

- One collection vocabulary from importers through repositories, DTOs, handlers and controllers.
- The departures request path is about 10–15 μs slower than with Illuminate. Fixing that means work in the library
  or hand-written loops in `pair()`, measured against the benchmark first.
- php-ds has quirks that Illuminate hid: `Seq::first()` throws on empty, `Ds\Map::map()` passes `($key, $value)`,
  map keys are not coerced (`'1'` and `1` differ), and PHPUnit `assertEquals` doesn't compare Ds contents under
  ext-ds. The wiki page `contrib/agents/wiki/api/collections.md` lists them with their fixes.
- DTO properties typed `Seq` need a `@var Seq<T>` docblock so `DtoNormalizer` can denormalize them, and a `Ds\Map`
  serializes through `DsMapNormalizer`.
- The request-path benchmark (`DeparturePairingBench`) and the import benchmark (`TrackStopsBench`) stay in the
  repository to re-check later changes.

## Compliance

- No dependency on `illuminate/collections` (or another collection library) in `api/composer.json`, and no
  `Illuminate\` imports or `collect()` calls in `api/src` or `api/tests`.
- Collection-valued repository return types, DTO properties, converter outputs and serialized values are `Ds\Seq`,
  `Ds\Map` or `Ds\Set`, not arrays of entities/DTOs, `iterable`, generators or other collection classes. Doctrine
  collections stay inside entities and are converted at the boundary.
- Multi-step transforms are chains of `Kadet\Functional\Collections` functions (`c\`) joined with `|>`. A single
  php-ds method call is fine where it reads better (`$seq->isEmpty()`, `$map->get($key, null)`).
- `Kadet\Functional\Collections\Lazy` is not used unless the change adds or cites a benchmark showing a gain.
- Repository `first()` returns `null` when nothing is found; it uses `c\first()`, never `Seq::first()` on a result
  that can be empty.
- Builtins passed as callbacks are first-class callables (`trim(...)`), not strings (`'trim'`).
- Code never mutates a collection it received as an argument or read from a DTO or repository (`sort()`, `push()`,
  `unshift()`, `apply()`, `remove()`, `put()` on it); it builds a new one.
