# API tests (`api/`)

Two suites. PHPUnit (`api/tests/`, not `tests/Behat`) for units. Behat (`api/features/`) for the **API contract**,
i.e. what consumers (the frontend) may rely on, decided in
[ADR 0001](../../../adr/0001-behavioural-tests-with-behat.md). CI runs both on every push and PR (job `test` in
`.github/workflows/next.yml`; `@wip` scenarios excluded).

```bash
task test:api                                  # PHPUnit
task test:behat                                # Behat, fresh test database each run
task test:behat -- features/departures         # one area
task test:behat -- --tags='~@wip'              # what CI runs
```

`task test:behat` deletes `api/var/db/test.db` and rebuilds it from the **SQLite migrations**, so a broken
migration fails the run (that's how two SQLite migration bugs were found). Every scenario then runs in a
transaction that is rolled back (`dama/doctrine-test-bundle`; needs `use_savepoints: true`).

## Layout

| Path | What |
|---|---|
| `api/behat.dist.php` | Config (PHP only, Behat 4). Strict: undefined steps fail. Register new contexts here. |
| `api/features/<area>/` | Domain scenarios (`stop-search/`, `departures/`). No URLs, JSON paths or status codes. |
| `api/features/contract/` | HTTP-level scenarios: status codes, response fields, parameter errors. |
| `api/tests/Behat/Context/` | Step definitions (attributes). Contexts never use each other. |
| `api/tests/Behat/Support/` | Shared per-scenario services: `ApiClient`, `TestData`, `FrozenTime`, the fake provider. |
| `api/config/services_test.yaml` | Test-only services: everything in `tests/Behat`, `TestProvider` tag, `MockClock` as `clock`. |

## The fake `test` provider

`TestProvider` (identifier `test`, "Test City") exists only in the `test` environment.

- Stops, lines and tracks are stored in the test database by `TestData` and read through the **real** generic
  Doctrine repositories, so name filters and `embed=destinations` run the production code.
- Departures come from `InMemoryDepartureRepository`, which implements the contract every provider must meet:
  departures of the given stops only, merged, ordered by departure time (estimate, else schedule), departures
  before "now" dropped, `limit`/`offset` applied. Scenarios about these document the contract. They test the
  fake, not ZTM Gdańsk code; the ZTM joining logic belongs in unit tests. A future step is to back departures
  with the database schedule as well.
- Entity ids are `test::<id>`; the API returns `<id>`. Use readable ids (`brama-wyzynna-01`).

## Writing scenarios

- Data goes in tables in domain words:
  - `Given there are stops:` (`id | name | variant`)
  - `Given line 6 runs through "a, b, c"` (creates the line and a track; destinations come from tracks)
  - `Given there are departures:` (`stop | line | scheduled | estimated | display`)
- `Given the current time is "2026-10-25 12:00" in "Europe/Warsaw"` freezes `MockClock` and Carbon. Times in
  tables are `HH:MM` in that zone, taken as the nearest such moment around now, so `00:05` at 23:55 is the next
  day.
- Empty cell = `null` (estimate, delay). Delay is in seconds.
- Assertions compare whole tables in order and print the actual JSON on failure (Behat 4 doesn't show PHPUnit
  diffs: `behat/phpunit-assertions-extension` 1.0 is incompatible with Behat 4.0).
- A scenario that exposes a bug is written first and fails. Mark it `@wip` only until its fix lands.
- ADR 0001: changed endpoint behaviour needs a scenario. Shared parameters are covered once for all endpoints in
  `features/contract/parameter-errors.feature`; add a row when an endpoint gets a limit binding.

## Contract decisions pinned by scenarios

- Unknown provider → 404 everywhere.
- Malformed `limit`/`offset` (not a non-negative integer), unknown filter operators (`name:bogus`) and departures
  without any `stop` → 400. `limit` above the maximum is clamped; `limit=0` → empty list.
- Unknown and repeated stop ids in `stop[]` are ignored.
- Name filters ignore case including Polish letters (SQLite gets a Unicode `LOWER()` from
  `App\Doctrine\SqliteUnicodeMiddleware`), match `%`/`_` literally (`ESCAPE '!'`) and trim the value.
- Stop groups come back ordered by stop id.
