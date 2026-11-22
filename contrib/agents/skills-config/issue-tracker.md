# Issue tracker: GitHub

Issues for this repo live in the GitHub Issues of `cojedzie/cojedzie`. Use the `gh` CLI for all operations. Specs
keep living in `contrib/agents/specs/` (see `AGENTS.md`), never in an issue.

The repository is public: issues are visible to anyone. Issues outlive specs and plans, so reference ADRs, wiki pages,
PRs and other issues (`#N`) rather than a spec that is dropped when its task is done.

## Conventions

- **Create an issue**: `gh issue create --title "..." --body "..." --label "..."`, then set its type (below). Use a
  heredoc for multi-line bodies. GitHub renders single newlines in issue bodies as line breaks, so don't hard-wrap
  paragraphs. Titles start with the components they touch: `API: …`, `Front: …`, `API, front: …`, `CI: …`.
- **Type**: every issue has a GitHub issue type instead of a category label: `Bug`, `Feature` (new things and
  changes) or `Task` (chores). `gh issue create` can't set it, so run
  `gh api -X PATCH repos/cojedzie/cojedzie/issues/<n> -f type=Feature` afterwards. Filter with
  `gh issue list --search "type:Bug"`.
- **Labels**: one triage label (see `triage-labels.md`), plus what the issue is about, with as many of each as fit:
  - `component:api`, `component:front`, `component:infra` (CI, Docker, releases, deploys);
  - `area:departures` (live departures), `area:timetable` (planned service, timetable pages, stop sets),
    `area:network` (stops, lines, tracks and finding them), `area:history` (departure history, ClickHouse),
    `area:providers` (provider integrations, data import), `area:federation`, `area:ui` (design tokens, shared
    components, Storybook, i18n);
  - `location:trójmiasto` only when the issue is specific to that network (e.g. ZTM Gdańsk data).

  A closed issue keeps its type and these labels, only the triage label goes.
- **Automatic labels**: the `Issues` workflow adds the component labels a new issue's title prefix names, and the
  area and location the reporter picked in the form. The `Labeler` workflow labels PRs by the paths they change
  (`.github/labeler.yml`). Areas and locations it can't tell are set during triage.
- **Features**: a feature with several tickets gets a parent issue holding the decisions they share; tickets are its
  sub-issues. Related issues that aren't part of the work are linked with a `Related: #N` line.
- **Read an issue**: `gh issue view <number> --json number,title,body,labels,comments`.
- **List issues**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'`
  with appropriate `--label` and `--state` filters.
- **Make an issue a sub-issue of a parent**: `gh issue create --parent <parent> ...`, or
  `gh issue edit <parent> --add-sub-issue <child>` afterwards.
- **Blocking**: GitHub's native issue dependencies (see **Blocking** under Wayfinding operations), plus a
  `Blocked by: #N, #N` line at the top of the body for readers.
- **Comment on an issue**: `gh issue comment <number> --body "..."`. Progress notes (branch started, PR opened,
  follow-ups found) go in comments.
- **Apply / remove labels**: `gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close**: a PR that implements the issue says `Closes #N` in its body, so merging closes it. Otherwise
  `gh issue close <number> --comment "..."`.

## Issues filed by people

People file issues from the web through the forms in `.github/ISSUE_TEMPLATE/` (blank issues are off): a bug report
or a feature, typed `Bug` / `Feature` and labelled `needs-triage`. The `Issues` workflow (`.github/workflows/issues.yml`)
adds `needs-triage` to any issue opened without a triage label, and removes the triage labels when an issue is
closed. So `gh issue list --label needs-triage` is the whole triage queue, wherever an issue came from. Reporters may
write in Polish; keep their wording when quoting them, and write the triage notes in English.

## Pull requests as a triage surface

**PRs as a request surface: no.** _(Set to `yes` if this repo treats external PRs as feature requests; `/triage`
reads this flag.)_

When set to `yes`, PRs run through the same labels and states as issues, using the `gh pr` equivalents:

- **Read a PR**: `gh pr view <number> --comments` and `gh pr diff <number>` for the diff.
- **List external PRs for triage**:
  `gh api --paginate 'repos/{owner}/{repo}/pulls?state=open' --jq '.[] | select(.author_association | IN("OWNER","MEMBER","COLLABORATOR") | not) | {number, title, author: .user.login, author_association, labels: [.labels[].name]}'`.
- **Comment / label / close**: `gh pr comment`, `gh pr edit --add-label`/`--remove-label`, `gh pr close`.

GitHub shares one number space across issues and PRs, so a bare `#42` may be either: resolve with `gh pr view 42` and
fall back to `gh issue view 42`.

## When a skill says "publish to the issue tracker"

Create a GitHub issue. A spec goes to `contrib/agents/specs/` instead, and its issue (created, or the one the spec was
written for) is updated so it stands on its own once the spec is dropped. Don't link the spec. The body has these
sections:

- **Summary**: the problem and the solution from the rider's point of view, a few paragraphs.
- **User stories**: the 5–6 most telling stories of the spec, not the whole list.
- **Decisions**: the implementation decisions an implementer needs, without file paths (names of modules, routes and
  components are fine).
- **UX/UI brief**, only when the look or interaction still has to be designed: the context (pages, layout, the
  surrounding shell), the content to show, what to decide and draw, the constraints (design tokens, accessibility,
  widths) and the deliverables (mockups at phone and desktop width, states). The issue then gets `ready-for-ux`
  (see `triage-labels.md`) instead of `ready-for-agent`.
- **Acceptance criteria** and **Out of scope**.

Keep the `Parent:` line, and a `Blocked by:` line only while a blocker is open.

## When a skill says "fetch the relevant ticket"

Read it as in **Read an issue** above.

## Wayfinding operations

Used by `/wayfinder`. The **map** is a single issue with **child** issues as tickets.

- **Map**: a single issue labelled `wayfinder:map`, holding the Notes / Decisions-so-far / Fog body.
  `gh issue create --label wayfinder:map`.
- **Child ticket**: an issue linked to the map as a GitHub sub-issue (see **Make an issue a sub-issue of a parent**).
  Labels: `wayfinder:<type>` (`research`/`prototype`/`grilling`/`task`). Once claimed, the ticket is assigned to the
  driving dev.
- **Blocking**: GitHub's **native issue dependencies**. Add an edge with
  `gh api --method POST repos/cojedzie/cojedzie/issues/<child>/dependencies/blocked_by -F issue_id=<blocker-db-id>`,
  where `<blocker-db-id>` is the blocker's numeric **database id** (`gh api repos/cojedzie/cojedzie/issues/<n> --jq .id`,
  _not_ the `#number` or `node_id`). GitHub reports `issue_dependencies_summary.blocked_by` (open blockers only, the
  live gate). A ticket is unblocked when every blocker is closed.
- **Frontier query**: list the map's open children (`gh api repos/cojedzie/cojedzie/issues/<map>/sub_issues`), drop
  any with an open blocker (`issue_dependencies_summary.blocked_by > 0`) or an assignee; first in map order wins.
- **Claim**: `gh issue edit <n> --add-assignee @me`, the session's first write.
- **Resolve**: `gh issue comment <n> --body "<answer>"`, then `gh issue close <n>`, then append a context pointer
  (gist + link) to the map's Decisions-so-far.
