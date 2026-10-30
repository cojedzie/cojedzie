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
  4. **Clean up the history**: rebase the branch so that every commit is one logical change, described by what
     changed and why, not by when the work happened. Fold review fixes, follow-ups and tidy-ups into the commits
     they belong to, and drop the spec/plan commits (and any later edits to those files), so the docs never
     appear in the merged history. Rewriting history and force-pushing need the user's confirmation.
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
docker compose up                 # nginx on :80 → http://cojedzie.localhost (the whole dev stack, see below);
                                  # the first run installs dependencies and imports data
task setup                        # composer install + yarn install + migrate/import, without starting the stack
task test                         # test:api, then test:behat (both force SQLite var/db/test.db)
task test:api                     # phpunit (all API tests)
task test:api -- --filter JsonStreamingParserTest   # single test class/method
task test:api -- tests/ZtmGdansk  # single directory
task test:behat                   # Behat API contract scenarios (fresh SQLite test DB)
task test:behat -- features/departures              # single area
task yarn -- test:app --run                                # frontend tests (vitest)
task yarn -- test:app --run test/container.test.ts         # single frontend test
task lint                         # eslint (frontend)
task reformat                     # ECS --fix (api) + prettier --write (front)
task console -- <cmd>             # bin/console
task composer -- <cmd> / task yarn -- <cmd>
task api:update                   # import provider data
task api:migrations:generate      # diff migrations for sqlite, mysql AND postgres (starts mysql and postgres itself)
task benchmark:api                # phpbench
```

- The dev stack is the single `docker-compose.yml`, built from the `dev` targets of the Dockerfiles. Default
  services: `nginx`, `api`, `worker`, `schedule`, `frontend`, `storybook`, `mercure`, `mysql`. The one-shot
  `api-setup` (`composer install`, migrate, import) and `front-setup` (`yarn install`, `build:server`) run first.
  The first `up` imports provider data, which takes about 10 minutes. MySQL is the dev database.
- Profiles: `--profile postgres`, `--profile elastic` (Elasticsearch/Kibana) and `--profile federated` (a second
  node with its own SQLite database `api/var/db/federated.db`). The node must be registered on the hub first:
  `task console -- federation:register <email> http://federated:8080`, then pass the printed id and secret as
  `FEDERATION_SERVER_ID` and `FEDERATION_SERVER_SECRET`. It restarts up to 5 times (`restart: on-failure:5`) to ride out a race with nginx; unregistered credentials never succeed.
- `COJEDZIE_DEV_SERVER=rr` runs the API on RoadRunner instead of php-fpm (same image); nginx then loads
  `nginx/rr.conf` instead of `nginx/fpm.conf`. nginx resolves upstreams lazily, so a stopped service doesn't take it
  down.
- `COJEDZIE_UID` (default 1000) is the uid the PHP and node services run as, so files in the bind mounts belong to
  you. Set it when your uid differs.
- `api/.env.local` is optional; when present it is loaded by the api services.
- `task console` and `task api:update` run with `--no-deps`, so MySQL must be running (`docker compose up -d mysql`
  or the whole stack).
- Storybook runs with `--no-version-updates`: its version check crashes on Node 24.
- FontAwesome Pro packages aren't installable without a token: place them in `front/resources/fontawesome` by hand
  (git-ignored, CI uses `.github/actions/fontawesome`).
- `COJEDZIE_API_MODE` (`debug` default, `performance`) controls the API container runtime mode.
- Environment variables that control the app are namespaced (ADR 0011): `COJEDZIE_…`, or a subsystem's own prefix
  (`FEDERATION_…`, `SENTRY_…`, a provider's like `ZTM_GDANSK_…`). Standard names of Symfony, recipes and other
  tools (`APP_ENV`, `MERCURE_…`, `CORS_ALLOW_ORIGIN`) stay as they are; the database is `COJEDZIE_DATABASE_URL`.

## Conventions

- **Commits**: Conventional Commits enforced by commitlint (`.husky/commit-msg`). Subject and body in
  *sentence case*, header ≤ 80 chars. Allowed scopes: `front`, `api`, `streaming-parser` (or none). Extra
  types beyond conventional: `security`, `release`. Use the `commit` skill.
- **Pre-commit hook** checks staged PHP files with ECS (excluding `api/config`) and staged JS/TS in `front/`
  with prettier — both via docker compose, so containers must be buildable.
- Source files carry the AGPL-3.0 license header (copy from a neighbouring file).
- PHP code style: ECS (`api/ecs.php`); Rector config in `api/rector.php`. Overridden methods use `#[\Override]`.
- Releases: run the `Release` workflow (`.github/workflows/release.yml`) with a version, see ADR 0005. It tags a
  tested `master` commit, builds and publishes all images (`docker-bake.hcl`, see the Docker section of the API
  architecture page), creates the GitHub Release with notes from
  `contrib/release-notes.sh`, and deploys to `production` after approval. No version is committed.
- CI (`.github/workflows/ci.yml`) runs the tests (job `test`, a required check) and builds the `next` images with
  `docker-bake.hcl` on every push, pushing them only from `master`.
- `.github/workflows/deploy.yml` deploys images pinned by digest to any environment, reading the digests from the
  run that built them: `next` after each successful CI run on `master`, `production` when called by Release. Run
  it manually to redeploy.
