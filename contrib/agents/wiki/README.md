# Wiki — Co Jedzie

Knowledge base for working in this repository: architecture that spans many files, the reasoning behind it,
and anything learned during sessions that isn't obvious from the code.

## Pages

Pages are split by component: `api/` for the Symfony backend, `frontend/` for the Vue app. Put cross-cutting
topics on the side that owns them and link from the other (e.g. federation: node side in `api/`, client side
in `frontend/`).

### API (`api/`)

| Page | Contents |
|---|---|
| [api/architecture.md](api/architecture.md) | Request flow overview, providers, API docs, notable directories |
| [api/filtering.md](api/filtering.md) | Requirements, HTTP parameter bindings, handlers and their mapping tables |
| [api/dto-and-entities.md](api/dto-and-entities.md) | Entity vs DTO model, ids, converters, reference DTOs, serialization groups |
| [api/collections.md](api/collections.md) | php-ds collections, `kadet/functional` pipelines, boundaries, Illuminate translations, traps |
| [api/data-import.md](api/data-import.md) | `app:update`, data importers, Doctrine entities and per-engine migrations |
| [api/federation.md](api/federation.md) | Federated API nodes, hub, endpoint versioning |
| [api/testing.md](api/testing.md) | PHPUnit and Behat suites, fake `test` provider, writing contract scenarios |

### Frontend (`frontend/`)

| Page | Contents |
|---|---|
| [frontend/architecture.md](frontend/architecture.md) | Vue 3 app, Express server, Vuex store, API client, node load balancing, Storybook |

## Maintaining the wiki

- Update the relevant page when a session discovers something non-obvious (a gotcha, a cross-cutting flow,
  why something is the way it is). Create a new page in `api/` or `frontend/` if no existing one fits, and
  add it to the table above and to the lookup table in `AGENTS.md`.
- Keep pages factual and verified against the code; remove or fix statements that turn out to be wrong.
- Don't duplicate what is trivially discoverable (file listings, single-class behaviour).
