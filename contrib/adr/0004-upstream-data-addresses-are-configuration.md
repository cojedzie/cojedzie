# 0004. Upstream data addresses are configuration

- **Status:** Accepted
- **Date:** 2026-10-26
- **Applies to:** `api/src/Provider/**`, `api/config/services.yaml`, any code fetching data from an external service

## Context

Providers fetch data from services we don't control. Their addresses change without our involvement:

- ZTM Gdańsk retired its `delays` endpoint in favour of `departures`; the old URL was a constant and the app kept
  calling an endpoint that only lived on as a redirect.
- Files of ZTM's open-data dataset are addressed by resource ids that change when the dataset is republished.
- ZKM Gdynia's stops API disappeared (404) while its URL was hard-coded in the stop importer.
- Tests had to take over PHP's `http://` stream handler to serve recorded data, because the URL was a `final`
  constant.

A hard-coded address means a code change and a release for every upstream move, and makes such code hard to test.

## Considered options

1. **Constants in the classes using them** (the previous state). Simple, but every upstream change needs a release
   and tests can't redirect requests.
2. **One environment variable per base host, paths and resource ids in code.** Fewer variables, but resource ids
   change independently of the host, so they still need code changes.
3. **One environment variable per independently changing address, grouped per provider in a config class.** More
   variables, but each upstream change is a configuration change, and tests construct the config directly.

## Decision

We will treat every address the API fetches data from as configuration (option 3):

- Each address that can change on its own (an API base, a dataset file) gets its own environment variable.
  Fixed, documented endpoint paths under a configurable API base (e.g. `/departures`) may stay in code.
- A provider's addresses are grouped in one `final readonly` config class in the provider's namespace (e.g.
  `App\Provider\ZtmGdansk\ZtmGdanskConfig`) whose constructor reads them with `#[Autowire(env: …)]`; classes
  receive the config object, not individual strings.
- Defaults are the current production addresses, declared as `env(NAME)` parameters in `config/services.yaml`,
  so nothing needs to be set to run the app.
- Tests point the config at recorded data by constructing it directly.

Out of scope: URLs that are content or identifiers rather than data sources (attribution links, problem `type`
URIs, OpenAPI examples, fixtures).

## Consequences

- An upstream move (new endpoint, republished dataset file) is fixed by setting an environment variable; a code
  change is only needed when the response format changes.
- Integration code can be tested against local fixtures without hijacking stream handlers.
- More environment variables to document; the provider's config class and `services.yaml` are the reference.
- A wrong override fails at runtime, not at build time.

## Compliance

- No class under `api/src/` fetches from a hard-coded external URL (`http://`/`https://` literal or constant
  passed to `file_get_contents`, an HTTP client, a JSON streamer or similar).
- Upstream addresses of a provider are properties of its config class, each read from its own environment variable
  with `#[Autowire(env: …)]`, and have an `env(NAME)` default in `config/services.yaml`.
- Classes that fetch upstream data take the provider's config class (or a value from it), not their own
  `#[Autowire(env: …)]` URL parameters.
- Only fixed endpoint paths appended to a configured base may remain as constants in code.
