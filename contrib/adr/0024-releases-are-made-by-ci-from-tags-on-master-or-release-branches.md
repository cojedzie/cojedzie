# 0024. Releases are made by CI from tags, on master or on release branches

- **Status:** Accepted
- **Supersedes:** [0005](archive/0005-releases-are-made-by-ci-from-tags.md)
- **Date:** 2026-11-06
- **Applies to:** `.github/workflows/**`, `docker-bake.hcl`, `contrib/release-notes.sh`, version metadata in
  `api/composer.json` and `front/package.json`

## Context

ADR 0005 moved releases to CI: the `Release` workflow tags a tested `master` commit, builds all images and
deploys them to production. Releases could be made only from `master`.

A bug was found in production (the frontend reconnected to the Mercure hub every 1–2 seconds, forever) while
`master` already had unreleased changes that were not ready for production. Fixing production meant either
releasing all of `master` or not fixing it at all.

## Considered options

1. **Release only from `master` (keep ADR 0005).** Simple, but a fix waits for everything merged before it.
2. **Release from any branch.** Flexible, but versions and history stop following any order, and a release can
   come from a branch nobody reviewed.
3. **Release from `master` or from a `release/YYYY.N` branch cut from a tag, which only releases patches of its
   own line.** Hotfixes need a branch and a backport, but versions stay ordered and every release comes from a
   protected, reviewed branch.

## Decision

We will release with the `Release` workflow, started manually with a version (option 3):

- A version is `YYYY.N.P`. The tag `vYYYY.N.P` is the only place the version is recorded. Builds read it with
  `git describe`; no version is committed to `composer.json`, `package.json` or a changelog file.
- Releases are made from a commit on which the `test` and `test-front` checks have passed, from either:
  - `master`, with a version newer than every `v*` tag, or
  - a release branch `release/YYYY.N`, with a version `YYYY.N.P` newer than every `vYYYY.N.*` tag.
- A release branch is cut from the tag of the release that needs a fix (`release/2026.2` from `v2026.2.0`), only
  when one is needed. It takes fixes, and the CI changes needed to build and release them, through pull requests.
  Every fix made on a release branch is also made on `master`, so the next release from `master` keeps it.
- Images are built by CI only, all images and variants, tagged `YYYY.N.P` and `YYYY.N`, and pushed to
  registry.kadet.net and Docker Hub. Only a release newer than every `v*` tag is also tagged `latest`, is marked
  latest on GitHub and is deployed to production; a patch of an older line is published but not deployed.
- Release notes are generated from Conventional Commits (`contrib/release-notes.sh`) into the GitHub Release,
  covering the commits since the latest tag on the released branch.
- Production is deployed by the workflow in the `production` environment, which requires approval, with every
  image pinned by digest. `next` is deployed the same way from every `master` push, building only the images it
  runs. Release branches never deploy to `next`.

## Consequences

- A hotfix is a release branch, a pull request into it, a release and an approval. The fix must also be
  brought to `master`, which is easy to forget.
- A release from a branch uses that branch's copy of the workflows, so a release branch cut from an older tag
  may first need the CI changes made on `master` since (a runner image update, a change to the release
  workflow itself).
- History has no release commits; the version of a checkout is `git describe --tags`.
- `CHANGELOG.md` is no longer updated; release notes live in GitHub Releases.
- Releasing needs `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` secrets in addition to the registry ones.
- Deploying through the API passes image versions as extra variables for that run only; the versions in the
  server configuration are a fallback for full playbook runs and must not point to an older release.
- Release branches should be protected like `master` (pull requests, required `test` check).

## Compliance

- No workflow or script writes a version into `api/composer.json`, `front/package.json` or `CHANGELOG.md`.
- Release images are built and pushed only by `.github/workflows/release.yml`, which runs only on `master` and
  `release/YYYY.N` branches and rejects a version outside the branch's line.
- Only a release newer than every `v*` tag gets the `latest` image tag and is deployed to production.
- Deployments pass image references with a digest (`tag@sha256:…`), not a bare tag.
- Commits use Conventional Commit types (`feat`, `fix`, `perf`, `security`, ...) so release notes can be
  generated from them.
