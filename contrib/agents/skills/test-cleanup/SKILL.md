---
name: test-cleanup
description: Review the tests a branch adds or changes and remove the ones that only served implementation, so what reaches master tests behaviour worth protecting. Use before finishing a development branch (alongside adr-compliance), after subagent-driven or TDD work that produced many tests, or when asked to review or clean up tests.
---

# Test cleanup

Tests written while implementing are scaffolding: they prove each step works, pin the types and shapes the
implementer was juggling, and check fixtures. Most of that stops being useful once the work is done, but it
stays in the suite, slows it down and breaks on every refactor without catching anything. Before a branch is
finished, keep the tests that **actually test something worth protecting** and remove the rest.

Read `contrib/adr/0001-behavioural-tests-with-behat.md` and `contrib/agents/wiki/api/testing.md` first. They
decide where a kept test belongs.

## 1. Collect the tests

```bash
git diff --name-status master..HEAD -- api/tests api/features front/test front/src '*.test.ts' '*.spec.ts'
git diff master..HEAD -- <each test file>
```

Read every added or changed test in full, together with the code it tests. Judge only what the branch
introduces. Pre-existing tests are out of scope unless the branch changed them.

## 2. Judge each test

Ask of every test method (and of every data-provider case): **if this fails, has something a user, an API
consumer, an operator or another module depends on broken?** If the honest answer is "no, the code just
changed shape", it goes.

**Keep** a test when it pins:

- behaviour users or consumers observe: API responses, JSON shapes, status codes, error messages, ordering,
  what an import writes;
- business rules and edge cases that are easy to get wrong (empty input, nothing matches, boundaries, limits,
  time zones);
- a bug that was fixed on the branch (regression test);
- a contract between modules that callers rely on (`first()` returns `null` rather than throwing, a converter
  reads stored data back);
- the honesty of something kept for later use, such as a benchmark whose variants must stay equivalent to
  production code.

**Remove** a test when it only checks:

- types and classes the language already enforces: `assertInstanceOf` on a typed return value or property,
  setters storing the declared type, defaults matching the declaration;
- implementation details: defensive copies, which internal collection is used, call counts on mocks, private
  methods reached by reflection *when a public path tests the same thing*;
- trivial wrappers and delegation with no logic of their own, already exercised through their callers;
- fixtures and test data ("the fixture has N rows", "the filter drops something");
- the same behaviour as another test, at a lower level, with nothing added;
- every input combination where a few representative ones would do (shrink a data provider).

**Trim** a test that is worth keeping but carries detail assertions: drop the `assertInstanceOf` and shape
lines and keep the assertions about values and behaviour.

**Move** a test that checks the right thing in the wrong place. In this repository, HTTP contract checks
(status codes, JSON shapes, advertised endpoints) belong in Behat `api/features/contract/` per ADR 0001, not in
a PHPUnit kernel test. Reuse the generic `ApiContext` steps, add a step only when none fits.

When a file loses most of its tests, extract the survivors into a file named after what they test (one test
class per subject under test, not per migration step).

Make sure every remaining test can fail: an assertion that holds for any output (for example `assertEquals`
on objects without visible properties, such as php-ds collections under ext-ds) tests nothing. Fix or remove
it.

## 3. Report, then apply

Present a table to the user before changing anything that is not obviously scaffolding:

| File | Test | Verdict | Why |
|---|---|---|---|
| `api/tests/...` | `testX` | keep / trim / move / remove | one line |

Then apply the verdicts, run the full suites (`task test:api`, `task test:behat`, frontend tests if touched)
and ECS on the changed PHP files, and report the counts before and after.

If the branch's history is still private, offer to fold the removals into the commits that added the tests
(`git commit --fixup=<commit>` + `git rebase -i --autosquash master`), so tests that never belonged on master
don't appear in its history. Rewriting history needs the user's confirmation.
