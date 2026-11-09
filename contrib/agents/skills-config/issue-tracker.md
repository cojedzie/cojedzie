# Issue tracker: GitHub

Issues for this repo live in the GitHub Issues of `cojedzie/cojedzie`. Use the `gh` CLI for all operations. Specs
keep living in `contrib/agents/specs/` (see `AGENTS.md`), never in an issue.

The repository is public: issues are visible to anyone. Issues outlive specs and plans, so reference ADRs, wiki pages,
PRs and other issues (`#N`) rather than a spec that is dropped when its task is done.

## Conventions

- **Create an issue**: `gh issue create --title "..." --body "..." --label "..."`. Use a heredoc for multi-line
  bodies. GitHub renders single newlines in issue bodies as line breaks, so don't hard-wrap paragraphs.
- **Labels**: one category label (`bug`, `enhancement`, `documentation`, ...) and one triage label (see
  `triage-labels.md`). A closed issue keeps only its category label.
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

Create a GitHub issue. A spec goes to `contrib/agents/specs/` instead.

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
