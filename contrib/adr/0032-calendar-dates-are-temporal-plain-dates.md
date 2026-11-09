# 0032. Calendar dates are Temporal plain dates

- **Status:** Accepted
- **Date:** 2026-11-10
- **Applies to:** `front/src/calendar/**`, `front/src/timetable/**`, `front/src/model/**`, `front/src/stores/**`,
  `front/src/components/**`, `front/src/composables/**`, `front/package.json`, `front/eslint.config.mjs`

## Context

The timetable (#141) and its date picker (#169) work with calendar dates: the chosen day, a provider's range of
service days, the service day of a trip, today in the provider's time zone. These are days, not instants: they have
no time of day and no time zone of their own.

The frontend first passed them around as ISO strings (`"2026-11-09"`), with hand-written helpers for arithmetic and
for today, which took the date from `Intl.DateTimeFormat` in the Swedish locale because it happens to write dates as
`Y-m-d`. That relies on locale data that has changed before: `en-CA`, the other locale used for the same trick,
switched to `M/D/Y` with ICU 72 in Chrome and Firefox 110. Strings also say nothing in a signature: any string passes
where a date is expected, and nothing stops comparing a date with a time.

The app already depends on moment, for times and durations. It is in maintenance mode and its values are mutable.

Temporal reached TC39 stage 4 in March 2026. It is native in Firefox (139) and Chrome and Edge (144); Safari has no
stable release with it yet, and the app supports Safari from 16.4 (ADR 0014).

## Considered options

1. **ISO strings with own helpers.** No dependency, but the helpers reinvent date arithmetic and formatting, today
   needs a locale trick, and the type doesn't say what the value is.
2. **`Date`.** Built in, but a `Date` is an instant: a day needs a time and a time zone picked by convention, and
   the device's time zone shifts it.
3. **moment.** Already a dependency, but it is in maintenance mode, its values are mutable, and like `Date` it has no
   type for a day without a time.
4. **date-fns (with `@date-fns/tz`).** Maintained and tree-shaken, but it works on `Date`, so it has the same problem
   with days, and it is another library to move away from once Temporal is everywhere.
5. **Temporal, polyfilled until Safari ships it** (chosen). `Temporal.PlainDate` is a day without time or time zone;
   arithmetic, comparison and today in a time zone are built in, and formatting goes through `Intl`. The polyfill
   costs about 20 KB gzipped (`temporal-polyfill`; the reference `@js-temporal/polyfill` is about 50 KB) until it can
   be dropped.

## Decision

We will represent calendar dates in the frontend as `Temporal.PlainDate` (months as `Temporal.PlainYearMonth`),
from `temporal-polyfill`.

- Temporal is imported from `@/calendar`, which re-exports it from `src/calendar/temporal.ts`. That file is the only
  one importing the polyfill, so moving to the native API, once every supported browser has it, changes one line.
- Dates are converted from and to ISO strings only at the edges: where API responses are converted (e.g.
  `serviceDay`), in API queries (`date`), and in URLs and saved state. `Jsonified` maps a `PlainDate` to a string.
- Today comes from `useToday()`, in the provider's time zone, not from the device's clock.
- Labels are formatted with `toLocaleString`, i.e. `Intl`, never assembled from locale tricks.

## Consequences

- Signatures say what they take: a function wanting a day takes a `PlainDate`.
- Plain dates are objects: two equal dates are not `===`, so code compares them with `equals()` or
  `Temporal.PlainDate.compare()`, and Vue sees a new object as a change. Tests compare `toString()`, as matchers
  can't look into Temporal objects.
- A `PlainYearMonth` formats only in its own calendar, so months are formatted through their first day.
- About 20 KB more in the bundle until the polyfill can go. Replacing moment with Temporal (#174) would make up for
  it.
- moment stays for times and durations until #174; new code doesn't use it for calendar dates.

## Compliance

- Calendar dates in `front/src` are `Temporal.PlainDate` (or `PlainYearMonth`), not `string`, `Date` or `Moment`,
  except at the edges listed above.
- Only `front/src/calendar/temporal.ts` imports `temporal-polyfill` (ESLint enforces it); everything else imports
  `Temporal` from `@/calendar`.
- Dates are not compared with `===`, `<` or `>`; use `equals()` or `compare()`.
- No date is formatted or parsed through a locale's format (e.g. `sv-SE`, `en-CA`) to get an ISO string.
