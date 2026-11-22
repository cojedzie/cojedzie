# Triage Labels

The skills speak in terms of five canonical triage roles, to which we add one of our own. This file maps those roles
to the strings used in this repo's issue tracker: GitHub labels on `cojedzie/cojedzie`. An issue carries one of them
next to its type (`Bug`, `Feature`, `Task`) and its component, area and location labels (see `issue-tracker.md`).

| Label in mattpocock/skills | Label in our tracker | Meaning                                  |
| -------------------------- | -------------------- | ---------------------------------------- |
| `needs-triage`             | `needs-triage`       | Maintainer needs to evaluate this issue  |
| `needs-info`               | `needs-info`         | Waiting on reporter for more information |
| `ready-for-agent`          | `ready-for-agent`    | Fully specified, ready for an AFK agent  |
| `ready-for-human`          | `ready-for-human`    | Requires human implementation            |
| _(none, ours)_             | `ready-for-ux`       | Needs UX/UI design before implementation |
| `wontfix`                  | `wontfix`            | Will not be actioned                     |

`ready-for-ux` is a role of our own: the issue is specified, but its look or interaction still has to be designed.
Its body carries a **UX/UI brief** (see the publishing section of `issue-tracker.md`). Skills never pick up a
`ready-for-ux` issue for implementation. Once the design is settled, its outcome goes into the issue
(decisions, acceptance criteria) and the label is swapped for `ready-for-agent` or `ready-for-human`.

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), use the corresponding string from this table.

Edit the "Label in our tracker" column to match whatever vocabulary you actually use.
