# 0012. Images are built with bake from per-service Dockerfiles

- **Status:** Accepted
- **Date:** 2026-10-30
- **Applies to:** `api/Dockerfile`, `api/docker/**`, `worker/**`, `standalone/**`, `front/Dockerfile`, `nginx/**`,
  `docker-bake.hcl`, `docker-compose.yml`, `contrib/bake-digests.sh`, `.github/workflows/**`, `Taskfile.yml`

## Context

Images were built by `.utils/build-images.sh` from Dockerfiles in `build/`. To keep layers small and cached, it
chained images: `base` (vendor and code) → `base-fpm`/`base-cli` → `api-fpm`/`worker`, and `base` → `api-rr` →
`standalone`. Each link was pushed through a throwaway registry in CI and copied to the publish registries
afterwards.

- The PHP extension setup was copied into five Dockerfiles, and the copies drifted apart. The dev RoadRunner image
  had no amqp or redis, and only the dev images set the timezone.
- The dev images (`api/Dockerfile`, `api/rr.Dockerfile`) shared nothing with the production ones. `worker` and
  `schedule` in dev used a published image from 2023 that could no longer run the API, so the stack didn't start.
- The compose stack was a base file plus overlay files. It needed manual steps before `up`: building the front
  server, writing `api/.env.local`, pointing the API at MySQL.

Constraints:
- The published images are used outside our deployment, so their names, tags and registries can't change:
  - `cojedzie/api` (default = rr, `-rr`, `-fpm`).
  - `cojedzie/standalone` (`-rr`).
  - `cojedzie/worker` and `cojedzie/front`.
- `deploy.yml` reads `digests.txt`.
- Dev keeps php-fpm behind nginx, because php-fpm needs no reload after a code change.

## Considered options

1. **Keep `build-images.sh` and the chained images, and fix the drift.** This is the smallest change. The
   duplication and the throwaway registry stay, and dev still has its own Dockerfiles.
2. **Multi-stage Dockerfiles per service, described by a `docker-bake.hcl`** (chosen). One stage installs the
   extensions. Images that extend another service's image (`worker`, `standalone`) get it as a named build
   context. Bake handles tags, registries, cache and secrets declaratively, and pushes directly.
3. **Separate dev Dockerfiles kept next to the production ones.** This was rejected: it is how dev and production
   drifted.

For the dev stack:
- **Profiles instead of overlay files.** RoadRunner can't be a profile, because profiles only add services, and
  running RoadRunner next to fpm was rejected because it should replace fpm. So it's a variable that switches the
  same dev image.
- **The federated node has its own SQLite database**, like a standalone node. A second MySQL database was rejected,
  because it needs extra grants and init scripts that only run on a fresh volume.

## Decision

We will build every image from the Dockerfile in its service's folder, and describe all published images in
`docker-bake.hcl`:

- **`api/Dockerfile`** is multi-stage:
  - `php-cli` and `php-fpm` run `api/docker/install-php.sh` (extensions, opcache/JIT, timezone).
  - `vendor` installs composer dependencies before `app` copies the code.
  - The production targets are `rr`, `cli` and `fpm`.
  - The `dev` target, which is the last stage, has xdebug, composer and the RoadRunner binary, and takes its code
    from a bind mount.
- **`worker/Dockerfile` and `standalone/Dockerfile`** start `FROM api`. Bake points that named context at
  `target:api-cli` and `target:api`.
- **`front/Dockerfile`** has a `dev` stage and a `prod` stage, and `prod` is last.
- **`docker-bake.hcl`:**
  - It maps targets to images.
  - Tags come from `REGISTRIES` × `TAGS` × the variant suffixes.
  - The `ci` group builds `api`, `worker` and `front`. The `release` group builds everything.
  - Each target has its own GHA cache scope.
  - The Sentry secret is attached only when the token is set.
  - CI and Release call it through `docker/bake-action` with `files: docker-bake.hcl` and `source: .`.
  - `contrib/bake-digests.sh` writes `digests.txt` from bake's metadata.
- **The dev stack is one `docker-compose.yml`:**
  - It builds the `dev` targets.
  - One-shot `api-setup` and `front-setup` services install dependencies and migrate before the long-running
    services start.
  - The extras are behind the profiles `postgres`, `federated` and `elastic`.
  - `COJEDZIE_DEV_SERVER=fpm|rr` picks php-fpm or RoadRunner.
  - The services run as `COJEDZIE_UID`.

## Consequences

- **One extension setup.** A change to the PHP setup is made once and reaches the dev and all production images.
- **No intermediate images.** CI needs no local registry or copy step, and bake builds the shared stages once.
- **Fresh clones.** A fresh clone starts with `docker compose up`. The first run imports provider data, which takes
  about 10 minutes.
- **Invoking bake.** Bake also reads `docker-compose.yml` by default, and its service names collide with bake
  target names. Every bake invocation therefore names the file.
- **Federated node.** It needs `federation:register` on the hub before it can join, as before.

## Compliance

- **Dockerfile locations.** A new or changed image lives in `<service>/Dockerfile`. There is no `build/` folder and
  no Dockerfile outside a service folder.
- **The PHP setup.** PHP extensions, ini settings and the timezone change only in `api/docker/install-php.sh`.
  Dev-only tooling (xdebug, git, su-exec, tini) is added only in the `dev` stage.
- **Stage order.** In `api/Dockerfile`, `dev` stays the last stage. In `front/Dockerfile`, `prod` stays last.
- **Extending another image.** An image that extends another service's image uses `FROM <name>` with a bake
  `contexts` entry, not a published tag of that image.
- **Published images.** A new image or variant is a target in `docker-bake.hcl` and is in the `release` group. A
  change to tag naming keeps every existing published tag.
- **Calling bake.** Workflows and docs call bake with the file named (`-f docker-bake.hcl` / `files:`).
- **Compose.** Dev services build a `dev` target of their service's Dockerfile, not a published image. Optional
  services get a `profiles:` entry instead of a separate compose file.
