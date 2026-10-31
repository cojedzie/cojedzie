# 0019. JavaScript dependencies are managed by npm

- **Status:** Accepted
- **Date:** 2026-10-31
- **Applies to:** `front/package.json`, `front/package-lock.json`, `package.json`, `package-lock.json`,
  `front/Dockerfile`, `.github/workflows/ci.yml`, `Taskfile.yml`, `docker-compose.yml`

## Context

The frontend and the root package (commitlint, husky) were installed with Yarn 1, which is frozen and no longer
developed. In the front image it cost a lot: Yarn 1 downloads every package of the lockfile even with
`--production`, and `yarn install` left its download cache (2.3 GB uncompressed) in the layer next to
`node_modules`. The released image only copies `node_modules`, but CI exports every intermediate layer to the
GitHub Actions cache (`mode=max`, ADR 0012). Each master build uploaded two ~1.2 GB layers, which took about four
of the build's six minutes and filled the 10 GB cache, so the install layers were evicted and missed on the next
run.

## Considered options

1. **Keep Yarn 1, clear its cache in the layer** (`yarn cache clean` or a cache mount). Fixes the image, but
   keeps a package manager that gets no updates.
2. **Yarn 4 (Berry).** Maintained, but it means Corepack, a `.yarnrc.yml`, a choice between PnP and
   `node_modules`, and one more tool next to the npm that ships with Node.
3. **npm** (chosen). Ships with the `node` images, `npm ci` gives reproducible installs from
   `package-lock.json`, `--omit=dev` installs only production dependencies, and its download cache lives outside
   the project.

## Decision

We will manage JavaScript dependencies with npm: `package-lock.json` is the lockfile, installs in images and CI
use `npm ci`, and scripts are run with `npm run`.

## Consequences

- **Small cached layers.** Image builds mount npm's cache (`/root/.npm`) as a BuildKit cache, so the install
  layers hold only `node_modules`.
- **Peer dependencies are installed.** npm installs missing peer dependencies, which Yarn 1 did not, so the
  lockfiles contain a few more packages (e.g. `@types/react`).
- **Arguments to scripts need `--`.** `npm run test:app -- --run`, not `yarn test:app --run`.
- **`task npm`** replaces `task yarn` for running package manager commands in the frontend container.

## Compliance

- **No Yarn.** There is no `yarn.lock` in the repository, and Dockerfiles, workflows, the Taskfile and compose
  files don't call `yarn`.
- **Locked installs.** Images and CI install with `npm ci`, never `npm install`; the dev `front-setup` service is
  the only place that uses `npm install`.
- **Cache outside layers.** Every `npm ci` in a Dockerfile mounts `/root/.npm` as a cache
  (`RUN --mount=type=cache,target=/root/.npm`).
