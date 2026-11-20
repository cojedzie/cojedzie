# 0040. Fixes are backported to the supported release line by CI

- **Status:** Accepted
- **Date:** 2026-11-20
- **Applies to:** `.github/workflows/backport.yml`, `.github/workflows/dependabot.yml`, rules of `release/*` branches

## Context

Dependabot merges minor and patch updates into `master` on its own (ADR 0023), security updates included. They
reach production only with the next release, and a release from `master` also carries every feature merged since
the last one, which may not be ready. ADR 0024 allows patch releases from `release/YYYY.N` branches, but bringing a
fix there by hand is easy to forget and, with one maintainer, rarely happens for dependency updates.

## Considered options

1. **Release `master` more often**: fixes ship sooner, but so does unfinished work, and the release still needs a
   person.
2. **Dependabot `target-branch` on the release branch**: Dependabot opens updates against the release branch
   itself. Security updates always target the default branch, though, and the configuration would have to name a
   new branch for every new line.
3. **Cherry-pick merged PRs to the release branch of the newest line**: one mechanism for Dependabot updates and
   human fixes, chosen per PR by a label; a cherry-pick can conflict, mostly on lock files. The cherry-picking
   itself can be our own script or `korthout/backport-action`, which already picks the right commits for each
   merge method, enables auto-merge and commits conflicts into a draft PR.

## Decision

We will use option 3:

- The **supported line** is the line `YYYY.N` of the newest `v*` tag. Only it gets backports. When `master` releases
  a new line, the supported line moves to it.
- A PR merged into `master` with the `backport` label is backported: the Backport workflow cherry-picks its merged
  commits onto `release/<supported line>` with `korthout/backport-action` (pinned by commit) and opens a PR against
  that branch with auto-merge (rebase) and the original title. If the release branch doesn't exist yet, the
  workflow cuts it from the line's newest tag first.
- The Dependabot workflow adds the `backport` label to exactly the updates it auto-merges (ADR 0023): minor and
  patch, without maintainer changes. Majors and updates with new maintainers are never backported automatically.
- A cherry-pick that conflicts becomes a draft PR with the first conflict committed, and the original PR gets a
  comment on how to finish it. Draft PRs don't auto-merge.
- Branches and PRs are made with the merge app's token, so CI runs on the backport branch.
- The workflow runs on `pull_request` (`closed`), not `pull_request_target`: the PRs we backport come from branches
  of this repository, so the run has the secrets, and public repositories need an extra Actions policy to run
  `pull_request_target`. PRs from forks aren't backported automatically.
- `release/*` branches are protected by a ruleset like `master`: changes come through PRs, the checks `test`,
  `test-front` and `build` are required, force pushes and deletion are blocked.

## Consequences

- Dependency fixes reach the supported line without manual work; releasing them is still a separate step
  (ADR 0024).
- Lock files of `master` and the release branch drift apart, so backports of grouped dependency updates conflict
  more as a line ages. For a conflicting dependency update, redoing the update on the backport branch is often
  simpler than resolving the lock file.
- A release branch cut from a tag carries that tag's workflows, which may lack CI changes made since (ADR 0024);
  such changes need the `backport` label too.
- The merge app's credentials (`DEPENDABOT_MERGE_APP_CLIENT_ID`, `DEPENDABOT_MERGE_APP_PRIVATE_KEY`) are needed as
  Actions secrets as well as Dependabot secrets.
- Auto-merge into a release branch is only as safe as its ruleset: without required checks, GitHub merges the
  backport PR at once.

## Compliance

- The Backport workflow runs on `pull_request`, not `pull_request_target`, and only for merged PRs.
- Backports target only `release/<line of the newest v* tag>`.
- The Dependabot workflow labels a PR `backport` only when it enables auto-merge for it.
- Pushes and PRs of the Backport workflow use the app token, never `GITHUB_TOKEN`.
- CI on `release/*` branches doesn't push images or deploy to `next`.
