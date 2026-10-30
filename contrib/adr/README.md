# Architecture Decision Records

This directory records the significant decisions behind Co Jedzie: what was decided, why, and what follows
from it. ADRs are written for every contributor, human or agent.

## When to write an ADR

Write one when a change:

- picks between real alternatives that someone could reasonably revisit later (a library, a pattern, a data
  model, a protocol),
- adds a rule the codebase should follow from now on ("all X go through Y", "never do Z in W"),
- changes or reverses an earlier ADR.

Don't write one for local implementation details that the code and the wiki already explain.

## Format

- One decision per file: `NNNN-short-title-in-kebab-case.md`, numbered sequentially (`0001-...`). Numbers are
  never reused.
- Start from [`template.md`](template.md).
- **Status** is one of `Proposed`, `Accepted`, `Deprecated` or `Superseded by NNNN`. Only `Accepted` ADRs are
  binding.
- An accepted ADR's decision is not edited afterwards. To change it, write a new ADR that supersedes it and
  update the old ADR's status. Typos, links and clarifications that don't change meaning are fine.
- **Only current ADRs (`Proposed`, `Accepted`) stay in this directory.** When an ADR becomes `Deprecated` or
  `Superseded`, set its status and move it to [`archive/`](archive/) in the same change (`git mv`, same
  number and file name). Then fix links to it, including the superseding ADR's link and the index below. This
  way the top-level directory is exactly the set of decisions that still matter.
- **Applies to** lists the paths or components the decision governs, and **Compliance** lists concrete,
  checkable rules. Together they let reviewers, and the `adr-compliance` agent skill, check a changeset
  against the decision. Keep the rules specific enough that a diff can be judged against them.

## Index

| ADR | Title | Status |
|---|---|---|
| [0001](0001-behavioural-tests-with-behat.md) | Behavioural tests with Behat | Accepted |
| [0002](0002-unicode-aware-sqlite-string-functions.md) | Unicode-aware string functions on SQLite | Accepted |
| [0003](0003-malformed-request-parameters-are-bad-requests.md) | Malformed request parameters are rejected with 400 | Accepted |
| [0004](0004-upstream-data-addresses-are-configuration.md) | Upstream data addresses are configuration | Accepted |
| [0005](0005-releases-are-made-by-ci-from-tags.md) | Releases are made by CI from tags | Accepted |
| [0006](0006-collections-are-php-ds-values-with-kadet-functional-pipelines.md) | Collections are php-ds values with kadet/functional pipelines | Accepted |
| [0007](0007-imported-rows-are-upserted-portably.md) | Imported rows are upserted portably, importers are tested on recorded feeds | Accepted |
| [0008](0008-federated-servers-authenticate-through-symfony-security.md) | Federated servers authenticate through Symfony Security | Accepted |
| [0009](0009-schedules-are-configured-by-environment-variables.md) | Schedules are configured by environment variables | Accepted |
| [0010](0010-stale-imported-data-is-collected-after-a-retention-period.md) | Stale imported data is collected after a retention period | Accepted |
| [0011](0011-environment-variables-are-namespaced.md) | Environment variables controlling the app are namespaced | Accepted |
| [0012](0012-images-are-built-with-bake-from-per-service-dockerfiles.md) | Images are built with bake from per-service Dockerfiles | Accepted |
| [0013](0013-frontend-state-in-pinia-saved-through-user-state-storage.md) | Frontend state lives in Pinia, saved through `UserStateStorage` | Accepted |
| [0014](0014-frontend-styles-tailwind-utilities-and-scss.md) | Frontend styles: Tailwind utilities, components in SCSS | Accepted |
| [0015](0015-icons-are-tabler-icons-behind-named-registry.md) | Icons are Tabler icons behind a named registry | Accepted |

<!-- Add a row for each new ADR: | [0001](0001-short-title.md) | Title | Accepted | -->

### Archived

| ADR | Title | Status |
|---|---|---|

<!-- Move rows here when archiving: | [0001](archive/0001-short-title.md) | Title | Superseded by [0002](0002-new-title.md) | -->
