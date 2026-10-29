# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

Co Jedzie (cojedzie.pl) — realtime public transport departures. Monorepo with a Symfony API (`api/`) and a
Vue 3 frontend (`front/`), developed entirely through docker compose.

## Agent files live in `contrib/agents/`

Everything related to agentic workflows is stored in `contrib/agents/`, not in vendor-specific locations
(`.claude/`, `docs/superpowers/`, ...):

- `contrib/agents/wiki/` — project knowledge base (see below).
- `contrib/agents/skills/` — skills in the Agent Skills format (`<name>/SKILL.md`). `.claude/skills` is a
  symlink to this directory. `commit` creates commits without AI attribution; `adr-compliance` checks a branch
  or commit range against the ADRs; `test-cleanup` removes tests that only served implementation.
- `contrib/agents/specs/` and `contrib/agents/plans/` — design specs and implementation plans. This overrides
  the superpowers plugin defaults (`docs/superpowers/specs/`, `docs/superpowers/plans/`); keep its file naming.
  They are **temporary**, see below.
- Any other agent artifact (notes, prompts, reports) goes into a fitting subdirectory of `contrib/agents/`.

Architecture Decision Records are **not** agent-specific and live in `contrib/adr/` (format and template in
`contrib/adr/README.md`). Read the accepted ADRs relevant to your task before designing or changing code.
Only the top level of `contrib/adr/` is current. `contrib/adr/archive/` holds deprecated and superseded ADRs,
so skip it unless a current ADR refers to one.

## Specs and plans are temporary

Specs and plans are valid only while their task is in progress. They must not reach `master`.

- Commit spec/plan files in **their own commits**, separate from code, so they're easy to drop later.
- When the task is complete (before merging, e.g. as part of superpowers' finishing-a-development-branch):
  1. **Distill** them: move details that stay useful into the relevant wiki page(s), and record important
     decisions (chosen approach, rejected alternatives and why) as ADRs in `contrib/adr/`. Commit these
     like any other change.
  2. **Check compliance**: run the `adr-compliance` skill on the branch.
  3. **Clean up tests**: run the `test-cleanup` skill, so only tests worth keeping reach `master`.
  4. **Rebase the branch** to remove the spec/plan commits (and any later edits to those files), so the docs
     never appear in the merged history. Rewriting history and force-pushing need the user's confirmation.
- Nothing may be lost: if a spec contains something worth keeping that fits neither the wiki nor an ADR, ask
  the user where it belongs instead of dropping it.

## Wiki

Before working on a task, read the page(s) matching its topic. Pages are split into `api/` and `frontend/`.

| Task touches | Read |
|---|---|
| API controllers, providers, request flow (overview) | `contrib/agents/wiki/api/architecture.md` |
| Query parameters, requirements, bindings, filter handlers, embeds | `contrib/agents/wiki/api/filtering.md` |
| DTOs, entities, ids, converters, serialization groups | `contrib/agents/wiki/api/dto-and-entities.md` |
| Collections, kadet/functional, php-ds | `contrib/agents/wiki/api/collections.md` |
| `app:update`, importers, entities, migrations | `contrib/agents/wiki/api/data-import.md` |
| Federated nodes, hub, endpoint versions | `contrib/agents/wiki/api/federation.md` |
| API tests, Behat scenarios, fake test provider | `contrib/agents/wiki/api/testing.md` |
| Vue app, Express server, store, API client, node load balancing, Storybook | `contrib/agents/wiki/frontend/architecture.md` |

`contrib/agents/wiki/README.md` is the index. When you learn something non-obvious during a session, update the
relevant page (or add one and register it in both tables).

## Commands

All tooling runs inside containers via [Task](https://taskfile.dev) (`Taskfile.yml`); pass extra args after `--`.

```bash
docker compose up                 # nginx on :80 → http://cojedzie.localhost (api, frontend w/ Vite HMR, mercure, storybook)
task dependencies                 # composer install + yarn install
task test:api                     # phpunit (all API tests)
task test:api -- --filter JsonStreamingParserTest   # single test class/method
task test:api -- tests/ZtmGdansk  # single directory
task test:behat                   # Behat API contract scenarios (fresh SQLite test DB)
task test:behat -- features/departures              # single area
docker compose -f docker-compose.yml -f docker-compose.test.yml run --rm vitest   # frontend tests (vitest)
task yarn -- test:app --run test/container.test.ts                                # single frontend test
task lint                         # eslint (frontend)
task reformat                     # ECS --fix (api) + prettier --write (front)
task console -- <cmd>             # bin/console
task composer -- <cmd> / task yarn -- <cmd>
task api:update                   # import provider data
task api:migrations:generate      # diff migrations for sqlite, mysql AND postgres (needs those services up)
task benchmark:api                # phpbench
```

- `docker-compose.rr.yml` runs the API on RoadRunner instead of php-fpm; `docker-compose.federated.yml` adds a
  federated node; `docker-compose.elastic.yml` adds Elasticsearch/Kibana.
- `worker`/`schedule` services require `api/.env.local` to exist.
- `COJEDZIE_API_MODE` (`debug` default, `performance`) controls the API container runtime mode.
- Environment variables that control the app are namespaced (ADR 0011): `COJEDZIE_…`, or a subsystem's own prefix
  (`FEDERATION_…`, `SENTRY_…`, a provider's like `ZTM_GDANSK_…`). Standard names of Symfony, recipes and other
  tools (`APP_ENV`, `DATABASE_URL`, `MERCURE_…`, `CORS_ALLOW_ORIGIN`) stay as they are.

## Conventions

- **Commits**: Conventional Commits enforced by commitlint (`.husky/commit-msg`). Subject and body in
  *sentence case*, header ≤ 80 chars. Allowed scopes: `front`, `api`, `streaming-parser` (or none). Extra
  types beyond conventional: `security`, `release`. Use the `commit` skill.
- **Pre-commit hook** checks staged PHP files with ECS (excluding `api/config`) and staged JS/TS in `front/`
  with prettier — both via docker compose, so containers must be buildable.
- Source files carry the AGPL-3.0 license header (copy from a neighbouring file).
- PHP code style: ECS (`api/ecs.php`); Rector config in `api/rector.php`. Overridden methods use `#[\Override]`.
- Releases: run the `Release` workflow (`.github/workflows/release.yml`) with a version, see ADR 0005. It tags a
  tested `master` commit, builds and publishes all images, creates the GitHub Release with notes from
  `.utils/release-notes.sh`, and deploys to `production` after approval. No version is committed.
- CI (`.github/workflows/ci.yml`) runs the tests (job `test`, a required check) and builds the `next` images with
  `.utils/build-images.sh` on every push, pushing them only from `master`.
- `.github/workflows/deploy.yml` deploys images pinned by digest to any environment, reading the digests from the
  run that built them: `next` after each successful CI run on `master`, `production` when called by Release. Run
  it manually to redeploy.
