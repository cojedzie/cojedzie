# Issue tracker: Local Markdown

Issues for this repo live as markdown files in `contrib/agents/issues/`. Specs keep living in
`contrib/agents/specs/` (see `AGENTS.md`).

Issues are **temporary**, like specs and plans: commit them in their own commits, separate from code, distill
anything worth keeping into the wiki or an ADR when the task is complete, and drop them from the history before
merging. They must not reach `master`.

## Conventions

- One feature per directory: `contrib/agents/issues/<feature-slug>/`
- The spec is `contrib/agents/specs/YYYY-MM-DD-<feature-slug>-design.md` (superpowers file naming), never inside
  the issues directory. Reference it from the issues by path.
- Implementation issues are one file per ticket at `contrib/agents/issues/<feature-slug>/<NN>-<slug>.md`, numbered
  from `01`, never a single combined tickets file
- Triage state is recorded as a `Status:` line near the top of each issue file (see `triage-labels.md` for the role
  strings)
- Comments and conversation history append to the bottom of the file under a `## Comments` heading

## When a skill says "publish to the issue tracker"

Create a new file under `contrib/agents/issues/<feature-slug>/` (creating the directory if needed). A spec goes to
`contrib/agents/specs/` instead.

## When a skill says "fetch the relevant ticket"

Read the file at the referenced path. The user will normally pass the path or the issue number directly.

## Wayfinding operations

Used by `/wayfinder`. The **map** is a file with one **child** file per ticket.

- **Map**: `contrib/agents/issues/<effort>/map.md` (the Notes / Decisions-so-far / Fog body).
- **Child ticket**: `contrib/agents/issues/<effort>/<NN>-<slug>.md`, numbered from `01`, with the question in the
  body. A `Type:` line records the ticket type (`research`/`prototype`/`grilling`/`task`); a `Status:` line records
  `claimed`/`resolved`.
- **Blocking**: a `Blocked by: NN, NN` line near the top. A ticket is unblocked when every file it lists is
  `resolved`.
- **Frontier**: scan `contrib/agents/issues/<effort>/` for ticket files that are open, unblocked, and unclaimed;
  first by number wins.
- **Claim**: set `Status: claimed` and save before any work.
- **Resolve**: append the answer under an `## Answer` heading, set `Status: resolved`, then append a context pointer
  (gist + link) to the map's Decisions-so-far in `map.md`.
