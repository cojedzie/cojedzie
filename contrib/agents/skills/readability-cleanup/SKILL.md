---
name: readability-cleanup
description: Rewrite the code and tests a branch adds or changes so they read like a story, optimised for reading and understanding rather than for writing. Use as the last pass before cleaning up the history of a finished development branch (after adr-compliance and test-cleanup), or when asked to clean code up for readability.
---

# Readability cleanup

Code is read far more often than it is written. Once a branch works, rewrite what it added so that a reader who
has never seen it understands **what happens and why** from the code itself, top to bottom, without simulating
it in their head. Behaviour does not change in this pass; only the shape of the code does.

## 1. Collect the code

```bash
git diff --stat master..HEAD -- api/src api/tests front/src front/test
git diff master..HEAD -- <each file>
```

Read every added or changed file in full. Judge only what the branch introduces or modifies, but rewrite a whole
function when the branch touched part of it.

## 2. Rules

**A function either orchestrates or does one specific thing, never both.**

- An orchestrating function reads like a table of contents: a short sequence of calls to well-named functions,
  each one step of the story. It contains no string slicing, offset arithmetic, SQL, regexes or nested
  conditions.
- A worker function does one concrete piece of business logic or low-level mechanics, and its name says which.
- If you have to write a comment to explain what a block does, the block wants to be a function with that
  comment as its name.

**Nested loops are a smell.** The body of the inner loop is almost always a function of its own ("import the
rows of one day", "find the next date key"). Same for a loop inside a `try` inside a loop.

**Prefer a functional pipeline when it says more.** `map`/`filter`/`array_map`/generators that name each step
often read better than a loop accumulating into variables. Don't force it: a plain loop that reads clearly
stays a loop.

**Name things for what they mean, not for how they're computed.** No `$match[0][1]`, `$i`, `$tmp`, `$data`:
give positions, slices and intermediate values names (or small value objects / named array shapes) that say
what they are. Magic numbers and patterns get named constants.

**Keep the levels apart.** Business code (importers, rules) should not contain parsing mechanics; mechanics
(stream readers, SQL helpers) should not know business rules. Move code to the level it belongs to.

**Control flow should be flat.** Early returns over nested `if`s; no `continue` in the middle of a long loop
body to emulate a state machine. A state machine, when needed, is written as one: named states, one function
per transition.

**Prohibited**, as an example of what this pass removes:

```php
if (preg_match(self::DATE_KEY, $buffer, $match, PREG_OFFSET_CAPTURE)) {
    if ($date !== null) {
        yield $date => self::dayValue(substr($buffer, 0, $match[0][1]), ',');
    }

    $date   = $match[1][0];
    $buffer = substr($buffer, $match[0][1] + strlen($match[0][0]));

    continue;
}
```

It mixes the orchestration (yield one day per date key) with the mechanics (offsets into a regex match, slicing
the buffer), indexes into an untyped match array and drives a loop with `continue`. Nobody can tell what it does
without running it in their head.

## 3. Tests

Tests are documentation of what should happen. A test that doesn't help the reader understand the behaviour is
rewritten or dropped:

- The test name states the behaviour (`testDayFailingToDownloadIsReportedAndOthersAreImported`), not the method
  (`testImport2`).
- Arrange, act and assert are visible at a glance. Fixture building goes into helpers named after what they
  build (`aTripOnDay('2026-10-28')`), so the test body shows only what matters for this case.
- Assertions compare meaningful values, not row counts or internal shapes, where possible.
- Rewrite tests whose setup hides the point; drop the ones that, once readable, turn out to test nothing a
  reader cares about (see the `test-cleanup` skill).

## 4. Apply

- Rewrite file by file. Keep each function short enough to see at once.
- Run the full suites (`task test:api`, `task test:behat`, frontend tests if touched) and `task lint` after the
  rewrite. Behaviour must not change: a test that has to change its expectations is a red flag.
- Commit the rewrite with `git commit --fixup=<commit>` against the commits that introduced the code, so the
  history cleanup folds it in and the unreadable version never reaches `master`. Rewriting history needs the
  user's confirmation.
