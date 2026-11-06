# 0023. Dependabot merges minor and patch updates on its own

- **Status:** Accepted
- **Date:** 2026-11-06
- **Applies to:** `.github/dependabot.yml`, `.github/workflows/dependabot.yml`, `.github/workflows/ci.yml`

## Context

Only Dependabot's security updates were enabled, and their PRs never merged: the `build` job of CI logged in to
the registry with secrets that runs started by Dependabot can't read, so every Dependabot PR failed. Alerts piled
up (20 open). Most were in build-time dependencies, and the worst ones came from a plugin abandoned years ago,
which no update could fix.

The project has one maintainer. Updates that need a review per PR don't get reviewed, so they have to be safe to
take without one. CI (`test`, `test-front`, `build`) covers the API, the frontend and the images.

## Considered options

1. **Security updates only, merged by hand**: the state before; updates waited for weeks and dependencies drifted
   until a security fix needed a major upgrade.
2. **Merge every update automatically**: majors break APIs often enough that CI alone shouldn't decide them.
3. **Version updates for every ecosystem, minor and patch merged automatically once CI passes, majors
   reviewed**: the routine updates take no time, the risky ones still get a human.
4. **Renovate**: more options (automerge built in, schedules, presets), but a third-party app with its own config;
   Dependabot's grouping and cooldown are now enough.

## Decision

We will use option 3:

- Dependabot proposes version updates for `front/` and root npm, `api/` composer, GitHub Actions and the base
  images. Minor and patch updates are grouped per ecosystem; majors get a PR each.
- Updates are proposed only after a cooldown (3 days, 7 for majors), so a compromised or broken release is likely
  to be pulled before it reaches us. Security updates skip the cooldown.
- `.github/workflows/dependabot.yml` enables auto-merge (rebase) for minor and patch updates whose PR reports no
  maintainer changes. GitHub merges them when the required checks pass. Majors and updates with new maintainers
  wait for a review.
- The merge uses a GitHub App token, not `GITHUB_TOKEN`: a merge made with `GITHUB_TOKEN` doesn't start the `push`
  workflows on `master`, so the `next` images wouldn't be built or deployed. Its credentials are the Dependabot
  secrets `DEPENDABOT_MERGE_APP_CLIENT_ID` and `DEPENDABOT_MERGE_APP_PRIVATE_KEY`.
- CI jobs must pass without secrets on branches other than `master`, since Dependabot's runs don't get them.

## Consequences

- Merged updates are deployed to `next` like any other change; production still needs a release (ADR 0024).
- What auto-merge accepts is only as good as the required checks. A check that isn't required doesn't hold a merge
  back, so the branch protection of `master` must require `test`, `test-front` and `build`.
- Dependencies Dependabot can't update (build arguments such as `PHP_VERSION`, compose service images) stay
  manual.
- The merge app needs the Contents, Pull requests and Workflows permissions (the last one for updates of
  GitHub Actions).

## Compliance

- A CI job required on `master` doesn't use secrets on other branches (guard the step with
  `if: github.ref == 'refs/heads/master'` or make it work without them).
- Auto-merge is enabled only for `version-update:semver-minor` and `version-update:semver-patch` updates without
  maintainer changes.
- Dependabot commit messages follow commitlint: prefixes `build(front)`, `build(api)`, `build` or `ci`.
