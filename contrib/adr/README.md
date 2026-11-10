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
| [0006](0006-collections-are-php-ds-values-with-kadet-functional-pipelines.md) | Collections are php-ds values with kadet/functional pipelines | Accepted |
| [0007](0007-imported-rows-are-upserted-portably.md) | Imported rows are upserted portably, importers are tested on recorded feeds | Accepted |
| [0008](0008-federated-servers-authenticate-through-symfony-security.md) | Federated servers authenticate through Symfony Security | Accepted |
| [0011](0011-environment-variables-are-namespaced.md) | Environment variables controlling the app are namespaced | Accepted |
| [0012](0012-images-are-built-with-bake-from-per-service-dockerfiles.md) | Images are built with bake from per-service Dockerfiles | Accepted |
| [0013](0013-frontend-state-in-pinia-saved-through-user-state-storage.md) | Frontend state lives in Pinia, saved through `UserStateStorage` | Accepted |
| [0016](0016-maps-are-drawn-by-maplibre.md) | Maps are drawn by MapLibre | Accepted |
| [0017](0017-icons-are-tabler-icons-ours-on-tablers-grid.md) | Icons are Tabler icons behind a named registry, ours drawn on Tabler's grid | Accepted |
| [0019](0019-javascript-dependencies-are-managed-by-npm.md) | JavaScript dependencies are managed by npm | Accepted |
| [0020](0020-departure-history-is-stored-in-clickhouse.md) | Departure history is stored in ClickHouse | Accepted |
| [0021](0021-schedules-are-cron-expressions-or-intervals-from-environment-variables.md) | Schedules are cron expressions or intervals from environment variables | Accepted |
| [0023](0023-dependabot-merges-minor-and-patch-updates.md) | Dependabot merges minor and patch updates on its own | Accepted |
| [0024](0024-releases-are-made-by-ci-from-tags-on-master-or-release-branches.md) | Releases are made by CI from tags, on master or on release branches | Accepted |
| [0026](0026-the-import-is-a-graph-of-messages.md) | The import is a persisted graph of importers that may split into jobs | Accepted |
| [0029](0029-timetables-are-selected-by-calendar-or-service-day.md) | Timetables are selected by calendar day or service day | Accepted |
| [0030](0030-the-timetable-is-enriched-from-departure-history.md) | The timetable is enriched from departure history | Accepted |
| [0031](0031-non-passenger-tracks-are-stored-and-hidden-by-the-api.md) | Non-passenger tracks are stored, and hidden by the API | Accepted |
| [0033](0033-dates-and-times-in-the-frontend-are-temporal.md) | Dates and times in the frontend are Temporal | Accepted |
| [0034](0034-primitive-style-values-are-design-tokens.md) | Primitive style values are design tokens | Accepted |
| [0035](0035-component-styles-are-scoped-css-processed-by-tailwind.md) | Component styles are scoped CSS processed by Tailwind | Accepted |
| [0036](0036-parents-dont-style-inside-child-components.md) | Parents don't style inside child components | Accepted |
| [0037](0037-app-shell-navigates-between-feature-sections.md) | App shell: a top bar and a mobile bottom bar navigating between feature sections | Accepted |
| [0038](0038-stale-data-is-collected-by-retention-and-validity-with-a-grace.md) | Stale data is collected by retention and validity, with a grace | Accepted |
| [0039](0039-timetable-entries-are-valid-for-the-days-they-belong-to.md) | Timetable entries are valid for the days they belong to | Accepted |

<!-- Add a row for each new ADR: | [0001](0001-short-title.md) | Title | Accepted | -->

### Archived

| ADR | Title | Status |
|---|---|---|
| [0005](archive/0005-releases-are-made-by-ci-from-tags.md) | Releases are made by CI from tags | Superseded by [0024](0024-releases-are-made-by-ci-from-tags-on-master-or-release-branches.md) |
| [0009](archive/0009-schedules-are-configured-by-environment-variables.md) | Schedules are configured by environment variables | Superseded by [0021](0021-schedules-are-cron-expressions-or-intervals-from-environment-variables.md) |
| [0010](archive/0010-stale-imported-data-is-collected-after-a-retention-period.md) | Stale imported data is collected after a retention period | Superseded by [0027](archive/0027-stale-imported-data-is-collected-by-retention-and-validity.md) |
| [0014](archive/0014-frontend-styles-tailwind-utilities-and-scss.md) | Frontend styles: Tailwind utilities, components in SCSS | Superseded by [0035](0035-component-styles-are-scoped-css-processed-by-tailwind.md) |
| [0015](archive/0015-icons-are-tabler-icons-behind-named-registry.md) | Icons are Tabler icons behind a named registry | Superseded by [0017](0017-icons-are-tabler-icons-ours-on-tablers-grid.md) |
| [0018](archive/0018-component-styles-live-in-their-components.md) | Component styles live in their components | Superseded by [0035](0035-component-styles-are-scoped-css-processed-by-tailwind.md) |
| [0022](archive/0022-app-shell-with-top-bar-and-mobile-bottom-bar.md) | App shell: a top bar and a mobile bottom bar, built from one navigation list | Superseded by [0037](0037-app-shell-navigates-between-feature-sections.md) |
| [0025](archive/0025-imported-schedules-are-versioned-by-validity.md) | Imported schedules are versioned by validity | Superseded by [0039](0039-timetable-entries-are-valid-for-the-days-they-belong-to.md) |
| [0027](archive/0027-stale-imported-data-is-collected-by-retention-and-validity.md) | Stale imported data is collected by retention and validity | Superseded by [0038](0038-stale-data-is-collected-by-retention-and-validity-with-a-grace.md) |
| [0032](archive/0032-calendar-dates-are-temporal-plain-dates.md) | Calendar dates are Temporal plain dates | Superseded by [0033](0033-dates-and-times-in-the-frontend-are-temporal.md) |

<!-- Move rows here when archiving: | [0001](archive/0001-short-title.md) | Title | Superseded by [0002](0002-new-title.md) | -->
