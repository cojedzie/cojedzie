# 0041. The supported release line is released weekly by CI, and at once for serious vulnerabilities

- **Status:** Accepted
- **Date:** 2026-11-20
- **Applies to:** `.github/workflows/patch-release.yml`, `.github/workflows/release.yml`,
  `.github/workflows/dependabot.yml`, `.github/workflows/backport.yml`, `contrib/supported-line.sh`

## Context

Fixes and dependency updates reach the release branch of the supported line on their own (ADR 0040), but a patch
release still needs someone to start the `Release` workflow (ADR 0024) with the next version. With one maintainer,
backported fixes would wait on the branch until someone remembers them. Production is deployed only after an
approval in the `production` environment, so starting a release automatically doesn't put anything in production
without a person. A fix of a serious vulnerability shouldn't wait for a weekly release, though.

## Considered options

1. **Release by hand**: the state before; fixes wait for the maintainer to notice them.
2. **Release after every backport**: fixes ship as soon as possible, but every Dependabot group becomes a release
   and an approval request, several a week.
3. **Release on a weekly schedule when something is waiting, at once for serious vulnerabilities**: one release
   and one approval a week in the usual case, timed after Dependabot's weekly updates have been merged and
   backported; a high or critical vulnerability skips the wait.

For telling how serious a fix is, Dependabot's `fetch-metadata` reports a single CVSS score per PR, which doesn't
fit grouped updates. The repository's open Dependabot alerts carry a severity per package instead.

## Decision

We will use option 3:

- The `Patch release` workflow runs every Thursday at 06:00 UTC (and by hand). Dependabot proposes updates on
  Mondays, so they have been merged and backported by then.
- It releases the supported line (`contrib/supported-line.sh`): when `release/<line>` has commits since the line's
  newest tag, and the `test` and `test-front` checks passed on its head, it starts the `Release` workflow on that
  branch without a version, so the next patch is released (ADR 0024).
- With nothing to release, it does nothing. A head whose tests failed or haven't finished fails the run instead of
  releasing, so it isn't skipped silently.
- When CI passes on the supported line's release branch, the same workflow releases it at once if a PR merged into
  it since the line's newest tag is labelled `release-now`. It runs through `workflow_run`, so `master`'s copy of the
  workflow decides, not the release branch's.
- The Dependabot workflow labels an update it auto-merges `release-now` when one of its packages has an open
  Dependabot alert of **high** or **critical** severity. The Backport workflow copies the label to the backport PR.
  A maintainer may label any backport (or the original PR, before backporting) `release-now` for the same effect.
- A release started without a version skips itself when its commit already carries a tag of the line, so releases
  started close together don't release the same commits twice.
- The deployment to production keeps its approval; nothing is deployed without the maintainer.

## Consequences

- A backported fix reaches production within a week plus the time to approve; a high or critical one as soon as
  its backport has passed CI.
- Reading Dependabot alerts needs the merge app's Dependabot alerts (read) permission. Without it, the Dependabot
  workflow warns and nothing is labelled `release-now`.
- An update matched to an alert by package name may not reach the patched version; the release then just comes
  early.
- The release branch runs its own copy of the `Release` workflow, so changes to it must be backported too.
- A Thursday with a failing release branch produces a failed run, and the fixes wait for the next week or a manual
  run.

## Compliance

- Automatic releases start the `Release` workflow; they never tag, build or deploy by themselves.
- Only the release branch of the supported line is released automatically.
- An automatic release requires `test` and `test-front` to have passed on the commit it releases.
- Only `release-now` releases the line between the weekly runs.
- Nothing bypasses the approval of the `production` environment.
