# Collections: php-ds and kadet/functional (`api/`)

Collections in the API are php-ds 2.0 values transformed with `kadet/functional`'s collection functions chained
with `|>`. The decision, its alternatives and the benchmark numbers are in
[ADR 0006](../../../adr/0006-collections-are-php-ds-values-with-kadet-functional-pipelines.md). The library's full
reference is its README, `api/vendor/kadet/functional/README.md` (after `task dependencies`). Read it before writing
`c\` code.

## Imports

```php
use Ds\Map;
use Ds\Seq;
use Ds\Set;
use Kadet\Functional\Collections as c;   // eager: every step returns a collection
use Kadet\Functional\Predicates as p;    // p\property('delay', p\gt(60)), p\instance(Foo::class), ...
use Kadet\Functional\Transforms as t;    // t\property('id') as a selector
use Kadet\Functional as f;               // f\all(...), f\partial(...), ...
```

Older code imports single functions instead (`use function Kadet\Functional\Transforms\property;`). Both are fine.
`Kadet\Functional\Collections\Lazy` is used only in the collection benchmarks (`api/tests/Benchmark/Collections`);
don't use it in `src/` without a benchmark showing a gain.

## Boundaries

- Lists are `Ds\Seq`, keyed data is `Ds\Map`, sets are `Ds\Set`. php-ds 2.0 has no `Vector`, `Deque` or
  `Collection` interface.
- Repository `all()` returns `Seq`, `first()` returns the DTO or `null` (`$this->all(...) |> c\first()`).
- DTO collection properties are `Seq` with a `@var Seq<T>` docblock. Setters take `iterable` and store a new `Seq`.
- `EntityConverter` turns Doctrine collections into `Seq` (an uninitialized `PersistentCollection` becomes an empty
  one). Doctrine's `ArrayCollection`/`PersistentCollection` stay inside entities.
- Generators never cross these boundaries. Importers may pass generators between their own steps (`yield`, the JSON
  streamer).
- Serialization: a `Seq` becomes a JSON list, a string-keyed `Map` a JSON object (`DsMapNormalizer`). Check the
  container type when changing a result that is serialized: turning a list into a `Map` changes `[...]` into
  `{...}`. Details in [dto-and-entities.md](dto-and-entities.md).

## Writing pipelines

```php
$byLine = $departures
    |> c\filter(fn (Departure $departure) => $departure->getDelay() > 0)
    |> c\groupBy(fn (Departure $departure) => $departure->getLine()->getId());   // Map<string, Seq<Departure>>
