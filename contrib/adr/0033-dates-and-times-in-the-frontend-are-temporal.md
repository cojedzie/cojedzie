# 0033. Dates and times in the frontend are Temporal

- **Status:** Accepted
- **Supersedes:** [0032](archive/0032-calendar-dates-are-temporal-plain-dates.md)
- **Date:** 2026-11-10
- **Applies to:** `front/src/time/**`, `front/src/timetable/**`, `front/src/model/**`, `front/src/stores/**`,
  `front/src/components/**`, `front/src/composables/**`, `front/src/utils/**`, `front/package.json`,
  `front/eslint.config.mjs`

## Context

ADR 0032 made calendar dates `Temporal.PlainDate`, polyfilled until Safari ships Temporal, and left times to moment.
That left two date libraries, and two time zones. The timetable and "today" use the provider's time zone, but the
departures board, trip stop times, message validity and the provider's last update are converted to the device's
(`moment(...).local()`). A rider abroad checking a stop of their home network sees times an hour or more off from
the stop's display and its printed timetable.

moment is in maintenance mode, its values are mutable, and its relative times round coarsely: from 45 minutes away
a departure reads "za godzinę".

The time zone to show times in may become a rider's preference later; it isn't one now. And departures can arrive
before the provider's details, which carry its time zone: auto-refresh doesn't wait for them.

## Considered options

1. **Keep moment for times, Temporal for days** (ADR 0032). No migration, but two libraries with different rules,
   and the device's time zone stays baked into the models by `.local()`.
2. **Temporal, models storing `ZonedDateTime` in the provider's zone.** Times carry their zone, but converting API
   data needs the provider's time zone at hand, which it isn't always, and a preference would mean converting
   everything again.
3. **Temporal, models storing `Instant`, the zone applied only when shown** (chosen). Reading API data needs no
   zone; the zone to show times in is decided in one place, so a preference later only changes that place.

## Decision

We will use Temporal for every date and time in the frontend, and no moment.

- **Calendar dates** are `Temporal.PlainDate`, months `Temporal.PlainYearMonth` (as in 0032).
- **Moments** (departure and trip stop times, message validity, the provider's last update, stop history dates,
  fetch times) are `Temporal.Instant` in models and stores. A `ZonedDateTime` exists only where a moment is shown or
  split into a day and a time.
- **Clock times** of a day (the timetable's hours and minutes) are `Temporal.PlainTime`.
- **Days are the provider's:** "today", the timetable's date and service days are in the provider's time zone,
  provided by the application (`useToday`).
- **Clock times are shown in the display time zone,** provided by the application (`useDisplayTimeZone`): the
  provider's for now. A rider's preference, if it comes, changes only what is provided; it never changes days.
- **Formatting goes through `Intl`** (`toLocaleString`, `Intl.RelativeTimeFormat`), never through locale patterns
  assembled by hand or a locale's format used to get an ISO string. Relative times count whole minutes, rounded
  down, within an hour.
- **Temporal comes from `@/time`.** Its `temporal.ts` is the only file importing `temporal-polyfill`, so moving to the
  native API, once every supported browser has it, changes one line.
- **ISO strings exist only at the edges:** API responses and queries, URLs, saved state. Saved formats don't change
  because of the library: the stop history keeps writing dates with milliseconds, as moment did.

## Consequences

- One library and one set of rules; signatures say whether they take a day, a moment or a clock time.
- Times on every view match the stop and the printed timetable wherever the rider is.
- Temporal objects are compared with `equals()` / `compare()`, never `===`, and tests compare `toString()`, as
  matchers can't see into Temporal objects.
- A `PlainYearMonth` formats only in its own calendar, so months are formatted through their first day.
- The polyfill (about 20 KB gzipped) stays until Safari ships Temporal; moment and its locale data go.

## Compliance

- Nothing in `front/` imports `moment`.
- Only `front/src/time/temporal.ts` imports `temporal-polyfill` (ESLint enforces it); everything else imports
  `Temporal` from `@/time`.
- Models and stores hold moments as `Temporal.Instant`, days as `Temporal.PlainDate`, clock times as
  `Temporal.PlainTime`; no `ZonedDateTime`, `Date` or string for them outside the edges listed above.
- Shown clock times are formatted in the display time zone (`useDisplayTimeZone`), days in the provider's
  (`useToday`); nothing uses the device's time zone directly except as the fallback where nothing is provided.
- Dates and times are not compared with `===`, `<` or `>`.
- No date or time is formatted by hand-written locale patterns or parsed through a locale's format.
