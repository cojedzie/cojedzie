# 0005. Releases are made by CI from tags

- **Status:** Proposed
- **Date:** 2026-10-26
- **Applies to:** `.github/workflows/**`, `.utils/build-images.sh`, `.utils/release-notes.sh`, version metadata in
  `api/composer.json` and `front/package.json`

## Context

Releases used to be made by hand: `bump-version.sh` wrote the version into `composer.json` and `package.json`,
committed `release: X` and tagged it; images were built and pushed from a developer's machine, and production
was switched to the new version in the server configuration.

- Images built locally depended on the state of the working copy: they shipped files that are ignored by git
  (e.g. `front/public/images`) and that CI builds don't have.
- The committed version fields and `CHANGELOG.md` went stale (versions stuck at 2023.1.4, changelog at
  2022.3.1), while images already took their version from `git describe` (`COJEDZIE_VERSION`).
- `master` requires the `test` check, so CI can't commit a version bump without a token that bypasses it.
- Deploying by a floating tag allowed a service to keep running an older image cached under the same tag.

## Considered options

1. **Keep manual releases.** No work, but releases stay error-prone and depend on a local environment.
2. **CI commits a version bump and changelog, then tags.** Keeps version fields in the repository, but needs a
   branch-protection bypass and adds a commit per release that carries no code.
3. **CI tags a tested `master` commit; the tag is the only version.** No commits, notes are generated from
   Conventional Commits into the GitHub Release.

## Decision

We will release with the `Release` workflow (option 3), started manually with a version:

- A version is `YYYY.N.P` and must be newer than the latest `v*` tag. Releases are made from `master`, from a
  commit on which the `test` check has passed.
- The tag `vYYYY.N.P` is the only place the version is recorded. Builds read it with `git describe`; no version
  is committed to `composer.json`, `package.json` or a changelog file.
- Images are built by CI only, all images and variants, tagged `YYYY.N.P`, `YYYY.N` and `latest`, and pushed
  to registry.kadet.net and Docker Hub.
- Release notes are generated from Conventional Commits (`.utils/release-notes.sh`) into the GitHub Release.
- Production is deployed by the workflow in the `production` environment, which requires approval, with every
  image pinned by digest. `next` is deployed the same way from every `master` push, building only the images
  it runs.

## Consequences

- A release is one click plus an approval; nothing depends on a local working copy.
- History has no release commits; the version of a checkout is `git describe --tags`.
- `CHANGELOG.md` is no longer updated; new release notes live in GitHub Releases.
- Releasing needs `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` secrets in addition to the registry ones.
- Deploying through the API passes image versions as extra variables for that run only; the versions in the
  server configuration are a fallback for full playbook runs and must not point to an older release.

## Compliance

- No workflow or script writes a version into `api/composer.json`, `front/package.json` or `CHANGELOG.md`.
- Release images are built and pushed only by `.github/workflows/release.yml`.
- Deployments pass image references with a digest (`tag@sha256:…`), not a bare tag.
- Commits use Conventional Commit types (`feat`, `fix`, `perf`, `security`, ...) so release notes can be
  generated from them.