```

- Callbacks get `($value, $key)`. Builtins are passed as first-class callables (`trim(...)`), which get the value
  only.
- Eager transforms return the container they got: `Seq` → `Seq`, `Map` → `Map`, `Set` → `Set`, array → array.
  List transforms (`values`, `keys`, `flatMap`, `prepend`, `append`, `concat`) on a `Map` give a `Seq`.
- A one-step operation may call php-ds directly where that reads better: `$seq->isEmpty()`,
  `$map->get($key, null)`, `$map->hasKey($key)`.
- Never mutate a collection you received (argument, DTO property, repository result): `Seq::sort()`, `push()`,
  `unshift()`, `apply()` and `Map::put()`/`remove()` change it in place for every other reader. Use `c\` functions,
  or build your own copy first (`RealtimeScheduleDepartureRepository::pair()` re-keys the schedule into a new `Map`
  before calling `remove()` on it).

## Translations from Illuminate

The API used `illuminate/collections` until the migration. For reading old code or porting a snippet:

| Illuminate | Now |
|---|---|
| `collect($items)` | `new Seq($items)` (or `$items \|> c\toSeq()`) |
| `->map($fn)` / `->filter($fn)` / `->reject($fn)` | `\|> c\map($fn)` / `c\filter($fn)` / `c\reject($fn)` |
| `->filter()->values()` | `\|> c\filter()` on a `Seq` (it reindexes; on an array add `c\values()`) |
| `->keyBy($fn)` / `->groupBy($fn)` | `\|> c\keyBy($fn)` / `c\groupBy($fn)` (a `Map`; groups are `Seq`s) |
| `->sortBy($fn)` / `->sortByDesc($fn)` | `\|> c\sortBy($fn)` / `c\sortBy($fn, descending: true)` |
| `->unique()` / `->unique($fn)` | `\|> c\unique()` / `c\uniqueBy($fn)` |
| `->first()` / `->first($fn)` | `\|> c\first()` / `c\first($fn)` (`null` when nothing) |
| `->contains($fn)` / `->some($fn)` | `\|> c\any($fn)` / `c\some($fn)`; `c\contains($value)` for a value (`===`) |
| `->each($fn)` | `\|> c\each($fn)` (returns the input unchanged) |
| `->slice($o, $n)` / `->take($n)` | `\|> c\slice($o, $n)` / `c\take($n)` |
| `->prepend($x)` / `->push($x)` / `->merge($other)` | `\|> c\prepend($x)` / `c\append($x)` / `c\concat($other)` (lists) or `c\merge($other)` (keyed) |
| `->implode(', ')` | `\|> c\join(', ')` |
| `->has($k)` / `->get($k, $default)` | `$map->hasKey($k)` / `$map->get($k, $default)` |
| `->all()` / `->toArray()` | `\|> c\toArray()` (or `$seq->toArray()`) |
| `->where('field', $v)` / `->only($keys)` | `\|> c\filter(p\property('field', p\same($v)))` (strict `===`; Illuminate's `where` compares loosely, use `p\equals($v)` for `==`) / `c\filter(fn ($v, $k) => in_array($k, $keys, true))` |

## Traps

- **`assertEquals` doesn't compare Ds contents under ext-ds.** PHPUnit compares objects through
  `get_object_vars()`, which is empty for ext-ds classes, so `assertEquals(new Seq([1]), new Seq([2]))` passes, and
  so does any comparison of DTOs holding them. Compare `->toArray()` results, assert element by element, or compare
  serialized JSON.
- **`Seq::first()`/`last()` throw `UnderflowException` on an empty `Seq`.** Use `c\first()`/`c\last()`, which return
  `null`. Same for `Map::get($key)` without a default, which throws `OutOfBoundsException`: pass a default.
- **`Ds\Map::map()` passes `($key, $value)`**, the reverse of `c\map`. Use `c\map`, so every callback gets
  `($value, $key)`.
- **List operations on a `Set` return a `Set`, which drops duplicates.** `c\flatMap`, `c\map` and the other
  transforms keep the container, so mapping a `Set` of ids to entities collapses equal results, and order follows the
  set. Wrap it first (`new Seq($set)`) when order or duplicates matter.
- **`Map` keys are typed.** Unlike PHP arrays, `'1'` and `1` are different keys, so a lookup with an id of the wrong
  type misses silently. Normalize ids to one type (usually `string`) both when building the map and when looking up.
- **`Seq` DTO properties need `@var Seq<T>`.** `DtoNormalizer` reads it to denormalize the property back into a
  `Seq` of `T` (federation stores `Status\Aggregated` as JSON and `ConnectionConverter` reads it back). Without it
  the property can't be rebuilt from JSON. `DsCollectionSerializationTest` pins the round trip.
- **Illuminate's `filter` kept keys** and the old code followed it with `->values()`. `c\filter` on a `Seq`
  reindexes, but on a PHP array it keeps keys like `array_filter()`.
- **A generator given to an eager `c\` transform comes back as a `Seq`**, and a lazy chain must end in a collector
  (`c\toSeq()`, `c\keyBy()`, ...) or a query before it leaves the function: a generator can be read only once.
- **`c\min()`/`c\max()` with a selector return the selected value**, not the element. Sort and take
  `c\first()` to get the element.
