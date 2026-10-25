# 0001. Behavioural tests with Behat

- **Status:** Accepted
- **Date:** 2026-10-25
- **Applies to:** `api/features/**`, `api/tests/Behat/**`, `api/behat.dist.php`, `api/config/packages/test/**`,
  `Taskfile.yml`, `.github/workflows/**`

## Context

The API is covered only by unit tests (`api/tests/`: streaming parser, tokenizer, streams, one ZTM Gdańsk
extractor). Nothing checks the behaviour users actually depend on: that stops can be found, that departures
come back for a stop, that filters and embeds work, that federation routes requests. A dependency update is
due, and without behavioural coverage there is no safety net for it.

We want to work BDD-style: describe a behaviour as a scenario first, then implement it.

Constraints specific to Co Jedzie:

- Data comes from external providers (ZTM Gdańsk) and is inherently time-dependent (departures are relative to
  now). Scenarios must not depend on either.
- The frontend talks to the API only over HTTP and selects nodes by advertised endpoint versions
  (`front/src/api/endpoints.ts`), so the HTTP contract is itself user-facing behaviour.
- Federation and realtime updates go through Messenger, an HTTP client and the Mercure hub.

State of the tooling (October 2026):

- Behat 4.0 is released. YAML config and PHPDoc annotations are gone: configuration is `behat.dist.php`
  (`Behat\Config\Config`), steps and hooks are PHP attributes, GHERKIN_42 is the default parser mode (`Rule`,
  `Example`), and PHPUnit assertions need `behat/phpunit-assertions-extension`.
- `friends-of-behat/symfony-extension` 2.7 supports Behat 4 and Symfony 8, but requires PHP ≥ 8.3 and
  Symfony ≥ 7.4. The API is on Symfony 6.4.

## Considered options

1. **Behat 4, in-kernel, API level** (Symfony extension, requests handled by the test kernel). Fast, no web
   server, full container access for fixtures and fakes. Gherkin is readable by non-PHP contributors and
   doubles as living documentation. Doesn't exercise nginx/RoadRunner or the frontend.
2. **Behat 3.x with YAML config and annotations.** Works on Symfony 6.4 today, but every file written would
   need migrating to Behat 4 shortly after. Rejected.
3. **PHPUnit `WebTestCase` functional tests.** No new tool, but scenarios are code, not specifications;
   doesn't support the BDD workflow we want.
4. **Browser end-to-end (Playwright, possibly with `playwright-bdd` to reuse Gherkin).** Covers the full stack,
   but slow, flaky with realtime data, and needs the whole compose stack. Better as a later, thin layer.
5. **Behat over real HTTP** (BrowserKit/HTTP client against a running server). Closer to production, but
   slower and loses direct container access for fakes.

## Decision

We will write behavioural tests for the API in Behat 4, run in-kernel through the Symfony extension
(option 1). Behat is introduced as part of the dependency update that brings the API to Symfony ≥ 7.4.

**Layout and configuration**

- Feature files live in `api/features/<area>/*.feature`, contexts in `api/tests/Behat/Context/`, shared test
  services in `api/tests/Behat/Support/` (namespace `App\Tests\Behat`).
- Configuration is `api/behat.dist.php`; a local `behat.php` may override it and is git-ignored.
- Steps, hooks and transformations use PHP attributes (`#[Given]`, `#[When]`, `#[Then]`, `#[BeforeScenario]`,
  `#[Transform]`), using Turnip patterns (`:name`) where possible rather than regexes.
- Contexts are Symfony services (autowired by the extension). Contexts share per-scenario state through
  injected support services (e.g. an `ApiClient` holding the last response), never by looking each other up.

**Writing scenarios**

- Every feature has a narrative (`In order to … / As a … / I want …`). Related scenarios are grouped under
  `Rule:` blocks naming the business rule they illustrate.
- Scenarios are **declarative**: steps describe what a user or API consumer does and observes, in domain
  language ("When I search for stops named "Brama"", "Then I should see departures of line 6 at 12:05"). URLs,
  JSON paths and status codes stay inside step implementations.
- The exception is **HTTP contract** features (`api/features/contract/`): status codes, headers (`Link:
  rel=mercure`), endpoint versions advertised for the frontend. These may use a generic `ApiContext`
  vocabulary (request, status, JSON path, header).
- New or changed user-facing API behaviour starts with a scenario (red), then the implementation (green).

**Determinism and isolation**

- Scenarios don't depend on external providers or the wall clock. In the `test` environment the API uses a
  fixture-backed test provider whose data is defined by `Given` steps, and a frozen clock
  (`symfony/clock` `MockClock`) set by a `Given the current time is "…"` step with an explicit time zone.
- Outgoing HTTP uses `MockHttpClient`, Mercure uses a recording hub, and Messenger uses an in-memory transport
  consumed explicitly by a step. Contexts may assert on these fakes.
- Each scenario starts from a clean state: database changes are rolled back (`dama/doctrine-test-bundle` or
  schema reset), and fakes and the clock are reset in a `#[BeforeScenario]` hook.

**Running**

- `task test:behat` runs Behat in the API container. CI runs it alongside PHPUnit with
  `--fail-on-deprecations` and JUnit output. Tags (`@wip`, `@federation`, …) select subsets with tag
  expressions; `@wip` scenarios are excluded from CI.
- Unit tests stay in PHPUnit; provider-specific parsing (ZTM payloads) is tested there with recorded payloads,
  not in Behat.

Browser-level testing of the frontend is out of scope. Adding it requires its own ADR.

## Consequences

- Behat depends on the Symfony 7.4+ upgrade. It can't land before that part of the dependency update.
- Code that needs "now" must take it from `Psr\Clock\ClockInterface` rather than `new \DateTime()`,
  `time()` or `Carbon::now()`. Existing call sites (departure repositories, federation checker, status
  service, …) are refactored as scenarios reach them.
- Provider selection must allow the test provider in the `test` environment (`ProviderResolver`). The
  existing `Dummy` provider may be extended for this or replaced by a dedicated fixture provider.
- Declarative steps take more effort per step than generic HTTP steps, but keep feature files stable when
  URLs or response shapes change, and readable as documentation.
- In-kernel runs don't cover nginx, RoadRunner or the real Mercure hub.
- Two test runners to maintain, plus a shared step vocabulary that needs curation to stay consistent.

## Compliance

- Behat configuration is PHP (`api/behat.dist.php`); no `behat.yml`/`behat.yml.dist` is added.
- New `.feature` files are under `api/features/`; new contexts are under `api/tests/Behat/Context/`.
- Step definitions, hooks and transformations use attributes, not PHPDoc annotations.
- Each `Feature:` has an `In order to` / `As a` / `I want` narrative.
- Feature files outside `api/features/contract/` don't contain URLs, JSON paths or HTTP status codes in steps.
- Contexts don't access other contexts; shared state goes through services in `api/tests/Behat/Support/`.
- Steps and feature files don't reference real provider data or rely on the wall-clock time; time comes from
  a `Given the current time is …` step.
- No scenario performs real network I/O (external HTTP, Mercure hub).
- Changes adding or changing an API endpoint's behaviour include a Behat scenario covering it.
- Code added or changed in `api/src/` that needs the current time uses `ClockInterface`.
- CI runs Behat with `--fail-on-deprecations`; a changeset must not disable or skip it outside `@wip`.
