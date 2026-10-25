# 0003. Malformed request parameters are rejected with 400

- **Status:** Accepted
- **Date:** 2026-10-25
- **Applies to:** `api/src/Filter/Binding/Http/**`, `api/src/Controller/Api/v1/**`

## Context

Query parameters are turned into requirements by `ParameterBinding`s. Before the Behat contract tests, malformed
values had no consistent outcome: `limit=abc` failed with a 500 `TypeError`, `offset=-1` with a Doctrine 500,
unknown operators (`name:bogus`) with a 500 `RuntimeException`, `limit=-1` was silently clamped to 0, and
`limit=0` returned every row. Consumers couldn't tell their mistakes from server errors.

## Considered options

1. **400 Bad Request with the API's problem JSON**, raised by the binding that parses the parameter. Clear for
   clients, keeps controllers free of parsing.
2. **Silently correct values** (clamp, default). Hides client mistakes and gives results that look valid.
3. **Validate per controller.** Repeats the rules in every endpoint and drifts.

## Decision

We will reject parameters that can't be turned into a requirement with **400 Bad Request**, by throwing
`App\Filter\Binding\Http\Exception\InvalidParameterException` (a `BadRequestHttpException`) from the binding.
Value ranges that have a documented bound are clamped instead of rejected (`limit` above its maximum). Specific
rules:

- `limit` and `offset` must be integers ≥ 0; `limit` above the maximum is clamped; `limit=0` gives an empty list.
- Unknown filter operators are rejected (`InvalidOperatorException` extends `InvalidParameterException`).
- A parameter an endpoint can't work without (e.g. `stop` for departures) is declared required on its binding
  (`IdConstraintParameterBinding(required: true)`), which rejects it when missing.
- Requirements are validated in the filter layer, never in controllers: bindings turn malformed input into 400s,
  and requirement objects (`LimitConstraint`, …) refuse invalid values in their constructors, so an invalid
  requirement can't exist.
- A wrong path wins over a wrong query: an unknown `{provider}` is a 404 before any parameter is parsed
  (`App\Subscriber\ProviderSubscriber`).

## Consequences

- Clients get a 400 with a message naming the parameter; 500 means a server fault again.
- Requests that were silently "fixed" before (`limit=-1`) now fail. The frontend never sends them.
- New bindings must validate their input the same way and get rows in the shared contract features.

## Compliance

- Bindings signal invalid input by throwing `InvalidParameterException` (or a subclass), never by a PHP type error,
  a generic exception, or a silently corrected value.
- Shared parameter rules are tested once for all endpoints using them, in
  `api/features/contract/parameter-errors.feature` (and `filter-values.feature` for field filters); a new endpoint
  using a shared binding adds its rows there.
- No endpoint answers 500 for any combination of malformed parameters.
- Controllers don't check requirements or parameters (no `if ($requirement === null) throw …`); a required
  parameter is declared on its binding.
- Requirement classes reject invalid state in their constructors (exceptions, not silent correction).
