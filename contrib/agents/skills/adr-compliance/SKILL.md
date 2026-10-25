---
name: adr-compliance
description: Check a changeset (current branch, a named branch, or a commit range) for compliance with the Architecture Decision Records in contrib/adr. Reports violations to the author and recommends either fixing the code or updating/superseding the ADR. Use when asked to check ADR compliance, before finishing a branch, or when reviewing changes that touch architecture.
---

# ADR compliance check

Check whether a changeset follows the accepted Architecture Decision Records in `contrib/adr/`, and tell the
author where it doesn't and what to do about it.

This skill is **read-only**: it reports and recommends, and changes nothing unless the author asks.

## 1. Determine the changeset

Interpret the argument:

| Argument | Range |
|---|---|
| none | current branch: `$(git merge-base master HEAD)..HEAD`, plus uncommitted changes (`git diff HEAD`) if any |
| a branch name `B` | `$(git merge-base master B)..B` |
| a range `A..B` or `A...B` | as given |
| a single commit `C` | `C^..C` |

State the resolved range, commit count and changed files before continuing. If the range is empty, say so
and stop.

```bash
git log --oneline <range>
git diff --stat <range>
git diff <range>            # read in full, or per file for large changesets
```

Exclude `contrib/agents/specs/` and `contrib/agents/plans/` from the code under review (they are temporary),
but read them if present: they often explain *why* the code deviates.

## 2. Collect the ADRs

- Read `contrib/adr/README.md` and every `contrib/adr/NNNN-*.md` **in the top-level directory only**, as of
  the *end* of the range (`git show <end>:contrib/adr/...`). This includes ADRs added or changed by the
  changeset itself.
- **Do not scan `contrib/adr/archive/`.** It holds deprecated and superseded ADRs, which are not binding. Open
  an archived ADR only when a current ADR's **Supersedes** link or context points to it and you need it to
  understand the current decision.
- **Binding:** ADRs with status `Accepted`.
- **Context only:** `Proposed` ADRs. Mention conflicts with them as notes, not violations.
- If a top-level ADR has status `Deprecated`/`Superseded`, treat it as non-binding and mention under "Notes"
  that it should be moved to `archive/`. If the changeset supersedes an ADR, check that it also archives it.
- If there are no accepted ADRs, report that and stop. Optionally go on to step 5.

## 3. Find the relevant ADRs

An ADR is relevant when:

- a changed path matches its **Applies to**, or
- the diff touches the subject of its **Decision** or **Compliance** rules, even outside the listed paths
  (a decision about API ids is relevant to a frontend change that builds ids).

List the relevant ADRs with one line each on why they're relevant. For large changesets with many relevant
ADRs, it's fine to check ADRs in parallel (e.g. one subagent per ADR, each given the ADR and the diff).

## 4. Check each relevant ADR

For each **Compliance** rule (or, if the ADR has none, the **Decision**), judge the changed code:

- **Violation**: changed lines clearly break the rule.
- **Possible violation**: depends on intent or on code outside the diff. Read the surrounding code before
  deciding, and keep it in this category only if it's still unclear.
- **Compliant**: no finding. Don't list these one by one.

Judge only what the changeset introduces or modifies. Pre-existing violations in untouched code can be
mentioned once under "Notes", but never count against the change.

For every (possible) violation, decide which way to recommend:

- **Fix the code** when the deviation looks accidental, or the ADR's reasoning still holds.
- **Update the ADR** when the deviation looks deliberate and justified: the spec/plan/commit messages explain
  it, the ADR's context no longer holds, or the change is a better approach that the ADR didn't foresee.
  Recommend a **new ADR that supersedes** the old one (accepted ADRs are not edited in substance), say what
  the new decision would be, and note that the old ADR moves to `contrib/adr/archive/`.

Never quietly decide that the ADR "probably doesn't matter". If it's binding and broken, report it.

## 5. Look for undocumented decisions (secondary)

Briefly flag changes that look like new architectural decisions without an ADR (a new dependency, a new
cross-cutting pattern, a new storage or protocol, reversing an established convention). Suggest a title for
the ADR. Keep this short; it's advice, not a violation.

## 6. Report

Address the author directly. Use this structure:

```markdown
## ADR compliance: <range> (<N> commits, <M> files)

**Result:** Compliant | <X> violation(s), <Y> possible violation(s)

### Violations
1. **ADR-NNNN <title>**: rule: "<compliance rule>"
   - `path/to/file.php:42`: what the code does and why it breaks the rule
   - **Recommendation:** fix the code (how) | supersede the ADR with a new one deciding "<new decision>" (why)

### Possible violations
...same shape, plus what would settle it...

### Decisions that may need an ADR
- <change>: suggested ADR "<title>"

### Notes
- Relevant ADRs checked: NNNN, NNNN
- Pre-existing issues, Proposed-ADR conflicts, anything else
```

Omit empty sections. If everything complies, say so in one line and list the ADRs checked.

Then ask the author how to proceed: fix the code, draft the superseding ADR(s) from `contrib/adr/template.md`,
or leave it as is. Don't make either change without their answer.
