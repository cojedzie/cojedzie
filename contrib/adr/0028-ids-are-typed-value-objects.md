# 0028. Ids are typed value objects

- **Status:** Proposed
- **Date:** 2026-11-08
- **Applies to:** `api/src/**`

## Context

Ids travel through the API as bare scalars and library types: a task id is a `Uuid` in one method and a `string`
in the next, an import id a `string`, and provider-scoped ids of stops, lines, tracks and trips are strings built
by `IdUtils::generate()` (`provider::local`) and taken apart by `IdUtils::strip()`. Nothing stops passing a job id
where a task id is expected, a provider-local id where a scoped one is, or an RFC 4122 string where a binary UUID
is, and every boundary converts with `Uuid::fromString()` / `->toRfc4122()` by hand. Signatures don't say which
id they want.

## Considered options

1. **Keep scalars and `Uuid`**, documenting the kind of id in docblocks. Nothing to migrate, but nothing is
   enforced and conversions stay scattered.
2. **One value object per kind of id** (`ImportId`, `ImportTaskId`, `ImportJobId`, `StopId`, `LineId`, ...),
   immutable, with named constructors (`fromString()`, `generate()`, for provider ids `scoped($provider, $local)`)
   and the conversions they need (`toString()`, `local()`). Doctrine stores them through custom types, so entities
   and queries use them directly. Mistakes become type errors, and conversions live in one place per kind.
3. **A single generic `Id` class.** Less code than 2, but it doesn't tell kinds apart, which is the point.

## Decision

Proposed: option 2. We will model each kind of id as its own immutable value object, stored by a Doctrine type
and used in signatures, entities, messages and DTO conversion, instead of `string`, `int` or `Uuid`.

## Consequences

- Signatures document themselves (`run(ImportTaskId $task)`), and swapping two ids is a type error.
- Each kind needs a class and a Doctrine type; serialization (messages, API) needs normalizers.
- Migrating is gradual: start with the import graph (import, task and job ids), then provider-scoped ids, which
  replace most of `IdUtils`.
- Raw SQL in importers keeps working on the stored representation.

## Compliance

- New code doesn't take or return ids as `string`, `int` or `Uuid`; it uses the id's value object.
- Converting an id to or from its stored or transport form happens only in its value object, its Doctrine type
  and its normalizer.
