# Issue tracker: Local Markdown

Issues for this repo live as markdown files in `contrib/agents/issues/`. Specs keep living in
`contrib/agents/specs/` (see `AGENTS.md`).

The directory is an **Obsidian vault kept out of git** (`/contrib/agents/issues/` in `.gitignore`): issues are
local to the maintainer's machine and never committed. `INDEX.md` lists the features, each feature directory has
its own `INDEX.md` listing its tickets; link tickets with relative markdown links. Issues outlive specs and plans, so
reference ADRs and wiki pages rather than a spec that is dropped when its task is done.

## Conventions

- One feature per directory: `contrib/agents/issues/<feature-slug>/`, listed in `contrib/agents/issues/INDEX.md`
- The spec is `contrib/agents/specs/YYYY-MM-DD-<feature-slug>-design.md` (superpowers file naming), never inside
  the issues directory. While the task runs, issues may reference it by path.
- Implementation issues are one file per ticket at `contrib/agents/issues/<feature-slug>/<NN>-<slug>.md`, numbered
  from `01`, never a single combined tickets file
- Triage state is recorded as a `Status:` line near the top of each issue file (see `triage-labels.md` for the role
  strings)
- Comments and conversation history append to the bottom of the file under a `## Comments` heading

## When a skill says "publish to the issue tracker"

Create a new file under `contrib/agents/issues/<feature-slug>/` (creating the directory and its `INDEX.md` if needed)
and add it to the indexes. A spec goes to
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
